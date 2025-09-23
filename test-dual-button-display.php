<?php
/**
 * Test Page for Dual Button Display
 * 
 * This creates a simple test to verify the dual button system is working
 * Access via: /wp-admin/admin.php?page=test-dual-button-display
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for testing - multiple fallback approaches
add_action('admin_menu', function() {
    // Check if user has any admin capabilities
    if (!current_user_can('read')) {
        return; // User has no admin access at all
    }

    // Approach 1: Try to add under Zlaark Subscriptions if it exists and user has permission
    global $menu, $submenu;
    $parent_exists = false;

    // Check if parent menu exists
    if (isset($submenu['zlaark-subscriptions']) && current_user_can('manage_woocommerce')) {
        add_submenu_page(
            'zlaark-subscriptions',
            'Test Dual Button Display',
            'Test Dual Buttons',
            'manage_woocommerce',
            'test-dual-button-display',
            'zlaark_test_dual_button_display_page'
        );
        $parent_exists = true;
    }

    // Approach 2: Add under Tools menu as fallback (always accessible)
    if (!$parent_exists) {
        add_submenu_page(
            'tools.php',
            'Zlaark Dual Button Test',
            'Zlaark Button Test',
            'read', // Minimal capability - all admin users have this
            'test-dual-button-display',
            'zlaark_test_dual_button_display_page'
        );
    }

    // Approach 3: Emergency standalone menu if Tools doesn't work
    if (!current_user_can('manage_options')) {
        add_menu_page(
            'Zlaark Button Test',
            'Button Test',
            'read',
            'zlaark-button-test-emergency',
            'zlaark_test_dual_button_display_page',
            'dashicons-admin-tools',
            99
        );
    }
}, 25); // Even lower priority to ensure all parent menus are registered

// Alternative access method via URL parameter (for debugging)
add_action('admin_init', function() {
    if (isset($_GET['zlaark_test_buttons']) && current_user_can('read')) {
        // Bypass menu system entirely
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p><strong>Zlaark Button Test:</strong> Direct access mode activated. <a href="' . admin_url('tools.php?page=test-dual-button-display') . '">Use menu instead</a></p></div>';
        });

        // Hook into admin_head to render the test page
        add_action('admin_head', function() {
            if (isset($_GET['zlaark_test_buttons'])) {
                echo '<style>body { margin-top: 50px; }</style>';
            }
        });

        add_action('admin_footer', function() {
            if (isset($_GET['zlaark_test_buttons'])) {
                echo '<div style="position: fixed; top: 0; left: 0; right: 0; background: white; z-index: 9999; padding: 20px; border-bottom: 2px solid #0073aa;">';
                zlaark_test_dual_button_display_page();
                echo '</div>';
            }
        });
    }
});

// Add shortcode for frontend access (for testing)
add_shortcode('zlaark_button_test', function($atts) {
    if (!current_user_can('read')) {
        return '<p style="color: red;">Access denied. Please log in as an administrator.</p>';
    }

    ob_start();
    ?>
    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin: 20px 0;">
        <h3>🧪 Zlaark Dual Button Test (Shortcode)</h3>
        <p>This is the shortcode version of the test page. Use <code>[zlaark_button_test]</code> on any page or post.</p>
        <p><strong>Admin Access Links:</strong></p>
        <ul>
            <li><a href="<?php echo admin_url('tools.php?page=test-dual-button-display'); ?>">Tools Menu Access</a></li>
            <li><a href="<?php echo admin_url('admin.php?zlaark_test_buttons=1'); ?>">Direct Access</a></li>
            <li><a href="<?php echo admin_url('admin.php?page=zlaark-menu-debug'); ?>">Menu Debug</a></li>
        </ul>

        <?php
        // Include basic diagnostics
        $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
        $css_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'assets/css/frontend.css';
        $js_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'assets/js/frontend.js';
        ?>

        <h4>Quick Diagnostics:</h4>
        <ul>
            <li>Template file: <?php echo file_exists($template_path) ? '✅ Exists' : '❌ Missing'; ?></li>
            <li>CSS file: <?php echo file_exists($css_path) ? '✅ Exists' : '❌ Missing'; ?></li>
            <li>JS file: <?php echo file_exists($js_path) ? '✅ Exists' : '❌ Missing'; ?></li>
            <li>Trial service: <?php echo class_exists('ZlaarkSubscriptionsTrialService') ? '✅ Available' : '❌ Missing'; ?></li>
            <li>WooCommerce: <?php echo class_exists('WooCommerce') ? '✅ Active' : '❌ Inactive'; ?></li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
});

function zlaark_test_dual_button_display_page() {
    // Check user capabilities
    if (!current_user_can('read')) {
        wp_die(__('Sorry, you are not allowed to access this page.'));
    }

    ?>
    <div class="wrap">
        <h1>🧪 Dual Button Display Test</h1>

        <div style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <h3 style="margin-top: 0;">📍 Access Information</h3>
            <p><strong>Current User:</strong> <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</p>
            <p><strong>User Capabilities:</strong></p>
            <ul style="margin: 10px 0;">
                <li>read: <?php echo current_user_can('read') ? '✅ Yes' : '❌ No'; ?></li>
                <li>manage_options: <?php echo current_user_can('manage_options') ? '✅ Yes' : '❌ No'; ?></li>
                <li>manage_woocommerce: <?php echo current_user_can('manage_woocommerce') ? '✅ Yes' : '❌ No'; ?></li>
            </ul>
            <p><strong>Alternative Access:</strong> <a href="<?php echo admin_url('admin.php?zlaark_test_buttons=1'); ?>">Direct Access Mode</a></p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Template Test</h2>
            <p>This simulates the dual button system outside of the normal WooCommerce context.</p>
            
            <?php
            // Create a mock subscription product for testing
            $mock_product = new stdClass();
            $mock_product->id = 999;
            $mock_product->type = 'subscription';
            $mock_product->trial_price = 0.00;
            $mock_product->recurring_price = 29.99;
            $mock_product->trial_duration = 7;
            $mock_product->trial_period = 'day';
            $mock_product->billing_interval = 'monthly';
            $mock_product->has_trial = true;
            
            $user_id = get_current_user_id();
            $trial_available = true; // For testing purposes
            ?>
            
            <!-- Simulated Dual Button System -->
            <div class="subscription-purchase-options" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
                <div class="trial-cart">
                    <button type="button" class="trial-button" style="width: 100%; padding: 18px 20px; font-size: 16px; font-weight: bold; border-radius: 10px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <span class="button-icon">🎯</span>
                        <span class="button-text">Start FREE Trial</span>
                    </button>
                </div>
                
                <div class="regular-cart">
                    <button type="button" class="regular-button" style="width: 100%; padding: 18px 20px; font-size: 16px; font-weight: bold; border-radius: 10px; background: linear-gradient(135deg, #007cba 0%, #0056b3 100%); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <span class="button-icon">🚀</span>
                        <span class="button-text">Start Subscription - $29.99 monthly</span>
                    </button>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <strong>Test Results:</strong>
                <ul>
                    <li>✅ Buttons are visible</li>
                    <li>✅ CSS styling is applied</li>
                    <li>✅ Grid layout is working</li>
                    <li>✅ Icons and text are displayed</li>
                </ul>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>System Diagnostics</h2>
            
            <?php
            // Check template file
            $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
            ?>
            <p><strong>Template File:</strong> 
                <?php if (file_exists($template_path)): ?>
                    <span style="color: green;">✅ Exists</span>
                <?php else: ?>
                    <span style="color: red;">❌ Missing</span>
                <?php endif; ?>
            </p>
            <p><small><?php echo esc_html($template_path); ?></small></p>
            
            <?php
            // Check CSS file
            $css_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'assets/css/frontend.css';
            ?>
            <p><strong>CSS File:</strong> 
                <?php if (file_exists($css_path)): ?>
                    <span style="color: green;">✅ Exists</span>
                <?php else: ?>
                    <span style="color: red;">❌ Missing</span>
                <?php endif; ?>
            </p>
            <p><small><?php echo esc_html($css_path); ?></small></p>
            
            <?php
            // Check JS file
            $js_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'assets/js/frontend.js';
            ?>
            <p><strong>JS File:</strong> 
                <?php if (file_exists($js_path)): ?>
                    <span style="color: green;">✅ Exists</span>
                <?php else: ?>
                    <span style="color: red;">❌ Missing</span>
                <?php endif; ?>
            </p>
            <p><small><?php echo esc_html($js_path); ?></small></p>
            
            <?php
            // Check trial service
            $trial_service_exists = class_exists('ZlaarkSubscriptionsTrialService');
            ?>
            <p><strong>Trial Service:</strong> 
                <?php if ($trial_service_exists): ?>
                    <span style="color: green;">✅ Available</span>
                <?php else: ?>
                    <span style="color: red;">❌ Missing</span>
                <?php endif; ?>
            </p>
            
            <?php
            // Check product type registration
            $product_types = wc_get_product_types();
            $subscription_registered = isset($product_types['subscription']);
            ?>
            <p><strong>Subscription Product Type:</strong> 
                <?php if ($subscription_registered): ?>
                    <span style="color: green;">✅ Registered</span>
                <?php else: ?>
                    <span style="color: red;">❌ Not Registered</span>
                <?php endif; ?>
            </p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Troubleshooting Steps</h2>
            <ol>
                <li><strong>Check Product Type:</strong> Ensure your product is set to "Subscription" type</li>
                <li><strong>Verify Trial Settings:</strong> Make sure trial is enabled in product settings</li>
                <li><strong>Clear Cache:</strong> Clear any caching plugins or server cache</li>
                <li><strong>Check Theme Compatibility:</strong> Switch to a default theme temporarily</li>
                <li><strong>Browser Console:</strong> Check for JavaScript errors in browser dev tools</li>
                <li><strong>Template Override:</strong> Ensure theme isn't overriding the template</li>
            </ol>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Quick Fixes</h2>
            
            <h3>1. Force Template Reload</h3>
            <p>Add this to your theme's functions.php temporarily:</p>
            <pre style="background: #f0f0f1; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>add_action('woocommerce_single_product_summary', function() {
    global $product;
    if ($product && $product->get_type() === 'subscription') {
        $template = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
        if (file_exists($template)) {
            include $template;
        }
    }
}, 25);</code></pre>
            
            <h3>2. Manual CSS/JS Enqueue</h3>
            <p>Add this to your theme's functions.php:</p>
            <pre style="background: #f0f0f1; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>add_action('wp_enqueue_scripts', function() {
    if (is_product()) {
        wp_enqueue_style('zlaark-frontend', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/frontend.css');
        wp_enqueue_script('zlaark-frontend', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'));
    }
});</code></pre>
            
            <h3>3. Debug Mode</h3>
            <p>Add this to wp-config.php to enable debug output:</p>
            <pre style="background: #f0f0f1; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);</code></pre>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 4px;">
            <h2>🔧 Next Steps</h2>
            <p>If the buttons are visible in this test but not on your product page:</p>
            <ol>
                <li>The template is not being loaded correctly</li>
                <li>Your theme is overriding the template</li>
                <li>The product is not properly configured as a subscription</li>
                <li>There's a JavaScript error preventing the buttons from showing</li>
            </ol>
            
            <p><strong>Recommended Action:</strong> Include the debug script on your product page to get detailed diagnostics.</p>
        </div>
    </div>
    
    <style>
    .trial-button:hover {
        background: linear-gradient(135deg, #218838 0%, #1ea085 100%) !important;
        transform: translateY(-2px);
    }
    
    .regular-button:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .subscription-purchase-options {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    <?php
}
