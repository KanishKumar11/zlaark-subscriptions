/**
 * Admin JavaScript for Zlaark Subscriptions
 */

(function($) {
    'use strict';

    var ZlaarkSubscriptionsAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initProductTypeFields();
            this.initSubscriptionActions();
            this.initWebhookTesting();
        },
        
        bindEvents: function() {
            // Subscription action confirmations
            $(document).on('click', '.subscription-action', this.handleSubscriptionAction);
            
            // Manual cron triggers
            $(document).on('click', '.trigger-cron', this.triggerCron);
            
            // Webhook testing
            $(document).on('click', '.test-webhook', this.testWebhook);
            
            // Regenerate webhook secret
            $(document).on('click', '.regenerate-webhook-secret', this.regenerateWebhookSecret);
            
            // Clear logs
            $(document).on('click', '.clear-logs', this.clearLogs);
        },
        
        initProductTypeFields: function() {
            // Show/hide subscription fields based on product type
            $('select#product-type').on('change', function() {
                var productType = $(this).val();
                
                if (productType === 'subscription') {
                    $('.show_if_subscription').show();
                    $('.hide_if_subscription').hide();
                    
                    // Set virtual and disable stock management
                    $('#_virtual').prop('checked', true).trigger('change');
                    $('#_manage_stock').prop('checked', false).trigger('change');
                    $('#_downloadable').prop('checked', false).trigger('change');
                } else {
                    $('.show_if_subscription').hide();
                    $('.hide_if_subscription').show();
                }
            }).trigger('change');
            
            // Validate subscription fields
            this.validateSubscriptionFields();
        },
        
        validateSubscriptionFields: function() {
            $('#_subscription_trial_price, #_subscription_recurring_price').on('blur', function() {
                var trialPrice = parseFloat($('#_subscription_trial_price').val()) || 0;
                var recurringPrice = parseFloat($('#_subscription_recurring_price').val()) || 0;
                
                if (trialPrice > recurringPrice && recurringPrice > 0) {
                    alert(zlaark_subscriptions_admin.strings.trial_price_warning || 'Trial price should not be higher than recurring price.');
                }
            });
            
            $('#_subscription_recurring_price').on('blur', function() {
                var recurringPrice = parseFloat($(this).val()) || 0;
                
                if (recurringPrice <= 0) {
                    $(this).css('border-color', '#d63638');
                } else {
                    $(this).css('border-color', '');
                }
            });
        },
        
        initSubscriptionActions: function() {
            // Bulk actions
            $('#doaction, #doaction2').on('click', function(e) {
                var action = $(this).siblings('select').val();
                var checkedItems = $('input[name="subscription[]"]:checked');
                
                if (checkedItems.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one subscription.');
                    return false;
                }
                
                var confirmMessage = '';
                switch (action) {
                    case 'cancel':
                        confirmMessage = 'Are you sure you want to cancel the selected subscriptions?';
                        break;
                    case 'delete':
                        confirmMessage = 'Are you sure you want to delete the selected subscriptions? This action cannot be undone.';
                        break;
                    case 'pause':
                        confirmMessage = 'Are you sure you want to pause the selected subscriptions?';
                        break;
                    case 'resume':
                        confirmMessage = 'Are you sure you want to resume the selected subscriptions?';
                        break;
                }
                
                if (confirmMessage && !confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        handleSubscriptionAction: function(e) {
            var $this = $(this);
            var action = $this.data('action');
            var subscriptionId = $this.data('id');
            
            var confirmMessage = '';
            switch (action) {
                case 'cancel':
                    confirmMessage = zlaark_subscriptions_admin.strings.confirm_cancel;
                    break;
                case 'delete':
                    confirmMessage = zlaark_subscriptions_admin.strings.confirm_delete;
                    break;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $this.addClass('subscription-loading').text('Processing...');
        },
        
        triggerCron: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var cronAction = $button.data('cron-action');
            
            $button.prop('disabled', true).text('Running...');
            
            $.ajax({
                url: zlaark_subscriptions_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zlaark_subscriptions_run_cron',
                    cron_action: cronAction,
                    nonce: zlaark_subscriptions_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cron job completed successfully: ' + response.data);
                    } else {
                        alert('Cron job failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to run cron job. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Run');
                }
            });
        },
        
        initWebhookTesting: function() {
            // Display webhook URL
            this.displayWebhookInfo();
        },
        
        displayWebhookInfo: function() {
            var webhookUrl = window.location.origin + '/zlaark-subscriptions/webhook/';
            $('.webhook-url-display').text(webhookUrl);
        },
        
        testWebhook: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: zlaark_subscriptions_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zlaark_test_webhook',
                    nonce: zlaark_subscriptions_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Webhook test successful: ' + response.data);
                    } else {
                        alert('Webhook test failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to test webhook. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Webhook');
                }
            });
        },
        
        regenerateWebhookSecret: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to regenerate the webhook secret? You will need to update your Razorpay webhook configuration.')) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Regenerating...');
            
            $.ajax({
                url: zlaark_subscriptions_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zlaark_regenerate_webhook_secret',
                    nonce: zlaark_subscriptions_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.webhook-secret-display').text(response.data.secret);
                        alert('Webhook secret regenerated successfully.');
                    } else {
                        alert('Failed to regenerate webhook secret: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to regenerate webhook secret. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Regenerate Secret');
                }
            });
        },
        
        clearLogs: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: zlaark_subscriptions_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zlaark_clear_logs',
                    nonce: zlaark_subscriptions_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.subscription-logs tbody').empty();
                        alert('Logs cleared successfully.');
                    } else {
                        alert('Failed to clear logs: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to clear logs. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Clear Logs');
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ZlaarkSubscriptionsAdmin.init();
    });
    
})(jQuery);
