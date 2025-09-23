<?php
/**
 * Trial Eligibility Service
 *
 * Handles trial eligibility checking, dual button logic, and trial management
 *
 * @package ZlaarkSubscriptions
 * @version 1.0.4
 */

defined('ABSPATH') || exit;

class ZlaarkSubscriptionsTrialService {
    
    /**
     * Database instance
     *
     * @var ZlaarkSubscriptionsDatabase
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = ZlaarkSubscriptionsDatabase::instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle dual button cart actions
        add_action('woocommerce_add_to_cart', array($this, 'handle_subscription_add_to_cart'), 10, 6);
        
        // Add trial type to cart item data
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_trial_type_to_cart'), 10, 3);
        
        // Display trial type in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_trial_type_in_cart'), 10, 2);

        // Modify cart item price for trials
        add_action('woocommerce_before_calculate_totals', array($this, 'modify_cart_item_price'));

        // Validate trial eligibility before checkout
        add_action('woocommerce_checkout_process', array($this, 'validate_trial_eligibility_checkout'));
        
        // Add subscription type to order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_subscription_type_to_order_item'), 10, 4);

        // Process trial after successful order
        add_action('woocommerce_order_status_completed', array($this, 'process_trial_order'));
        add_action('woocommerce_order_status_processing', array($this, 'process_trial_order'));

        // Handle order status changes that affect trial tracking
        add_action('woocommerce_order_status_failed', array($this, 'handle_failed_trial_order'));
        add_action('woocommerce_order_status_cancelled', array($this, 'handle_cancelled_trial_order'));
        add_action('woocommerce_order_status_refunded', array($this, 'handle_refunded_trial_order'));
    }
    
    /**
     * Check if user is eligible for trial
     *
     * @param int $user_id
     * @param int $product_id
     * @param bool $strict_mode Whether to perform additional security checks
     * @return array
     */
    public function check_trial_eligibility($user_id, $product_id, $strict_mode = false) {
        $result = array(
            'eligible' => false,
            'reason' => '',
            'message' => '',
            'debug_info' => array()
        );

        // Check if user is logged in
        if (!$user_id || $user_id <= 0) {
            $result['reason'] = 'not_logged_in';
            $result['message'] = __('You must be logged in to start a trial.', 'zlaark-subscriptions');
            $result['debug_info']['user_id'] = $user_id;
            return $result;
        }

        // Check if product exists and is valid
        $product = wc_get_product($product_id);
        if (!$product || $product->get_type() !== 'subscription') {
            $result['reason'] = 'not_subscription';
            $result['message'] = __('This product is not a subscription.', 'zlaark-subscriptions');
            $result['debug_info']['product_id'] = $product_id;
            $result['debug_info']['product_type'] = $product ? $product->get_type() : 'not_found';
            return $result;
        }

        // Check if product has trial enabled
        if (!method_exists($product, 'has_trial') || !$product->has_trial()) {
            $result['reason'] = 'no_trial_available';
            $result['message'] = __('This subscription does not offer a trial period.', 'zlaark-subscriptions');
            $result['debug_info']['has_trial_method'] = method_exists($product, 'has_trial');
            $result['debug_info']['has_trial'] = method_exists($product, 'has_trial') ? $product->has_trial() : false;
            return $result;
        }

        // Primary check: Has user already used trial for this product?
        if ($this->db->has_user_used_trial($user_id, $product_id)) {
            $result['reason'] = 'trial_already_used';
            $result['message'] = __('You have already used the trial for this subscription.', 'zlaark-subscriptions');
            $result['debug_info']['trial_history'] = $this->db->get_user_trial_history($user_id, $product_id);
            return $result;
        }

        // Strict mode additional checks
        if ($strict_mode) {
            // Check for existing active subscriptions for this product
            $existing_subscriptions = $this->db->get_user_subscriptions($user_id, $product_id);
            if (!empty($existing_subscriptions)) {
                foreach ($existing_subscriptions as $subscription) {
                    if (in_array($subscription->status, array('active', 'trial'))) {
                        $result['reason'] = 'existing_subscription';
                        $result['message'] = __('You already have an active subscription for this product.', 'zlaark-subscriptions');
                        $result['debug_info']['existing_subscription'] = $subscription;
                        return $result;
                    }
                }
            }

            // Check for pending orders with trial subscriptions
            $pending_orders = $this->get_pending_trial_orders($user_id, $product_id);
            if (!empty($pending_orders)) {
                $result['reason'] = 'pending_trial_order';
                $result['message'] = __('You have a pending trial order for this subscription.', 'zlaark-subscriptions');
                $result['debug_info']['pending_orders'] = $pending_orders;
                return $result;
            }

            // Check for failed orders that might indicate trial abuse
            $failed_trial_count = $this->get_failed_trial_order_count($user_id, $product_id);
            $max_failed_attempts = apply_filters('zlaark_subscriptions_max_failed_trial_attempts', 3);

            if ($failed_trial_count >= $max_failed_attempts) {
                $result['reason'] = 'too_many_failed_attempts';
                $result['message'] = __('Too many failed trial attempts. Please contact support.', 'zlaark-subscriptions');
                $result['debug_info']['failed_trial_count'] = $failed_trial_count;
                $result['debug_info']['max_failed_attempts'] = $max_failed_attempts;
                return $result;
            }
        }
        
        // Check if user has active subscription for this product
        if ($this->has_active_subscription($user_id, $product_id)) {
            $result['reason'] = 'already_subscribed';
            $result['message'] = __('You already have an active subscription for this product.', 'zlaark-subscriptions');
            return $result;
        }
        
        $result['eligible'] = true;
        $result['message'] = __('You are eligible for the trial period.', 'zlaark-subscriptions');
        $result['debug_info']['checks_passed'] = true;

        return $result;
    }

