/**
 * Frontend JavaScript for Zlaark Subscriptions
 */

(function($) {
    'use strict';

    var ZlaarkSubscriptionsFrontend = {
        
        init: function() {
            this.bindEvents();
            this.initSubscriptionActions();
            this.initRazorpayCheckout();
        },
        
        bindEvents: function() {
            // Subscription action handlers
            $(document).on('click', '.subscription-action', this.handleSubscriptionAction);
            
            // Subscription product add to cart validation
            $(document).on('click', '.single_add_to_cart_button', this.validateSubscriptionAddToCart);
            
            // Content restriction handlers
            this.initContentRestriction();
        },
        
        initSubscriptionActions: function() {
            // Handle subscription management actions
            $('.subscription-action').each(function() {
                var $this = $(this);
                var action = $this.data('action');
                
                // Add confirmation for destructive actions
                if (action === 'cancel') {
                    $this.on('click', function(e) {
                        if (!confirm(zlaark_subscriptions_frontend.strings.confirm_cancel)) {
                            e.preventDefault();
                            return false;
                        }
                    });
                } else if (action === 'pause') {
                    $this.on('click', function(e) {
                        if (!confirm(zlaark_subscriptions_frontend.strings.confirm_pause)) {
                            e.preventDefault();
                            return false;
                        }
                    });
                }
            });
        },
        
        handleSubscriptionAction: function(e) {
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
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        ZlaarkSubscriptionsFrontend.showNotice(response.data, 'success');
                        
                        // Reload page after short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        ZlaarkSubscriptionsFrontend.showNotice(response.data, 'error');
                        $this.removeClass('subscription-loading').text($this.data('original-text') || action);
                    }
                },
                error: function() {
                    ZlaarkSubscriptionsFrontend.showNotice('An error occurred. Please try again.', 'error');
                    $this.removeClass('subscription-loading').text($this.data('original-text') || action);
                }
            });
        },
        
        validateSubscriptionAddToCart: function(e) {
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
        
        initRazorpayCheckout: function() {
            // Initialize Razorpay checkout for subscription products
            if (typeof Razorpay !== 'undefined' && $('.woocommerce-checkout').length) {
                this.setupRazorpayCheckout();
            }
        },
        
        setupRazorpayCheckout: function() {
            var self = this;
            
            // Override WooCommerce checkout process for subscription products
            $('body').on('checkout_place_order_zlaark_razorpay', function() {
                return self.processRazorpayCheckout();
            });
        },
        
        processRazorpayCheckout: function() {
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
                handler: function(response) {
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
                    ondismiss: function() {
                        ZlaarkSubscriptionsFrontend.handleRazorpayDismiss();
                    }
                }
            };
            
            var rzp = new Razorpay(options);
            rzp.open();
            
            return false; // Prevent default form submission
        },
        
        cartHasSubscription: function() {
            // Check if any cart item is a subscription
            return $('.cart-subscription-item').length > 0 || 
                   $('input[name="subscription_checkout"]').val() === '1';
        },
        
        getCheckoutData: function() {
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
        
        handleRazorpaySuccess: function(response) {
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
        
        handleRazorpayDismiss: function() {
            // Handle payment modal dismissal
            this.showNotice('Payment was cancelled. Please try again.', 'error');
        },
        
        initContentRestriction: function() {
            // Handle content restriction for non-subscribers
            $('.subscription-restriction').each(function() {
                var $this = $(this);
                var $content = $this.next('.restricted-content');
                
                if ($content.length) {
                    $content.hide();
                }
            });
        },
        
        showNotice: function(message, type) {
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
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 500);
        },
        
        // Utility functions
        formatPrice: function(amount, currency) {
            currency = currency || 'INR';
            var symbol = currency === 'INR' ? '₹' : currency;
            return symbol + parseFloat(amount).toFixed(2);
        },
        
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ZlaarkSubscriptionsFrontend.init();
    });
    
    // Make it globally available
    window.ZlaarkSubscriptionsFrontend = ZlaarkSubscriptionsFrontend;
    
})(jQuery);
