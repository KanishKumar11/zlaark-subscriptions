<?php
/**
 * Plugin Name: Zlaark Subscriptions
 * Plugin URI: https://github.com/kanishkumar11/zlaark-subscriptions
 * Description: A comprehensive WooCommerce subscription plugin with paid trials and Razorpay integration. Functions independently without requiring WooCommerce Subscriptions.
 * Version: 1.0.2
 * Author: Kanish Kumar
 * Author URI: https://kanishkumar.in   
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zlaark-subscriptions
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ZLAARK_SUBSCRIPTIONS_VERSION', '1.0.0');
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_FILE', __FILE__);
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class ZlaarkSubscriptions {
    
    /**
     * Plugin instance
     *
     * @var ZlaarkSubscriptions
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return ZlaarkSubscriptions
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'), 10);
        add_action('init', array($this, 'load_textdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Check minimum requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
        
        // Hook into WooCommerce
        add_action('woocommerce_loaded', array($this, 'woocommerce_loaded'));

        // Also try to initialize on plugins_loaded with higher priority
        add_action('plugins_loaded', array($this, 'late_init'), 20);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('zlaark-subscriptions', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * Check minimum requirements
     *
     * @return bool
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            add_action('admin_notices', array($this, 'wordpress_version_notice'));
            return false;
        }
        
        // Check WooCommerce version
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '7.0', '<')) {
            add_action('admin_notices', array($this, 'woocommerce_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core includes
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-install.php';
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-database.php';
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-wc-product-subscription.php';
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-product-type.php';
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-manager.php';
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-cron.php';
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-emails.php';
        
        // Payment gateway
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/gateways/class-zlaark-razorpay-gateway.php';
        
        // Admin includes
        if (is_admin()) {
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/admin/class-zlaark-subscriptions-admin.php';
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/admin/class-zlaark-subscriptions-admin-list.php';
        }
        
        // Frontend includes
        if (!is_admin()) {
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/frontend/class-zlaark-subscriptions-frontend.php';
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/frontend/class-zlaark-subscriptions-my-account.php';
        }
        
        // Webhook handler
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-webhooks.php';

        // Test files (only in development)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'tests/test-plugin-structure.php';
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize database
        ZlaarkSubscriptionsDatabase::instance();
        
        // Initialize subscription manager
        ZlaarkSubscriptionsManager::instance();
        
        // Initialize cron jobs
        ZlaarkSubscriptionsCron::instance();
        
        // Initialize emails
        ZlaarkSubscriptionsEmails::instance();
        
        // Initialize webhook handler
        ZlaarkSubscriptionsWebhooks::instance();
        
        // Initialize admin components
        if (is_admin()) {
            ZlaarkSubscriptionsAdmin::instance();
        }
        
        // Initialize frontend components (always initialize for AJAX and frontend)
        if (!is_admin() || wp_doing_ajax()) {
            ZlaarkSubscriptionsFrontend::instance();
            ZlaarkSubscriptionsMyAccount::instance();
        }
    }
    
    /**
     * Late initialization to ensure WooCommerce is ready
     */
    public function late_init() {
        // Only run if WooCommerce is active and not already initialized
        if (class_exists('WooCommerce') && !did_action('zlaark_subscriptions_product_type_init')) {
            // Initialize product type
            ZlaarkSubscriptionsProductType::instance();

            // Mark as initialized
            do_action('zlaark_subscriptions_product_type_init');
        }
    }

    /**
     * Called when WooCommerce is loaded
     */
    public function woocommerce_loaded() {
        // Initialize product type
        ZlaarkSubscriptionsProductType::instance();

        // Add payment gateway
        add_filter('woocommerce_payment_gateways', array($this, 'add_payment_gateway'));
    }
    
    /**
     * Add Razorpay payment gateway
     *
     * @param array $gateways
     * @return array
     */
    public function add_payment_gateway($gateways) {
        $gateways[] = 'ZlaarkRazorpayGateway';
        return $gateways;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check requirements before activation
        if (!$this->is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Zlaark Subscriptions requires WooCommerce to be installed and active.', 'zlaark-subscriptions'));
        }
        
        if (!$this->check_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Zlaark Subscriptions requires PHP 8.0+ and WordPress 6.0+.', 'zlaark-subscriptions'));
        }
        
        // Run installation
        require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-zlaark-subscriptions-install.php';
        ZlaarkSubscriptionsInstall::install();
        
        // Schedule cron events
        if (!wp_next_scheduled('zlaark_subscriptions_daily_check')) {
            wp_schedule_event(time(), 'daily', 'zlaark_subscriptions_daily_check');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('zlaark_subscriptions_daily_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . 
             esc_html__('Zlaark Subscriptions', 'zlaark-subscriptions') . 
             '</strong> ' . 
             esc_html__('requires WooCommerce to be installed and active.', 'zlaark-subscriptions') . 
             '</p></div>';
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="error"><p><strong>' . 
             esc_html__('Zlaark Subscriptions', 'zlaark-subscriptions') . 
             '</strong> ' . 
             esc_html__('requires PHP version 8.0 or higher.', 'zlaark-subscriptions') . 
             '</p></div>';
    }
    
    /**
     * WordPress version notice
     */
    public function wordpress_version_notice() {
        echo '<div class="error"><p><strong>' . 
             esc_html__('Zlaark Subscriptions', 'zlaark-subscriptions') . 
             '</strong> ' . 
             esc_html__('requires WordPress version 6.0 or higher.', 'zlaark-subscriptions') . 
             '</p></div>';
    }
    
    /**
     * WooCommerce version notice
     */
    public function woocommerce_version_notice() {
        echo '<div class="error"><p><strong>' . 
             esc_html__('Zlaark Subscriptions', 'zlaark-subscriptions') . 
             '</strong> ' . 
             esc_html__('requires WooCommerce version 7.0 or higher.', 'zlaark-subscriptions') . 
             '</p></div>';
    }
}

/**
 * Returns the main instance of ZlaarkSubscriptions
 *
 * @return ZlaarkSubscriptions
 */
function zlaark_subscriptions() {
    return ZlaarkSubscriptions::instance();
}

// Initialize the plugin
zlaark_subscriptions();
