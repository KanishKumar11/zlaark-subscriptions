<?php
/**
 * Cron jobs for subscription management
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cron class
 */
class ZlaarkSubscriptionsCron {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsCron
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
     * @return ZlaarkSubscriptionsCron
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
        // Daily cron job
        add_action('zlaark_subscriptions_daily_check', array($this, 'daily_check'));
        
        // Hourly cron job for urgent tasks
        add_action('init', array($this, 'schedule_hourly_check'));
        add_action('zlaark_subscriptions_hourly_check', array($this, 'hourly_check'));
        
        // Manual cron trigger for testing
        add_action('wp_ajax_zlaark_subscriptions_run_cron', array($this, 'manual_cron_trigger'));
    }
    
    /**
     * Schedule hourly check
     */
    public function schedule_hourly_check() {
        if (!wp_next_scheduled('zlaark_subscriptions_hourly_check')) {
            wp_schedule_event(time(), 'hourly', 'zlaark_subscriptions_hourly_check');
        }
    }
    
    /**
     * Daily check
     */
    public function daily_check() {
        $this->log('Starting daily subscription check');
        
        // Process expired trials
        $this->process_expired_trials();
        
        // Process due payments
        $this->process_due_payments();
        
        // Clean up old webhook logs
        $this->cleanup_webhook_logs();
        
        // Send reminder emails
        $this->send_reminder_emails();
        
        $this->log('Daily subscription check completed');
    }
    
    /**
     * Hourly check for urgent tasks
     */
    public function hourly_check() {
        $this->log('Starting hourly subscription check');
        
        // Process overdue payments (more frequent check)
        $this->process_overdue_payments();
        
        // Process failed subscriptions for expiry
        $this->process_failed_subscription_expiry();
        
        $this->log('Hourly subscription check completed');
    }
    
    /**
     * Process expired trials
     */
    private function process_expired_trials() {
        $expired_trials = $this->db->get_expired_trials();
        
        $this->log(sprintf('Found %d expired trials to process', count($expired_trials)));
        
        foreach ($expired_trials as $subscription) {
            try {
                // Check if customer has valid payment method
                if (!empty($subscription->razorpay_customer_id)) {
                    // Transition to active and process first recurring payment
                    $this->manager->update_subscription_status($subscription->id, 'active', 'Trial period ended');
                    $this->manager->process_renewal($subscription->id);
                } else {
                    // No payment method, cancel subscription
                    $this->manager->update_subscription_status($subscription->id, 'cancelled', 'No payment method after trial');
                }
                
                $this->log(sprintf('Processed expired trial for subscription #%d', $subscription->id));
                
            } catch (Exception $e) {
                $this->log(sprintf('Error processing expired trial #%d: %s', $subscription->id, $e->getMessage()));
            }
        }
    }
    
    /**
     * Process due payments
     */
    private function process_due_payments() {
        $due_subscriptions = $this->db->get_subscriptions_due_for_payment();
        
        $this->log(sprintf('Found %d subscriptions due for payment', count($due_subscriptions)));
        
        foreach ($due_subscriptions as $subscription) {
            try {
                $result = $this->manager->process_renewal($subscription->id);
                
                if ($result) {
                    $this->log(sprintf('Successfully processed payment for subscription #%d', $subscription->id));
                } else {
                    $this->log(sprintf('Failed to process payment for subscription #%d', $subscription->id));
                }
                
            } catch (Exception $e) {
                $this->log(sprintf('Error processing payment for subscription #%d: %s', $subscription->id, $e->getMessage()));
            }
            
            // Add small delay to avoid overwhelming the payment gateway
            sleep(1);
        }
    }
    
    /**
     * Process overdue payments (more frequent check)
     */
    private function process_overdue_payments() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        // Get subscriptions that are overdue by more than 1 hour
        $overdue_subscriptions = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE status = 'active' 
            AND next_payment_date <= %s 
            AND next_payment_date IS NOT NULL
            AND failed_payment_count < %d
        ", 
        date('Y-m-d H:i:s', strtotime('-1 hour')),
        get_option('zlaark_subscriptions_failed_payment_retries', 3)
        ));
        
        if (!empty($overdue_subscriptions)) {
            $this->log(sprintf('Found %d overdue subscriptions to retry', count($overdue_subscriptions)));
            
            foreach ($overdue_subscriptions as $subscription) {
                try {
                    $this->manager->process_renewal($subscription->id);
                    $this->log(sprintf('Retried payment for overdue subscription #%d', $subscription->id));
                } catch (Exception $e) {
                    $this->log(sprintf('Error retrying payment for subscription #%d: %s', $subscription->id, $e->getMessage()));
                }
                
                // Add delay between retries
                sleep(2);
            }
        }
    }

    /**
     * Process failed subscriptions for expiry
     */
    private function process_failed_subscription_expiry() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        $expiry_days = get_option('zlaark_subscriptions_failed_expiry_days', 30); // 30 days default
        
        // Get failed subscriptions that have been failed for more than expiry period
        $expired_failed = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE status = 'failed' 
            AND updated_at <= %s
        ", 
        date('Y-m-d H:i:s', strtotime('-' . $expiry_days . ' days'))
        ));
        
        if (!empty($expired_failed)) {
            $this->log(sprintf('Found %d failed subscriptions to expire', count($expired_failed)));
            
            foreach ($expired_failed as $subscription) {
                try {
                    $this->manager->update_subscription_status($subscription->id, 'expired', 'Expired due to failed payments');
                    $this->log(sprintf('Expired failed subscription #%d', $subscription->id));
                } catch (Exception $e) {
                    $this->log(sprintf('Error expiring failed subscription #%d: %s', $subscription->id, $e->getMessage()));
                }
            }
        }
    }

    /**
     * Send reminder emails
     */
    private function send_reminder_emails() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        $emails = ZlaarkSubscriptionsEmails::instance();
        
        // Send trial ending reminders (2 days before trial ends)
        $trial_ending_soon = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE status = 'trial' 
            AND trial_end_date BETWEEN %s AND %s
        ", 
        date('Y-m-d H:i:s', strtotime('+1 day')),
        date('Y-m-d H:i:s', strtotime('+3 days'))
        ));
        
        foreach ($trial_ending_soon as $subscription) {
            // Check if reminder already sent
            $reminder_sent = get_option('zlaark_trial_reminder_sent_' . $subscription->id);
            if (!$reminder_sent) {
                $emails->send_trial_ending_reminder($subscription->id);
                update_option('zlaark_trial_reminder_sent_' . $subscription->id, time());
                $this->log(sprintf('Sent trial ending reminder for subscription #%d', $subscription->id));
            }
        }
        
        // Send payment due reminders (1 day before payment)
        $payment_due_soon = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE status = 'active' 
            AND next_payment_date BETWEEN %s AND %s
        ", 
        date('Y-m-d H:i:s', strtotime('+12 hours')),
        date('Y-m-d H:i:s', strtotime('+36 hours'))
        ));
        
        foreach ($payment_due_soon as $subscription) {
            // Check if reminder already sent for this payment cycle
            $reminder_key = 'zlaark_payment_reminder_sent_' . $subscription->id . '_' . strtotime($subscription->next_payment_date);
            $reminder_sent = get_option($reminder_key);
            
            if (!$reminder_sent) {
                $emails->send_payment_due_reminder($subscription->id);
                update_option($reminder_key, time());
                $this->log(sprintf('Sent payment due reminder for subscription #%d', $subscription->id));
            }
        }
    }
    
    /**
     * Clean up old webhook logs
     */
    private function cleanup_webhook_logs() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_webhook_logs';
        
        // Delete logs older than 30 days
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM $table 
            WHERE created_at < %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))));
        
        if ($deleted > 0) {
            $this->log(sprintf('Cleaned up %d old webhook logs', $deleted));
        }
    }
    
    /**
     * Manual cron trigger for testing
     */
    public function manual_cron_trigger() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'zlaark-subscriptions'));
        }
        
        if (!wp_verify_nonce($_REQUEST['nonce'], 'zlaark_subscriptions_admin_nonce')) {
            wp_die(__('Invalid nonce', 'zlaark-subscriptions'));
        }
        
        $action = sanitize_text_field($_REQUEST['cron_action']);
        
        switch ($action) {
            case 'daily':
                $this->daily_check();
                wp_send_json_success(__('Daily check completed', 'zlaark-subscriptions'));
                break;
                
            case 'hourly':
                $this->hourly_check();
                wp_send_json_success(__('Hourly check completed', 'zlaark-subscriptions'));
                break;
                
            case 'expired_trials':
                $this->process_expired_trials();
                wp_send_json_success(__('Expired trials processed', 'zlaark-subscriptions'));
                break;
                
            case 'due_payments':
                $this->process_due_payments();
                wp_send_json_success(__('Due payments processed', 'zlaark-subscriptions'));
                break;
                
            default:
                wp_send_json_error(__('Invalid action', 'zlaark-subscriptions'));
        }
    }
    
    /**
     * Log cron activities
     *
     * @param string $message
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Zlaark Subscriptions Cron] ' . $message);
        }
        
        // Also store in database for admin viewing
        $this->store_log($message);
    }
    
    /**
     * Store log in database
     *
     * @param string $message
     */
    private function store_log($message) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_logs';
        
        // Create logs table if it doesn't exist
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS $table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                message text NOT NULL,
                level varchar(20) NOT NULL DEFAULT 'info',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY created_at (created_at)
            ) {$wpdb->get_charset_collate()}
        ");
        
        $wpdb->insert($table, array(
            'message' => $message,
            'level' => 'info',
            'created_at' => current_time('mysql')
        ));
        
        // Clean up old logs (keep only last 1000 entries)
        $wpdb->query("
            DELETE FROM $table 
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id FROM $table 
                    ORDER BY created_at DESC 
                    LIMIT 1000
                ) tmp
            )
        ");
    }
    
    /**
     * Get recent logs for admin display
     *
     * @param int $limit
     * @return array
     */
    public function get_recent_logs($limit = 50) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_logs';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_logs';
        $wpdb->query("TRUNCATE TABLE $table");
    }
}
