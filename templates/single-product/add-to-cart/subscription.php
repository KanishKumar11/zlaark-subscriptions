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

    // Check if product has trial
    $has_trial = method_exists($product, 'has_trial') && $product->has_trial();
    $user_id = get_current_user_id();
    $trial_available = false;

    // Get trial service and check eligibility if trial exists
    if ($has_trial && class_exists('ZlaarkSubscriptionsTrialService')) {
        try {
            $trial_service = new ZlaarkSubscriptionsTrialService();
            $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product->get_id());
            $trial_available = $trial_eligibility['eligible'];
        } catch (Exception $e) {
            $trial_available = false;
        }
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

        <!-- Subscription Type Selection -->
        <?php if ($has_trial && $trial_available): ?>
            <div class="subscription-type-selection">
                <h4><?php _e('Choose Your Option:', 'zlaark-subscriptions'); ?></h4>
                <div class="subscription-options">
                    <label class="subscription-option">
                        <input type="radio" name="subscription_type" value="trial" checked />
                        <span class="option-label">
                            <?php if ($product->get_trial_price() > 0): ?>
                                <?php printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($product->get_trial_price())); ?>
                            <?php else: ?>
                                <?php _e('Start FREE Trial', 'zlaark-subscriptions'); ?>
                            <?php endif; ?>
                        </span>
                    </label>
                    <label class="subscription-option">
                        <input type="radio" name="subscription_type" value="regular" />
                        <span class="option-label">
                            <?php printf(__('Start Subscription - %s %s', 'zlaark-subscriptions'), wc_price($product->get_recurring_price()), $product->get_billing_interval()); ?>
                        </span>
                    </label>
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" name="subscription_type" value="regular" />
        <?php endif; ?>

        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt">
            <?php
            if ($has_trial && $trial_available) {
                if ($product->get_trial_price() > 0) {
                    printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($product->get_trial_price()));
                } else {
                    echo esc_html__('Start FREE Trial', 'zlaark-subscriptions');
                }
            } else {
                printf(__('Subscribe - %s %s', 'zlaark-subscriptions'), wc_price($product->get_recurring_price()), $product->get_billing_interval());
            }
            ?>
        </button>

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

    .subscription-type-selection {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 20px;
        margin: 20px 0;
    }

    .subscription-type-selection h4 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #495057;
    }

    .subscription-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .subscription-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .subscription-option:hover {
        background: #e9ecef;
        border-color: #007cba;
    }

    .subscription-option input[type="radio"] {
        margin: 0;
    }

    .option-label {
        font-weight: 500;
        color: #495057;
    }

    .single_add_to_cart_button {
        width: 100% !important;
        padding: 15px 20px !important;
        font-size: 16px !important;
        font-weight: bold !important;
        border-radius: 6px !important;
        margin-top: 15px !important;
    }
    </style>

    <!-- JavaScript for dynamic button text -->
    <script>
    jQuery(document).ready(function($) {
        $('input[name="subscription_type"]').change(function() {
            var button = $('.single_add_to_cart_button');
            var selectedType = $(this).val();

            if (selectedType === 'trial') {
                <?php if ($product->get_trial_price() > 0): ?>
                    button.text('<?php printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($product->get_trial_price())); ?>');
                <?php else: ?>
                    button.text('<?php echo esc_js(__('Start FREE Trial', 'zlaark-subscriptions')); ?>');
                <?php endif; ?>
            } else {
                button.text('<?php printf(__('Subscribe - %s %s', 'zlaark-subscriptions'), wc_price($product->get_recurring_price()), $product->get_billing_interval()); ?>');
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
