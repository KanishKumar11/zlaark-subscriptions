<?php
/**
 * Basic plugin structure tests
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin structure test class
 */
class ZlaarkSubscriptionsStructureTest {
    
    /**
     * Run all tests
     */
    public static function run_tests() {
        $tests = array(
            'test_plugin_constants',
            'test_required_files',
            'test_class_existence',
            'test_database_tables',
            'test_hooks_registered'
        );
        
        $results = array();
        
        foreach ($tests as $test) {
            $results[$test] = self::$test();
        }
        
        return $results;
    }
    
    /**
     * Test plugin constants
     */
    private static function test_plugin_constants() {
        $constants = array(
            'ZLAARK_SUBSCRIPTIONS_VERSION',
            'ZLAARK_SUBSCRIPTIONS_PLUGIN_FILE',
            'ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR',
            'ZLAARK_SUBSCRIPTIONS_PLUGIN_URL',
            'ZLAARK_SUBSCRIPTIONS_PLUGIN_BASENAME'
        );
        
        $missing = array();
        
        foreach ($constants as $constant) {
            if (!defined($constant)) {
                $missing[] = $constant;
            }
        }
        
        return array(
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All constants defined' : 'Missing constants: ' . implode(', ', $missing)
        );
    }
    
    /**
     * Test required files exist
     */
    private static function test_required_files() {
        $files = array(
            'includes/class-zlaark-subscriptions-install.php',
            'includes/class-zlaark-subscriptions-database.php',
            'includes/class-wc-product-subscription.php',
            'includes/class-zlaark-subscriptions-product-type.php',
            'includes/class-zlaark-subscriptions-manager.php',
            'includes/class-zlaark-subscriptions-cron.php',
            'includes/class-zlaark-subscriptions-emails.php',
            'includes/class-zlaark-subscriptions-webhooks.php',
            'includes/gateways/class-zlaark-razorpay-gateway.php',
            'includes/admin/class-zlaark-subscriptions-admin.php',
            'includes/admin/class-zlaark-subscriptions-admin-list.php',
            'includes/frontend/class-zlaark-subscriptions-frontend.php',
            'includes/frontend/class-zlaark-subscriptions-my-account.php'
        );
        
        $missing = array();
        
        foreach ($files as $file) {
            $full_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . $file;
            if (!file_exists($full_path)) {
                $missing[] = $file;
            }
        }
        
        return array(
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All required files exist' : 'Missing files: ' . implode(', ', $missing)
        );
    }
    
    /**
     * Test class existence
     */
    private static function test_class_existence() {
        $classes = array(
            'ZlaarkSubscriptions',
            'ZlaarkSubscriptionsInstall',
            'ZlaarkSubscriptionsDatabase',
            'WC_Product_Subscription',
            'ZlaarkSubscriptionsProductType',
            'ZlaarkSubscriptionsManager',
            'ZlaarkSubscriptionsCron',
            'ZlaarkSubscriptionsEmails',
            'ZlaarkSubscriptionsWebhooks',
            'ZlaarkRazorpayGateway'
        );
        
        $missing = array();
        
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                $missing[] = $class;
            }
        }
        
        return array(
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All required classes exist' : 'Missing classes: ' . implode(', ', $missing)
        );
    }
    
    /**
     * Test database tables
     */
    private static function test_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'zlaark_subscription_orders',
            $wpdb->prefix . 'zlaark_subscription_payments',
            $wpdb->prefix . 'zlaark_webhook_logs'
        );
        
        $missing = array();
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($result !== $table) {
                $missing[] = $table;
            }
        }
        
        return array(
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All database tables exist' : 'Missing tables: ' . implode(', ', $missing)
        );
    }
    
    /**
     * Test hooks registered
     */
    private static function test_hooks_registered() {
        $hooks = array(
            'plugins_loaded' => 'ZlaarkSubscriptions',
            'init' => 'ZlaarkSubscriptions',
            'woocommerce_loaded' => 'ZlaarkSubscriptions'
        );
        
        $missing = array();
        
        foreach ($hooks as $hook => $class) {
            if (!has_action($hook)) {
                $missing[] = $hook;
            }
        }
        
        return array(
            'passed' => empty($missing),
            'message' => empty($missing) ? 'All required hooks registered' : 'Missing hooks: ' . implode(', ', $missing)
        );
    }
    
    /**
     * Display test results
     */
    public static function display_results($results) {
        echo '<div class="wrap">';
        echo '<h1>Zlaark Subscriptions - Structure Tests</h1>';
        
        $total_tests = count($results);
        $passed_tests = 0;
        
        foreach ($results as $test_name => $result) {
            if ($result['passed']) {
                $passed_tests++;
            }
        }
        
        echo '<div class="notice notice-' . ($passed_tests === $total_tests ? 'success' : 'warning') . '">';
        echo '<p><strong>Test Results: ' . $passed_tests . '/' . $total_tests . ' tests passed</strong></p>';
        echo '</div>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test</th><th>Status</th><th>Message</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($results as $test_name => $result) {
            $status_class = $result['passed'] ? 'success' : 'error';
            $status_text = $result['passed'] ? 'PASS' : 'FAIL';
            
            echo '<tr>';
            echo '<td>' . esc_html(str_replace('_', ' ', ucfirst($test_name))) . '</td>';
            echo '<td><span class="dashicons dashicons-' . ($result['passed'] ? 'yes' : 'no') . '"></span> ' . $status_text . '</td>';
            echo '<td>' . esc_html($result['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

// Add admin page for tests
if (is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'zlaark-subscriptions',
            'Structure Tests',
            'Tests',
            'manage_options',
            'zlaark-subscriptions-tests',
            function() {
                $results = ZlaarkSubscriptionsStructureTest::run_tests();
                ZlaarkSubscriptionsStructureTest::display_results($results);
            }
        );
    });
}
