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

        initRazorpayCheckout: function () {
            // Initialize Razorpay checkout for subscription products
            if (typeof Razorpay !== 'undefined' && $('.woocommerce-checkout').length) {
                this.setupRazorpayCheckout();
            }
        },

        setupRazorpayCheckout: function () {
            // Razorpay setup logic here
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

        // Diagnostics and dual-button initialization
        initDiagnostics: function () {
            console.log('Zlaark: Frontend JS loaded');
        },

        initDualButtons: function () {
            var self = this;
            var ajaxUrl = (window.zlaark_subscriptions_frontend && zlaark_subscriptions_frontend.ajax_url) || '/wp-admin/admin-ajax.php';
            var nonce = (window.zlaark_subscriptions_frontend && zlaark_subscriptions_frontend.nonce) || '';

            console.log('Zlaark: initDualButtons binding; buttons found', $('.trial-button, .regular-button, .subscription-button, .zlaark-trial-btn, .zlaark-subscription-btn').length);

            // Delegated click handler
            $(document).off('click.zlaarkSub').on('click.zlaarkSub', '.trial-button, .regular-button, .subscription-button, .zlaark-trial-btn, .zlaark-subscription-btn, [data-subscription-type]', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var $button = $(this);
                
                if ($button.prop('disabled')) {
                    console.warn('Zlaark: button disabled, ignoring');
                    return false;
                }

                // Determine subscription type
                var subscriptionType = $button.data('subscription-type') || 
                                     ($button.hasClass('trial-button') || $button.hasClass('zlaark-trial-btn') ? 'trial' : 'regular');
                
                // Get product ID
                var productId = $button.data('product-id') || $button.val() || 
                               $button.closest('form').find('input[name="add-to-cart"]').val();

                console.log('Zlaark: Button click', {subscriptionType: subscriptionType, productId: productId});

                if (!productId) {
                    self.showNotice('Unable to determine product ID.', 'error');
                    return false;
                }

                // Loading state
                $button.addClass('loading').prop('disabled', true);

                // AJAX request
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
                        console.log('Zlaark: AJAX Success', response);
                        
                        // Log pricing debug if available
                        if (response && response.data && response.data.debug_pricing) {
                            console.log('=== PRICING DEBUG ===');
                            console.log('Trial price:', response.data.debug_pricing.trial_price);
                            console.log('Recurring price:', response.data.debug_pricing.recurring_price);
                            console.log('Same price issue:', response.data.debug_pricing.same_price_issue);
                            console.log('=== END PRICING DEBUG ===');
                        }

                        if (response && response.success) {
                            $button.removeClass('loading').addClass('success');
                            setTimeout(function () {
                                var redirect = (response.data && response.data.redirect) || '/checkout/';
                                window.location.href = redirect;
                            }, 600);
                        } else {
                            $button.removeClass('loading').prop('disabled', false);
                            var msg = (response && response.data && response.data.message) || 'Failed to add to cart.';
                            self.showNotice(msg, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Zlaark: AJAX Error', status, error);
                        $button.removeClass('loading').prop('disabled', false);
                        self.showNotice('Request failed: ' + status, 'error');
                    },
                    timeout: 10000
                });
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function () {
        ZlaarkSubscriptionsFrontend.init();
    });

})(jQuery);