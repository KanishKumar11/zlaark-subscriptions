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
             * Display subscription pricing summary before add to cart button
             */
            if (method_exists($product, 'has_trial') && $product->has_trial()) {
                $trial_price = $product->get_trial_price();
                $trial_duration = $product->get_trial_duration();
                $trial_period = $product->get_trial_period();
                
                echo '<div class="subscription-trial-summary">';
                if ($trial_price > 0) {
                    printf(
                        '<p class="trial-price">%s</p>',
                        sprintf(
                            __('Start with %s for %d %s', 'zlaark-subscriptions'),
                            wc_price($trial_price),
                            $trial_duration,
                            $trial_period
                        )
                    );
                } else {
                    printf(
                        '<p class="trial-price free-trial">%s</p>',
                        sprintf(
                            __('Start with FREE trial for %d %s', 'zlaark-subscriptions'),
                            $trial_duration,
                            $trial_period
                        )
                    );
                }
                echo '</div>';
            }
            
            if (method_exists($product, 'get_recurring_price')) {
                $recurring_price = $product->get_recurring_price();
                $billing_interval = $product->get_billing_interval();
                
                echo '<div class="subscription-recurring-summary">';
                printf(
                    '<p class="recurring-price">%s</p>',
                    sprintf(
                        __('Then %s %s', 'zlaark-subscriptions'),
                        wc_price($recurring_price),
                        $billing_interval
                    )
                );
                echo '</div>';
            }
            ?>

            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt subscription-add-to-cart-button">
                <?php 
                if (method_exists($product, 'has_trial') && $product->has_trial()) {
                    echo esc_html__('Start Trial', 'zlaark-subscriptions');
                } else {
                    echo esc_html__('Start Subscription', 'zlaark-subscriptions');
                }
                ?>
            </button>

            <?php do_action('woocommerce_after_add_to_cart_button'); ?>
        </div>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>
