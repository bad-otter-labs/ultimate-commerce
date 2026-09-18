(function () {
    'use strict';

    var __ = wp.i18n.__;
    var _n = wp.i18n._n;
    var sprintf = wp.i18n.sprintf;
    var config = window.ucWishlistConfig || {};
    var LOCAL_KEY = String(config.storageKey || 'uc_wishlist_v1');
    var ids = [];
    var loggedIn = false;
    var nonce = '';
    var initialised = false;

    function maxItems() {
        var value = Number(config.maxItems || 100);
        return value > 0 && value <= 100 ? value : 100;
    }

    function normalize(values) {
        var result = [];
        (Array.isArray(values) ? values : []).forEach(function (value) {
            var id = parseInt(value, 10);
            if (id > 0 && result.indexOf(id) === -1 && result.length < maxItems()) {
                result.push(id);
            }
        });
        return result;
    }

    function readLocal() {
        try {
            return normalize(JSON.parse(window.localStorage.getItem(LOCAL_KEY) || '[]'));
        } catch (error) {
            return [];
        }
    }

    function writeLocal(values) {
        try {
            window.localStorage.setItem(LOCAL_KEY, JSON.stringify(normalize(values)));
            return true;
        } catch (error) {
            return false;
        }
    }

    function clearLocal() {
        try {
            window.localStorage.removeItem(LOCAL_KEY);
        } catch (error) {
            return;
        }
    }

    function request(path, options) {
        options = options || {};
        var headers = new Headers({ Accept: 'application/json' });
        if (nonce) {
            headers.set('X-WP-Nonce', String(nonce));
        }
        if (options.body) {
            headers.set('Content-Type', 'application/json');
        }

        return fetch(String(config.restRoot || '') + path, {
            method: options.method || 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: headers,
            body: options.body || undefined
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok) {
                    var message = payload && payload.message
                        ? String(payload.message)
                        : __('The wishlist could not be updated.', 'ultimate-commerce-for-woocommerce');
                    throw new Error(message);
                }
                return payload || {};
            });
        });
    }

    function bootstrap() {
        var endpoint = String(config.bootstrapUrl || '');
        if (!endpoint) {
            return Promise.resolve({
                logged_in: false,
                product_ids: [],
                rest_nonce: ''
            });
        }

        return fetch(endpoint, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: new Headers({ Accept: 'application/json' })
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error('wishlist_bootstrap_failed');
                }
                return payload.data || {};
            });
        });
    }

    function emit() {
        document.dispatchEvent(new CustomEvent('uc:wishlist-updated', {
            bubbles: true,
            detail: { product_ids: ids.slice() }
        }));
    }

    function setStatus(message) {
        Array.prototype.forEach.call(document.querySelectorAll('[data-uc-wishlist-status="1"]'), function (node) {
            node.textContent = message || '';
        });
    }

    function buttonLabel(productName, saved) {
        if (productName) {
            return saved
                ? sprintf(__('Remove %s from wishlist', 'ultimate-commerce-for-woocommerce'), productName)
                : sprintf(__('Add %s to wishlist', 'ultimate-commerce-for-woocommerce'), productName);
        }

        return saved
            ? __('Remove from wishlist', 'ultimate-commerce-for-woocommerce')
            : __('Add to wishlist', 'ultimate-commerce-for-woocommerce');
    }

    function syncButtons() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-uc-wishlist-toggle="1"]'), function (button) {
            var productId = parseInt(button.getAttribute('data-product-id') || '0', 10);
            var productName = String(button.getAttribute('data-product-name') || '');
            var saved = ids.indexOf(productId) !== -1;
            var label = buttonLabel(productName, saved);
            button.setAttribute('aria-pressed', saved ? 'true' : 'false');
            button.setAttribute('aria-label', label);
            var labelNode = button.querySelector('[data-uc-wishlist-label="1"]');
            if (labelNode) {
                labelNode.textContent = saved
                    ? __('Saved', 'ultimate-commerce-for-woocommerce')
                    : __('Add to wishlist', 'ultimate-commerce-for-woocommerce');
            } else {
                button.textContent = label;
            }
        });
    }

    function itemNode(product) {
        var article = document.createElement('article');
        article.className = 'uc-wishlist-item';
        article.setAttribute('data-product-id', String(product.id || ''));

        var link = document.createElement('a');
        link.className = 'uc-wishlist-item__link';
        link.href = String(product.permalink || '#');

        if (Array.isArray(product.images) && product.images[0] && product.images[0].src) {
            var image = document.createElement('img');
            image.className = 'uc-wishlist-item__image';
            image.src = String(product.images[0].thumbnail || product.images[0].src);
            image.alt = String(product.images[0].alt || '');
            image.loading = 'lazy';
            link.appendChild(image);
        }

        var name = document.createElement('span');
        name.className = 'uc-wishlist-item__name';
        name.textContent = String(product.name || '');
        link.appendChild(name);
        article.appendChild(link);

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'uc-wishlist-item__remove';
        remove.setAttribute('data-uc-wishlist-toggle', '1');
        remove.setAttribute('data-product-id', String(product.id || ''));
        remove.setAttribute('data-product-name', String(product.name || ''));
        remove.setAttribute('aria-pressed', 'true');
        remove.textContent = __('Remove', 'ultimate-commerce-for-woocommerce');
        article.appendChild(remove);

        return article;
    }

    function renderLists() {
        var lists = document.querySelectorAll('[data-uc-wishlist-list="1"]');
        if (!lists.length) {
            return;
        }

        if (!ids.length) {
            Array.prototype.forEach.call(lists, function (list) {
                var items = list.querySelector('[data-uc-wishlist-items="1"]');
                var empty = list.querySelector('[data-uc-wishlist-empty="1"]');
                if (items) {
                    items.replaceChildren();
                }
                if (empty) {
                    empty.hidden = false;
                }
            });
            setStatus(__('No saved products.', 'ultimate-commerce-for-woocommerce'));
            return;
        }

        var endpoint = String(config.storeProducts || '');
        if (!endpoint) {
            return;
        }

        var query = '?include=' + encodeURIComponent(ids.join(',')) + '&per_page=' + encodeURIComponent(String(ids.length));
        fetch(endpoint + query, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(__('Saved products could not be loaded.', 'ultimate-commerce-for-woocommerce'));
                }
                return response.json();
            })
            .then(function (products) {
                var byId = {};
                (Array.isArray(products) ? products : []).forEach(function (product) {
                    byId[Number(product.id || 0)] = product;
                });

                Array.prototype.forEach.call(lists, function (list) {
                    var items = list.querySelector('[data-uc-wishlist-items="1"]');
                    var empty = list.querySelector('[data-uc-wishlist-empty="1"]');
                    if (!items) {
                        return;
                    }
                    items.replaceChildren();
                    ids.forEach(function (id) {
                        if (byId[id]) {
                            items.appendChild(itemNode(byId[id]));
                        }
                    });
                    if (empty) {
                        empty.hidden = items.children.length > 0;
                    }
                });

                setStatus(sprintf(
                    _n('%d saved product.', '%d saved products.', ids.length, 'ultimate-commerce-for-woocommerce'),
                    ids.length
                ));
                syncButtons();
            })
            .catch(function (error) {
                setStatus(error && error.message ? String(error.message) : __('Saved products could not be loaded.', 'ultimate-commerce-for-woocommerce'));
            });
    }

    function setIds(nextIds, persistGuest) {
        ids = normalize(nextIds);
        if (persistGuest && !loggedIn) {
            writeLocal(ids);
        }
        syncButtons();
        renderLists();
        emit();
    }

    function toggle(productId, button) {
        if (productId <= 0) {
            return;
        }

        var saved = ids.indexOf(productId) !== -1;
        if (!saved && ids.length >= maxItems()) {
            setStatus(__('The wishlist has reached its item limit.', 'ultimate-commerce-for-woocommerce'));
            return;
        }

        if (!loggedIn) {
            var guestIds = ids.slice();
            if (saved) {
                guestIds = guestIds.filter(function (id) { return id !== productId; });
            } else {
                guestIds.push(productId);
            }
            setIds(guestIds, true);
            return;
        }

        if (button) {
            button.disabled = true;
        }

        var promise = saved
            ? request('/items/' + productId, { method: 'DELETE' })
            : request('/items', { method: 'POST', body: JSON.stringify({ product_id: productId }) });

        promise.then(function (payload) {
            setIds(payload.product_ids || [], false);
            setStatus(saved
                ? __('Removed from wishlist.', 'ultimate-commerce-for-woocommerce')
                : __('Added to wishlist.', 'ultimate-commerce-for-woocommerce'));
        }).catch(function (error) {
            setStatus(error && error.message ? String(error.message) : __('The wishlist could not be updated.', 'ultimate-commerce-for-woocommerce'));
        }).finally(function () {
            if (button) {
                button.disabled = false;
            }
        });
    }

    function initialise() {
        if (initialised) {
            return;
        }
        initialised = true;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-uc-wishlist-toggle="1"]');
            if (!button) {
                return;
            }
            event.preventDefault();
            toggle(parseInt(button.getAttribute('data-product-id') || '0', 10), button);
        });

        bootstrap().then(function (state) {
            loggedIn = !!state.logged_in;
            nonce = String(state.rest_nonce || '');

            if (!loggedIn) {
                setIds(readLocal(), false);
                return null;
            }

            var remoteIds = normalize(state.product_ids || []);
            var guestIds = readLocal();
            if (!guestIds.length) {
                setIds(remoteIds, false);
                return null;
            }

            return request('/merge', {
                method: 'POST',
                body: JSON.stringify({ product_ids: guestIds })
            }).then(function (merged) {
                clearLocal();
                setIds(merged.product_ids || remoteIds, false);
            });
        }).catch(function () {
            loggedIn = false;
            nonce = '';
            setIds(readLocal(), false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }
}());
