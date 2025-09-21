<?php
/**
 * Installation and upgrade routines
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installation class
 */
class ZlaarkSubscriptionsInstall {
    
    /**
     * Install the plugin
     */
    public static function install() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create pages
        self::create_pages();
        
        // Set version
        update_option('zlaark_subscriptions_version', ZLAARK_SUBSCRIPTIONS_VERSION);
        
        // Set installation date
        if (!get_option('zlaark_subscriptions_install_date')) {
            update_option('zlaark_subscriptions_install_date', current_time('mysql'));
        }
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Subscription orders table
        $table_name = $wpdb->prefix . 'zlaark_subscription_orders';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            razorpay_subscription_id varchar(255) DEFAULT NULL,
            razorpay_customer_id varchar(255) DEFAULT NULL,
            trial_start_date datetime DEFAULT NULL,
            trial_end_date datetime DEFAULT NULL,
            next_payment_date datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            trial_price decimal(10,2) DEFAULT 0.00,
            recurring_price decimal(10,2) NOT NULL,
            billing_interval varchar(20) NOT NULL DEFAULT 'monthly',
            max_cycles int(11) DEFAULT NULL,
            current_cycle int(11) DEFAULT 0,
            failed_payment_count int(11) DEFAULT 0,
            last_payment_date datetime DEFAULT NULL,
            cancellation_reason text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY status (status),
            KEY next_payment_date (next_payment_date),
            KEY razorpay_subscription_id (razorpay_subscription_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Payment history table
        $payment_table = $wpdb->prefix . 'zlaark_subscription_payments';
        
        $payment_sql = "CREATE TABLE $payment_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            razorpay_payment_id varchar(255) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'INR',
            status varchar(20) NOT NULL,
            payment_method varchar(50) DEFAULT NULL,
            failure_reason text DEFAULT NULL,
            payment_date datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subscription_id (subscription_id),
            KEY razorpay_payment_id (razorpay_payment_id),
            KEY status (status),
            KEY payment_date (payment_date)
        ) $charset_collate;";
        
        dbDelta($payment_sql);
        
        // Webhook logs table
        $webhook_table = $wpdb->prefix . 'zlaark_subscription_webhook_logs';
        
        $webhook_sql = "CREATE TABLE $webhook_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            razorpay_event_id varchar(255) DEFAULT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            processed_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY razorpay_event_id (razorpay_event_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($webhook_sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_options = array(
            'zlaark_subscriptions_trial_grace_period' => 3,
            'zlaark_subscriptions_failed_payment_retries' => 3,
            'zlaark_subscriptions_retry_interval' => 2,
            'zlaark_subscriptions_auto_cancel_after_retries' => 'yes',
            'zlaark_subscriptions_email_notifications' => 'yes',
            'zlaark_subscriptions_webhook_secret' => wp_generate_password(32, false),
        );
        
        foreach ($default_options as $option => $value) {
            if (!get_option($option)) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Create required pages
     */
    private static function create_pages() {
        // Create subscription management page if it doesn't exist
        $page_id = get_option('zlaark_subscriptions_page_id');
        
        if (!$page_id || !get_post($page_id)) {
            $page_data = array(
                'post_title'     => __('Manage Subscriptions', 'zlaark-subscriptions'),
                'post_content'   => '[zlaark_subscriptions_manage]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'post_author'    => 1,
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            );
            
            $page_id = wp_insert_post($page_data);
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('zlaark_subscriptions_page_id', $page_id);
            }
        }
    }
    
    /**
     * Check if upgrade is needed
     */
    public static function check_version() {
        $current_version = get_option('zlaark_subscriptions_version');
        
        if (version_compare($current_version, ZLAARK_SUBSCRIPTIONS_VERSION, '<')) {
            self::upgrade($current_version);
        }
    }
    
    /**
     * Upgrade routine
     *
     * @param string $from_version
     */
    private static function upgrade($from_version) {
        // Future upgrade routines can be added here
        
        // Update version
        update_option('zlaark_subscriptions_version', ZLAARK_SUBSCRIPTIONS_VERSION);
    }
    
    /**
     * Uninstall the plugin
     */
    public static function uninstall() {
        global $wpdb;
        
        // Remove tables
        $tables = array(
            $wpdb->prefix . 'zlaark_subscription_orders',
            $wpdb->prefix . 'zlaark_subscription_payments',
            $wpdb->prefix . 'zlaark_subscription_webhook_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove options
        $options = array(
            'zlaark_subscriptions_version',
            'zlaark_subscriptions_install_date',
            'zlaark_subscriptions_trial_grace_period',
            'zlaark_subscriptions_failed_payment_retries',
            'zlaark_subscriptions_retry_interval',
            'zlaark_subscriptions_auto_cancel_after_retries',
            'zlaark_subscriptions_email_notifications',
            'zlaark_subscriptions_webhook_secret',
            'zlaark_subscriptions_page_id'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove pages
        $page_id = get_option('zlaark_subscriptions_page_id');
        if ($page_id) {
            wp_delete_post($page_id, true);
        }
        
        // Clear scheduled events
        wp_clear_scheduled_hook('zlaark_subscriptions_daily_check');
    }
}
