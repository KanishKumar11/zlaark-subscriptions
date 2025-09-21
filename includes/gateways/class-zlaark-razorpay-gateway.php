<?php
/**
 * Razorpay Payment Gateway for Subscriptions
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Razorpay Gateway Class
 */
class ZlaarkRazorpayGateway extends WC_Payment_Gateway {
    
    /**
     * Razorpay API instance
     *
     * @var object
     */
    private $razorpay_api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'zlaark_razorpay';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Razorpay (Subscriptions)', 'zlaark-subscriptions');
        $this->method_description = __('Accept payments via Razorpay with subscription support.', 'zlaark-subscriptions');
        $this->supports = array(
            'products',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'multiple_subscriptions',
        );
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->key_id = $this->testmode ? $this->get_option('test_key_id') : $this->get_option('live_key_id');
        $this->key_secret = $this->testmode ? $this->get_option('test_key_secret') : $this->get_option('live_key_secret');
        
        // Initialize Razorpay API
        $this->init_razorpay_api();
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('woocommerce_api_' . $this->id, array($this, 'webhook'));
    }
    
    /**
     * Initialize Razorpay API
     */
    private function init_razorpay_api() {
        if (!class_exists('Razorpay\Api\Api')) {
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'vendor/razorpay/razorpay/Razorpay.php';
        }
        
        if (!empty($this->key_id) && !empty($this->key_secret)) {
            $this->razorpay_api = new Razorpay\Api\Api($this->key_id, $this->key_secret);
        }
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'zlaark-subscriptions'),
                'type'    => 'checkbox',
                'label'   => __('Enable Razorpay Payment', 'zlaark-subscriptions'),
                'default' => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'zlaark-subscriptions'),
                'type'        => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'zlaark-subscriptions'),
                'default'     => __('Razorpay', 'zlaark-subscriptions'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'zlaark-subscriptions'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'zlaark-subscriptions'),
                'default'     => __('Pay securely using your credit card, debit card, net banking, UPI or wallet via Razorpay.', 'zlaark-subscriptions'),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'   => __('Test mode', 'zlaark-subscriptions'),
                'label'   => __('Enable Test Mode', 'zlaark-subscriptions'),
                'type'    => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'zlaark-subscriptions'),
                'default' => 'yes',
                'desc_tip'    => true,
            ),
            'test_key_id' => array(
                'title'       => __('Test Key ID', 'zlaark-subscriptions'),
                'type'        => 'text',
                'description' => __('Get your API keys from your Razorpay account.', 'zlaark-subscriptions'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_key_secret' => array(
                'title'       => __('Test Key Secret', 'zlaark-subscriptions'),
                'type'        => 'password',
                'description' => __('Get your API keys from your Razorpay account.', 'zlaark-subscriptions'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'live_key_id' => array(
                'title'       => __('Live Key ID', 'zlaark-subscriptions'),
                'type'        => 'text',
                'description' => __('Get your API keys from your Razorpay account.', 'zlaark-subscriptions'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'live_key_secret' => array(
                'title'       => __('Live Key Secret', 'zlaark-subscriptions'),
                'type'        => 'password',
                'description' => __('Get your API keys from your Razorpay account.', 'zlaark-subscriptions'),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }
    
    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        
        // Check if cart contains subscription products
        $has_subscription = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['is_subscription']) && $cart_item['is_subscription']) {
                $has_subscription = true;
                break;
            }
        }
        
        if ($has_subscription) {
            echo '<div class="razorpay-subscription-notice">';
            echo '<p>' . __('This order contains subscription products. Your payment method will be saved for future recurring payments.', 'zlaark-subscriptions') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Load payment scripts
     */
    public function payment_scripts() {
        if (!is_admin() && !is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }
        
        if ('no' === $this->enabled) {
            return;
        }
        
        if (empty($this->key_id)) {
            return;
        }
        
        wp_enqueue_script('razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', array(), '1.0.0', true);
        wp_enqueue_script('zlaark-razorpay', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/razorpay-checkout.js', array('jquery', 'razorpay-checkout'), ZLAARK_SUBSCRIPTIONS_VERSION, true);
        
        wp_localize_script('zlaark-razorpay', 'zlaark_razorpay_params', array(
            'key_id' => $this->key_id,
            'currency' => get_woocommerce_currency(),
            'testmode' => $this->testmode,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zlaark_razorpay_nonce'),
        ));
    }
    
    /**
     * Process the payment
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'failure',
                'messages' => __('Order not found.', 'zlaark-subscriptions')
            );
        }
        
        try {
            // Check if order contains subscription products
            $has_subscription = false;
            foreach ($order->get_items() as $item) {
                if ($item->get_meta('_is_subscription') === 'yes') {
                    $has_subscription = true;
                    break;
                }
            }
            
            if ($has_subscription) {
                return $this->process_subscription_payment($order);
            } else {
                return $this->process_regular_payment($order);
            }
            
        } catch (Exception $e) {
            wc_add_notice(__('Payment error: ', 'zlaark-subscriptions') . $e->getMessage(), 'error');
            return array(
                'result' => 'failure',
                'messages' => $e->getMessage()
            );
        }
    }
    
    /**
     * Process subscription payment
     *
     * @param WC_Order $order
     * @return array
     */
    private function process_subscription_payment($order) {
        if (!$this->razorpay_api) {
            throw new Exception(__('Razorpay API not initialized.', 'zlaark-subscriptions'));
        }
        
        // Create Razorpay order
        $razorpay_order_data = array(
            'amount' => $order->get_total() * 100, // Amount in paise
            'currency' => $order->get_currency(),
            'receipt' => $order->get_order_number(),
            'notes' => array(
                'woocommerce_order_id' => $order->get_id(),
                'subscription_order' => 'yes'
            )
        );
        
        $razorpay_order = $this->razorpay_api->order->create($razorpay_order_data);
        
        // Store Razorpay order ID
        $order->update_meta_data('_razorpay_order_id', $razorpay_order['id']);
        $order->save();
        
        // Return success with redirect to payment page
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }
    
    /**
     * Process regular payment
     *
     * @param WC_Order $order
     * @return array
     */
    private function process_regular_payment($order) {
        // Similar to subscription payment but without subscription setup
        return $this->process_subscription_payment($order);
    }
    
    /**
     * Process subscription renewal
     *
     * @param object $subscription
     * @return array
     */
    public function process_subscription_renewal($subscription) {
        if (!$this->razorpay_api) {
            return array(
                'success' => false,
                'error' => __('Razorpay API not initialized.', 'zlaark-subscriptions')
            );
        }
        
        try {
            // Create payment using saved customer and subscription
            $payment_data = array(
                'amount' => $subscription->recurring_price * 100,
                'currency' => 'INR',
                'customer_id' => $subscription->razorpay_customer_id,
                'description' => sprintf(__('Subscription renewal for order #%d', 'zlaark-subscriptions'), $subscription->order_id)
            );
            
            $payment = $this->razorpay_api->payment->create($payment_data);
            
            if ($payment['status'] === 'captured') {
                return array(
                    'success' => true,
                    'payment_id' => $payment['id']
                );
            } else {
                return array(
                    'success' => false,
                    'error' => __('Payment not captured.', 'zlaark-subscriptions')
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Handle webhook
     */
    public function webhook() {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (!$data) {
            status_header(400);
            exit('Invalid JSON');
        }
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($body, $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '')) {
            status_header(401);
            exit('Unauthorized');
        }
        
        // Log webhook
        $db = ZlaarkSubscriptionsDatabase::instance();
        $log_id = $db->log_webhook(array(
            'event_type' => $data['event'],
            'razorpay_event_id' => $data['event_id'] ?? '',
            'payload' => $body
        ));
        
        try {
            $this->process_webhook_event($data);
            
            // Mark as processed
            $db->update_webhook_log($log_id, array('status' => 'processed'));
            
            status_header(200);
            exit('OK');
            
        } catch (Exception $e) {
            // Mark as failed
            $db->update_webhook_log($log_id, array(
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ));
            
            status_header(500);
            exit('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $body
     * @param string $signature
     * @return bool
     */
    private function verify_webhook_signature($body, $signature) {
        $webhook_secret = get_option('zlaark_subscriptions_webhook_secret');
        
        if (empty($webhook_secret)) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $body, $webhook_secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Process webhook event
     *
     * @param array $data
     */
    private function process_webhook_event($data) {
        $event = $data['event'];
        $payload = $data['payload'];
        
        switch ($event) {
            case 'payment.captured':
                $this->handle_payment_captured($payload);
                break;
                
            case 'payment.failed':
                $this->handle_payment_failed($payload);
                break;
                
            case 'subscription.charged':
                $this->handle_subscription_charged($payload);
                break;
                
            case 'subscription.halted':
                $this->handle_subscription_halted($payload);
                break;
                
            case 'subscription.cancelled':
                $this->handle_subscription_cancelled($payload);
                break;
        }
    }
    
    /**
     * Handle payment captured webhook
     *
     * @param array $payload
     */
    private function handle_payment_captured($payload) {
        $payment = $payload['payment']['entity'];
        $order_id = $payment['notes']['woocommerce_order_id'] ?? null;
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order && $order->get_status() === 'pending') {
                $order->payment_complete($payment['id']);
                $order->add_order_note(sprintf(__('Payment captured via Razorpay. Payment ID: %s', 'zlaark-subscriptions'), $payment['id']));
            }
        }
    }
    
    /**
     * Handle payment failed webhook
     *
     * @param array $payload
     */
    private function handle_payment_failed($payload) {
        $payment = $payload['payment']['entity'];
        $order_id = $payment['notes']['woocommerce_order_id'] ?? null;
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('failed', sprintf(__('Payment failed via Razorpay. Error: %s', 'zlaark-subscriptions'), $payment['error_description'] ?? 'Unknown error'));
            }
        }
    }
    
    /**
     * Handle subscription charged webhook
     *
     * @param array $payload
     */
    private function handle_subscription_charged($payload) {
        // Handle successful subscription renewal
        $subscription_entity = $payload['subscription']['entity'];
        $payment = $payload['payment']['entity'];
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription_by_razorpay_id($subscription_entity['id']);
        
        if ($subscription) {
            $manager = ZlaarkSubscriptionsManager::instance();
            $manager->record_payment($subscription->id, array(
                'amount' => $payment['amount'] / 100,
                'status' => 'completed',
                'payment_method' => 'razorpay',
                'razorpay_payment_id' => $payment['id']
            ));
        }
    }
    
    /**
     * Handle subscription halted webhook
     *
     * @param array $payload
     */
    private function handle_subscription_halted($payload) {
        $subscription_entity = $payload['subscription']['entity'];
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription_by_razorpay_id($subscription_entity['id']);
        
        if ($subscription) {
            $manager = ZlaarkSubscriptionsManager::instance();
            $manager->handle_failed_payment($subscription->id, 'Subscription halted by Razorpay');
        }
    }
    
    /**
     * Handle subscription cancelled webhook
     *
     * @param array $payload
     */
    private function handle_subscription_cancelled($payload) {
        $subscription_entity = $payload['subscription']['entity'];
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription_by_razorpay_id($subscription_entity['id']);
        
        if ($subscription) {
            $manager = ZlaarkSubscriptionsManager::instance();
            $manager->update_subscription_status($subscription->id, 'cancelled', 'Cancelled via Razorpay');
        }
    }
}
