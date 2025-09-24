<?php
/**
 * Plugin Name: Zlaark Subscriptions
 * Plugin URI: https://github.com/kanishkumar11/zlaark-subscriptions
 * Description: A comprehensive WooCommerce subscription plugin with paid trials and Razorpay integration. Functions independently without requiring WooCommerce Subscriptions.
 * Version: 1.0.4
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

        // Early product type registration - try multiple hooks
        add_action('plugins_loaded', array($this, 'early_product_type_init'), 5);
        add_action('init', array($this, 'early_product_type_init'), 5);

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

        // Initialize product type immediately if WooCommerce is available
        $this->init_product_type();

        // Initialize components
        $this->init_components();

        // Hook into WooCommerce
        add_action('woocommerce_loaded', array($this, 'woocommerce_loaded'));

        // Debug initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Zlaark Subscriptions: Plugin initialized successfully');
            // Schedule health check after everything is loaded
            add_action('wp_loaded', array($this, 'check_system_health'));
        }
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
        $core_files = [
            'includes/class-zlaark-subscriptions-install.php',
            'includes/class-zlaark-subscriptions-database.php',
            'includes/class-wc-product-subscription.php',
            'includes/class-zlaark-subscriptions-product-type.php',
            'includes/class-zlaark-subscriptions-trial-service.php',
            'includes/class-zlaark-subscriptions-manager.php',
            'includes/class-zlaark-subscriptions-cron.php',
            'includes/class-zlaark-subscriptions-emails.php',
            'includes/class-zlaark-subscriptions-debug.php',
            'includes/class-zlaark-subscriptions-webhooks.php',
            'includes/gateways/class-zlaark-razorpay-gateway.php'
        ];

        foreach ($core_files as $file) {
            $file_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Zlaark Subscriptions: Missing file - $file_path");
            }
        }

        // Admin includes - Always include admin files for proper initialization
        // They will only initialize if is_admin() is true
        $admin_files = [
            'includes/admin/class-zlaark-subscriptions-admin.php',
            'includes/admin/class-zlaark-subscriptions-admin-list.php',
            'includes/admin/class-zlaark-subscriptions-shortcodes.php'
        ];

        foreach ($admin_files as $file) {
            $file_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Zlaark Subscriptions: Missing admin file - $file_path");
            }
        }

        // Frontend includes - Always include for shortcodes and AJAX support
        // They will handle their own context checking
        $frontend_files = [
            'includes/frontend/class-zlaark-subscriptions-frontend.php',
            'includes/frontend/class-zlaark-subscriptions-my-account.php'
        ];



        foreach ($frontend_files as $file) {
            $file_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Zlaark Subscriptions: Missing frontend file - $file_path");
            }
        }





        // Always include the dual button display fix
        $fix_file = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'fix-dual-button-display.php';
        if (file_exists($fix_file)) {
            require_once $fix_file;
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize database
        if (class_exists('ZlaarkSubscriptionsDatabase')) {
            ZlaarkSubscriptionsDatabase::instance();
        }

        // Initialize subscription manager
        if (class_exists('ZlaarkSubscriptionsManager')) {
            ZlaarkSubscriptionsManager::instance();
        }

        // Initialize cron jobs
        if (class_exists('ZlaarkSubscriptionsCron')) {
            ZlaarkSubscriptionsCron::instance();
        }

        // Initialize emails
        if (class_exists('ZlaarkSubscriptionsEmails')) {
            ZlaarkSubscriptionsEmails::instance();
        }

        // Initialize webhook handler
        if (class_exists('ZlaarkSubscriptionsWebhooks')) {
            ZlaarkSubscriptionsWebhooks::instance();
        }

        // Initialize debug system
        if (class_exists('ZlaarkSubscriptionsDebug')) {
            ZlaarkSubscriptionsDebug::instance();
        }

        // Trial service will be initialized when WooCommerce is loaded

        // Initialize admin components
        if (is_admin()) {
            if (class_exists('ZlaarkSubscriptionsAdmin')) {
                ZlaarkSubscriptionsAdmin::instance();
            } else {
                error_log('Zlaark Subscriptions: Admin class not found during initialization');
            }
        }

        // Initialize frontend components (always initialize for shortcodes, AJAX, and frontend)
        // Frontend class handles its own context checking
        if (class_exists('ZlaarkSubscriptionsFrontend')) {
            ZlaarkSubscriptionsFrontend::instance();
        } else {
            error_log('Zlaark Subscriptions: Frontend class not found during initialization');
        }

        // My Account only needs to initialize on frontend and AJAX
        if ((!is_admin() || wp_doing_ajax()) && class_exists('ZlaarkSubscriptionsMyAccount')) {
            ZlaarkSubscriptionsMyAccount::instance();
        }


    }
    
    /**
     * Early product type initialization - runs as early as possible
     */
    public function early_product_type_init() {
        if (class_exists('WooCommerce')) {
            $this->init_product_type();
        }
    }

    /**
     * Initialize product type with safety checks
     */
    private function init_product_type() {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        // Initialize product type
        if (class_exists('ZlaarkSubscriptionsProductType')) {
            ZlaarkSubscriptionsProductType::instance();

            // Mark as initialized
            $initialized = true;
            do_action('zlaark_subscriptions_product_type_init');

            // Log initialization for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Zlaark Subscriptions: Product type initialized at ' . current_action());
            }
        } else {
            error_log('Zlaark Subscriptions: ZlaarkSubscriptionsProductType class not found');
        }
    }

    /**
     * Called when WooCommerce is loaded
     */
    public function woocommerce_loaded() {
        // Initialize product type
        $this->init_product_type();

        // Initialize trial service after WooCommerce is loaded
        if (class_exists('ZlaarkSubscriptionsTrialService')) {
            ZlaarkSubscriptionsTrialService::instance();
        }

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

    /**
     * Check system health and log any issues
     */
    public function check_system_health() {
        $issues = [];

        // Check if classes are loaded
        $required_classes = [
            'ZlaarkSubscriptionsAdmin',
            'ZlaarkSubscriptionsFrontend',
            'WC_Product_Subscription',
            'ZlaarkSubscriptionsDatabase'
        ];

        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                $issues[] = "Missing class: $class";
            }
        }

        // Check if shortcodes are registered
        global $shortcode_tags;
        $required_shortcodes = ['zlaark_subscriptions_manage', 'zlaark_user_subscriptions', 'subscription_required'];

        foreach ($required_shortcodes as $shortcode) {
            if (!isset($shortcode_tags[$shortcode])) {
                $issues[] = "Missing shortcode: $shortcode";
            }
        }

        // Check if admin menu exists (only in admin)
        if (is_admin()) {
            global $submenu;
            if (!isset($submenu['zlaark-subscriptions'])) {
                $issues[] = "Admin menu not registered";
            }
        }

        // Log issues
        if (!empty($issues) && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Zlaark Subscriptions Health Check Issues: ' . implode(', ', $issues));
        }

        return empty($issues);
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
