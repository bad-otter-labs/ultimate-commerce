(function () {
    'use strict';

    var __ = wp.i18n.__;
    var _n = wp.i18n._n;
    var sprintf = wp.i18n.sprintf;

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }
        callback();
    }

    onReady(function () {
        var drawer = document.querySelector('[data-uc-cart-drawer="1"]');
        if (!drawer || typeof window.fetch !== 'function') {
            return;
        }

        var panel = drawer.querySelector('[data-uc-cart-panel="1"]');
        var itemsRoot = drawer.querySelector('[data-uc-cart-items="1"]');
        var emptyState = drawer.querySelector('[data-uc-cart-empty="1"]');
        var summary = drawer.querySelector('[data-uc-cart-summary="1"]');
        var total = drawer.querySelector('[data-uc-cart-total="1"]');
        var status = drawer.querySelector('[data-uc-cart-status="1"]');
        var errorBox = drawer.querySelector('[data-uc-cart-error="1"]');
        var checkout = drawer.querySelector('[data-uc-cart-checkout="1"]');
        var apiRoot = String(drawer.getAttribute('data-store-api-root') || '').replace(/\/$/, '');
        var currentCart = null;
        var loadPromise = null;
        var nonce = '';
        var cartToken = '';
        var previousFocus = null;
        var busy = false;

        if (!panel || !itemsRoot || !apiRoot) {
            return;
        }

        function emit(name, detail) {
            drawer.dispatchEvent(new CustomEvent(name, {
                bubbles: true,
                detail: detail || {}
            }));
        }

        function setStatus(message) {
            if (status) {
                status.textContent = message || '';
            }
        }

        function setError(message, detail) {
            if (errorBox) {
                errorBox.textContent = message || '';
                errorBox.hidden = !message;
            }
            if (message) {
                emit('uc:cart-error', detail || { message: message });
            }
        }

        function setBusy(nextBusy, message) {
            busy = !!nextBusy;
            drawer.setAttribute('aria-busy', busy ? 'true' : 'false');
            if (message !== undefined) {
                setStatus(message);
            }
            Array.prototype.forEach.call(
                drawer.querySelectorAll('[data-uc-cart-mutation="1"]'),
                function (control) {
                    control.disabled = busy;
                }
            );
        }

        function syncSecurityHeaders(response) {
            var nextNonce = response.headers.get('Nonce');
            var nextCartToken = response.headers.get('Cart-Token');
            if (nextNonce) {
                nonce = nextNonce;
            }
            if (nextCartToken) {
                cartToken = nextCartToken;
            }
        }

        function apiError(message, response, payload) {
            var error = new Error(message || __('Cart request failed.', 'ultimate-commerce-for-woocommerce'));
            error.ucApi = true;
            error.status = response ? response.status : 0;
            error.code = payload && payload.code ? String(payload.code) : '';
            error.payload = payload || null;
            return error;
        }

        function request(path, options) {
            options = options || {};
            var method = String(options.method || 'GET').toUpperCase();
            var headers = new Headers({
                'Accept': 'application/json'
            });

            if (method !== 'GET' && method !== 'HEAD') {
                headers.set('Content-Type', 'application/json');
                if (nonce) {
                    headers.set('Nonce', nonce);
                } else if (cartToken) {
                    headers.set('Cart-Token', cartToken);
                }
            }

            return window.fetch(apiRoot + path, {
                method: method,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: headers,
                body: options.body === undefined ? undefined : JSON.stringify(options.body)
            }).catch(function (networkError) {
                networkError.ucNetwork = true;
                throw networkError;
            }).then(function (response) {
                syncSecurityHeaders(response);
                return response.text().then(function (text) {
                    var payload = null;
                    if (text) {
                        try {
                            payload = JSON.parse(text);
                        } catch (ignore) {
                            payload = null;
                        }
                    }
                    if (!response.ok) {
                        var message = payload && payload.message
                            ? String(payload.message)
                            : sprintf(__('Cart request failed (%d).', 'ultimate-commerce-for-woocommerce'), response.status);
                        throw apiError(message, response, payload);
                    }
                    return payload;
                });
            });
        }

        function loadCart(force) {
            if (!force && currentCart) {
                return Promise.resolve(currentCart);
            }
            if (!force && loadPromise) {
                return loadPromise;
            }

            setBusy(true, __('Loading cart…', 'ultimate-commerce-for-woocommerce'));
            setError('');
            loadPromise = request('/cart').then(function (cart) {
                currentCart = cart || { items: [], totals: {} };
                renderCart(currentCart);
                setBusy(false, '');
                return currentCart;
            }).catch(function (error) {
                setBusy(false, '');
                throw error;
            }).then(function (cart) {
                loadPromise = null;
                return cart;
            }, function (error) {
                loadPromise = null;
                throw error;
            });

            return loadPromise;
        }

        function mutate(path, body, retryNonce) {
            setBusy(true, __('Updating cart…', 'ultimate-commerce-for-woocommerce'));
            setError('');
            return request(path, { method: 'POST', body: body }).then(function (cart) {
                currentCart = cart || { items: [], totals: {} };
                renderCart(currentCart);
                setBusy(false, __('Cart updated.', 'ultimate-commerce-for-woocommerce'));
                emit('uc:cart-updated', { cart: currentCart });
                return currentCart;
            }).catch(function (error) {
                if (retryNonce !== false && error && error.ucApi && error.status === 403) {
                    nonce = '';
                    cartToken = '';
                    currentCart = null;
                    loadPromise = null;
                    return loadCart(true).then(function () {
                        return mutate(path, body, false);
                    });
                }
                setBusy(false, '');
                throw error;
            });
        }

        function money(raw, source) {
            source = source || {};
            var minor = parseInt(source.currency_minor_unit, 10);
            if (!Number.isFinite(minor) || minor < 0 || minor > 6) {
                minor = 2;
            }
            var numeric = Number(raw || 0) / Math.pow(10, minor);
            if (!Number.isFinite(numeric)) {
                numeric = 0;
            }
            var negative = numeric < 0;
            var fixed = Math.abs(numeric).toFixed(minor).split('.');
            var thousand = source.currency_thousand_separator === undefined ? ',' : String(source.currency_thousand_separator);
            var decimal = source.currency_decimal_separator === undefined ? '.' : String(source.currency_decimal_separator);
            var whole = fixed[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
            var amount = (negative ? '-' : '') + whole + (minor ? decimal + fixed[1] : '');
            return String(source.currency_prefix || '') + amount + String(source.currency_suffix || '');
        }

        function node(tag, className, text) {
            var element = document.createElement(tag);
            if (className) {
                element.className = className;
            }
            if (text !== undefined && text !== null) {
                element.textContent = String(text);
            }
            return element;
        }

        function itemVariation(item) {
            var variation = Array.isArray(item.variation) ? item.variation : [];
            if (!variation.length) {
                return null;
            }
            var list = node('ul', 'uc-cart-item__variation');
            variation.forEach(function (attribute) {
                var label = attribute && (attribute.attribute || attribute.name) ? String(attribute.attribute || attribute.name) : '';
                var value = attribute && attribute.value ? String(attribute.value) : '';
                if (!value) {
                    return;
                }
                var row = node('li', 'uc-cart-item__variation-row');
                row.appendChild(node('span', 'uc-cart-item__variation-label', label ? label + ': ' : ''));
                row.appendChild(node('span', 'uc-cart-item__variation-value', value));
                list.appendChild(row);
            });
            return list.childNodes.length ? list : null;
        }

        function boundedQuantity(value, limits) {
            limits = limits || {};
            var minimum = Number(limits.minimum === undefined ? 1 : limits.minimum);
            var maximum = Number(limits.maximum === undefined ? 999999 : limits.maximum);
            var multiple = Number(limits.multiple_of === undefined ? 1 : limits.multiple_of);
            var next = Number(value);
            if (!Number.isFinite(next)) {
                next = minimum;
            }
            if (Number.isFinite(minimum)) {
                next = Math.max(minimum, next);
            }
            if (Number.isFinite(maximum) && maximum > 0) {
                next = Math.min(maximum, next);
            }
            if (Number.isFinite(multiple) && multiple > 0) {
                next = Math.round(next / multiple) * multiple;
            }
            return next;
        }

        function updateItem(key, quantity) {
            if (busy) {
                return;
            }
            mutate('/cart/update-item', { key: key, quantity: quantity }).catch(showMutationError);
        }

        function removeItem(key) {
            if (busy) {
                return;
            }
            mutate('/cart/remove-item', { key: key }).catch(showMutationError);
        }

        function renderItem(item) {
            var row = node('article', 'uc-cart-item');
            row.setAttribute('data-uc-cart-key', String(item.key || ''));

            var image = Array.isArray(item.images) && item.images.length ? item.images[0] : null;
            if (image && image.src) {
                var media = node('a', 'uc-cart-item__media');
                media.href = String(item.permalink || '#');
                var img = node('img', 'uc-cart-item__image');
                img.src = String(image.thumbnail || image.src);
                img.alt = String(image.alt || '');
                img.loading = 'lazy';
                media.appendChild(img);
                row.appendChild(media);
            }

            var body = node('div', 'uc-cart-item__body');
            var title = node('a', 'uc-cart-item__title', item.name || '');
            title.href = String(item.permalink || '#');
            body.appendChild(title);

            var variation = itemVariation(item);
            if (variation) {
                body.appendChild(variation);
            }

            var controls = node('div', 'uc-cart-item__controls');
            var limits = item.quantity_limits || {};
            if (limits.editable === false) {
                controls.appendChild(node('span', 'uc-cart-item__quantity-static', '× ' + String(item.quantity || 0)));
            } else {
                var decrement = node('button', 'uc-cart-item__quantity-button', '−');
                decrement.type = 'button';
                decrement.setAttribute('aria-label', __('Decrease quantity', 'ultimate-commerce-for-woocommerce'));
                decrement.setAttribute('data-uc-cart-mutation', '1');

                var input = node('input', 'uc-cart-item__quantity');
                input.type = 'number';
                input.value = String(item.quantity || limits.minimum || 1);
                input.min = String(limits.minimum === undefined ? 1 : limits.minimum);
                if (limits.maximum !== undefined && limits.maximum !== null) {
                    input.max = String(limits.maximum);
                }
                input.step = String(limits.multiple_of || 1);
                input.setAttribute('aria-label', sprintf(__('Quantity for %s', 'ultimate-commerce-for-woocommerce'), String(item.name || __('cart item', 'ultimate-commerce-for-woocommerce'))));
                input.setAttribute('data-uc-cart-mutation', '1');

                var increment = node('button', 'uc-cart-item__quantity-button', '+');
                increment.type = 'button';
                increment.setAttribute('aria-label', __('Increase quantity', 'ultimate-commerce-for-woocommerce'));
                increment.setAttribute('data-uc-cart-mutation', '1');

                decrement.addEventListener('click', function () {
                    var step = Number(limits.multiple_of || 1);
                    updateItem(String(item.key || ''), boundedQuantity(Number(input.value) - step, limits));
                });
                increment.addEventListener('click', function () {
                    var step = Number(limits.multiple_of || 1);
                    updateItem(String(item.key || ''), boundedQuantity(Number(input.value) + step, limits));
                });
                input.addEventListener('change', function () {
                    updateItem(String(item.key || ''), boundedQuantity(input.value, limits));
                });

                controls.appendChild(decrement);
                controls.appendChild(input);
                controls.appendChild(increment);
            }

            var remove = node('button', 'uc-cart-item__remove', __('Remove', 'ultimate-commerce-for-woocommerce'));
            remove.type = 'button';
            remove.setAttribute('data-uc-cart-mutation', '1');
            remove.addEventListener('click', function () {
                removeItem(String(item.key || ''));
            });
            controls.appendChild(remove);
            body.appendChild(controls);

            var itemTotals = item.totals || {};
            body.appendChild(node('div', 'uc-cart-item__line-total', money(itemTotals.line_total || 0, itemTotals)));
            row.appendChild(body);
            return row;
        }

        function renderCart(cart) {
            var items = cart && Array.isArray(cart.items) ? cart.items : [];
            itemsRoot.textContent = '';
            items.forEach(function (item) {
                itemsRoot.appendChild(renderItem(item));
            });

            if (emptyState) {
                emptyState.hidden = items.length !== 0;
            }
            if (summary) {
                summary.hidden = items.length === 0;
            }
            if (total) {
                var totals = cart && cart.totals ? cart.totals : {};
                total.textContent = money(totals.total_price || 0, totals);
            }
            if (checkout) {
                checkout.setAttribute('aria-disabled', items.length ? 'false' : 'true');
                checkout.tabIndex = items.length ? 0 : -1;
            }

            var count = cart && cart.items_count !== undefined
                ? cart.items_count
                : items.reduce(function (sum, item) { return sum + Number(item.quantity || 0); }, 0);
            Array.prototype.forEach.call(document.querySelectorAll('[data-uc-cart-count="1"]'), function (countNode) {
                countNode.textContent = count > 0 ? String(count) : '';
                countNode.setAttribute('aria-label', sprintf(_n('%d item in cart', '%d items in cart', count, 'ultimate-commerce-for-woocommerce'), count));
            });
        }

        function showMutationError(error) {
            var message = error && error.message ? String(error.message) : __('The cart could not be updated.', 'ultimate-commerce-for-woocommerce');
            setError(message, {
                message: message,
                code: error && error.code ? error.code : '',
                status: error && error.status ? error.status : 0
            });
            setStatus('');
        }

        function focusable() {
            return Array.prototype.slice.call(panel.querySelectorAll(
                'a[href]:not([tabindex="-1"]), button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(function (element) {
                return !element.hidden && element.offsetParent !== null;
            });
        }

        function openDrawer() {
            if (!drawer.hidden) {
                return;
            }
            previousFocus = document.activeElement;
            drawer.hidden = false;
            document.documentElement.classList.add('uc-cart-drawer-open');
            Array.prototype.forEach.call(document.querySelectorAll('[data-uc-cart-toggle="1"]'), function (trigger) {
                trigger.setAttribute('aria-expanded', 'true');
            });
            window.requestAnimationFrame(function () {
                var close = drawer.querySelector('[data-uc-cart-close="1"]');
                (close || panel).focus();
            });
            loadCart(false).catch(function (error) {
                showMutationError(error);
            });
            emit('uc:cart-opened', { cart: currentCart });
        }

        function closeDrawer() {
            if (drawer.hidden) {
                return;
            }
            drawer.hidden = true;
            document.documentElement.classList.remove('uc-cart-drawer-open');
            Array.prototype.forEach.call(document.querySelectorAll('[data-uc-cart-toggle="1"]'), function (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            });
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            }
            previousFocus = null;
            emit('uc:cart-closed', { cart: currentCart });
        }

        function quickAdd(body, fallback) {
            setError('');
            setBusy(true, __('Adding to cart…', 'ultimate-commerce-for-woocommerce'));
            return loadCart(false).then(function () {
                return mutate('/cart/add-item', body);
            }).then(function (cart) {
                openDrawer();
                return cart;
            }).catch(function (error) {
                setBusy(false, '');
                if (error && error.ucNetwork && typeof fallback === 'function') {
                    fallback();
                    return null;
                }
                openDrawer();
                showMutationError(error);
                return null;
            });
        }

        function variationRequest(form) {
            var variationIdInput = form.querySelector('input[name="variation_id"]');
            var quantityInput = form.querySelector('input[name="quantity"]');
            var variationId = variationIdInput ? parseInt(variationIdInput.value, 10) : 0;
            var quantity = quantityInput ? Number(quantityInput.value || 1) : 1;
            var controls = Array.prototype.slice.call(form.querySelectorAll('[data-uc-attribute]'));
            var variation = [];

            Array.prototype.forEach.call(form.querySelectorAll('[data-uc-native-attribute-input]'), function (input) {
                var key = String(input.getAttribute('data-uc-native-attribute-input') || '').trim();
                var value = String(input.value || '').trim();
                if (!key || !value) {
                    return;
                }
                var control = controls.find(function (candidate) {
                    return candidate.getAttribute('data-uc-attribute') === key;
                });
                var labelNode = control ? control.querySelector('.uc-variation-control__label') : null;
                var attribute = key.indexOf('pa_') === 0
                    ? key
                    : String(labelNode ? labelNode.textContent : key).trim();
                variation.push({ attribute: attribute, value: value });
            });

            return {
                id: variationId,
                quantity: quantity,
                variation: variation
            };
        }

        document.addEventListener('click', function (event) {
            var close = event.target.closest('[data-uc-cart-close="1"]');
            if (close && drawer.contains(close)) {
                event.preventDefault();
                closeDrawer();
                return;
            }

            var toggle = event.target.closest('[data-uc-cart-toggle="1"]');
            if (toggle) {
                event.preventDefault();
                if (drawer.hidden) {
                    openDrawer();
                } else {
                    closeDrawer();
                }
                return;
            }

            var action = event.target.closest('[data-uc-action="add_to_cart"]');
            if (!action) {
                return;
            }
            var card = action.closest('[data-uc-product-card="1"]');
            var productId = card ? parseInt(card.getAttribute('data-product-id'), 10) : 0;
            if (!productId) {
                return;
            }

            event.preventDefault();
            quickAdd({ id: productId, quantity: 1 }, function () {
                window.location.assign(action.href);
            });
        });

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-uc-variation-form="1"]');
            if (!form) {
                return;
            }
            var submit = form.querySelector('[data-uc-variation-submit="1"]');
            var requestBody = variationRequest(form);
            if (!requestBody.id || (submit && submit.disabled)) {
                return;
            }

            event.preventDefault();
            quickAdd(requestBody, function () {
                form.submit();
            });
        });

        drawer.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDrawer();
                return;
            }
            if (event.key !== 'Tab') {
                return;
            }
            var controls = focusable();
            if (!controls.length) {
                event.preventDefault();
                panel.focus();
                return;
            }
            var first = controls[0];
            var last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        document.addEventListener('uc:cart-open', function () {
            openDrawer();
        });
        document.addEventListener('uc:cart-refresh', function () {
            currentCart = null;
            loadCart(true).catch(showMutationError);
        });
    });
}());
