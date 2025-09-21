<?php
/**
 * Webhook handler for Razorpay events
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Webhooks class
 */
class ZlaarkSubscriptionsWebhooks {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsWebhooks
     */
    private static $instance = null;
    
    /**
     * Database instance
     *
     * @var ZlaarkSubscriptionsDatabase
     */
    private $db;
    
    /**
     * Manager instance
     *
     * @var ZlaarkSubscriptionsManager
     */
    private $manager;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsWebhooks
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
        $this->manager = ZlaarkSubscriptionsManager::instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle webhook endpoint
        add_action('woocommerce_api_zlaark_razorpay_webhook', array($this, 'handle_webhook'));
        add_action('init', array($this, 'add_webhook_endpoint'));
        
        // Admin webhook management
        add_action('wp_ajax_zlaark_test_webhook', array($this, 'test_webhook'));
        add_action('wp_ajax_zlaark_regenerate_webhook_secret', array($this, 'regenerate_webhook_secret'));
    }
    
    /**
     * Add webhook endpoint
     */
    public function add_webhook_endpoint() {
        add_rewrite_rule(
            '^zlaark-subscriptions/webhook/?$',
            'index.php?wc-api=zlaark_razorpay_webhook',
            'top'
        );
    }
    
    /**
     * Handle incoming webhook
     */
    public function handle_webhook() {
        // Get raw POST data
        $raw_body = file_get_contents('php://input');
        
        if (empty($raw_body)) {
            $this->send_response(400, 'Empty request body');
            return;
        }
        
        // Parse JSON
        $data = json_decode($raw_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->send_response(400, 'Invalid JSON');
            return;
        }
        
        // Verify webhook signature
        $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
        
        if (!$this->verify_signature($raw_body, $signature)) {
            $this->send_response(401, 'Invalid signature');
            return;
        }
        
        // Log webhook
        $log_id = $this->db->log_webhook(array(
            'event_type' => $data['event'] ?? 'unknown',
            'razorpay_event_id' => $data['event_id'] ?? '',
            'payload' => $raw_body,
            'status' => 'processing'
        ));
        
        try {
            // Process webhook event
            $this->process_webhook_event($data);
            
            // Mark as processed
            if ($log_id) {
                $this->db->update_webhook_log($log_id, array('status' => 'processed'));
            }
            
            $this->send_response(200, 'OK');
            
        } catch (Exception $e) {
            // Mark as failed
            if ($log_id) {
                $this->db->update_webhook_log($log_id, array(
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ));
            }
            
            $this->log_error('Webhook processing failed: ' . $e->getMessage());
            $this->send_response(500, 'Processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $body
     * @param string $signature
     * @return bool
     */
    private function verify_signature($body, $signature) {
        $webhook_secret = get_option('zlaark_subscriptions_webhook_secret');
        
        if (empty($webhook_secret) || empty($signature)) {
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
        $event = $data['event'] ?? '';
        $payload = $data['payload'] ?? array();
        
        $this->log_info('Processing webhook event: ' . $event);
        
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
                
            case 'subscription.completed':
                $this->handle_subscription_completed($payload);
                break;
                
            case 'subscription.authenticated':
                $this->handle_subscription_authenticated($payload);
                break;
                
            case 'subscription.activated':
                $this->handle_subscription_activated($payload);
                break;
                
            default:
                $this->log_info('Unhandled webhook event: ' . $event);
        }
    }
    
    /**
     * Handle payment captured event
     *
     * @param array $payload
     */
    private function handle_payment_captured($payload) {
        $payment = $payload['payment']['entity'] ?? array();
        $order_id = $payment['notes']['woocommerce_order_id'] ?? null;
        
        if (!$order_id) {
            throw new Exception('No WooCommerce order ID found in payment notes');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Order not found: ' . $order_id);
        }
        
        if ($order->get_status() === 'pending') {
            $order->payment_complete($payment['id']);
            $order->add_order_note(sprintf(
                __('Payment captured via Razorpay webhook. Payment ID: %s', 'zlaark-subscriptions'),
                $payment['id']
            ));
            
            $this->log_info('Payment captured for order #' . $order_id);
        }
    }
    
    /**
     * Handle payment failed event
     *
     * @param array $payload
     */
    private function handle_payment_failed($payload) {
        $payment = $payload['payment']['entity'] ?? array();
        $order_id = $payment['notes']['woocommerce_order_id'] ?? null;
        
        if (!$order_id) {
            throw new Exception('No WooCommerce order ID found in payment notes');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Order not found: ' . $order_id);
        }
        
        $error_description = $payment['error_description'] ?? 'Payment failed';
        
        $order->update_status('failed', sprintf(
            __('Payment failed via Razorpay webhook. Error: %s', 'zlaark-subscriptions'),
            $error_description
        ));
        
        $this->log_info('Payment failed for order #' . $order_id . ': ' . $error_description);
    }
    
    /**
     * Handle subscription charged event
     *
     * @param array $payload
     */
    private function handle_subscription_charged($payload) {
        $subscription_entity = $payload['subscription']['entity'] ?? array();
        $payment = $payload['payment']['entity'] ?? array();
        
        $razorpay_subscription_id = $subscription_entity['id'] ?? '';
        
        if (!$razorpay_subscription_id) {
            throw new Exception('No Razorpay subscription ID found');
        }
        
        $subscription = $this->db->get_subscription_by_razorpay_id($razorpay_subscription_id);
        
        if (!$subscription) {
            throw new Exception('Subscription not found: ' . $razorpay_subscription_id);
        }
        
        // Record successful payment
        $this->manager->record_payment($subscription->id, array(
            'amount' => $payment['amount'] / 100, // Convert from paise
            'status' => 'completed',
            'payment_method' => 'razorpay',
            'razorpay_payment_id' => $payment['id']
        ));
        
        // Update subscription
        $product = wc_get_product($subscription->product_id);
        if ($product) {
            $billing_days = $product->get_billing_interval_in_days();
            $next_payment = date('Y-m-d H:i:s', strtotime('+' . $billing_days . ' days'));
            
            $this->db->update_subscription($subscription->id, array(
                'next_payment_date' => $next_payment,
                'current_cycle' => $subscription->current_cycle + 1,
                'last_payment_date' => current_time('mysql'),
                'failed_payment_count' => 0
            ));
        }
        
        // Trigger renewal action
        do_action('zlaark_subscription_renewed', $subscription->id);
        
        $this->log_info('Subscription charged: #' . $subscription->id);
    }
    
    /**
     * Handle subscription halted event
     *
     * @param array $payload
     */
    private function handle_subscription_halted($payload) {
        $subscription_entity = $payload['subscription']['entity'] ?? array();
        $razorpay_subscription_id = $subscription_entity['id'] ?? '';
        
        if (!$razorpay_subscription_id) {
            throw new Exception('No Razorpay subscription ID found');
        }
        
        $subscription = $this->db->get_subscription_by_razorpay_id($razorpay_subscription_id);
        
        if (!$subscription) {
            throw new Exception('Subscription not found: ' . $razorpay_subscription_id);
        }
        
        // Handle as failed payment
        do_action('zlaark_subscription_payment_failed', $subscription->id, 'Subscription halted by Razorpay');
        
        $this->log_info('Subscription halted: #' . $subscription->id);
    }
    
    /**
     * Handle subscription cancelled event
     *
     * @param array $payload
     */
    private function handle_subscription_cancelled($payload) {
        $subscription_entity = $payload['subscription']['entity'] ?? array();
        $razorpay_subscription_id = $subscription_entity['id'] ?? '';
        
        if (!$razorpay_subscription_id) {
            throw new Exception('No Razorpay subscription ID found');
        }
        
        $subscription = $this->db->get_subscription_by_razorpay_id($razorpay_subscription_id);
        
        if (!$subscription) {
            throw new Exception('Subscription not found: ' . $razorpay_subscription_id);
        }
        
        // Update subscription status
        $this->manager->update_subscription_status($subscription->id, 'cancelled', 'Cancelled via Razorpay webhook');
        
        $this->log_info('Subscription cancelled: #' . $subscription->id);
    }
    
    /**
     * Handle subscription completed event
     *
     * @param array $payload
     */
    private function handle_subscription_completed($payload) {
        $subscription_entity = $payload['subscription']['entity'] ?? array();
        $razorpay_subscription_id = $subscription_entity['id'] ?? '';
        
        if (!$razorpay_subscription_id) {
            throw new Exception('No Razorpay subscription ID found');
        }
        
        $subscription = $this->db->get_subscription_by_razorpay_id($razorpay_subscription_id);
        
        if (!$subscription) {
            throw new Exception('Subscription not found: ' . $razorpay_subscription_id);
        }
        
        // Update subscription status
        $this->manager->update_subscription_status($subscription->id, 'expired', 'Completed via Razorpay webhook');
        
        $this->log_info('Subscription completed: #' . $subscription->id);
    }
    
    /**
     * Handle subscription authenticated event
     *
     * @param array $payload
     */
    private function handle_subscription_authenticated($payload) {
        $subscription_entity = $payload['subscription']['entity'] ?? array();
        $razorpay_subscription_id = $subscription_entity['id'] ?? '';
        
        if (!$razorpay_subscription_id) {
            throw new Exception('No Razorpay subscription ID found');
        }
        
        $subscription = $this->db->get_subscription_by_razorpay_id($razorpay_subscription_id);
        
        if ($subscription) {
            $this->log_info('Subscription authenticated: #' . $subscription->id);
        }
    }
    
    /**
     * Handle subscription activated event
     *
     * @param array $payload
     */
    private function handle_subscription_activated($payload) {
        $subscription_entity = $payload['subscription']['entity'] ?? array();
        $razorpay_subscription_id = $subscription_entity['id'] ?? '';
        
        if (!$razorpay_subscription_id) {
            throw new Exception('No Razorpay subscription ID found');
        }
        
        $subscription = $this->db->get_subscription_by_razorpay_id($razorpay_subscription_id);
        
        if ($subscription && $subscription->status !== 'active') {
            $this->manager->update_subscription_status($subscription->id, 'active', 'Activated via Razorpay webhook');
            $this->log_info('Subscription activated: #' . $subscription->id);
        }
    }
    
    /**
     * Send HTTP response
     *
     * @param int $code
     * @param string $message
     */
    private function send_response($code, $message) {
        status_header($code);
        echo $message;
        exit;
    }
    
    /**
     * Test webhook endpoint
     */
    public function test_webhook() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (!wp_verify_nonce($_REQUEST['nonce'], 'zlaark_subscriptions_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $webhook_url = home_url('/zlaark-subscriptions/webhook/');
        
        // Test webhook endpoint accessibility
        $response = wp_remote_get($webhook_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Zlaark-Subscriptions-Test/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Webhook endpoint not accessible: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 400) {
            wp_send_json_success('Webhook endpoint is accessible and responding correctly');
        } else {
            wp_send_json_error('Webhook endpoint returned unexpected response code: ' . $response_code);
        }
    }
    
    /**
     * Regenerate webhook secret
     */
    public function regenerate_webhook_secret() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (!wp_verify_nonce($_REQUEST['nonce'], 'zlaark_subscriptions_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $new_secret = wp_generate_password(32, false);
        update_option('zlaark_subscriptions_webhook_secret', $new_secret);
        
        wp_send_json_success(array(
            'secret' => $new_secret,
            'message' => 'Webhook secret regenerated successfully'
        ));
    }
    
    /**
     * Get webhook URL
     *
     * @return string
     */
    public function get_webhook_url() {
        return home_url('/zlaark-subscriptions/webhook/');
    }
    
    /**
     * Log info message
     *
     * @param string $message
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Zlaark Subscriptions Webhook] ' . $message);
        }
    }
    
    /**
     * Log error message
     *
     * @param string $message
     */
    private function log_error($message) {
        error_log('[Zlaark Subscriptions Webhook ERROR] ' . $message);
    }
}
