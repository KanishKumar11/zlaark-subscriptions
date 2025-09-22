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
    }
    
    /**
     * Check if user is eligible for trial
     *
     * @param int $user_id
     * @param int $product_id
     * @return array
     */
    public function check_trial_eligibility($user_id, $product_id) {
        $result = array(
            'eligible' => false,
            'reason' => '',
            'message' => ''
        );
        
        // Check if user is logged in
        if (!$user_id || $user_id <= 0) {
            $result['reason'] = 'not_logged_in';
            $result['message'] = __('You must be logged in to start a trial.', 'zlaark-subscriptions');
            return $result;
        }
        
        // Check if product has trial
        $product = wc_get_product($product_id);
        if (!$product || $product->get_type() !== 'subscription') {
            $result['reason'] = 'not_subscription';
            $result['message'] = __('This product is not a subscription.', 'zlaark-subscriptions');
            return $result;
        }
        
        if (!method_exists($product, 'has_trial') || !$product->has_trial()) {
            $result['reason'] = 'no_trial_available';
            $result['message'] = __('This subscription does not offer a trial period.', 'zlaark-subscriptions');
            return $result;
        }
        
        // Check if user has already used trial for this product
        if ($this->db->has_user_used_trial($user_id, $product_id)) {
            $result['reason'] = 'trial_already_used';
            $result['message'] = __('You have already used the trial for this subscription.', 'zlaark-subscriptions');
            return $result;
        }
        
        // Check if user has active subscription for this product
        if ($this->has_active_subscription($user_id, $product_id)) {
            $result['reason'] = 'already_subscribed';
            $result['message'] = __('You already have an active subscription for this product.', 'zlaark-subscriptions');
            return $result;
        }
        
        $result['eligible'] = true;
        $result['message'] = __('You are eligible for the trial period.', 'zlaark-subscriptions');
        
        return $result;
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
            $trial_eligibility = $this->check_trial_eligibility($user_id, $product_id);
            
            if (!$trial_eligibility['eligible']) {
                wc_add_notice($trial_eligibility['message'], 'error');
                WC()->cart->remove_cart_item($cart_item_key);
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
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['subscription_type']) && $cart_item['subscription_type'] === 'trial') {
                $product_id = $cart_item['product_id'];
                $user_id = get_current_user_id();
                
                $trial_eligibility = $this->check_trial_eligibility($user_id, $product_id);
                
                if (!$trial_eligibility['eligible']) {
                    wc_add_notice($trial_eligibility['message'], 'error');
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
                    
                    // Record trial usage
                    $this->db->record_trial_usage($user_id, $product_id);
                    
                    // Add order note
                    $order->add_order_note(__('Trial subscription started for user.', 'zlaark-subscriptions'));
                }
            }
        }
    }
}
