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

    // Get trial service and subscription options
    if (class_exists('ZlaarkSubscriptionsTrialService')) {
        $trial_service = new ZlaarkSubscriptionsTrialService();
        $user_id = get_current_user_id();
        $subscription_options = $trial_service->get_subscription_options($product->get_id(), $user_id);
    } else {
        // Fallback if trial service is not available
        $regular_price = method_exists($product, 'get_recurring_price') ? $product->get_recurring_price() : $product->get_price();
        $billing_interval = method_exists($product, 'get_billing_interval') ? $product->get_billing_interval() : __('recurring', 'zlaark-subscriptions');

        $subscription_options = array(
            'trial' => array(
                'available' => false,
                'label' => __('Trial Not Available', 'zlaark-subscriptions'),
                'price' => 0,
                'description' => __('Trial service is not available.', 'zlaark-subscriptions')
            ),
            'regular' => array(
                'available' => true,
                'label' => __('Start Subscription', 'zlaark-subscriptions'),
                'price' => $regular_price,
                'description' => sprintf(__('Start your subscription at %s %s', 'zlaark-subscriptions'), wc_price($regular_price), $billing_interval)
            )
        );
    }

    ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <!-- Enhanced Trial Information Display -->
    <div class="subscription-options-overview">
        <h3 class="subscription-options-title"><?php _e('Choose Your Subscription Option', 'zlaark-subscriptions'); ?></h3>

        <div class="subscription-options-grid">
            <?php if ($subscription_options['trial']['available']): ?>
                <!-- Trial Option -->
                <div class="subscription-option trial-option">
                    <div class="option-header">
                        <h4 class="option-title"><?php _e('Trial Option', 'zlaark-subscriptions'); ?></h4>
                        <?php if ($subscription_options['trial']['price'] > 0): ?>
                            <div class="option-price"><?php echo wc_price($subscription_options['trial']['price']); ?></div>
                        <?php else: ?>
                            <div class="option-price free"><?php _e('FREE', 'zlaark-subscriptions'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="option-description">
                        <p><?php echo esc_html($subscription_options['trial']['description']); ?></p>
                    </div>
                    <div class="option-benefits">
                        <ul>
                            <li>✓ <?php _e('Try before you commit', 'zlaark-subscriptions'); ?></li>
                            <li>✓ <?php _e('Cancel anytime during trial', 'zlaark-subscriptions'); ?></li>
                            <li>✓ <?php _e('Automatic conversion to full subscription', 'zlaark-subscriptions'); ?></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Regular Subscription Option -->
            <div class="subscription-option regular-option">
                <div class="option-header">
                    <h4 class="option-title"><?php _e('Full Subscription', 'zlaark-subscriptions'); ?></h4>
                    <div class="option-price"><?php echo wc_price($subscription_options['regular']['price']); ?></div>
                </div>
                <div class="option-description">
                    <p><?php echo esc_html($subscription_options['regular']['description']); ?></p>
                </div>
                <div class="option-benefits">
                    <ul>
                        <li>✓ <?php _e('Immediate full access', 'zlaark-subscriptions'); ?></li>
                        <li>✓ <?php _e('No trial limitations', 'zlaark-subscriptions'); ?></li>
                        <li>✓ <?php _e('Best value for committed users', 'zlaark-subscriptions'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Dual Button System -->
    <div class="subscription-purchase-options">
        <?php if ($subscription_options['trial']['available']): ?>
            <!-- Trial Button -->
            <form class="cart trial-cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
                <?php do_action('woocommerce_before_add_to_cart_button'); ?>

                <input type="hidden" name="subscription_type" value="trial" />
                <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />

                <button type="submit" class="single_add_to_cart_button button alt trial-button">
                    <span class="button-icon">🎯</span>
                    <span class="button-text"><?php echo esc_html($subscription_options['trial']['label']); ?></span>
                </button>

                <?php do_action('woocommerce_after_add_to_cart_button'); ?>
            </form>
        <?php else: ?>
            <!-- Trial Not Available Message -->
            <div class="trial-unavailable">
                <div class="unavailable-message">
                    <span class="unavailable-icon">🚫</span>
                    <span class="unavailable-text"><?php echo esc_html($subscription_options['trial']['description']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Regular Subscription Button -->
        <form class="cart regular-cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
            <?php do_action('woocommerce_before_add_to_cart_button'); ?>

            <input type="hidden" name="subscription_type" value="regular" />
            <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />

            <button type="submit" class="single_add_to_cart_button button alt regular-button">
                <span class="button-icon">🚀</span>
                <span class="button-text"><?php echo esc_html($subscription_options['regular']['label']); ?></span>
            </button>

            <?php do_action('woocommerce_after_add_to_cart_button'); ?>
        </form>
    </div>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php else: ?>
    <!-- Fallback for out of stock products -->
    <div class="subscription-out-of-stock">
        <p><?php _e('This subscription is currently out of stock.', 'zlaark-subscriptions'); ?></p>
    </div>
<?php endif; ?>
