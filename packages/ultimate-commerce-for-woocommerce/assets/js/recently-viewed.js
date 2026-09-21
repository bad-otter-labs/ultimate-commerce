(function () {
    'use strict';

    var __ = wp.i18n.__;
    var _n = wp.i18n._n;
    var sprintf = wp.i18n.sprintf;
    var config = window.ucRecentlyViewedConfig || {};
    var LOCAL_KEY = String(config.storageKey || 'uc_recently_viewed_v1');
    var ids = [];

    function maxItems() {
        var value = Number(config.maxItems || 12);
        return value > 0 && value <= 12 ? value : 12;
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
        ids = normalize(values);
        try {
            window.localStorage.setItem(LOCAL_KEY, JSON.stringify(ids));
        } catch (error) {
            return false;
        }
        return true;
    }

    function clearLocal() {
        ids = [];
        try {
            window.localStorage.removeItem(LOCAL_KEY);
        } catch (error) {
            return;
        }
    }

    function emit() {
        document.dispatchEvent(new CustomEvent('uc:recently-viewed-updated', {
            bubbles: true,
            detail: { product_ids: ids.slice() }
        }));
    }

    function setStatus(message) {
        Array.prototype.forEach.call(document.querySelectorAll('[data-uc-recently-viewed-status="1"]'), function (node) {
            node.textContent = message || '';
        });
    }

    function visibleIds() {
        var current = parseInt(config.currentProductId || 0, 10);
        return ids.filter(function (id) { return id !== current; });
    }

    function itemNode(product) {
        var article = document.createElement('article');
        article.className = 'uc-recently-viewed-item';
        article.setAttribute('data-product-id', String(product.id || ''));

        var link = document.createElement('a');
        link.className = 'uc-recently-viewed-item__link';
        link.href = String(product.permalink || '#');

        if (Array.isArray(product.images) && product.images[0] && product.images[0].src) {
            var image = document.createElement('img');
            image.className = 'uc-recently-viewed-item__image';
            image.src = String(product.images[0].thumbnail || product.images[0].src);
            image.alt = String(product.images[0].alt || '');
            image.loading = 'lazy';
            link.appendChild(image);
        }

        var name = document.createElement('span');
        name.className = 'uc-recently-viewed-item__name';
        name.textContent = String(product.name || '');
        link.appendChild(name);
        article.appendChild(link);
        return article;
    }

    function productQueryUrl(endpoint, productIds) {
        try {
            var url = new URL(endpoint, window.location.href);
            url.searchParams.set('include', productIds.join(','));
            url.searchParams.set('per_page', String(productIds.length));
            return url.toString();
        } catch (error) {
            var separator = endpoint.indexOf('?') === -1 ? '?' : '&';
            return endpoint + separator
                + 'include=' + encodeURIComponent(productIds.join(','))
                + '&per_page=' + encodeURIComponent(String(productIds.length));
        }
    }

    function renderLists() {
        var lists = document.querySelectorAll('[data-uc-recently-viewed-list="1"]');
        if (!lists.length) {
            return;
        }

        var requested = visibleIds();
        if (!requested.length) {
            Array.prototype.forEach.call(lists, function (list) {
                var items = list.querySelector('[data-uc-recently-viewed-items="1"]');
                var empty = list.querySelector('[data-uc-recently-viewed-empty="1"]');
                if (items) {
                    items.replaceChildren();
                }
                if (empty) {
                    empty.hidden = false;
                }
            });
            setStatus(__('No recently viewed products.', 'ultimate-commerce-for-woocommerce'));
            return;
        }

        var endpoint = String(config.storeProducts || '');
        if (!endpoint) {
            return;
        }

        fetch(productQueryUrl(endpoint, requested), { credentials: 'same-origin', cache: 'no-store' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(__('Recently viewed products could not be loaded.', 'ultimate-commerce-for-woocommerce'));
                }
                return response.json();
            })
            .then(function (products) {
                var byId = {};
                (Array.isArray(products) ? products : []).forEach(function (product) {
                    byId[Number(product.id || 0)] = product;
                });

                Array.prototype.forEach.call(lists, function (list) {
                    var items = list.querySelector('[data-uc-recently-viewed-items="1"]');
                    var empty = list.querySelector('[data-uc-recently-viewed-empty="1"]');
                    var limit = Math.max(1, Math.min(maxItems(), parseInt(list.getAttribute('data-limit') || '8', 10) || 8));
                    if (!items) {
                        return;
                    }
                    items.replaceChildren();
                    requested.slice(0, limit).forEach(function (id) {
                        if (byId[id]) {
                            items.appendChild(itemNode(byId[id]));
                        }
                    });
                    if (empty) {
                        empty.hidden = items.children.length > 0;
                    }
                });

                setStatus(sprintf(
                    /* translators: %d: Number of recently viewed products. */
                    _n('%d recently viewed product.', '%d recently viewed products.', requested.length, 'ultimate-commerce-for-woocommerce'),
                    requested.length
                ));
            })
            .catch(function (error) {
                setStatus(error && error.message ? String(error.message) : __('Recently viewed products could not be loaded.', 'ultimate-commerce-for-woocommerce'));
            });
    }

    function rememberCurrentProduct() {
        var current = parseInt(config.currentProductId || 0, 10);
        if (current <= 0) {
            return;
        }
        var next = ids.filter(function (id) { return id !== current; });
        next.unshift(current);
        writeLocal(next);
        emit();
    }

    function initialise() {
        ids = readLocal();
        rememberCurrentProduct();

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-uc-recently-viewed-clear="1"]');
            if (!button) {
                return;
            }
            event.preventDefault();
            clearLocal();
            renderLists();
            emit();
            setStatus(__('Recently viewed products cleared.', 'ultimate-commerce-for-woocommerce'));
        });

        renderLists();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }
}());
