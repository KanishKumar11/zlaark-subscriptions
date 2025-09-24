<?php
/**
 * Plugin Name: Zlaark Subscriptions (MINIMAL TEST VERSION)
 * Description: Minimal version for testing - only core functionality
 * Version: 1.0.0-minimal
 * Author: Zlaark
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ZLAARK_SUBSCRIPTIONS_VERSION', '1.0.0-minimal');
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_FILE', __FILE__);
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZLAARK_SUBSCRIPTIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Minimal Zlaark Subscriptions class for testing
 */
class ZlaarkSubscriptionsMinimal {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Basic initialization
        add_action('plugins_loaded', array($this, 'init'));
        
        // Admin notice for testing
        add_action('admin_notices', array($this, 'test_admin_notice'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load only essential files for testing
        $this->load_essential_files();
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Load essential files only
     */
    private function load_essential_files() {
        // Only load the most basic files first
        $essential_files = [
            'includes/class-zlaark-subscriptions-product-type.php',
            'includes/class-zlaark-subscriptions-product.php'
        ];
        
        foreach ($essential_files as $file) {
            $file_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                try {
                    require_once $file_path;
                    error_log("Zlaark Minimal: Successfully loaded $file");
                } catch (Exception $e) {
                    error_log("Zlaark Minimal: Exception loading $file - " . $e->getMessage());
                } catch (Error $e) {
                    error_log("Zlaark Minimal: Fatal error loading $file - " . $e->getMessage());
                }
            } else {
                error_log("Zlaark Minimal: Missing file - $file_path");
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize product type
        if (class_exists('ZlaarkSubscriptionsProductType')) {
            try {
                ZlaarkSubscriptionsProductType::instance();
                error_log("Zlaark Minimal: Product type initialized successfully");
            } catch (Exception $e) {
                error_log("Zlaark Minimal: Exception initializing product type - " . $e->getMessage());
            } catch (Error $e) {
                error_log("Zlaark Minimal: Fatal error initializing product type - " . $e->getMessage());
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        error_log("Zlaark Minimal: Plugin activated");
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        error_log("Zlaark Minimal: Plugin deactivated");
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Zlaark Subscriptions requires WooCommerce to be installed and active.', 'zlaark-subscriptions'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Test admin notice
     */
    public function test_admin_notice() {
        if (current_user_can('manage_options')) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><strong>Zlaark Subscriptions Minimal Test:</strong> Plugin is running in minimal test mode. If you see this message, basic plugin loading is working.</p>
            </div>
            <?php
        }
    }
}

// Initialize the minimal plugin
function zlaark_subscriptions_minimal_init() {
    try {
        ZlaarkSubscriptionsMinimal::instance();
        error_log("Zlaark Minimal: Plugin instance created successfully");
    } catch (Exception $e) {
        error_log("Zlaark Minimal: Exception creating plugin instance - " . $e->getMessage());
    } catch (Error $e) {
        error_log("Zlaark Minimal: Fatal error creating plugin instance - " . $e->getMessage());
    }
}

// Start the plugin
zlaark_subscriptions_minimal_init();
