<?php
/**
 * Comprehensive Subscription System Diagnosis
 * 
 * This script diagnoses issues with the Zlaark Subscriptions system
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

class ZlaarkSubscriptionsDiagnosis {
    
    public static function run_diagnosis() {
        $results = [];
        
        // 1. Check WordPress and WooCommerce
        $results['wordpress'] = self::check_wordpress();
        $results['woocommerce'] = self::check_woocommerce();
        
        // 2. Check plugin activation and files
        $results['plugin_status'] = self::check_plugin_status();
        $results['file_structure'] = self::check_file_structure();
        
        // 3. Check class initialization
        $results['class_loading'] = self::check_class_loading();
        
        // 4. Check admin menu registration
        $results['admin_menu'] = self::check_admin_menu();
        
        // 5. Check shortcode registration
        $results['shortcodes'] = self::check_shortcodes();
        
        // 6. Check product template loading
        $results['product_templates'] = self::check_product_templates();
        
        // 7. Check hooks and actions
        $results['hooks'] = self::check_hooks();
        
        return $results;
    }
    
    private static function check_wordpress() {
        return [
            'loaded' => defined('ABSPATH'),
            'version' => get_bloginfo('version'),
            'admin_context' => is_admin(),
            'current_user_can_manage' => current_user_can('manage_options')
        ];
    }
    
    private static function check_woocommerce() {
        return [
            'active' => class_exists('WooCommerce'),
            'version' => class_exists('WooCommerce') ? WC()->version : 'Not installed',
            'product_types' => function_exists('wc_get_product_types') ? wc_get_product_types() : []
        ];
    }
    
    private static function check_plugin_status() {
        $plugin_file = 'zlaark-subscriptions/zlaark-subscriptions.php';
        return [
            'plugin_file_exists' => file_exists(WP_PLUGIN_DIR . '/' . $plugin_file),
            'is_active' => function_exists('is_plugin_active') ? is_plugin_active($plugin_file) : 'Unknown',
            'main_class_exists' => class_exists('ZlaarkSubscriptions'),
            'constants_defined' => [
                'ZLAARK_SUBSCRIPTIONS_VERSION' => defined('ZLAARK_SUBSCRIPTIONS_VERSION'),
                'ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR' => defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR'),
                'ZLAARK_SUBSCRIPTIONS_PLUGIN_URL' => defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_URL')
            ]
        ];
    }
    
    private static function check_file_structure() {
        $base_dir = defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR') ? ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR : WP_PLUGIN_DIR . '/zlaark-subscriptions/';
        
        $required_files = [
            'zlaark-subscriptions.php',
            'includes/admin/class-zlaark-subscriptions-admin.php',
            'includes/admin/class-zlaark-subscriptions-admin-list.php',
            'includes/frontend/class-zlaark-subscriptions-frontend.php',
            'includes/frontend/class-zlaark-subscriptions-my-account.php',
            'includes/class-wc-product-subscription.php',
            'includes/class-zlaark-subscriptions-product-type.php',
            'templates/single-product/add-to-cart/subscription.php',
            'assets/js/frontend.js',
            'assets/css/frontend.css'
        ];
        
        $file_status = [];
        foreach ($required_files as $file) {
            $file_status[$file] = file_exists($base_dir . $file);
        }
        
        return $file_status;
    }
    
    private static function check_class_loading() {
        $classes = [
            'ZlaarkSubscriptions',
            'ZlaarkSubscriptionsAdmin',
            'ZlaarkSubscriptionsFrontend',
            'ZlaarkSubscriptionsMyAccount',
            'WC_Product_Subscription',
            'ZlaarkSubscriptionsProductType',
            'ZlaarkSubscriptionsDatabase',
            'ZlaarkSubscriptionsManager',
            'ZlaarkSubscriptionsTrialService'
        ];
        
        $class_status = [];
        foreach ($classes as $class) {
            $class_status[$class] = class_exists($class);
        }
        
        return $class_status;
    }
    
    private static function check_admin_menu() {
        if (!is_admin()) {
            return ['status' => 'Not in admin context'];
        }
        
        global $menu, $submenu;
        
        return [
            'main_menu_exists' => self::menu_exists('zlaark-subscriptions'),
            'submenu_items' => isset($submenu['zlaark-subscriptions']) ? count($submenu['zlaark-subscriptions']) : 0,
            'submenu_pages' => isset($submenu['zlaark-subscriptions']) ? array_column($submenu['zlaark-subscriptions'], 2) : []
        ];
    }
    
    private static function menu_exists($slug) {
        global $menu;
        if (!is_array($menu)) return false;
        
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === $slug) {
                return true;
            }
        }
        return false;
    }
    
    private static function check_shortcodes() {
        global $shortcode_tags;
        
        $expected_shortcodes = [
            'zlaark_subscriptions_manage',
            'zlaark_user_subscriptions',
            'subscription_required',
            'zlaark_debug'
        ];
        
        $shortcode_status = [];
        foreach ($expected_shortcodes as $shortcode) {
            $shortcode_status[$shortcode] = isset($shortcode_tags[$shortcode]);
        }
        
        return $shortcode_status;
    }
    
    private static function check_product_templates() {
        $template_path = defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR') ? 
            ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php' :
            WP_PLUGIN_DIR . '/zlaark-subscriptions/templates/single-product/add-to-cart/subscription.php';
            
        return [
            'template_exists' => file_exists($template_path),
            'template_path' => $template_path,
            'woocommerce_template_hooks' => self::check_woocommerce_hooks()
        ];
    }
    
    private static function check_woocommerce_hooks() {
        global $wp_filter;
        
        $hooks_to_check = [
            'woocommerce_single_product_summary',
            'woocommerce_before_add_to_cart_button',
            'woocommerce_after_add_to_cart_button'
        ];
        
        $hook_status = [];
        foreach ($hooks_to_check as $hook) {
            $hook_status[$hook] = isset($wp_filter[$hook]) ? count($wp_filter[$hook]->callbacks) : 0;
        }
        
        return $hook_status;
    }
    
    private static function check_hooks() {
        global $wp_filter;
        
        $important_hooks = [
            'admin_menu',
            'wp_enqueue_scripts',
            'woocommerce_loaded',
            'init',
            'plugins_loaded'
        ];
        
        $hook_status = [];
        foreach ($important_hooks as $hook) {
            $callbacks = isset($wp_filter[$hook]) ? $wp_filter[$hook]->callbacks : [];
            $zlaark_callbacks = 0;
            
            foreach ($callbacks as $priority => $callback_group) {
                foreach ($callback_group as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        strpos(get_class($callback['function'][0]), 'Zlaark') !== false) {
                        $zlaark_callbacks++;
                    }
                }
            }
            
            $hook_status[$hook] = [
                'total_callbacks' => count($callbacks),
                'zlaark_callbacks' => $zlaark_callbacks
            ];
        }
        
        return $hook_status;
    }
    
    public static function display_results($results) {
        echo "<div style='font-family: monospace; background: #f0f0f0; padding: 20px; margin: 20px;'>";
        echo "<h2>Zlaark Subscriptions System Diagnosis</h2>";
        
        foreach ($results as $category => $data) {
            echo "<h3>" . ucwords(str_replace('_', ' ', $category)) . "</h3>";
            echo "<pre>" . print_r($data, true) . "</pre>";
        }
        
        echo "</div>";
    }
}

// If running standalone, execute diagnosis
if (!defined('ABSPATH') || (defined('WP_CLI') && WP_CLI)) {
    $results = ZlaarkSubscriptionsDiagnosis::run_diagnosis();
    ZlaarkSubscriptionsDiagnosis::display_results($results);
}

// Add admin page for diagnosis
if (is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            'Subscription System Diagnosis',
            'Subscription Diagnosis',
            'manage_options',
            'subscription-diagnosis',
            function() {
                $results = ZlaarkSubscriptionsDiagnosis::run_diagnosis();
                ZlaarkSubscriptionsDiagnosis::display_results($results);
            }
        );
    });
}
