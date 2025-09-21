<?php
/**
 * Email notifications for subscriptions
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Emails class
 */
class ZlaarkSubscriptionsEmails {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsEmails
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
     * @return ZlaarkSubscriptionsEmails
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
        // Add email templates to WooCommerce
        add_filter('woocommerce_email_classes', array($this, 'add_email_classes'));
        
        // Email actions
        add_action('zlaark_subscription_created', array($this, 'send_subscription_created_email'));
        add_action('zlaark_subscription_status_changed', array($this, 'handle_status_change_email'), 10, 3);
        add_action('zlaark_subscription_renewed', array($this, 'send_renewal_success_email'));
        add_action('zlaark_subscription_payment_failed', array($this, 'send_payment_failed_email'), 10, 2);
    }
    
    /**
     * Add email classes to WooCommerce
     *
     * @param array $email_classes
     * @return array
     */
    public function add_email_classes($email_classes) {
        // We'll add custom email classes here if needed
        return $email_classes;
    }
    
    /**
     * Send subscription created email
     *
     * @param int $subscription_id
     */
    public function send_subscription_created_email($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $subject = sprintf(__('Your subscription to %s has been activated', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('subscription_created', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Handle status change emails
     *
     * @param int $subscription_id
     * @param string $new_status
     * @param string $old_status
     */
    public function handle_status_change_email($subscription_id, $new_status, $old_status) {
        switch ($new_status) {
            case 'cancelled':
                $this->send_subscription_cancelled_email($subscription_id);
                break;
                
            case 'expired':
                $this->send_subscription_expired_email($subscription_id);
                break;
                
            case 'active':
                if ($old_status === 'trial') {
                    $this->send_trial_ended_email($subscription_id);
                }
                break;
        }
    }
    
    /**
     * Send trial ending reminder
     *
     * @param int $subscription_id
     */
    public function send_trial_ending_reminder($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription || $subscription->status !== 'trial') {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $trial_end_date = new DateTime($subscription->trial_end_date);
        $days_remaining = $trial_end_date->diff(new DateTime())->days;
        
        $subject = sprintf(__('Your trial for %s ends in %d days', 'zlaark-subscriptions'), $product->get_name(), $days_remaining);
        
        $message = $this->get_email_template('trial_ending_reminder', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product,
            'days_remaining' => $days_remaining,
            'trial_end_date' => $trial_end_date->format('F j, Y')
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Send trial ended email
     *
     * @param int $subscription_id
     */
    public function send_trial_ended_email($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $subject = sprintf(__('Your trial for %s has ended', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('trial_ended', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Send payment due reminder
     *
     * @param int $subscription_id
     */
    public function send_payment_due_reminder($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription || $subscription->status !== 'active') {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $payment_date = new DateTime($subscription->next_payment_date);
        
        $subject = sprintf(__('Upcoming payment for your %s subscription', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('payment_due_reminder', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product,
            'payment_date' => $payment_date->format('F j, Y'),
            'amount' => $subscription->recurring_price
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Send renewal success email
     *
     * @param int $subscription_id
     */
    public function send_renewal_success_email($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $next_payment_date = new DateTime($subscription->next_payment_date);
        
        $subject = sprintf(__('Payment successful for your %s subscription', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('renewal_success', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product,
            'amount' => $subscription->recurring_price,
            'next_payment_date' => $next_payment_date->format('F j, Y')
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Send payment failed email
     *
     * @param int $subscription_id
     * @param int $failed_count
     * @param int $max_retries
     */
    public function send_payment_failed_email($subscription_id, $failed_count = 1, $max_retries = 3) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $remaining_attempts = $max_retries - $failed_count;
        
        $subject = sprintf(__('Payment failed for your %s subscription', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('payment_failed', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product,
            'amount' => $subscription->recurring_price,
            'failed_count' => $failed_count,
            'remaining_attempts' => $remaining_attempts,
            'will_cancel' => $remaining_attempts <= 0
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Send subscription cancelled email
     *
     * @param int $subscription_id
     */
    public function send_subscription_cancelled_email($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $subject = sprintf(__('Your %s subscription has been cancelled', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('subscription_cancelled', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product,
            'cancellation_reason' => $subscription->cancellation_reason
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Send subscription expired email
     *
     * @param int $subscription_id
     */
    public function send_subscription_expired_email($subscription_id) {
        if (get_option('zlaark_subscriptions_email_notifications') !== 'yes') {
            return;
        }
        
        $subscription = $this->db->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $user = get_user_by('id', $subscription->user_id);
        $product = wc_get_product($subscription->product_id);
        
        if (!$user || !$product) {
            return;
        }
        
        $subject = sprintf(__('Your %s subscription has expired', 'zlaark-subscriptions'), $product->get_name());
        
        $message = $this->get_email_template('subscription_expired', array(
            'user' => $user,
            'subscription' => $subscription,
            'product' => $product
        ));
        
        $this->send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Get email template
     *
     * @param string $template
     * @param array $variables
     * @return string
     */
    private function get_email_template($template, $variables = array()) {
        // Extract variables for use in template
        extract($variables);
        
        // Start output buffering
        ob_start();
        
        // Try to load custom template first
        $custom_template = get_stylesheet_directory() . '/zlaark-subscriptions/emails/' . $template . '.php';
        $plugin_template = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
        
        if (file_exists($custom_template)) {
            include $custom_template;
        } elseif (file_exists($plugin_template)) {
            include $plugin_template;
        } else {
            // Fallback to default template
            include ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/emails/default.php';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Send email
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param array $headers
     * @return bool
     */
    private function send_email($to, $subject, $message, $headers = array()) {
        // Default headers
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('woocommerce_email_from_name', get_bloginfo('name')) . ' <' . get_option('woocommerce_email_from_address', get_option('admin_email')) . '>'
        );
        
        $headers = array_merge($default_headers, $headers);
        
        // Apply filters
        $to = apply_filters('zlaark_subscriptions_email_recipient', $to, $subject, $message);
        $subject = apply_filters('zlaark_subscriptions_email_subject', $subject, $to, $message);
        $message = apply_filters('zlaark_subscriptions_email_message', $message, $to, $subject);
        $headers = apply_filters('zlaark_subscriptions_email_headers', $headers, $to, $subject, $message);
        
        // Send email
        return wp_mail($to, $subject, $message, $headers);
    }
}
