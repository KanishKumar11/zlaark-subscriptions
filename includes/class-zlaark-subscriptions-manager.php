<?php
/**
 * Subscription management core logic
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription manager class
 */
class ZlaarkSubscriptionsManager {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsManager
     */
    private static $instance = null;
    
    /**
     * Database instance
     *
     * @var ZlaarkSubscriptionsDatabase
     */
    private $db;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsManager
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = ZlaarkSubscriptionsDatabase::instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle order completion for subscription products
        add_action('woocommerce_order_status_completed', array($this, 'handle_subscription_order_completed'));
        add_action('woocommerce_payment_complete', array($this, 'handle_subscription_payment_complete'));
        
        // Handle subscription status changes
        add_action('zlaark_subscription_status_changed', array($this, 'handle_subscription_status_change'), 10, 3);
        
        // Handle failed payments
        add_action('zlaark_subscription_payment_failed', array($this, 'handle_failed_payment'), 10, 2);
        
        // Handle manual payments
        add_action('woocommerce_order_status_completed', array($this, 'check_manual_payment_completion'), 20);
        add_action('woocommerce_payment_complete', array($this, 'check_manual_payment_completion'), 20);
        
        // Prevent multiple subscriptions for same product
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_subscription_cart'), 10, 3);
        
        // Modify cart for subscription products
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_subscription_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_subscription_cart_item_from_session'), 10, 3);

        // Modify cart item price based on subscription type (trial vs regular)
        // Use priority 5 to run before other cart modifications
        add_action('woocommerce_before_calculate_totals', array($this, 'modify_subscription_cart_item_prices'), 5, 1);

        // Additional hook to ensure pricing is applied when cart items are loaded
        add_action('woocommerce_cart_loaded_from_session', array($this, 'ensure_subscription_pricing_on_load'), 5, 1);

        // Handle subscription checkout
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_subscription_order_item_meta'), 10, 4);
        
