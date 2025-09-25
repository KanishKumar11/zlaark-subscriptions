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
            var $button = $(this);
            var $form = $button.closest('form');

            // Add loading state
            $button.addClass('loading').prop('disabled', true);

            // Add visual feedback
            var originalText = $button.find('.button-text').text();
            $button.find('.button-text').text('Processing...');

            // Let the form submit naturally, but provide feedback
            setTimeout(function () {
                if ($button.hasClass('loading')) {
                    $button.removeClass('loading').prop('disabled', false);
                    $button.find('.button-text').text(originalText);
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
                    var pid = $('.trial-button, .regular-button').first().val() || $('input[name="add-to-cart"]').val() || '';
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

            // Ensure no duplicate handlers
            $(document).off('click.zlaarkSub', '.trial-button, .regular-button');

            $(document).on('click.zlaarkSub', '.trial-button, .regular-button', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var $button = $(this);
                if ($button.prop('disabled')) return false;

                var subscriptionType = $button.data('subscription-type') || ($('#subscription_type').val() || 'regular');
                var productId = $button.val() || $button.attr('value') || $('form.cart').find('input[name="add-to-cart"]').val() || $button.data('product_id') || $button.closest('form').find('input[name="product_id"]').val() || '';

                try { console.log('Zlaark: DualButton click', { subscriptionType: subscriptionType, productId: productId }); } catch (e) { }

                if (!productId) {
                    self.showNotice('Unable to determine product ID.', 'error');
                    return false;
                }

                // UI: loading state
                var originalText = ($button.text() || '').trim();
                $button.data('original-text', originalText);
                $button.addClass('loading').attr('aria-busy', 'true').prop('disabled', true);

                var resetUI = function () {
                    $button.removeClass('loading success').attr('aria-busy', 'false').prop('disabled', false);
                    if ($button.data('original-text')) $button.text($button.data('original-text'));
                };

                // Safety timeout to avoid permanent loading
                var safetyTimer = setTimeout(resetUI, 12000);

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'zlaark_add_subscription_to_cart',
                        product_id: productId,
                        subscription_type: subscriptionType,
                        quantity: 1,
                        nonce: nonce
                    },
                    success: function (response) {
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
                        clearTimeout(safetyTimer);
                        resetUI();
                        self.showNotice('Request failed: ' + (status || '') + ' ' + (error || ''), 'error');
                    },
                    timeout: 10000
                });

                return false;
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
