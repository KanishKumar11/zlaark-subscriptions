<?php
/**
 * Subscription add to cart template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/subscription.php.
 *
 * @package ZlaarkSubscriptions
 * @version 1.0.1
 */

defined('ABSPATH') || exit;

global $product;

if (!$product->is_purchasable()) {
    return;
}

echo wc_get_stock_html($product); // WPCS: XSS ok.

if ($product->is_in_stock()) : ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
        <?php do_action('woocommerce_before_add_to_cart_button'); ?>

        <div class="subscription-add-to-cart">
            <?php
            /**
             * Display comprehensive subscription pricing summary
             */
            $has_trial = method_exists($product, 'has_trial') && $product->has_trial();
            $trial_price = $has_trial ? $product->get_trial_price() : 0;
            $trial_duration = $has_trial ? $product->get_trial_duration() : 0;
            $trial_period = $has_trial ? $product->get_trial_period() : '';
            $recurring_price = method_exists($product, 'get_recurring_price') ? $product->get_recurring_price() : 0;
            $billing_interval = method_exists($product, 'get_billing_interval') ? $product->get_billing_interval() : '';
            $signup_fee = method_exists($product, 'get_signup_fee') ? $product->get_signup_fee() : 0;
            ?>

            <div class="subscription-pricing-breakdown">
                <?php if ($has_trial): ?>
                    <div class="subscription-trial-highlight">
                        <div class="trial-badge">
                            <?php if ($trial_price > 0): ?>
                                <span class="trial-price-badge"><?php echo wc_price($trial_price); ?></span>
                                <span class="trial-duration"><?php printf(__('for %d %s', 'zlaark-subscriptions'), $trial_duration, $trial_period); ?></span>
                            <?php else: ?>
                                <span class="trial-free-badge"><?php _e('FREE', 'zlaark-subscriptions'); ?></span>
                                <span class="trial-duration"><?php printf(__('for %d %s', 'zlaark-subscriptions'), $trial_duration, $trial_period); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="trial-description">
                            <?php if ($trial_price > 0): ?>
                                <p class="trial-info"><?php printf(__('Start your subscription with a special trial price of %s for %d %s.', 'zlaark-subscriptions'), wc_price($trial_price), $trial_duration, $trial_period); ?></p>
                            <?php else: ?>
                                <p class="trial-info"><?php printf(__('Start with a completely FREE trial for %d %s - no payment required!', 'zlaark-subscriptions'), $trial_duration, $trial_period); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="subscription-after-trial">
                        <div class="after-trial-label"><?php _e('After your trial:', 'zlaark-subscriptions'); ?></div>
                        <div class="recurring-price-display">
                            <span class="recurring-amount"><?php echo wc_price($recurring_price); ?></span>
                            <span class="recurring-interval"><?php echo $billing_interval; ?></span>
                        </div>
                        <?php if ($signup_fee > 0): ?>
                            <div class="signup-fee-info">
                                <small><?php printf(__('Plus one-time setup fee: %s', 'zlaark-subscriptions'), wc_price($signup_fee)); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- No trial - show regular subscription pricing -->
                    <div class="subscription-regular-pricing">
                        <div class="subscription-price-display">
                            <span class="subscription-amount"><?php echo wc_price($recurring_price); ?></span>
                            <span class="subscription-interval"><?php echo $billing_interval; ?></span>
                        </div>
                        <?php if ($signup_fee > 0): ?>
                            <div class="signup-fee-info">
                                <small><?php printf(__('Plus one-time setup fee: %s', 'zlaark-subscriptions'), wc_price($signup_fee)); ?></small>
                            </div>
                        <?php endif; ?>
                        <div class="subscription-description">
                            <p><?php printf(__('You will be charged %s %s starting immediately.', 'zlaark-subscriptions'), wc_price($recurring_price), $billing_interval); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt subscription-add-to-cart-button">
                <?php
                if ($has_trial) {
                    if ($trial_price > 0) {
                        printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($trial_price));
                    } else {
                        echo esc_html__('Start FREE Trial', 'zlaark-subscriptions');
                    }
                } else {
                    printf(__('Subscribe - %s %s', 'zlaark-subscriptions'), wc_price($recurring_price), $billing_interval);
                }
                ?>
            </button>

            <?php do_action('woocommerce_after_add_to_cart_button'); ?>
        </div>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>
