/**
 * Frontend JavaScript for Zlaark Subscriptions
 */

(function ($) {
    'use strict';

    var ZlaarkSubscriptionsFrontend = {

        init: function () {
            this.bindEvents();
            this.initSubscriptionActions();
            this.initRazorpayCheckout();
            this.initDiagnostics();
            this.initDualButtons();
        },

        bindEvents: function () {
            // Subscription action handlers
            $(document).on('click', '.subscription-action', this.handleSubscriptionAction);

            // Subscription product add to cart validation (legacy single button) - DISABLED for dual button products
            // $(document).on('click', '.single_add_to_cart_button', this.validateSubscriptionAddToCart);

            // Dual button system handlers - REMOVED: Now handled by template JavaScript to prevent conflicts
            // $(document).on('click', '.trial-button, .regular-button', this.handleDualButtonClick);

            // Shortcode button handlers
            $(document).on('click', '.zlaark-trial-btn, .zlaark-subscription-btn', this.handleShortcodeButtonClick);

            // Content restriction handlers
            this.initContentRestriction();
        },

        initSubscriptionActions: function () {
            // Handle subscription management actions
            $('.subscription-action').each(function () {
                var $this = $(this);
                var action = $this.data('action');

                // Add confirmation for destructive actions
                if (action === 'cancel') {
                    $this.on('click', function (e) {
                        if (!confirm(zlaark_subscriptions_frontend.strings.confirm_cancel)) {
                            e.preventDefault();
                            return false;
                        }
                    });
                } else if (action === 'pause') {
                    $this.on('click', function (e) {
                        if (!confirm(zlaark_subscriptions_frontend.strings.confirm_pause)) {
                            e.preventDefault();
                            return false;
                        }
                    });
                }
            });
        },

        handleSubscriptionAction: function (e) {
            e.preventDefault();

            var $this = $(this);
            var action = $this.data('action');
            var subscriptionId = $this.data('subscription-id');

            if (!subscriptionId) {
                alert('Invalid subscription ID.');
                return;
            }

            // Show loading state
            $this.addClass('subscription-loading').text(zlaark_subscriptions_frontend.strings.processing);

            $.ajax({
                url: zlaark_subscriptions_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'zlaark_' + action + '_subscription',
                    subscription_id: subscriptionId,
                    nonce: zlaark_subscriptions_frontend.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        ZlaarkSubscriptionsFrontend.showNotice(response.data, 'success');

                        // Reload page after short delay
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        ZlaarkSubscriptionsFrontend.showNotice(response.data, 'error');
                        $this.removeClass('subscription-loading').text($this.data('original-text') || action);
                    }
                },
                error: function () {
                    ZlaarkSubscriptionsFrontend.showNotice('An error occurred. Please try again.', 'error');
                    $this.removeClass('subscription-loading').text($this.data('original-text') || action);
                }
            });
        },

        validateSubscriptionAddToCart: function (e) {
            var $form = $(this).closest('form.cart');
            var $productType = $form.find('input[name="product_type"]');

            if ($productType.val() === 'subscription') {
                // Check if user is logged in
                if (!$('body').hasClass('logged-in')) {
                    e.preventDefault();
                    alert('You must be logged in to purchase a subscription.');
                    return false;
                }

                // Additional validation can be added here
            }
        },

        initRazorpayCheckout: function () {
            // Initialize Razorpay checkout for subscription products
            if (typeof Razorpay !== 'undefined' && $('.woocommerce-checkout').length) {
                this.setupRazorpayCheckout();
            }
        },

        setupRazorpayCheckout: function () {
            var self = this;

            // Override WooCommerce checkout process for subscription products
            $('body').on('checkout_place_order_zlaark_razorpay', function () {
                return self.processRazorpayCheckout();
            });
        },

        processRazorpayCheckout: function () {
            // Check if cart contains subscription products
            var hasSubscription = this.cartHasSubscription();

            if (!hasSubscription) {
                return true; // Proceed with normal checkout
            }

            // Handle subscription checkout with Razorpay
            var checkoutData = this.getCheckoutData();

            var options = {
                key: zlaark_razorpay_params.key_id,
                amount: checkoutData.amount,
                currency: checkoutData.currency,
                name: checkoutData.name,
                description: checkoutData.description,
                order_id: checkoutData.order_id,
                handler: function (response) {
                    // Handle successful payment
                    ZlaarkSubscriptionsFrontend.handleRazorpaySuccess(response);
                },
                prefill: {
                    name: checkoutData.customer.name,
                    email: checkoutData.customer.email,
                    contact: checkoutData.customer.phone
                },
                theme: {
                    color: '#0073aa'
                },
                modal: {
                    ondismiss: function () {
                        ZlaarkSubscriptionsFrontend.handleRazorpayDismiss();
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();

            return false; // Prevent default form submission
        },

        cartHasSubscription: function () {
            // Check if any cart item is a subscription
            return $('.cart-subscription-item').length > 0 ||
                $('input[name="subscription_checkout"]').val() === '1';
        },

        getCheckoutData: function () {
            // Extract checkout data from form
            var $form = $('form.checkout');

            return {
                amount: $form.find('input[name="razorpay_amount"]').val() || 0,
                currency: $form.find('input[name="razorpay_currency"]').val() || 'INR',
                name: $form.find('input[name="razorpay_name"]').val() || 'Subscription',
                description: $form.find('input[name="razorpay_description"]').val() || 'Subscription Payment',
                order_id: $form.find('input[name="razorpay_order_id"]').val(),
                customer: {
                    name: $form.find('input[name="billing_first_name"]').val() + ' ' + $form.find('input[name="billing_last_name"]').val(),
                    email: $form.find('input[name="billing_email"]').val(),
                    phone: $form.find('input[name="billing_phone"]').val()
                }
            };
        },

        handleRazorpaySuccess: function (response) {
            // Add payment details to form and submit
            var $form = $('form.checkout');

            $('<input>').attr({
                type: 'hidden',
                name: 'razorpay_payment_id',
                value: response.razorpay_payment_id
            }).appendTo($form);

            $('<input>').attr({
                type: 'hidden',
                name: 'razorpay_order_id',
                value: response.razorpay_order_id
            }).appendTo($form);

            $('<input>').attr({
                type: 'hidden',
                name: 'razorpay_signature',
                value: response.razorpay_signature
            }).appendTo($form);

            // Submit form
            $form.off('submit').submit();
        },

        handleRazorpayDismiss: function () {
            // Handle payment modal dismissal
            this.showNotice('Payment was cancelled. Please try again.', 'error');
        },

        handleShortcodeButtonClick: function (e) {
            console.log('Zlaark: handleShortcodeButtonClick triggered', this, e);

            var $button = $(this);
            var $form = $button.closest('form');

            // Check if this should be handled by the dual button system instead
            if ($button.hasClass('trial-button') || $button.hasClass('subscription-button')) {
                console.log('Zlaark: Shortcode button also has dual-button class, letting dual-button system handle it');
                return; // Let the dual button system handle it
            }

            console.log('Zlaark: Processing shortcode button click');

            // Add loading state
            $button.addClass('loading').prop('disabled', true);

            // Add visual feedback
            var originalText = $button.find('.button-text').text() || $button.text();
            if ($button.find('.button-text').length) {
                $button.find('.button-text').text('Processing...');
            } else {
                $button.text('Processing...');
            }

            // Let the form submit naturally, but provide feedback
            setTimeout(function () {
                if ($button.hasClass('loading')) {
                    $button.removeClass('loading').prop('disabled', false);
                    if ($button.find('.button-text').length) {
                        $button.find('.button-text').text(originalText);
                    } else {
                        $button.text(originalText);
                    }
                }
            }, 3000);
        },

        initContentRestriction: function () {
            // Handle content restriction for non-subscribers
            $('.subscription-restriction').each(function () {
                var $this = $(this);
                var $content = $this.next('.restricted-content');

                if ($content.length) {
                    $content.hide();
                }
            });
        },

        showNotice: function (message, type) {
            type = type || 'info';

            var $notice = $('<div class="subscription-notice ' + type + '">' + message + '</div>');

            // Find the best place to insert the notice
            var $target = $('.woocommerce-notices-wrapper').first();
            if (!$target.length) {
                $target = $('.woocommerce').first();
            }
            if (!$target.length) {
                $target = $('body');
            }

            $target.prepend($notice);

            // Auto-hide success notices
            if (type === 'success') {
                setTimeout(function () {
                    $notice.fadeOut(function () {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 500);
        },

        // Diagnostics: global logs and optional inline panel
        initDiagnostics: function () {
            try {
                console.log('Zlaark: Frontend JS loaded');
            } catch (e) { }

            // Global AJAX logging
            $(document).off('ajaxError.zlaark ajaxSuccess.zlaark')
                .on('ajaxError.zlaark', function (e, xhr, settings, error) {
                    try { console.warn('Zlaark Diag: ajaxError', { url: settings && settings.url, status: xhr && xhr.status, error: error }); } catch (e) { }
                })
                .on('ajaxSuccess.zlaark', function (e, xhr, settings) {
                    try { console.log('Zlaark Diag: ajaxSuccess', { url: settings && settings.url, status: xhr && xhr.status }); } catch (e) { }
                });

            // Inline diagnostics panel (enabled via ?zlaark_diag=1)
            if (window.location.search.indexOf('zlaark_diag=1') !== -1) {
                // CSS guard to ensure clicks are not swallowed by overlays during diagnostics
                try {
                    var style = document.createElement('style');
                    style.id = 'zlaark-diag-style';
                    style.textContent = '.trial-button, .regular-button, [data-subscription-type]{pointer-events:auto !important; position:relative !important; z-index:2147483647 !important;}';
                    document.head && document.head.appendChild(style);
                } catch (e) { }

                var $panel = $('<div/>', { css: { border: '2px solid #f59e0b', padding: '12px', borderRadius: '8px', margin: '16px 0', background: '#fff8e1' } });
                $panel.append('<strong>Subscription Diagnostics</strong><br><small>Live status, hook checks, and dry-run tests</small>');
                var $btns = $('<div style="margin-top:8px; display:flex; gap:8px;"></div>');
                var $out = $('<pre style="max-height:200px; overflow:auto; background:#fafafa; padding:8px; margin-top:8px;"></pre>');

                var ajaxUrl = (window.zlaark_subscriptions_frontend && zlaark_subscriptions_frontend.ajax_url) || (window.ajaxurl) || '/wp-admin/admin-ajax.php';
                var nonce = (window.zlaark_subscriptions_frontend && zlaark_subscriptions_frontend.nonce) || '';

                var $ping = $('<button type="button" class="button">Ping Diagnostics</button>');
                var $dry = $('<button type="button" class="button">Dry-run Add-to-Cart</button>');

                $ping.on('click', function () {
                    $out.text('Pinging diagnostic endpoint...');
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: { action: 'zlaark_diag_status' },
                        success: function (resp) { $out.text('Status:\n' + JSON.stringify(resp, null, 2)); },
                        error: function (x, s, e) { $out.text('Diag ERROR: ' + s + ' ' + e + '\n' + (x.responseText || '').substring(0, 300)); }
                    });
                });

                $dry.on('click', function () {
                    var pid = $('.trial-button, .regular-button, [data-subscription-type]').first().attr('data-product-id') || $('input[name="add-to-cart"]').val() || '';
                    $out.text('Dry-run add_to_cart (no mutation) for product ' + pid + ' ...');
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'zlaark_add_subscription_to_cart',
                            product_id: pid,
                            subscription_type: 'regular',
                            quantity: 1,
                            diagnostic: '1',
                            nonce: nonce
                        },
                        success: function (resp) { $out.text('Dry-run response:\n' + JSON.stringify(resp, null, 2)); },
                        error: function (x, s, e) { $out.text('Dry-run ERROR: ' + s + ' ' + e + '\n' + (x.responseText || '').substring(0, 300)); }
                    });
                });

                $btns.append($ping, $dry);
                $panel.append($btns, $out);

                var $container = $('.subscription-purchase-options');
                if ($container.length) { $container.before($panel); } else { $('body').prepend($panel); }
            }
        },

        // Robust dual-button initializer (runs even if template inline JS is stripped)
        initDualButtons: function () {
            var self = this;
            var ajaxUrl = (window.zlaark_subscriptions_frontend && zlaark_subscriptions_frontend.ajax_url) || (window.ajaxurl) || '/wp-admin/admin-ajax.php';
            var nonce = (window.zlaark_subscriptions_frontend && zlaark_subscriptions_frontend.nonce) || '';

            // Updated selectors to match actual HTML classes - COMPREHENSIVE selector
            var buttonSelector = '.trial-button, .regular-button, .subscription-button, .zlaark-trial-btn, .zlaark-subscription-btn, [data-subscription-type]';

            console.log('Zlaark: initDualButtons starting with selector:', buttonSelector);
            console.log('Zlaark: Buttons found with selector:', $(buttonSelector).length);

            // Test each part of the selector individually
            console.log('Zlaark: .trial-button found:', $('.trial-button').length);
            console.log('Zlaark: .regular-button found:', $('.regular-button').length);
            console.log('Zlaark: .subscription-button found:', $('.subscription-button').length);
            console.log('Zlaark: .zlaark-trial-btn found:', $('.zlaark-trial-btn').length);
            console.log('Zlaark: .zlaark-subscription-btn found:', $('.zlaark-subscription-btn').length);
            console.log('Zlaark: [data-subscription-type] found:', $('[data-subscription-type]').length);

            // Enhanced debugging: Check button visibility and properties
            $(buttonSelector).each(function (index) {
                var $btn = $(this);
                console.log('Zlaark: Button ' + index + ' analysis:', {
                    element: this,
                    visible: $btn.is(':visible'),
                    display: $btn.css('display'),
                    opacity: $btn.css('opacity'),
                    zIndex: $btn.css('z-index'),
                    position: $btn.css('position'),
                    pointerEvents: $btn.css('pointer-events'),
                    disabled: $btn.prop('disabled'),
                    classes: this.className,
                    type: $btn.attr('type'),
                    dataAttrs: {
                        subscriptionType: $btn.data('subscription-type'),
                        productId: $btn.data('product-id')
                    }
                });
            });

            // Use the same selector variable for consistency
            var selector = buttonSelector;
            var bindHandlers = function () {
                console.log('Zlaark: bindHandlers running, binding events for selector:', selector);
                $(document).off('click.zlaarkSub touchstart.zlaarkSub pointerdown.zlaarkSub keydown.zlaarkSub', selector);
                $(document)
                    .on('click.zlaarkSub', selector, function (e) {
                        console.log('Zlaark: Click event triggered on:', this, 'Event:', e);
                        // Prevent form submission for submit buttons
                        if ($(this).attr('type') === 'submit') {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                        processButtonClick(e, this);
                    })
                    .on('touchstart.zlaarkSub', selector, function (e) {
                        console.log('Zlaark: Touch event triggered on:', this, 'Event:', e);
                        processButtonClick(e, this);
                    })
                    .on('pointerdown.zlaarkSub', selector, function (e) {
                        console.log('Zlaark: Pointer event triggered on:', this, 'Event:', e);
                        processButtonClick(e, this);
                    })
                    .on('keydown.zlaarkSub', selector, function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            console.log('Zlaark: Keyboard event triggered on:', this, 'Event:', e);
                            processButtonClick(e, this);
                        }
                    });
            };

            // Unified processor with idempotency guard
            var processButtonClick = function (e, buttonEl) {
                console.log('Zlaark: processButtonClick called', { eventType: e && e.type, button: buttonEl });
                if (!buttonEl) return;
                var $button = $(buttonEl);

                // Keyboard filter
                if (e && e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') { return; }

                // Prevent duplicate processing in the same tick
                var nowTs = e && e.timeStamp || Date.now();
                var lastTs = $button.data('zlaarkProcessedAt') || 0;
                if (nowTs && Math.abs(nowTs - lastTs) < 5) return; // same event loop
                $button.data('zlaarkProcessedAt', nowTs);

                if (e) { try { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); } catch (_) { } }
                if ($button.prop('disabled')) { try { console.warn('Zlaark: button disabled, ignoring'); } catch (_) { } return false; }

                // Enhanced subscription type detection for shortcode buttons
                var subscriptionType = $button.data('subscription-type') || $button.data('subscriptionType') || $button.attr('data-subscription-type');

                console.log('Zlaark: Initial subscription type from data attributes:', subscriptionType);
                console.log('Zlaark: Button classes:', $button[0].className);
                console.log('Zlaark: Button class checks:', {
                    hasTrialBtn: $button.hasClass('zlaark-trial-btn'),
                    hasTrialButton: $button.hasClass('trial-button'),
                    hasSubscriptionBtn: $button.hasClass('zlaark-subscription-btn'),
                    hasSubscriptionButton: $button.hasClass('subscription-button'),
                    hasRegularButton: $button.hasClass('regular-button')
                });

                // Detect subscription type from button classes if not in data attributes
                // PRIORITY ORDER: Most specific classes first
                if (!subscriptionType) {
                    if ($button.hasClass('zlaark-subscription-btn')) {
                        subscriptionType = 'regular';
                        console.log('Zlaark: Detected as REGULAR from zlaark-subscription-btn class');
                    } else if ($button.hasClass('zlaark-trial-btn')) {
                        subscriptionType = 'trial';
                        console.log('Zlaark: Detected as TRIAL from zlaark-trial-btn class');
                    } else if ($button.hasClass('subscription-button')) {
                        subscriptionType = 'regular';
                        console.log('Zlaark: Detected as REGULAR from subscription-button class');
                    } else if ($button.hasClass('trial-button')) {
                        subscriptionType = 'trial';
                        console.log('Zlaark: Detected as TRIAL from trial-button class');
                    } else if ($button.hasClass('regular-button')) {
                        subscriptionType = 'regular';
                        console.log('Zlaark: Detected as REGULAR from regular-button class');
                    } else {
                        // Check hidden input in the form
                        var formSubscriptionType = $button.closest('form').find('input[name="subscription_type"]').val();
                        if (formSubscriptionType) {
                            subscriptionType = formSubscriptionType;
                            console.log('Zlaark: Detected from form hidden input:', subscriptionType);
                        } else {
                            subscriptionType = $('#subscription_type').val() || 'regular';
                            console.log('Zlaark: Defaulted to:', subscriptionType);
                        }
                    }
                } else {
                    console.log('Zlaark: Using subscription type from data attribute:', subscriptionType);
                }

                console.log('Zlaark: Final subscription type determined:', subscriptionType);

                // Enhanced product ID detection for shortcode buttons
                var productId = $button.data('product-id') || $button.data('product_id') || $button.attr('data-product-id') ||
                    $button.val() || $button.attr('value') ||
                    $button.closest('form').find('input[name="add-to-cart"]').val() ||
                    $button.closest('form').find('input[name="product_id"]').val() ||
                    $button.closest('form').find('#zlaark_add_to_cart_product_id').val() ||
                    $button.closest('form').find('input[name="zlaark_product_id"]').val() || '';

                // COMPREHENSIVE LOGGING FOR DEBUGGING PRICING ISSUE
                console.log('=== ZLAARK BUTTON CLICK DEBUG ===');
                console.log('Button element:', $button[0]);
                console.log('Button classes:', $button[0].className);
                console.log('Subscription type detected:', subscriptionType);
                console.log('Product ID detected:', productId);
                console.log('Button data attributes:', {
                    'data-subscription-type': $button.attr('data-subscription-type'),
                    'data-product-id': $button.attr('data-product-id')
                });
                console.log('Form hidden inputs:', {
                    'subscription_type': $button.closest('form').find('input[name="subscription_type"]').val(),
                    'add-to-cart': $button.closest('form').find('input[name="add-to-cart"]').val()
                });
                console.log('=== END DEBUG ===');

                try { console.log('Zlaark: DualButton click', { subscriptionType: subscriptionType, productId: productId }); } catch (e) { }
                if (!productId) { try { console.error('Zlaark: Missing productId for click', $button[0]); } catch (_) { } self.showNotice('Unable to determine product ID.', 'error'); return false; }

                // UI: loading state
                var originalText = ($button.text() || '').trim();
                $button.data('original-text', originalText);
                $button.addClass('loading').attr('aria-busy', 'true').prop('disabled', true);

                var resetUI = function () {
                    $button.removeClass('loading success').attr('aria-busy', 'false').prop('disabled', false);
                    if ($button.data('original-text')) $button.text($button.data('original-text'));
                };
                var safetyTimer = setTimeout(resetUI, 12000);

                // DETAILED AJAX PAYLOAD LOGGING FOR PRICING DEBUG
                var ajaxPayload = {
                    action: 'zlaark_add_subscription_to_cart',
                    product_id: productId,
                    subscription_type: subscriptionType,
                    quantity: 1,
                    nonce: nonce
                };

                console.log('=== AJAX REQUEST DEBUG ===');
                console.log('AJAX URL:', ajaxUrl);
                console.log('AJAX Payload:', ajaxPayload);
                console.log('Subscription Type being sent:', subscriptionType);
                console.log('Expected behavior:');
                console.log('- If subscription_type = "trial" -> should use trial pricing (₹99)');
                console.log('- If subscription_type = "regular" -> should use full subscription pricing');
                console.log('=== SENDING REQUEST ===');
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: ajaxPayload,
                    success: function (response) {
                        console.log('=== AJAX RESPONSE DEBUG ===');
                        console.log('Raw response:', response);
                        console.log('Response success:', response && response.success);
                        console.log('Response data:', response && response.data);
                        console.log('=== END RESPONSE DEBUG ===');
                        console.log('Zlaark: Dual-button AJAX success handler, response:', response);
                        clearTimeout(safetyTimer);
                        if (response && response.success) {
                            $button.removeClass('loading').addClass('success');
                            setTimeout(function () {
                                var redirect = (response.data && response.data.redirect) || (window.wc_checkout_url) || (window.location.href);
                                window.location.href = redirect;
                            }, 600);
                        } else {
                            resetUI();
                            var msg = (response && (response.data && response.data.message || response.data)) || 'Failed to add to cart.';
                            self.showNotice(msg, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log('Zlaark: Dual-button AJAX error handler, status:', status, 'error:', error, 'xhr:', xhr);
                        clearTimeout(safetyTimer);
                        resetUI();
                        self.showNotice('Request failed: ' + (status || '') + ' ' + (error || ''), 'error');
                    },
                    timeout: 10000
                });
            };

            // Bind now and with short delays to beat late-binding scripts
            bindHandlers();
            setTimeout(bindHandlers, 50);
            setTimeout(bindHandlers, 300);

            // Additional direct binding test - this should help identify if the issue is with event delegation
            setTimeout(function () {
                console.log('Zlaark: Setting up direct click handlers...');
                $(buttonSelector).each(function () {
                    var $btn = $(this);
                    console.log('Zlaark: Adding direct click handler to button:', this);

                    // Remove any existing direct handlers first
                    $btn.off('click.zlaarkDirect');

                    // Add VERY simple direct click handler for testing
                    $btn.on('click.zlaarkDirect', function (e) {
                        console.log('Zlaark: DIRECT click handler triggered!', this, e);
                        alert('Direct click handler worked! Button: ' + this.className);

                        // Prevent form submission for submit buttons
                        if ($(this).attr('type') === 'submit') {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                        // Call the same processor
                        processButtonClick(e, this);
                    });

                    // Also add a simple test click handler that doesn't prevent anything
                    $btn.on('click.zlaarkTest', function (e) {
                        console.log('Zlaark: TEST click handler (no prevention):', this.className);
                    });
                });

                // Test if jQuery click simulation works
                setTimeout(function () {
                    console.log('Zlaark: Testing programmatic click...');
                    $(buttonSelector).first().trigger('click');
                }, 1000);
            }, 500);

            // Capture-phase logger to detect swallowed clicks
            try {
                window.addEventListener('click', function (ev) {
                    var el = ev.target && ev.target.closest ? ev.target.closest(selector) : null;
                    if (el) {
                        try {
                            console.log('Zlaark: capture click observed on button', el);
                            // Add visual feedback for debugging
                            $(el).css('border', '3px solid red').delay(1000).queue(function () {
                                $(this).css('border', '').dequeue();
                            });
                        } catch (e) { }
                        processButtonClick(ev, el);
                    }
                }, true);
            } catch (e) { }

            // GLOBAL click detector - logs ALL clicks to see if our buttons are being clicked at all
            $(document).on('click', function (e) {
                // Log every single click for debugging
                console.log('Zlaark: GLOBAL click detected:', {
                    target: e.target,
                    targetTag: e.target.tagName,
                    targetClasses: e.target.className,
                    targetId: e.target.id
                });

                var $target = $(e.target);
                var buttonClasses = '.trial-button, .regular-button, .subscription-button, .zlaark-trial-btn, .zlaark-subscription-btn';
                if ($target.hasClass('trial-button') || $target.hasClass('regular-button') || $target.hasClass('subscription-button') ||
                    $target.hasClass('zlaark-trial-btn') || $target.hasClass('zlaark-subscription-btn') ||
                    $target.closest(buttonClasses).length) {
                    console.log('Zlaark: Document click detected on subscription button area:', {
                        target: e.target,
                        currentTarget: e.currentTarget,
                        closest: $target.closest(buttonClasses)[0],
                        propagationStopped: e.isPropagationStopped(),
                        defaultPrevented: e.isDefaultPrevented(),
                        targetClasses: e.target.className
                    });
                }
            });

            // Re-init after dynamic fragment loads
            $(document).off('wc_fragments_refreshed.zlaark wc_fragments_loaded.zlaark')
                .on('wc_fragments_refreshed.zlaark wc_fragments_loaded.zlaark', function () {
                    // No-op; delegated click handler already attached at document
                });
        },



        // Handle dual button system clicks
        handleDualButtonClick: function (e) {
            var $button = $(e.currentTarget);
            var subscriptionType = $button.data('subscription-type');

            // Set the subscription type in the hidden input
            $('#subscription_type').val(subscriptionType);

            // Add visual feedback
            $('.trial-button, .regular-button').removeClass('selected');
            $button.addClass('selected');

            // Optional: Add loading state
            $button.addClass('loading');

            // The form submission will be handled by the default button behavior
            // Remove loading state after a short delay if form submission fails
            setTimeout(function () {
                $button.removeClass('loading');
            }, 3000);
        },

        // Utility functions
        formatPrice: function (amount, currency) {
            currency = currency || 'INR';
            var symbol = currency === 'INR' ? '₹' : currency;
            return symbol + parseFloat(amount).toFixed(2);
        },

        formatDate: function (dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString();
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        ZlaarkSubscriptionsFrontend.init();
    });

    // Make it globally available
    window.ZlaarkSubscriptionsFrontend = ZlaarkSubscriptionsFrontend;

})(jQuery);