        // Register shortcodes
        add_shortcode('zlaark_subscription_pay_button', array($this, 'render_manual_payment_shortcode'));
    }
    
    /**
     * Handle subscription order completion
     *
     * @param int $order_id
     */
    public function handle_subscription_order_completed($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if ($product && $product->get_type() === 'subscription') {
                $this->create_subscription_from_order($order, $item, $product);
            }
        }
    }
    
    /**
     * Handle subscription payment completion
     *
     * @param int $order_id
     */
    public function handle_subscription_payment_complete($order_id) {
        $this->handle_subscription_order_completed($order_id);
    }
    
    /**
     * Create subscription from order
     *
     * @param WC_Order $order
     * @param WC_Order_Item_Product $item
     * @param WC_Product_Subscription $product
     * @return int|false
     */
    public function create_subscription_from_order($order, $item, $product) {
        // Check if subscription already exists
        $existing = $this->db->get_subscription_by_order($order->get_id());
        if ($existing) {
            return $existing->id;
        }
        
        $trial_start = current_time('mysql');
        $trial_end = null;
        $next_payment = null;
        $status = 'active';
        
        // Calculate trial end date
        if ($product->has_trial()) {
            $status = 'trial';
            $trial_days = $product->get_trial_period_in_days();
            $trial_end = date('Y-m-d H:i:s', strtotime($trial_start . ' +' . $trial_days . ' days'));
            $next_payment = $trial_end;
        } else {
            // No trial, set next payment date
            $billing_days = $product->get_billing_interval_in_days();
            $next_payment = date('Y-m-d H:i:s', strtotime($trial_start . ' +' . $billing_days . ' days'));
        }
        
        $subscription_data = array(
            'user_id' => $order->get_user_id(),
            'order_id' => $order->get_id(),
            'product_id' => $product->get_id(),
            'trial_start_date' => $trial_start,
            'trial_end_date' => $trial_end,
            'next_payment_date' => $next_payment,
            'status' => $status,
            'trial_price' => $product->get_trial_price(),
            'recurring_price' => $product->get_recurring_price(),
            'billing_interval' => $product->get_billing_interval(),
            'max_cycles' => $product->get_max_length(),
            'current_cycle' => 0
        );
        
        $subscription_id = $this->db->create_subscription($subscription_data);
        
        if ($subscription_id) {
            // Record initial payment
            $this->record_payment($subscription_id, array(
                'amount' => $order->get_total(),
                'status' => 'completed',
                'payment_method' => $order->get_payment_method(),
                'razorpay_payment_id' => $order->get_meta('_razorpay_payment_id')
            ));
            
            // Trigger action
            do_action('zlaark_subscription_created', $subscription_id, $order, $product);
            
            return $subscription_id;
        }
        
        return false;
    }
    
    /**
     * Update subscription status
     *
     * @param int $subscription_id
     * @param string $new_status
     * @param string $reason
     * @return bool
     */
    public function update_subscription_status($subscription_id, $new_status, $reason = '') {
        $subscription = $this->db->get_subscription($subscription_id);
        
        if (!$subscription) {
            return false;
        }
        
        $old_status = $subscription->status;
        
        if ($old_status === $new_status) {
            return true;
        }
        
        $update_data = array('status' => $new_status);
        
        // Handle status-specific updates
        switch ($new_status) {
            case 'cancelled':
                $update_data['cancellation_reason'] = $reason;
                break;
                
            case 'active':
                if ($old_status === 'trial') {
                    // Trial ended, set next payment date
                    $product = wc_get_product($subscription->product_id);
                    if ($product) {
                        $billing_days = $product->get_billing_interval_in_days();
                        $update_data['next_payment_date'] = date('Y-m-d H:i:s', strtotime('+' . $billing_days . ' days'));
                    }
                }
                break;
        }
        
        $result = $this->db->update_subscription($subscription_id, $update_data);
        
        if ($result) {
            do_action('zlaark_subscription_status_changed', $subscription_id, $new_status, $old_status);
        }
        
        return $result;
    }
    
    /**
     * Handle subscription status change
     *
     * @param int $subscription_id
     * @param string $new_status
     * @param string $old_status
     */
    public function handle_subscription_status_change($subscription_id, $new_status, $old_status) {
        $subscription = $this->db->get_subscription($subscription_id);
        
        if (!$subscription) {
            return;
        }
        
        // Send email notifications
        $emails = ZlaarkSubscriptionsEmails::instance();
        
        switch ($new_status) {
            case 'active':
                if ($old_status === 'trial') {
                    $emails->send_trial_ended_email($subscription_id);
                }
                break;
                
            case 'cancelled':
                $emails->send_subscription_cancelled_email($subscription_id);
                break;
                
            case 'expired':
                $emails->send_subscription_expired_email($subscription_id);
                break;
        }
    }
    
    /**
     * Process subscription renewal
     *
     * @param int $subscription_id
     * @return bool
     */
    public function process_renewal($subscription_id) {
        $subscription = $this->db->get_subscription($subscription_id);
        
        if (!$subscription || $subscription->status !== 'active') {
            return false;
        }
        
        $product = wc_get_product($subscription->product_id);
        if (!$product) {
            return false;
        }
        
        // Check if max cycles reached
        if ($subscription->max_cycles && $subscription->current_cycle >= $subscription->max_cycles) {
            $this->update_subscription_status($subscription_id, 'expired', 'Maximum cycles reached');
            return false;
        }
        
        // Process payment through Razorpay
        $gateway = new ZlaarkRazorpayGateway();
        $payment_result = $gateway->process_subscription_renewal($subscription);
        
        if ($payment_result['success']) {
            // Update subscription
            $billing_days = $product->get_billing_interval_in_days();
            $next_payment = date('Y-m-d H:i:s', strtotime('+' . $billing_days . ' days'));
            
            $this->db->update_subscription($subscription_id, array(
                'next_payment_date' => $next_payment,
                'current_cycle' => $subscription->current_cycle + 1,
                'last_payment_date' => current_time('mysql'),
                'failed_payment_count' => 0
            ));
            
            // Record payment
            $this->record_payment($subscription_id, array(
                'amount' => $subscription->recurring_price,
                'status' => 'completed',
                'payment_method' => 'razorpay',
                'razorpay_payment_id' => $payment_result['payment_id']
            ));
            
            do_action('zlaark_subscription_renewed', $subscription_id);
            
            return true;
        } else {
            // Payment failed
            do_action('zlaark_subscription_payment_failed', $subscription_id, $payment_result['error']);
            return false;
        }
    }
    
    /**
     * Handle failed payment
     *
     * @param int $subscription_id
     * @param string $error_message
     */
    public function handle_failed_payment($subscription_id, $error_message) {
        $subscription = $this->db->get_subscription($subscription_id);
        
        if (!$subscription) {
            return;
        }
        
        $failed_count = $subscription->failed_payment_count + 1;
        $max_retries = get_option('zlaark_subscriptions_failed_payment_retries', 3);
        
        // Record failed payment
        $this->record_payment($subscription_id, array(
            'amount' => $subscription->recurring_price,
            'status' => 'failed',
            'payment_method' => 'razorpay',
            'failure_reason' => $error_message
        ));
        
        if ($failed_count >= $max_retries) {
            // Max retries reached
            if (get_option('zlaark_subscriptions_auto_cancel_after_retries') === 'yes') {
                $this->update_subscription_status($subscription_id, 'cancelled', 'Payment failed after ' . $max_retries . ' attempts');
            } else {
                $this->update_subscription_status($subscription_id, 'failed', 'Payment failed after ' . $max_retries . ' attempts');
            }
        } else {
            // Schedule retry
            $retry_interval = get_option('zlaark_subscriptions_retry_interval', 2);
            $next_retry = date('Y-m-d H:i:s', strtotime('+' . $retry_interval . ' days'));
            
            $this->db->update_subscription($subscription_id, array(
                'failed_payment_count' => $failed_count,
                'next_payment_date' => $next_retry
            ));
        }
        
        // Send notification
        $emails = ZlaarkSubscriptionsEmails::instance();
        $emails->send_payment_failed_email($subscription_id, $failed_count, $max_retries);
    }
    
    /**
     * Record payment
     *
     * @param int $subscription_id
     * @param array $payment_data
     * @return int|false
     */
    public function record_payment($subscription_id, $payment_data) {
        $payment_data['subscription_id'] = $subscription_id;
        return $this->db->record_payment($payment_data);
    }
    
    /**
     * Validate subscription cart
     *
     * @param bool $passed
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function validate_subscription_cart($passed, $product_id, $quantity) {
        // Bypass validation during AJAX subscription add-to-cart requests
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'zlaark_add_subscription_to_cart') {
            return $passed;
        }
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'subscription') {
            return $passed;
        }
        
        // Check if user already has active subscription for this product
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $existing_subscriptions = $this->db->get_user_subscriptions($user_id, 'active');
            
            foreach ($existing_subscriptions as $subscription) {
                if ($subscription->product_id == $product_id) {
                    wc_add_notice(__('You already have an active subscription for this product.', 'zlaark-subscriptions'), 'error');
                    return false;
                }
            }
        }
        
        // Check if cart already contains subscription products
        foreach (WC()->cart->get_cart() as $cart_item) {
            $cart_product = $cart_item['data'];
            if ($cart_product && $cart_product->get_type() === 'subscription') {
                wc_add_notice(__('You can only purchase one subscription at a time.', 'zlaark-subscriptions'), 'error');
                return false;
            }
        }
        
        return $passed;
    }
    
    /**
     * Add subscription cart item data
     *
     * @param array $cart_item_data
     * @param int $product_id
     * @param int $variation_id
     * @return array
     */
    public function add_subscription_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = wc_get_product($product_id);

        if ($product && $product->get_type() === 'subscription') {
            $cart_item_data['is_subscription'] = true;
            $cart_item_data['subscription_data'] = array(
                'trial_price' => $product->get_trial_price(),
                'recurring_price' => $product->get_recurring_price(),
                'billing_interval' => $product->get_billing_interval(),
                'has_trial' => $product->has_trial()
            );

            // Preserve subscription type from AJAX request for pricing
            if (isset($cart_item_data['subscription_type'])) {
                $cart_item_data['subscription_data']['subscription_type'] = $cart_item_data['subscription_type'];
            } else {
                // Fallback: check POST data for subscription type
                if (isset($_POST['subscription_type'])) {
                    $cart_item_data['subscription_type'] = sanitize_text_field($_POST['subscription_type']);
                    $cart_item_data['subscription_data']['subscription_type'] = $cart_item_data['subscription_type'];
                } else {
                    // Default to regular if not specified
                    $cart_item_data['subscription_type'] = 'regular';
                    $cart_item_data['subscription_data']['subscription_type'] = 'regular';
                }
            }

            // Set the subscription type context on the product instance
            if (method_exists($product, 'set_subscription_type_context')) {
                $product->set_subscription_type_context($cart_item_data['subscription_type']);
            }
        }

        return $cart_item_data;
    }
    
    /**
     * Get subscription cart item from session
     *
     * @param array $session_data
     * @param array $values
     * @param string $key
     * @return array
     */
    public function get_subscription_cart_item_from_session($session_data, $values, $key) {
        if (isset($values['is_subscription'])) {
            $session_data['is_subscription'] = $values['is_subscription'];
            $session_data['subscription_data'] = $values['subscription_data'];

            // Restore subscription type from session
            if (isset($values['subscription_type'])) {
                $session_data['subscription_type'] = $values['subscription_type'];

                // Set context on the product if available
                if (isset($session_data['data']) && method_exists($session_data['data'], 'set_subscription_type_context')) {
                    $session_data['data']->set_subscription_type_context($values['subscription_type']);
                }
            }
        }

        return $session_data;
    }
    
    /**
     * Modify subscription cart item prices based on subscription type
     * This works with the context-aware product pricing
     *
     * @param WC_Cart $cart
     */
    public function modify_subscription_cart_item_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['is_subscription']) && $cart_item['is_subscription']) {
                $subscription_type = isset($cart_item['subscription_type']) ? $cart_item['subscription_type'] : 'regular';
                $subscription_data = isset($cart_item['subscription_data']) ? $cart_item['subscription_data'] : array();

                // Override subscription type from subscription_data if available
                if (isset($subscription_data['subscription_type'])) {
                    $subscription_type = $subscription_data['subscription_type'];
                }

                $product = $cart_item['data'];

                // Set the subscription type context on the product
                if (method_exists($product, 'set_subscription_type_context')) {
                    $product->set_subscription_type_context($subscription_type);

                    // Let the product's get_price() method handle the pricing logic
                    $contextual_price = $product->get_price();

                    // Verify the price is correct and fallback if needed
                    $expected_price = null;
                    if ($subscription_type === 'trial' && isset($subscription_data['trial_price'])) {
                        $expected_price = $subscription_data['trial_price'];
                    } elseif ($subscription_type === 'regular' && isset($subscription_data['recurring_price'])) {
                        $expected_price = $subscription_data['recurring_price'];
                    }

                    if ($expected_price !== null && $contextual_price != $expected_price) {
                        // Force the correct price as fallback
                        $product->set_price($expected_price);
                    }
                } else {
                    // Fallback to direct price setting if context methods not available
                    $new_price = null;
                    if ($subscription_type === 'trial' && isset($subscription_data['trial_price'])) {
                        $new_price = $subscription_data['trial_price'];
                    } elseif ($subscription_type === 'regular' && isset($subscription_data['recurring_price'])) {
                        $new_price = $subscription_data['recurring_price'];
                    }

                    if ($new_price !== null) {
                        $product->set_price($new_price);
                    }
                }
            }
        }
    }

    /**
     * Ensure subscription pricing is applied when cart is loaded from session
     * This handles cases where the cart is restored and pricing context might be lost
     *
     * @param WC_Cart $cart
     */
    public function ensure_subscription_pricing_on_load($cart) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['is_subscription']) && $cart_item['is_subscription']) {
                $subscription_type = isset($cart_item['subscription_type']) ? $cart_item['subscription_type'] : 'regular';
                $product = $cart_item['data'];

                if (method_exists($product, 'set_subscription_type_context')) {
                    $product->set_subscription_type_context($subscription_type);
                }
            }
        }
    }

    /**
     * Add subscription order item meta
     *
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function add_subscription_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['is_subscription']) && $values['is_subscription']) {
            $item->add_meta_data('_is_subscription', 'yes');
            $item->add_meta_data('_subscription_data', $values['subscription_data']);

            // Also store the subscription type for reference
            if (isset($values['subscription_type'])) {
                $item->add_meta_data('_subscription_type', $values['subscription_type']);
            }
        }
    }

    /**
     * Check if manual payment is allowed for subscription
     *
     * @param int $subscription_id
     * @return bool
     */
    public function is_manual_payment_allowed($subscription_id) {
        // Check if there's a pending manual payment order
        $pending_orders = wc_get_orders(array(
            'meta_key' => '_subscription_manual_payment',
            'meta_value' => $subscription_id,
            'status' => array('pending', 'processing'),
            'limit' => 1
        ));

        if (!empty($pending_orders)) {
            wc_add_notice(__('There is already a pending payment for this subscription. Please complete the existing payment first.', 'zlaark-subscriptions'), 'error');
            return false;
        }

        // Check manual payment cooldown (prevent spam - 5 minutes between attempts)
        $last_attempt = get_transient('zlaark_manual_payment_' . $subscription_id);
        if ($last_attempt) {
            wc_add_notice(__('Please wait a few minutes before requesting another payment link.', 'zlaark-subscriptions'), 'error');
            return false;
        }

        // Check user-based rate limiting (max 10 manual payments per hour)
        $user_id = get_current_user_id();
        $user_attempts = get_transient('zlaark_manual_payment_user_' . $user_id);
        if ($user_attempts && $user_attempts >= 10) {
            wc_add_notice(__('Too many payment requests. Please try again later.', 'zlaark-subscriptions'), 'error');
            return false;
        }

        return true;
    }

    /**
     * Create manual payment order for subscription
     *
     * @param int $subscription_id
     * @return string|false Payment URL or false on failure
     */
    public function create_manual_payment_order($subscription_id) {
        $subscription = $this->db->get_subscription($subscription_id);
        
        if (!$subscription || !in_array($subscription->status, array('failed', 'expired'))) {
            wc_add_notice(__('Subscription is not eligible for manual payment.', 'zlaark-subscriptions'), 'error');
            return false;
        }

        // Check if manual payment is allowed
        if (!$this->is_manual_payment_allowed($subscription_id)) {
            return false;
        }

        $product = wc_get_product($subscription->product_id);
        if (!$product) {
            return false;
        }

        // Check if manual payments are enabled
        if (get_option('zlaark_subscriptions_enable_manual_payments', 'yes') !== 'yes') {
            return false;
        }

        try {
            // Create a new order for manual payment
            $order = wc_create_order(array(
                'customer_id' => $subscription->user_id,
                'status' => 'pending'
            ));

            if (!$order) {
                return false;
            }

            // Add product to order
            $order->add_product($product, 1, array(
                'total' => $subscription->recurring_price,
                'subtotal' => $subscription->recurring_price
            ));

            // Add order meta to link it to the subscription
            $order->add_meta_data('_subscription_manual_payment', $subscription_id);
            $order->add_meta_data('_is_subscription_renewal', 'yes');

            // Set order totals
            $order->set_total($subscription->recurring_price);
            $order->calculate_totals();

            // Add order note
            $order->add_order_note(sprintf(
                __('Manual payment order created for subscription #%d', 'zlaark-subscriptions'),
                $subscription_id
            ));

            $order->save();

            // Create Razorpay payment order
            $gateway = new ZlaarkRazorpayGateway();
            $payment_result = $gateway->create_razorpay_order($order);

            if ($payment_result && isset($payment_result['order_id'])) {
                // Store Razorpay order ID
                $order->add_meta_data('_razorpay_order_id', $payment_result['order_id']);
                $order->save();

                // Set cooldown transient (5 minutes)
                set_transient('zlaark_manual_payment_' . $subscription_id, time(), 300);

                // Update user rate limiting
                $user_id = get_current_user_id();
                $current_attempts = get_transient('zlaark_manual_payment_user_' . $user_id) ?: 0;
                set_transient('zlaark_manual_payment_user_' . $user_id, $current_attempts + 1, 3600); // 1 hour

                // Return checkout URL with order
                return add_query_arg(array(
                    'pay_for_order' => 'true',
                    'key' => $order->get_order_key(),
                    'subscription_renewal' => $subscription_id
                ), $order->get_checkout_payment_url());
            } else {
                wc_add_notice(__('Failed to create payment order. Please try again later.', 'zlaark-subscriptions'), 'error');
            }

            return false;

        } catch (Exception $e) {
            error_log('Manual payment order creation failed: ' . $e->getMessage());
            
            // Store error for user display
            wc_add_notice(sprintf(
                __('Unable to process payment request: %s Please try again or contact support.', 'zlaark-subscriptions'),
                $e->getMessage()
            ), 'error');
            
            return false;
        }
    }

    /**
     * Handle successful manual payment
     *
     * @param int $order_id
     * @return bool
     */
    public function handle_manual_payment_success($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }

        $subscription_id = $order->get_meta('_subscription_manual_payment');
        
        if (!$subscription_id) {
            return false;
        }

        $subscription = $this->db->get_subscription($subscription_id);
        
        if (!$subscription) {
            return false;
        }

        // Record successful payment
        $this->record_payment($subscription_id, array(
            'amount' => $order->get_total(),
            'status' => 'completed',
            'payment_method' => 'razorpay',
            'razorpay_payment_id' => $order->get_meta('_razorpay_payment_id'),
            'manual_payment' => true,
            'order_id' => $order_id
        ));

        // Reset failed payment count and reactivate subscription
        $next_payment_date = date('Y-m-d H:i:s', strtotime('+' . $subscription->billing_interval . ' ' . $subscription->billing_period));
        
        $this->db->update_subscription($subscription_id, array(
            'status' => 'active',
            'failed_payment_count' => 0,
            'next_payment_date' => $next_payment_date
        ));

        // Send confirmation email
        $emails = ZlaarkSubscriptionsEmails::instance();
        $emails->send_manual_payment_success_email($subscription_id, $order->get_total());

        // Update subscription status change
        do_action('zlaark_subscription_status_changed', $subscription_id, 'active', $subscription->status);
        do_action('zlaark_subscription_manual_payment_success', $subscription_id, $order_id);

        return true;
    }

    /**
     * Check if completed order is a manual payment
     *
     * @param int $order_id
     */
    public function check_manual_payment_completion($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }

        $subscription_id = $order->get_meta('_subscription_manual_payment');
        
        if ($subscription_id && $order->get_meta('_is_subscription_renewal') === 'yes') {
            $this->handle_manual_payment_success($order_id);
        }
    }

    /**
     * Render manual payment shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_manual_payment_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'subscription_id' => '',
            'text' => __('Pay Now', 'zlaark-subscriptions'),
            'class' => 'woocommerce-button button pay-now',
            'style' => '',
            'show_amount' => 'no',
            'show_for_status' => 'failed,expired'
        ), $atts, 'zlaark_subscription_pay_button');

        // If no subscription ID provided, try to get user's failed subscriptions
        if (empty($atts['subscription_id']) && is_user_logged_in()) {
            return $this->render_user_failed_subscriptions_buttons($atts);
        }

        $subscription_id = intval($atts['subscription_id']);
        if (!$subscription_id) {
            return '<p class="error">' . __('Invalid subscription ID.', 'zlaark-subscriptions') . '</p>';
        }

        // Check if manual payments are enabled
        if (get_option('zlaark_subscriptions_enable_manual_payments', 'yes') !== 'yes') {
            return '';
        }

        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return '<p class="error">' . __('Subscription not found.', 'zlaark-subscriptions') . '</p>';
        }

        // Check if user owns this subscription
        if (is_user_logged_in() && $subscription->user_id != get_current_user_id()) {
            return '<p class="error">' . __('Access denied.', 'zlaark-subscriptions') . '</p>';
        }

        // Check subscription status
        $allowed_statuses = explode(',', $atts['show_for_status']);
        $allowed_statuses = array_map('trim', $allowed_statuses);
        
        if (!in_array($subscription->status, $allowed_statuses)) {
            return '';
        }

        $product = wc_get_product($subscription->product_id);
        if (!$product) {
            return '<p class="error">' . __('Product not found.', 'zlaark-subscriptions') . '</p>';
        }

        // Generate payment URL
        $payment_url = wp_nonce_url(
            add_query_arg(array(
                'subscription_action' => 'pay_now',
                'subscription_id' => $subscription_id
            ), wc_get_account_endpoint_url('subscriptions')),
            'subscription_action_pay_now_' . $subscription_id
        );

        // Build button text
        $button_text = $atts['text'];
        if ($atts['show_amount'] === 'yes') {
            $button_text .= ' - ₹' . number_format($subscription->recurring_price, 2);
        }

        // Build button HTML
        $html = '<div class="zlaark-manual-payment-button">';
        $html .= '<a href="' . esc_url($payment_url) . '" class="' . esc_attr($atts['class']) . '"';
        if (!empty($atts['style'])) {
            $html .= ' style="' . esc_attr($atts['style']) . '"';
        }
        $html .= '>' . esc_html($button_text) . '</a>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render payment buttons for all user's failed subscriptions
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function render_user_failed_subscriptions_buttons($atts) {
        if (!is_user_logged_in()) {
            return '<p class="info">' . __('Please login to view your subscriptions.', 'zlaark-subscriptions') . '</p>';
        }

        $user_id = get_current_user_id();
        $allowed_statuses = explode(',', $atts['show_for_status']);
        $allowed_statuses = array_map('trim', $allowed_statuses);

        // Get user's failed subscriptions
        $subscriptions = $this->db->get_user_subscriptions($user_id);
        $failed_subscriptions = array_filter($subscriptions, function($subscription) use ($allowed_statuses) {
            return in_array($subscription->status, $allowed_statuses);
        });

        if (empty($failed_subscriptions)) {
            return '<p class="info">' . __('No subscriptions requiring manual payment.', 'zlaark-subscriptions') . '</p>';
        }

        $html = '<div class="zlaark-failed-subscriptions">';
        $html .= '<h4>' . __('Subscriptions Requiring Payment', 'zlaark-subscriptions') . '</h4>';

        foreach ($failed_subscriptions as $subscription) {
            $product = wc_get_product($subscription->product_id);
            if (!$product) {
                continue;
            }

            $payment_url = wp_nonce_url(
                add_query_arg(array(
                    'subscription_action' => 'pay_now',
                    'subscription_id' => $subscription->id
                ), wc_get_account_endpoint_url('subscriptions')),
                'subscription_action_pay_now_' . $subscription->id
            );

            $button_text = sprintf(__('Pay for %s', 'zlaark-subscriptions'), $product->get_name());
            if ($atts['show_amount'] === 'yes') {
                $button_text .= ' - ₹' . number_format($subscription->recurring_price, 2);
            }

            $html .= '<div class="subscription-payment-item" style="margin: 10px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
            $html .= '<p><strong>' . esc_html($product->get_name()) . '</strong> - ' . ucfirst($subscription->status) . '</p>';
            $html .= '<a href="' . esc_url($payment_url) . '" class="' . esc_attr($atts['class']) . '"';
            if (!empty($atts['style'])) {
                $html .= ' style="' . esc_attr($atts['style']) . '"';
            }
            $html .= '>' . esc_html($button_text) . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
