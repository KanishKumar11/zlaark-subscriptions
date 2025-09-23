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

        <!-- Hidden input for subscription type -->
        <input type="hidden" name="subscription_type" id="subscription_type" value="<?php echo ($has_trial && $trial_available) ? 'trial' : 'regular'; ?>" />

        <!-- Dual Button System -->
        <div class="subscription-purchase-options">
            <?php if ($has_trial): ?>
                <div class="trial-cart">
                    <?php if ($trial_available): ?>
                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="trial-button" data-subscription-type="trial">
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
                <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="regular-button" data-subscription-type="regular">
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
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        color: white !important;
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3) !important;
    }

    .trial-button:hover {
        background: linear-gradient(135deg, #218838 0%, #1ea085 100%) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 16px rgba(40, 167, 69, 0.4) !important;
    }

    .regular-button {
        background: linear-gradient(135deg, #007cba 0%, #0056b3 100%) !important;
        color: white !important;
        box-shadow: 0 4px 8px rgba(0, 124, 186, 0.3) !important;
    }

    .regular-button:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 16px rgba(0, 124, 186, 0.4) !important;
    }

    .button-icon {
        font-size: 20px;
    }

    .button-text {
        font-weight: 600;
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

    <!-- JavaScript for dual button system -->
    <script>
    jQuery(document).ready(function($) {
        // Handle subscription type selection for dual buttons
        $('.trial-button, .regular-button').on('click', function(e) {
            var subscriptionType = $(this).data('subscription-type');
            $('#subscription_type').val(subscriptionType);

            // Optional: Add visual feedback
            $('.trial-button, .regular-button').removeClass('selected');
            $(this).addClass('selected');
        });

        // Prevent form submission if no subscription type is set (safety check)
        $('form.cart').on('submit', function(e) {
            var subscriptionType = $('#subscription_type').val();
            if (!subscriptionType) {
                e.preventDefault();
                alert('<?php echo esc_js(__('Please select a subscription option.', 'zlaark-subscriptions')); ?>');
                return false;
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
