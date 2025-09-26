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
        
        // Prevent multiple subscriptions for same product
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_subscription_cart'), 10, 3);
        
        // Modify cart for subscription products
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_subscription_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_subscription_cart_item_from_session'), 10, 3);

        // CRITICAL: Modify cart item price based on subscription type (trial vs regular)
        add_action('woocommerce_before_calculate_totals', array($this, 'modify_subscription_cart_item_prices'), 10, 1);

        // Handle subscription checkout
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_subscription_order_item_meta'), 10, 4);
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

            // PRESERVE SUBSCRIPTION TYPE FROM AJAX REQUEST
            // This is crucial for pricing - trial vs regular subscription
            if (isset($cart_item_data['subscription_type'])) {
                $cart_item_data['subscription_data']['subscription_type'] = $cart_item_data['subscription_type'];
                error_log('Zlaark: Preserving subscription_type in cart data: ' . $cart_item_data['subscription_type']);
            } else {
                // Fallback: check POST data for subscription type
                if (isset($_POST['subscription_type'])) {
                    $cart_item_data['subscription_type'] = sanitize_text_field($_POST['subscription_type']);
                    $cart_item_data['subscription_data']['subscription_type'] = $cart_item_data['subscription_type'];
                    error_log('Zlaark: Setting subscription_type from POST data: ' . $cart_item_data['subscription_type']);
                } else {
                    // Default to regular if not specified
                    $cart_item_data['subscription_type'] = 'regular';
                    $cart_item_data['subscription_data']['subscription_type'] = 'regular';
                    error_log('Zlaark: Defaulting subscription_type to regular');
                }
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
        }
        
        return $session_data;
    }
    
    /**
     * CRITICAL: Modify subscription cart item prices based on subscription type
     * This is where trial vs regular pricing is applied
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

                error_log('=== CART PRICE MODIFICATION DEBUG ===');
                error_log('Cart item key: ' . $cart_item_key);
                error_log('Subscription type: ' . $subscription_type);
                error_log('Available subscription data: ' . print_r($subscription_data, true));

                $product = $cart_item['data'];
                $new_price = null;

                if ($subscription_type === 'trial' && isset($subscription_data['trial_price'])) {
                    $new_price = $subscription_data['trial_price'];
                    error_log('Setting TRIAL price: ' . $new_price);
                } elseif ($subscription_type === 'regular' && isset($subscription_data['recurring_price'])) {
                    $new_price = $subscription_data['recurring_price'];
                    error_log('Setting REGULAR price: ' . $new_price);
                } else {
                    error_log('WARNING: Could not determine price for subscription_type: ' . $subscription_type);
                }

                if ($new_price !== null) {
                    $product->set_price($new_price);
                    error_log('Price set to: ' . $new_price . ' for subscription_type: ' . $subscription_type);
                } else {
                    error_log('ERROR: No price could be determined!');
                }
                error_log('=== END CART PRICE MODIFICATION DEBUG ===');
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
}
