<?php
/**
 * Comprehensive Validation of Subscription System Fixes
 * 
 * This script validates that all the fixes have been applied correctly
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if running standalone
    if (file_exists('wp-config.php')) {
        require_once 'wp-config.php';
        require_once ABSPATH . 'wp-settings.php';
    } else {
        die('WordPress not found');
    }
}

class ZlaarkSubscriptionFixValidator {
    
    public static function run_validation() {
        $results = [];
        
        // 1. Validate Admin Menu Fix
        $results['admin_menu'] = self::validate_admin_menu();
        
        // 2. Validate Shortcode Fix
        $results['shortcodes'] = self::validate_shortcodes();
        
        // 3. Validate Product Template Fix
        $results['product_template'] = self::validate_product_template();
        
        // 4. Validate Component Initialization
        $results['component_init'] = self::validate_component_initialization();
        
        // 5. Overall System Health
        $results['system_health'] = self::validate_system_health();
        
        return $results;
    }
    
    private static function validate_admin_menu() {
        if (!is_admin()) {
            return ['status' => 'Not in admin context', 'passed' => null];
        }
        
        global $submenu;
        
        $checks = [
            'admin_class_exists' => class_exists('ZlaarkSubscriptionsAdmin'),
            'menu_registered' => isset($submenu['zlaark-subscriptions']),
            'menu_items_count' => isset($submenu['zlaark-subscriptions']) ? count($submenu['zlaark-subscriptions']) : 0
        ];
        
        $passed = $checks['admin_class_exists'] && $checks['menu_registered'] && $checks['menu_items_count'] >= 4;
        
        return [
            'status' => $passed ? 'PASSED' : 'FAILED',
            'passed' => $passed,
            'details' => $checks
        ];
    }
    
    private static function validate_shortcodes() {
        global $shortcode_tags;
        
        $expected_shortcodes = [
            'zlaark_subscriptions_manage',
            'zlaark_user_subscriptions',
            'subscription_required',
            'trial_button',
            'subscription_button',
            'zlaark_debug'
        ];
        
        $registered = [];
        $missing = [];
        
        foreach ($expected_shortcodes as $shortcode) {
            if (isset($shortcode_tags[$shortcode])) {
                $registered[] = $shortcode;
            } else {
                $missing[] = $shortcode;
            }
        }
        
        $passed = empty($missing);
        
        return [
            'status' => $passed ? 'PASSED' : 'FAILED',
            'passed' => $passed,
            'registered' => $registered,
            'missing' => $missing,
            'frontend_class_exists' => class_exists('ZlaarkSubscriptionsFrontend')
        ];
    }
    
    private static function validate_product_template() {
        $template_path = defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR') ? 
            ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php' :
            WP_PLUGIN_DIR . '/zlaark-subscriptions/templates/single-product/add-to-cart/subscription.php';
        
        $checks = [
            'template_file_exists' => file_exists($template_path),
            'product_type_class_exists' => class_exists('ZlaarkSubscriptionsProductType'),
            'wc_product_subscription_exists' => class_exists('WC_Product_Subscription'),
            'product_type_registered' => function_exists('wc_get_product_types') && isset(wc_get_product_types()['subscription'])
        ];
        
        $passed = $checks['template_file_exists'] && $checks['product_type_class_exists'] && $checks['wc_product_subscription_exists'];
        
        return [
            'status' => $passed ? 'PASSED' : 'FAILED',
            'passed' => $passed,
            'details' => $checks,
            'template_path' => $template_path
        ];
    }
    
    private static function validate_component_initialization() {
        $required_classes = [
            'ZlaarkSubscriptions',
            'ZlaarkSubscriptionsAdmin',
            'ZlaarkSubscriptionsFrontend',
            'ZlaarkSubscriptionsDatabase',
            'ZlaarkSubscriptionsManager',
            'WC_Product_Subscription'
        ];
        
        $loaded = [];
        $missing = [];
        
        foreach ($required_classes as $class) {
            if (class_exists($class)) {
                $loaded[] = $class;
            } else {
                $missing[] = $class;
            }
        }
        
        $passed = empty($missing);
        
        return [
            'status' => $passed ? 'PASSED' : 'FAILED',
            'passed' => $passed,
            'loaded_classes' => $loaded,
            'missing_classes' => $missing
        ];
    }
    
    private static function validate_system_health() {
        // Check if main plugin instance exists
        $main_instance_exists = function_exists('zlaark_subscriptions');
        
        // Check if WooCommerce integration is working
        $wc_integration = class_exists('WooCommerce') && function_exists('wc_get_product_types');
        
        // Check if database tables exist (if database class is loaded)
        $db_ready = false;
        if (class_exists('ZlaarkSubscriptionsDatabase')) {
            try {
                $db = ZlaarkSubscriptionsDatabase::instance();
                $db_ready = method_exists($db, 'get_user_subscriptions');
            } catch (Exception $e) {
                $db_ready = false;
            }
        }
        
        $overall_health = $main_instance_exists && $wc_integration && $db_ready;
        
        return [
            'status' => $overall_health ? 'HEALTHY' : 'ISSUES_DETECTED',
            'passed' => $overall_health,
            'main_instance' => $main_instance_exists,
            'woocommerce_integration' => $wc_integration,
            'database_ready' => $db_ready
        ];
    }
    
    public static function display_results($results) {
        echo "<div style='font-family: monospace; background: #f9f9f9; padding: 20px; margin: 20px; border: 1px solid #ddd;'>";
        echo "<h2>🔍 Subscription System Fix Validation Results</h2>";
        
        $overall_status = true;
        
        foreach ($results as $category => $data) {
            $status_icon = '';
            $status_color = '';
            
            if (isset($data['passed'])) {
                if ($data['passed'] === true) {
                    $status_icon = '✅';
                    $status_color = 'color: green;';
                } elseif ($data['passed'] === false) {
                    $status_icon = '❌';
                    $status_color = 'color: red;';
                    $overall_status = false;
                } else {
                    $status_icon = '⚠️';
                    $status_color = 'color: orange;';
                }
            }
            
            echo "<h3 style='$status_color'>$status_icon " . ucwords(str_replace('_', ' ', $category)) . "</h3>";
            echo "<pre style='background: #fff; padding: 10px; border-left: 3px solid #ccc;'>";
            print_r($data);
            echo "</pre>";
        }
        
        echo "<hr>";
        echo "<h2 style='" . ($overall_status ? 'color: green;' : 'color: red;') . "'>";
        echo $overall_status ? "🎉 All Systems Operational!" : "⚠️ Issues Detected - Review Failed Tests";
        echo "</h2>";
        
        if (!$overall_status) {
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ul>";
            echo "<li>Review failed tests above</li>";
            echo "<li>Check WordPress error logs for detailed error messages</li>";
            echo "<li>Ensure all plugin files are properly uploaded</li>";
            echo "<li>Verify WooCommerce is active and up to date</li>";
            echo "</ul>";
        }
        
        echo "</div>";
    }
}

// Add admin page for validation
if (is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            'Validate Subscription Fixes',
            'Validate Fixes',
            'manage_options',
            'validate-subscription-fixes',
            function() {
                $results = ZlaarkSubscriptionFixValidator::run_validation();
                ZlaarkSubscriptionFixValidator::display_results($results);
            }
        );
    });
}

// If running standalone, execute validation
if (!defined('ABSPATH') || (defined('WP_CLI') && WP_CLI)) {
    $results = ZlaarkSubscriptionFixValidator::run_validation();
    ZlaarkSubscriptionFixValidator::display_results($results);
}
