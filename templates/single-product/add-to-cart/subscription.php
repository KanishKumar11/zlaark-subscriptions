<?php
/**
 * Subscription add to cart template with dual button system
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/subscription.php.
 *
 * @package ZlaarkSubscriptions
 * @version 1.0.4
 */

defined('ABSPATH') || exit;

global $product;

if (!$product->is_purchasable()) {
    return;
}

echo wc_get_stock_html($product); // WPCS: XSS ok.

if ($product->is_in_stock()) :

    // Check if trials are enabled for this product and if product has trial configuration
    $trial_enabled_for_product = method_exists($product, 'is_trial_enabled') ? $product->is_trial_enabled() : true; // Default to true for backward compatibility
    $has_trial = method_exists($product, 'has_trial') && $product->has_trial();
    $user_id = get_current_user_id();
    $trial_available = false;

    // Get trial service and check eligibility if trial exists AND is enabled for this product
    if ($trial_enabled_for_product && $has_trial && class_exists('ZlaarkSubscriptionsTrialService')) {
        try {
            $trial_service = ZlaarkSubscriptionsTrialService::instance();
            $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product->get_id());
            $trial_available = $trial_eligibility['eligible'];
        } catch (Exception $e) {
            $trial_available = false;
            error_log('Zlaark Subscriptions: Trial service error in template - ' . $e->getMessage());
        }
    }

    // Debug output (remove in production)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo "<!-- Zlaark Debug: Product ID {$product->get_id()}, Type: {$product->get_type()}, Has Trial: " . ($has_trial ? 'Yes' : 'No') . ", Trial Available: " . ($trial_available ? 'Yes' : 'No') . " -->";
    }

    ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <!-- Subscription Information Display -->
    <?php if ($has_trial): ?>
        <div class="subscription-trial-info">
            <div class="trial-highlight">
                <?php if ($trial_available): ?>
                    <div class="trial-available">
                        <h4><?php _e('Trial Available!', 'zlaark-subscriptions'); ?></h4>
                        <?php if ($product->get_trial_price() > 0): ?>
                            <p><?php printf(__('Try for %d %s at %s, then %s %s', 'zlaark-subscriptions'),
                                $product->get_trial_duration(),
                                $product->get_trial_period(),
                                wc_price($product->get_trial_price()),
                                wc_price($product->get_recurring_price()),
                                $product->get_billing_interval()
                            ); ?></p>
                        <?php else: ?>
                            <p><?php printf(__('Try FREE for %d %s, then %s %s', 'zlaark-subscriptions'),
                                $product->get_trial_duration(),
                                $product->get_trial_period(),
                                wc_price($product->get_recurring_price()),
                                $product->get_billing_interval()
                            ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="trial-unavailable">
                        <h4><?php _e('Trial Not Available', 'zlaark-subscriptions'); ?></h4>
                        <p><?php _e('You have already used the trial for this subscription or are not eligible.', 'zlaark-subscriptions'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add to Cart Form -->
    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
        <?php do_action('woocommerce_before_add_to_cart_button'); ?>

        <!-- Hidden input for subscription type - Default to regular, JavaScript will change to trial when trial button clicked -->
        <input type="hidden" name="subscription_type" id="subscription_type" value="regular" />
        <!-- Hidden input for Woo add-to-cart (ensures product_id is always available in DOM) -->
        <input type="hidden" name="add-to-cart" id="zlaark_add_to_cart_product_id" value="<?php echo esc_attr($product->get_id()); ?>" />

        <!-- Dual Button System -->
        <div class="subscription-purchase-options">
            <?php if ($trial_enabled_for_product && $has_trial): ?>
                <div class="trial-cart">
                    <?php if ($trial_available): ?>
                        <button type="button" class="trial-button" data-subscription-type="trial" data-product-id="<?php echo esc_attr($product->get_id()); ?>" value="<?php echo esc_attr($product->get_id()); ?>">
                            <span class="button-icon">🎯</span>
                            <span class="button-text">
                                <?php if ($product->get_trial_price() > 0): ?>
                                    <?php printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($product->get_trial_price())); ?>
                                <?php else: ?>
                                    <?php _e('Start FREE Trial', 'zlaark-subscriptions'); ?>
                                <?php endif; ?>
                            </span>
                        </button>
                    <?php else: ?>
                        <div class="trial-unavailable">
                            <div class="unavailable-message">
                                <span class="unavailable-icon">🚫</span>
                                <span class="unavailable-text"><?php _e('Trial Not Available', 'zlaark-subscriptions'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="regular-cart">
                <button type="button" class="regular-button" data-subscription-type="regular" data-product-id="<?php echo esc_attr($product->get_id()); ?>" value="<?php echo esc_attr($product->get_id()); ?>">
                    <span class="button-icon">🚀</span>
                    <span class="button-text">
                        <?php printf(__('Start Subscription - %s %s', 'zlaark-subscriptions'), wc_price($product->get_recurring_price()), $product->get_billing_interval()); ?>
                    </span>
                </button>
            </div>
        </div>

        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

    <!-- Inline CSS for subscription template -->
    <style>
    .subscription-trial-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }

    .trial-available {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 6px;
        border: 1px solid #c3e6cb;
    }

    .trial-unavailable {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 6px;
        border: 1px solid #f5c6cb;
    }

    .trial-available h4,
    .trial-unavailable h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
    }

    .trial-available p,
    .trial-unavailable p {
        margin: 0;
        font-size: 14px;
    }

    /* Dual Button System Styles */
    .subscription-purchase-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 30px 0;
    }

    @media (max-width: 768px) {
        .subscription-purchase-options {
            grid-template-columns: 1fr;
        }
    }

    .trial-cart,
    .regular-cart {
        margin: 0;
    }

    .trial-button,
    .regular-button {
        width: 100% !important;
        padding: 18px 20px !important;
        font-size: 16px !important;
        font-weight: bold !important;
        border-radius: 10px !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        border: none !important;
        cursor: pointer !important;
    }

    .trial-button {
        background: #D6809C !important;
        color: white !important;
        box-shadow: 0 4px 8px rgba(214, 128, 156, 0.3) !important;
    }

    .trial-button:hover {
        background: #927397 !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 16px rgba(214, 128, 156, 0.4) !important;
    }

    .regular-button {
        background: #927397 !important;
        color: white !important;
        box-shadow: 0 4px 8px rgba(146, 115, 151, 0.3) !important;
    }

    .regular-button:hover {
        background: #D6809C !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 16px rgba(146, 115, 151, 0.4) !important;
    }

    .button-icon {
        font-size: 20px;
    }

    .button-text {
        font-weight: 600;
    }

    /* Loading State Styling */
    .trial-button.loading,
    .regular-button.loading {
        opacity: 0.7 !important;
        cursor: not-allowed !important;
        transform: none !important;
        position: relative !important;
    }

    .trial-button.loading::after,
    .regular-button.loading::after {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 20px !important;
        height: 20px !important;
        margin: -10px 0 0 -10px !important;
        border: 2px solid rgba(255, 255, 255, 0.3) !important;
        border-top: 2px solid white !important;
        border-radius: 50% !important;
        animation: zlaark-spin 1s linear infinite !important;
    }

    /* Success State Styling */
    .trial-button.success,
    .regular-button.success {
        opacity: 1 !important;
        cursor: default !important;
        transform: scale(1.05) !important;
    }

    .trial-button.success {
        background: #927397 !important;
        box-shadow: 0 6px 12px rgba(214, 128, 156, 0.4) !important;
    }

    .regular-button.success {
        background: #D6809C !important;
        box-shadow: 0 6px 12px rgba(146, 115, 151, 0.4) !important;
    }

    .trial-button.loading .button-text,
    .regular-button.loading .button-text,
    .trial-button.loading .button-icon,
    .regular-button.loading .button-icon {
        opacity: 0 !important;
    }

    @keyframes zlaark-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Selected State Styling */
    .trial-button.selected,
    .regular-button.selected {
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5) !important;
    }

    /* Trial Unavailable Styling */
    .trial-unavailable {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: #f8d7da;
        border: 2px solid #f5c6cb;
        border-radius: 10px;
        color: #721c24;
    }

    .unavailable-message {
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: center;
    }

    .unavailable-icon {
        font-size: 20px;
    }

    .unavailable-text {
        font-weight: 500;
        font-size: 14px;
    }

    /* Selected button state */
    .trial-button.selected,
    .regular-button.selected {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2) !important;
    }

    /* Single button layout when no trial */
    .subscription-purchase-options:has(.regular-cart:only-child) {
        grid-template-columns: 1fr;
        max-width: 400px;
        margin: 30px auto;
    }
    </style>

    <!-- JavaScript for dual button system with AJAX -->
    <script>
    console.log('Zlaark: Template JS tag reached');

    jQuery(document).ready(function($) {
        console.log('Zlaark: Template JS reached');

        // Check if frontend.js has already initialized the buttons
        if (window.ZlaarkSubscriptionsFrontend) {
            console.log('Zlaark: Frontend.js detected - skipping template initialization to prevent conflicts');
            return;
        }

        console.log('Zlaark: Frontend.js not detected - initializing template buttons as fallback');

        // Enhanced dual button system with AJAX handling
        function initSubscriptionButtons() {
            // Remove any existing handlers to prevent conflicts
            $('.trial-button, .regular-button').off('click.zlaark');

            // Handle subscription type selection for dual buttons
            $('.trial-button, .regular-button').on('click.zlaark', function(e) {
                e.preventDefault(); // Always prevent default

                var $button = $(this);
                var subscriptionType = $button.data('subscription-type');

                // More reliable product ID extraction
                var productId = $button.val() ||
                               $button.attr('value') ||
                               $button.closest('form').find('[name="add-to-cart"]').val() ||
                               $button.closest('form').find('[name="add-to-cart"]').attr('value') ||
                               '<?php echo esc_js($product->get_id()); ?>';

                // Debug logging
                console.log('Zlaark: Template AJAX Button clicked', {
                    type: subscriptionType,
                    productId: productId,
                    buttonElement: $button[0],
                    buttonValue: $button.val(),
                    buttonAttr: $button.attr('value'),
                    formValue: $button.closest('form').find('[name="add-to-cart"]').val(),
                    userId: '<?php echo get_current_user_id(); ?>',
                    isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>
                });

                // Validation checks
                if (!subscriptionType) {
                    alert('<?php echo esc_js(__('Please select a subscription option.', 'zlaark-subscriptions')); ?>');
                    return false;
                }

                if (!productId) {
                    alert('<?php echo esc_js(__('Product ID not found.', 'zlaark-subscriptions')); ?>');
                    return false;
                }

                // Check if user is logged in (client-side check)
                if (!$('body').hasClass('logged-in')) {
                    alert('<?php echo esc_js(__('Please log in to purchase a subscription.', 'zlaark-subscriptions')); ?>');
                    window.location.href = '<?php echo esc_js(home_url('/auth')); ?>';
                    return false;
                }

                // Add visual feedback with debugging
                console.log('Zlaark: Before visual feedback', {
                    buttonVisible: $button.is(':visible'),
                    buttonDisplay: $button.css('display'),
                    buttonOpacity: $button.css('opacity')
                });

                // Cache original text for restore on error/timeout
                var $buttonText = $button.find('.button-text');
                if ($buttonText.length && !$buttonText.data('original-text')) {
                    $buttonText.data('original-text', $buttonText.text());
                }

                // Reset other buttons and set loading state on current
                $('.trial-button, .regular-button').removeClass('selected loading success').prop('disabled', false).attr('aria-busy', 'false');
                $button.addClass('selected loading').prop('disabled', true).attr('aria-busy', 'true');
                console.log('Zlaark: Loading state applied for', subscriptionType, 'subscription');

                console.log('Zlaark: After visual feedback', {
                    buttonVisible: $button.is(':visible'),
                    buttonDisplay: $button.css('display'),
                    buttonOpacity: $button.css('opacity'),
                    buttonClasses: $button[0].className
                });

                // Get AJAX configuration (fallback if localized script not loaded)
                var ajaxUrl = (typeof zlaark_subscriptions_frontend !== 'undefined' && zlaark_subscriptions_frontend.ajax_url)
                    ? zlaark_subscriptions_frontend.ajax_url
                    : '<?php echo admin_url('admin-ajax.php'); ?>';

                var ajaxNonce = (typeof zlaark_subscriptions_frontend !== 'undefined' && zlaark_subscriptions_frontend.nonce)
                    ? zlaark_subscriptions_frontend.nonce
                    : '<?php echo wp_create_nonce('zlaark_subscriptions_frontend_nonce'); ?>';

                console.log('Zlaark: AJAX Config', {
                    url: ajaxUrl,
                    nonce: ajaxNonce.substring(0, 10) + '...',
                    hasGlobalConfig: typeof zlaark_subscriptions_frontend !== 'undefined'
                });

                // Detailed log payload before AJAX request to add to cart
                console.log('Zlaark: AJAX request payload', {
                    action: 'zlaark_add_subscription_to_cart',
                    product_id: productId,
                    subscription_type: subscriptionType,
                    quantity: 1,
                    nonce: ajaxNonce
                });
                console.log('Zlaark: Sending AJAX request...');
                // AJAX request to add to cart
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'zlaark_add_subscription_to_cart',
                        product_id: productId,
                        subscription_type: subscriptionType,
                        quantity: 1,
                        nonce: ajaxNonce
                    },
                    success: function(response) {
                        console.log('Zlaark: AJAX Success', response);
                        // Log detailed response data for debugging
                        console.log('Zlaark: AJAX response data', response.data);
                        console.log('Zlaark: Entering success handler, success flag:', response.success);

                        // Ensure button is still visible
                        if (!$button.is(':visible')) {
                            console.warn('Zlaark: Button became invisible during AJAX!');
                            $button.show();
                        }

                        if (response && response.success) {
                            // Show success message briefly
                            $button.removeClass('loading').addClass('success');

                            var $buttonText = $button.find('.button-text');
                            if ($buttonText.length > 0) {
                                $buttonText.text('<?php echo esc_js(__('Added to Cart!', 'zlaark-subscriptions')); ?>');
                            }

                            console.log('Zlaark: SUCCESS - Would redirect to checkout', response.data.redirect);

                            // Redirect disabled for debugging
                            // setTimeout(function() {
                            //     if (response.data && response.data.redirect) {
                            //         window.location.href = response.data.redirect;
                            //     } else {
                            //         console.error('Zlaark: No redirect URL provided');
                            //         alert('<?php echo esc_js(__('Product added but redirect failed. Please go to checkout manually.', 'zlaark-subscriptions')); ?>');
                            //     }
                            // }, 1000);
                        } else {
                            // Handle error response
                            console.error('Zlaark: AJAX Error Response', response);
                            $button.removeClass('loading').prop('disabled', false);

                            var errorMessage = (response && response.data && response.data.message)
                                ? response.data.message
                                : '<?php echo esc_js(__('An error occurred. Please try again.', 'zlaark-subscriptions')); ?>';

                            if (response && response.data && response.data.redirect) {
                                // Redirect for login - disabled for debugging
                                console.log('Zlaark: Would redirect for authentication', response.data.redirect);
                                // window.location.href = response.data.redirect;
                            } else {
                                if ($buttonText && $buttonText.length) {
                                    $buttonText.text($buttonText.data('original-text'));
                                }
                                $button.attr('aria-busy', 'false');
                                alert(errorMessage);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Zlaark: Entering error handler');
                        console.error('Zlaark: AJAX Request Failed', {
                            xhr: xhr,
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            readyState: xhr.readyState,
                            statusCode: xhr.status
                        });

                        // Ensure button is visible and reset
                        if (!$button.is(':visible')) {
                            console.warn('Zlaark: Button became invisible during AJAX error!');
                            $button.show();
                        }

                        $button.removeClass('loading').prop('disabled', false).attr('aria-busy', 'false');

                        if ($buttonText && $buttonText.length) {
                            $buttonText.text($buttonText.data('original-text'));
                        }

                        var errorMsg = '<?php echo esc_js(__('Network error. Please try again.', 'zlaark-subscriptions')); ?>';
                        if (xhr.status === 0) {
                            errorMsg = '<?php echo esc_js(__('Connection failed. Please check your internet connection.', 'zlaark-subscriptions')); ?>';
                        } else if (xhr.status === 403) {
                            errorMsg = '<?php echo esc_js(__('Access denied. Please refresh the page and try again.', 'zlaark-subscriptions')); ?>';
                        } else if (xhr.status === 500) {
                            errorMsg = '<?php echo esc_js(__('Server error. Please try again later.', 'zlaark-subscriptions')); ?>';
                        }

                        alert(errorMsg);
                    },
                    timeout: 10000 // 10 second timeout
                });

                // Fallback timeout to prevent permanent loading state
                setTimeout(function() {
                    if ($button.hasClass('loading')) {
                        $button.removeClass('loading').prop('disabled', false).attr('aria-busy', 'false');
                        if ($buttonText && $buttonText.length) {
                            $buttonText.text($buttonText.data('original-text'));
                        }
                        console.log('Zlaark: Loading state timeout - button re-enabled');
                        alert('<?php echo esc_js(__('Request timed out. Please try again.', 'zlaark-subscriptions')); ?>');
                    }
                }, 12000);
            });

            console.log('Zlaark: AJAX subscription buttons initialized');
        }


        // Global AJAX diagnostics logging
        $(document).on('ajaxError', function(e, xhr, settings, error){
            console.warn('Zlaark Diag: ajaxError', {url: settings && settings.url, status: xhr && xhr.status, error: error, response: (xhr && xhr.responseText ? xhr.responseText.substring(0,200) : '')});
        });
        $(document).on('ajaxSuccess', function(e, xhr, settings){
            console.log('Zlaark Diag: ajaxSuccess', {url: settings && settings.url, status: xhr && xhr.status});
        });

        // Inline diagnostics panel (enabled via ?zlaark_diag=1)
        if (window.location.search.indexOf('zlaark_diag=1') !== -1) {
            var $panel = $('<div/>', {css: {border:'2px solid #f59e0b', padding:'12px', borderRadius:'8px', margin:'16px 0', background:'#fff8e1'}});
            $panel.append('<strong>Subscription Diagnostics</strong><br><small>Live status, hook checks, and dry-run tests</small>');
            var $btns = $('<div style="margin-top:8px; display:flex; gap:8px;"></div>');
            var $out = $('<pre style="max-height:200px; overflow:auto; background:#fafafa; padding:8px; margin-top:8px;"></pre>');

            var $ping = $('<button type="button" class="button">Ping Diagnostics</button>');
            var $dry = $('<button type="button" class="button">Dry-run Add-to-Cart</button>');

            $ping.on('click', function(){
                $out.text('Pinging diagnostic endpoint...');
                $.ajax({
                    url: (typeof zlaark_subscriptions_frontend!=='undefined'? zlaark_subscriptions_frontend.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>'),
                    type: 'POST',
                    data: { action: 'zlaark_diag_status' },
                    success: function(resp){ $out.text('Status:\n' + JSON.stringify(resp, null, 2)); },
                    error: function(x,s,e){ $out.text('Diag ERROR: ' + s + ' ' + e + '\n' + (x.responseText||'').substring(0,300)); }
                });
            });

            $dry.on('click', function(){
                var $btn = $('.trial-button, .regular-button').first();
                var pid = $btn.val() || $btn.attr('value') || '<?php echo esc_js($product->get_id()); ?>';
                $out.text('Dry-run add_to_cart (no mutation) for product ' + pid + ' ...');
                $.ajax({
                    url: (typeof zlaark_subscriptions_frontend!=='undefined'? zlaark_subscriptions_frontend.ajax_url : '<?php echo admin_url('admin-ajax.php'); ?>'),
                    type: 'POST',
                    data: {
                        action: 'zlaark_add_subscription_to_cart',
                        product_id: pid,
                        subscription_type: 'regular',
                        quantity: 1,
                        diagnostic: '1',
                        nonce: (typeof zlaark_subscriptions_frontend!=='undefined'? zlaark_subscriptions_frontend.nonce : '<?php echo wp_create_nonce('zlaark_subscriptions_frontend_nonce'); ?>')
                    },
                    success: function(resp){ $out.text('Dry-run response:\n' + JSON.stringify(resp, null, 2)); },
                    error: function(x,s,e){ $out.text('Dry-run ERROR: ' + s + ' ' + e + '\n' + (x.responseText||'').substring(0,300)); }
                });
            });

            $btns.append($ping, $dry);
            $panel.append($btns, $out);

            var $container = $('.subscription-purchase-options');
            if ($container.length) { $container.before($panel); } else { $('body').prepend($panel); }
        }

        // Initialize buttons
        initSubscriptionButtons();

        // Re-initialize if content is dynamically loaded
        $(document).on('wc_fragments_refreshed wc_fragments_loaded', function() {
            console.log('Zlaark: Re-initializing AJAX subscription buttons after fragments refresh');
            initSubscriptionButtons();
        });

        // Handle page visibility change to reset stuck buttons
        $(document).on('visibilitychange', function() {
            if (!document.hidden) {
                $('.trial-button, .regular-button').removeClass('loading success').prop('disabled', false);
                // Reset button text
                $('.trial-button .button-text').text($('.trial-button .button-text').data('original-text') || '<?php echo esc_js(__('Start Trial', 'zlaark-subscriptions')); ?>');
                $('.regular-button .button-text').text($('.regular-button .button-text').data('original-text') || '<?php echo esc_js(__('Start Subscription', 'zlaark-subscriptions')); ?>');
            }
        });
    });
    </script>

<?php else: ?>
    <!-- Fallback for out of stock products -->
    <div class="subscription-out-of-stock">
        <p><?php _e('This subscription is currently out of stock.', 'zlaark-subscriptions'); ?></p>
    </div>
<?php endif; ?>