    /**
     * Get pending trial orders for user and product
     *
     * @param int $user_id
     * @param int $product_id
     * @return array
     */
    private function get_pending_trial_orders($user_id, $product_id) {
        global $wpdb;

        $orders = $wpdb->get_results($wpdb->prepare("
            SELECT o.ID, o.post_status, o.post_date
            FROM {$wpdb->posts} o
            INNER JOIN {$wpdb->postmeta} pm_user ON o.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product ON oi.order_item_id = oim_product.order_item_id AND oim_product.meta_key = '_product_id'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_type ON oi.order_item_id = oim_type.order_item_id AND oim_type.meta_key = 'subscription_type'
            WHERE o.post_type = 'shop_order'
            AND o.post_status IN ('wc-pending', 'wc-on-hold')
            AND pm_user.meta_value = %d
            AND oim_product.meta_value = %d
            AND oim_type.meta_value = 'trial'
            ORDER BY o.post_date DESC
        ", $user_id, $product_id));

        return $orders;
    }

    /**
     * Get count of failed trial orders for user and product
     *
     * @param int $user_id
     * @param int $product_id
     * @return int
     */
    private function get_failed_trial_order_count($user_id, $product_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT o.ID)
            FROM {$wpdb->posts} o
            INNER JOIN {$wpdb->postmeta} pm_user ON o.ID = pm_user.post_id AND pm_user.meta_key = '_customer_user'
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.ID = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_product ON oi.order_item_id = oim_product.order_item_id AND oim_product.meta_key = '_product_id'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_type ON oi.order_item_id = oim_type.order_item_id AND oim_type.meta_key = 'subscription_type'
            WHERE o.post_type = 'shop_order'
            AND o.post_status IN ('wc-failed', 'wc-cancelled')
            AND pm_user.meta_value = %d
            AND oim_product.meta_value = %d
            AND oim_type.meta_value = 'trial'
            AND o.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", $user_id, $product_id));

        return intval($count);
    }
    
    /**
     * Check if user has active subscription for product
     *
     * @param int $user_id
     * @param int $product_id
     * @return bool
     */
    private function has_active_subscription($user_id, $product_id) {
        $subscriptions = $this->db->get_user_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            if ($subscription->product_id == $product_id && 
                in_array($subscription->status, array('active', 'trial'))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get subscription options for product
     *
     * @param int $product_id
     * @param int $user_id
     * @return array
     */
    public function get_subscription_options($product_id, $user_id = null) {
        $product = wc_get_product($product_id);
        $user_id = $user_id ?: get_current_user_id();
        
        $options = array(
            'regular' => array(
                'available' => true,
                'label' => __('Start Subscription', 'zlaark-subscriptions'),
                'price' => $product->get_recurring_price(),
                'description' => sprintf(
                    __('Start your subscription immediately at %s %s', 'zlaark-subscriptions'),
                    wc_price($product->get_recurring_price()),
                    $product->get_billing_interval()
                )
            ),
            'trial' => array(
                'available' => false,
                'label' => __('Start Trial', 'zlaark-subscriptions'),
                'price' => 0,
                'description' => ''
            )
        );
        
        // Check trial availability
        if (method_exists($product, 'has_trial') && $product->has_trial()) {
            $trial_eligibility = $this->check_trial_eligibility($user_id, $product_id);
            
            $options['trial']['available'] = $trial_eligibility['eligible'];
            $options['trial']['price'] = $product->get_trial_price();
            
            if ($trial_eligibility['eligible']) {
                $trial_duration = $product->get_trial_duration();
                $trial_period = $product->get_trial_period();
                
                if ($product->get_trial_price() > 0) {
                    $options['trial']['label'] = sprintf(
                        __('Start Trial - %s', 'zlaark-subscriptions'),
                        wc_price($product->get_trial_price())
                    );
                    $options['trial']['description'] = sprintf(
                        __('Try for %d %s at %s, then %s %s', 'zlaark-subscriptions'),
                        $trial_duration,
                        $trial_period,
                        wc_price($product->get_trial_price()),
                        wc_price($product->get_recurring_price()),
                        $product->get_billing_interval()
                    );
                } else {
                    $options['trial']['label'] = __('Start FREE Trial', 'zlaark-subscriptions');
                    $options['trial']['description'] = sprintf(
                        __('Try FREE for %d %s, then %s %s', 'zlaark-subscriptions'),
                        $trial_duration,
                        $trial_period,
                        wc_price($product->get_recurring_price()),
                        $product->get_billing_interval()
                    );
                }
            } else {
                $options['trial']['description'] = $trial_eligibility['message'];
            }
        }
        
        return $options;
    }
    
    /**
     * Handle subscription add to cart with trial type
     *
     * @param string $cart_item_key
     * @param int $product_id
     * @param int $quantity
     * @param int $variation_id
     * @param array $variation
     * @param array $cart_item_data
     */
    public function handle_subscription_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        // Check if this is a trial or regular subscription
        $subscription_type = isset($cart_item_data['subscription_type']) ? $cart_item_data['subscription_type'] : 'regular';
        
        if ($subscription_type === 'trial') {
            $user_id = get_current_user_id();

            // Use strict mode for cart validation to catch edge cases
            $trial_eligibility = $this->check_trial_eligibility($user_id, $product_id, true);

            if (!$trial_eligibility['eligible']) {
                wc_add_notice($trial_eligibility['message'], 'error');
                WC()->cart->remove_cart_item($cart_item_key);

                // Log security event for monitoring
                error_log("Zlaark Subscriptions: Cart validation blocked ineligible trial attempt - User: {$user_id}, Product: {$product_id}, Reason: {$trial_eligibility['reason']}");

                return;
            }
        }
    }
    
    /**
     * Add trial type to cart item data
     *
     * @param array $cart_item_data
     * @param int $product_id
     * @param int $variation_id
     * @return array
     */
    public function add_trial_type_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['subscription_type'])) {
            $cart_item_data['subscription_type'] = sanitize_text_field($_POST['subscription_type']);
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display trial type in cart
     *
     * @param array $item_data
     * @param array $cart_item
     * @return array
     */
    public function display_trial_type_in_cart($item_data, $cart_item) {
        if (isset($cart_item['subscription_type'])) {
            $subscription_type = $cart_item['subscription_type'];
            
            if ($subscription_type === 'trial') {
                $item_data[] = array(
                    'key' => __('Subscription Type', 'zlaark-subscriptions'),
                    'value' => __('Trial Subscription', 'zlaark-subscriptions'),
                    'display' => ''
                );
            } else {
                $item_data[] = array(
                    'key' => __('Subscription Type', 'zlaark-subscriptions'),
                    'value' => __('Regular Subscription', 'zlaark-subscriptions'),
                    'display' => ''
                );
            }
        }
        
        return $item_data;
    }

    /**
     * Modify cart item price based on subscription type
     *
     * @param WC_Cart $cart
     */
    public function modify_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['subscription_type'])) {
                $product = $cart_item['data'];
                $subscription_type = $cart_item['subscription_type'];

                if ($product && $product->get_type() === 'subscription') {
                    if ($subscription_type === 'trial' && method_exists($product, 'get_trial_price')) {
                        // Set trial price
                        $trial_price = $product->get_trial_price();
                        $cart_item['data']->set_price($trial_price);
                    } elseif ($subscription_type === 'regular' && method_exists($product, 'get_recurring_price')) {
                        // Set regular subscription price
                        $recurring_price = $product->get_recurring_price();
                        $cart_item['data']->set_price($recurring_price);
                    }
                }
            }
        }
    }

    /**
     * Validate trial eligibility during checkout
     */
    public function validate_trial_eligibility_checkout() {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['subscription_type']) && $cart_item['subscription_type'] === 'trial') {
                $product_id = $cart_item['product_id'];
                $user_id = get_current_user_id();

                // Use strict mode for final checkout validation
                $trial_eligibility = $this->check_trial_eligibility($user_id, $product_id, true);

                if (!$trial_eligibility['eligible']) {
                    // Critical security event - someone bypassed earlier validations
                    error_log("Zlaark Subscriptions: CRITICAL - Checkout validation blocked ineligible trial - User: {$user_id}, Product: {$product_id}, Reason: {$trial_eligibility['reason']}");

                    wc_add_notice($trial_eligibility['message'], 'error');

                    // Also remove the item from cart to prevent retry
                    WC()->cart->remove_cart_item($cart_item_key);

                    return;
                }

                // Additional race condition check - verify trial hasn't been used since cart was loaded
                if ($this->db->has_user_used_trial($user_id, $product_id)) {
                    error_log("Zlaark Subscriptions: Race condition detected - Trial used between cart and checkout - User: {$user_id}, Product: {$product_id}");

                    wc_add_notice(__('This trial is no longer available. Please refresh and try again.', 'zlaark-subscriptions'), 'error');
                    WC()->cart->remove_cart_item($cart_item_key);

                    return;
                }
            }
        }
    }

    /**
     * Add subscription type to order item meta
     *
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function add_subscription_type_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['subscription_type'])) {
            $item->add_meta_data('subscription_type', $values['subscription_type']);

            // Add human-readable label
            if ($values['subscription_type'] === 'trial') {
                $item->add_meta_data(__('Subscription Type', 'zlaark-subscriptions'), __('Trial Subscription', 'zlaark-subscriptions'));
            } else {
                $item->add_meta_data(__('Subscription Type', 'zlaark-subscriptions'), __('Regular Subscription', 'zlaark-subscriptions'));
            }
        }
    }

    /**
     * Process trial order after completion
     *
     * @param int $order_id
     */
    public function process_trial_order($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);

            if ($product && $product->get_type() === 'subscription') {
                $subscription_type = $item->get_meta('subscription_type');

                if ($subscription_type === 'trial') {
                    $user_id = $order->get_user_id();

                    // Double-check trial eligibility before recording
                    $trial_eligibility = $this->check_trial_eligibility($user_id, $product_id);

                    if ($trial_eligibility['eligible']) {
                        // Get the subscription ID if it exists
                        $subscription = $this->db->get_subscription_by_order($order_id);
                        $subscription_id = $subscription ? $subscription->id : null;

                        // Record trial usage with subscription ID
                        $trial_history_id = $this->db->record_trial_usage($user_id, $product_id, $subscription_id);

                        if ($trial_history_id) {
                            // Add order note
                            $order->add_order_note(__('Trial subscription started for user. Trial usage recorded.', 'zlaark-subscriptions'));

                            // Log for debugging
                            error_log("Zlaark Subscriptions: Trial usage recorded for user {$user_id}, product {$product_id}, trial history ID {$trial_history_id}");
                        } else {
                            // Failed to record trial usage - this is critical
                            $order->add_order_note(__('ERROR: Failed to record trial usage. Trial may be compromised.', 'zlaark-subscriptions'));
                            error_log("Zlaark Subscriptions: CRITICAL - Failed to record trial usage for user {$user_id}, product {$product_id}");
                        }
                    } else {
                        // User is not eligible for trial but somehow got through - this is a security issue
                        $order->add_order_note(sprintf(__('WARNING: Trial order processed for ineligible user. Reason: %s', 'zlaark-subscriptions'), $trial_eligibility['message']));
                        error_log("Zlaark Subscriptions: SECURITY WARNING - Trial order processed for ineligible user {$user_id}, product {$product_id}. Reason: {$trial_eligibility['reason']}");

                        // Consider cancelling the order or converting to regular subscription
                        $this->handle_ineligible_trial_order($order, $item, $trial_eligibility);
                    }
                }
            }
        }
    }

    /**
     * Handle trial order for ineligible user
     *
     * @param WC_Order $order
     * @param WC_Order_Item_Product $item
     * @param array $trial_eligibility
     */
    private function handle_ineligible_trial_order($order, $item, $trial_eligibility) {
        // Convert trial to regular subscription pricing
        $product = $item->get_product();
        if ($product && method_exists($product, 'get_recurring_price')) {
            $regular_price = $product->get_recurring_price();
            $trial_price = $product->get_trial_price();

            if ($regular_price > $trial_price) {
                // Add note about price difference
                $price_difference = $regular_price - $trial_price;
                $order->add_order_note(sprintf(
                    __('Trial converted to regular subscription. Price difference: %s', 'zlaark-subscriptions'),
                    wc_price($price_difference)
                ));

                // Update order item meta to reflect regular subscription
                $item->update_meta_data('subscription_type', 'regular');
                $item->update_meta_data('_original_subscription_type', 'trial_converted');
                $item->save();
            }
        }
    }

    /**
     * Handle failed trial order
     *
     * @param int $order_id
     */
    public function handle_failed_trial_order($order_id) {
        $this->handle_unsuccessful_trial_order($order_id, 'failed');
    }

    /**
     * Handle cancelled trial order
     *
     * @param int $order_id
     */
    public function handle_cancelled_trial_order($order_id) {
        $this->handle_unsuccessful_trial_order($order_id, 'cancelled');
    }

    /**
     * Handle refunded trial order
     *
     * @param int $order_id
     */
    public function handle_refunded_trial_order($order_id) {
        $this->handle_unsuccessful_trial_order($order_id, 'refunded');
    }

    /**
     * Handle unsuccessful trial order (failed, cancelled, refunded)
     *
     * @param int $order_id
     * @param string $reason
     */
    private function handle_unsuccessful_trial_order($order_id, $reason) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);

            if ($product && $product->get_type() === 'subscription') {
                $subscription_type = $item->get_meta('subscription_type');

                if ($subscription_type === 'trial') {
                    $user_id = $order->get_user_id();

                    // Check if trial usage was recorded for this order
                    $trial_history = $this->db->get_user_trial_history($user_id, $product_id);

                    foreach ($trial_history as $history) {
                        if ($history->subscription_id) {
                            $subscription = $this->db->get_subscription($history->subscription_id);
                            if ($subscription && $subscription->order_id == $order_id) {
                                // Update trial history status
                                $this->db->update_trial_status($user_id, $product_id, $reason);

                                // Add order note
                                $order->add_order_note(sprintf(
                                    __('Trial order %s - trial usage status updated to %s.', 'zlaark-subscriptions'),
                                    $reason,
                                    $reason
                                ));

                                // Log the event
                                error_log("Zlaark Subscriptions: Trial order {$reason} - User: {$user_id}, Product: {$product_id}, Order: {$order_id}");

                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}
