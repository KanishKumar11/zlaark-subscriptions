<?php
/**
 * Database operations for subscriptions
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class
 */
class ZlaarkSubscriptionsDatabase {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsDatabase
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsDatabase
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
        // Check for database updates
        add_action('admin_init', array($this, 'check_database_version'));
    }
    
    /**
     * Check database version
     */
    public function check_database_version() {
        ZlaarkSubscriptionsInstall::check_version();
    }
    
    /**
     * Create a new subscription
     *
     * @param array $data Subscription data
     * @return int|false Subscription ID or false on failure
     */
    public function create_subscription($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        $defaults = array(
            'status' => 'active',
            'trial_price' => 0.00,
            'billing_interval' => 'monthly',
            'current_cycle' => 0,
            'failed_payment_count' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update subscription
     *
     * @param int $subscription_id
     * @param array $data
     * @return bool
     */
    public function update_subscription($subscription_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $subscription_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get subscription by ID
     *
     * @param int $subscription_id
     * @return object|null
     */
    public function get_subscription($subscription_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $subscription_id
        ));
    }
    
    /**
     * Get subscription by order ID
     *
     * @param int $order_id
     * @return object|null
     */
    public function get_subscription_by_order($order_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order_id
        ));
    }
    
    /**
     * Get subscription by Razorpay subscription ID
     *
     * @param string $razorpay_subscription_id
     * @return object|null
     */
    public function get_subscription_by_razorpay_id($razorpay_subscription_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE razorpay_subscription_id = %s",
            $razorpay_subscription_id
        ));
    }
    
    /**
     * Get subscriptions by user ID
     *
     * @param int $user_id
     * @param string|int $status_or_product_id Optional status filter or product ID
     * @param string $status Optional status filter when second param is product ID
     * @return array
     */
    public function get_user_subscriptions($user_id, $status_or_product_id = '', $status = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'zlaark_subscription_orders';

        $sql = "SELECT * FROM $table WHERE user_id = %d";
        $params = array($user_id);

        // Handle different parameter combinations
        if (is_numeric($status_or_product_id)) {
            // Second parameter is product_id
            $sql .= " AND product_id = %d";
            $params[] = intval($status_or_product_id);

            if (!empty($status)) {
                $sql .= " AND status = %s";
                $params[] = $status;
            }
        } elseif (!empty($status_or_product_id)) {
            // Second parameter is status
            $sql .= " AND status = %s";
            $params[] = $status_or_product_id;
        }

        $sql .= " ORDER BY created_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get subscriptions due for payment
     *
     * @return array
     */
    public function get_subscriptions_due_for_payment() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'active' 
             AND next_payment_date <= %s 
             AND next_payment_date IS NOT NULL",
            current_time('mysql')
        ));
    }
    
    /**
     * Get subscriptions with expired trials
     *
     * @return array
     */
    public function get_expired_trials() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'trial' 
             AND trial_end_date <= %s 
             AND trial_end_date IS NOT NULL",
            current_time('mysql')
        ));
    }
    
    /**
     * Record payment
     *
     * @param array $data Payment data
     * @return int|false Payment ID or false on failure
     */
    public function record_payment($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_payments';
        
        $defaults = array(
            'currency' => 'INR',
            'payment_date' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get payment history for subscription
     *
     * @param int $subscription_id
     * @return array
     */
    public function get_payment_history($subscription_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_payments';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE subscription_id = %d ORDER BY payment_date DESC",
            $subscription_id
        ));
    }
    
    /**
     * Log webhook event
     *
     * @param array $data Webhook data
     * @return int|false Log ID or false on failure
     */
    public function log_webhook($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_webhook_logs';
        
        $defaults = array(
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update webhook log
     *
     * @param int $log_id
     * @param array $data
     * @return bool
     */
    public function update_webhook_log($log_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_webhook_logs';
        
        if (isset($data['status']) && $data['status'] === 'processed') {
            $data['processed_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $log_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get subscription statistics
     *
     * @return array
     */
    public function get_subscription_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        
        $stats = array();
        
        // Total subscriptions
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        // Active subscriptions
        $stats['active'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'active'");
        
        // Trial subscriptions
        $stats['trial'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'trial'");
        
        // Cancelled subscriptions
        $stats['cancelled'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'cancelled'");
        
        // Expired subscriptions
        $stats['expired'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'expired'");
        
        // Monthly recurring revenue
        $stats['mrr'] = $wpdb->get_var("
            SELECT SUM(recurring_price) 
            FROM $table 
            WHERE status IN ('active', 'trial') 
            AND billing_interval = 'monthly'
        ");
        
        return $stats;
    }
    
    /**
     * Delete subscription and related data
     *
     * @param int $subscription_id
     * @return bool
     */
    public function delete_subscription($subscription_id) {
        global $wpdb;
        
        // Delete payments
        $wpdb->delete(
            $wpdb->prefix . 'zlaark_subscription_payments',
            array('subscription_id' => $subscription_id),
            array('%d')
        );
        
        // Delete subscription
        $result = $wpdb->delete(
            $wpdb->prefix . 'zlaark_subscription_orders',
            array('id' => $subscription_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Check if user has used trial for a product
     *
     * @param int $user_id
     * @param int $product_id
     * @return bool
     */
    public function has_user_used_trial($user_id, $product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'zlaark_subscription_trial_history';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND product_id = %d",
            $user_id,
            $product_id
        ));

        return $count > 0;
    }

    /**
     * Record trial usage for user and product
     *
     * @param int $user_id
     * @param int $product_id
     * @param int $subscription_id
     * @return int|false Trial history ID or false on failure
     */
    public function record_trial_usage($user_id, $product_id, $subscription_id = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'zlaark_subscription_trial_history';

        // Race condition protection - double-check that trial hasn't already been recorded
        if ($this->has_user_used_trial($user_id, $product_id)) {
            error_log("Zlaark Subscriptions: Attempted to record duplicate trial usage - User: {$user_id}, Product: {$product_id}");
            return false;
        }

        $data = array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'trial_started_at' => current_time('mysql'),
            'trial_status' => 'active',
            'subscription_id' => $subscription_id,
            'created_at' => current_time('mysql')
        );

        $result = $wpdb->insert($table, $data);

        if ($result) {
            $trial_history_id = $wpdb->insert_id;

            // Log successful trial recording
            error_log("Zlaark Subscriptions: Trial usage recorded successfully - User: {$user_id}, Product: {$product_id}, Trial History ID: {$trial_history_id}");

            return $trial_history_id;
        } else {
            // Log database error
            error_log("Zlaark Subscriptions: Failed to record trial usage - User: {$user_id}, Product: {$product_id}, DB Error: " . $wpdb->last_error);
            return false;
        }
    }

    /**
     * Update trial history status
     *
     * @param int $user_id
     * @param int $product_id
     * @param string $status
     * @return bool
     */
    public function update_trial_status($user_id, $product_id, $status) {
        global $wpdb;

        $table = $wpdb->prefix . 'zlaark_subscription_trial_history';

        $result = $wpdb->update(
            $table,
            array(
                'trial_status' => $status,
                'trial_ended_at' => current_time('mysql')
            ),
            array(
                'user_id' => $user_id,
                'product_id' => $product_id
            )
        );

        return $result !== false;
    }

    /**
     * Get trial history for user
     *
     * @param int $user_id
     * @param int|null $product_id Optional product filter
     * @return array
     */
    public function get_user_trial_history($user_id, $product_id = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'zlaark_subscription_trial_history';

        if ($product_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND product_id = %d ORDER BY created_at DESC",
                $user_id,
                $product_id
            ));
        } else {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            ));
        }
    }

    /**
     * Get trial history for product
     *
     * @param int $product_id
     * @return array
     */
    public function get_product_trial_history($product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'zlaark_subscription_trial_history';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE product_id = %d ORDER BY created_at DESC",
            $product_id
        ));
    }


}
