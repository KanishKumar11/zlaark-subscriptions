<?php
/**
 * Menu Access Verification Script
 * 
 * This script helps diagnose menu access issues and provides
 * multiple ways to access the dual button test page.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin notice with access links
add_action('admin_notices', function() {
    if (!current_user_can('read')) {
        return;
    }
    
    // Only show on relevant admin pages
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->base, ['dashboard', 'plugins', 'tools_page_test-dual-button-display'])) {
        return;
    }
    
    ?>
    <div class="notice notice-info is-dismissible">
        <h3>🔧 Zlaark Subscriptions - Dual Button Test Access</h3>
        <p>Multiple ways to access the dual button test page:</p>
        <p>
            <?php if (current_user_can('manage_woocommerce')): ?>
                <a href="<?php echo admin_url('admin.php?page=test-dual-button-display'); ?>" class="button button-primary">📊 Main Test Page</a>
            <?php endif; ?>
            
            <a href="<?php echo admin_url('tools.php?page=test-dual-button-display'); ?>" class="button button-secondary">🛠️ Tools Menu</a>
            
            <a href="<?php echo admin_url('admin.php?zlaark_test_buttons=1'); ?>" class="button button-secondary">🚀 Direct Access</a>
            
            <a href="<?php echo admin_url('admin.php?page=zlaark-menu-debug'); ?>" class="button button-secondary">🔍 Menu Debug</a>
        </p>
        <p><small><strong>Current User Capabilities:</strong> 
            read: <?php echo current_user_can('read') ? '✅' : '❌'; ?> | 
            manage_options: <?php echo current_user_can('manage_options') ? '✅' : '❌'; ?> | 
            manage_woocommerce: <?php echo current_user_can('manage_woocommerce') ? '✅' : '❌'; ?>
        </small></p>
    </div>
    <?php
});

// Add menu debug page
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Zlaark Menu Debug',
        'Zlaark Menu Debug',
        'read',
        'zlaark-menu-debug',
        'zlaark_menu_debug_page'
    );
});

function zlaark_menu_debug_page() {
    if (!current_user_can('read')) {
        wp_die(__('Sorry, you are not allowed to access this page.'));
    }
    
    global $menu, $submenu;
    
    ?>
    <div class="wrap">
        <h1>🔍 Zlaark Menu Debug Information</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>User Information</h2>
            <table class="widefat">
                <tr><td><strong>User ID:</strong></td><td><?php echo get_current_user_id(); ?></td></tr>
                <tr><td><strong>Display Name:</strong></td><td><?php echo wp_get_current_user()->display_name; ?></td></tr>
                <tr><td><strong>User Login:</strong></td><td><?php echo wp_get_current_user()->user_login; ?></td></tr>
                <tr><td><strong>User Roles:</strong></td><td><?php echo implode(', ', wp_get_current_user()->roles); ?></td></tr>
            </table>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Capability Check</h2>
            <table class="widefat">
                <?php
                $capabilities = [
                    'read', 'edit_posts', 'manage_options', 'manage_woocommerce', 
                    'edit_products', 'manage_product_terms', 'edit_shop_orders'
                ];
                
                foreach ($capabilities as $cap) {
                    $has_cap = current_user_can($cap);
                    echo '<tr><td><strong>' . $cap . ':</strong></td><td>' . ($has_cap ? '✅ Yes' : '❌ No') . '</td></tr>';
                }
                ?>
            </table>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Menu Structure</h2>
            <h3>Main Menu Items</h3>
            <ul>
                <?php
                if (isset($menu) && is_array($menu)) {
                    foreach ($menu as $item) {
                        if (is_array($item) && isset($item[2])) {
                            echo '<li><strong>' . esc_html($item[2]) . '</strong> - ' . esc_html($item[0]) . '</li>';
                        }
                    }
                } else {
                    echo '<li>No menu items found</li>';
                }
                ?>
            </ul>
            
            <h3>Zlaark Subscriptions Submenu</h3>
            <?php if (isset($submenu['zlaark-subscriptions'])): ?>
                <ul>
                    <?php foreach ($submenu['zlaark-subscriptions'] as $item): ?>
                        <li><strong><?php echo esc_html($item[2]); ?></strong> - <?php echo esc_html($item[0]); ?> (<?php echo esc_html($item[1]); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: red;">❌ Zlaark Subscriptions submenu not found</p>
            <?php endif; ?>
            
            <h3>Tools Submenu</h3>
            <?php if (isset($submenu['tools.php'])): ?>
                <ul>
                    <?php foreach ($submenu['tools.php'] as $item): ?>
                        <?php if (strpos($item[2], 'test-dual-button') !== false || strpos($item[2], 'zlaark') !== false): ?>
                            <li style="background: #d4edda; padding: 5px;"><strong><?php echo esc_html($item[2]); ?></strong> - <?php echo esc_html($item[0]); ?> (<?php echo esc_html($item[1]); ?>)</li>
                        <?php else: ?>
                            <li><strong><?php echo esc_html($item[2]); ?></strong> - <?php echo esc_html($item[0]); ?> (<?php echo esc_html($item[1]); ?>)</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: red;">❌ Tools submenu not found</p>
            <?php endif; ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Plugin Status</h2>
            <table class="widefat">
                <tr><td><strong>Zlaark Subscriptions Active:</strong></td><td><?php echo class_exists('ZlaarkSubscriptions') ? '✅ Yes' : '❌ No'; ?></td></tr>
                <tr><td><strong>WooCommerce Active:</strong></td><td><?php echo class_exists('WooCommerce') ? '✅ Yes' : '❌ No'; ?></td></tr>
                <tr><td><strong>Admin Class Exists:</strong></td><td><?php echo class_exists('ZlaarkSubscriptionsAdmin') ? '✅ Yes' : '❌ No'; ?></td></tr>
                <tr><td><strong>Plugin Directory:</strong></td><td><?php echo defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR') ? ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR : 'Not defined'; ?></td></tr>
            </table>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>Quick Access Links</h2>
            <p>Try these different access methods:</p>
            <p>
                <a href="<?php echo admin_url('tools.php?page=test-dual-button-display'); ?>" class="button button-primary">🛠️ Tools Menu Access</a>
                <a href="<?php echo admin_url('admin.php?zlaark_test_buttons=1'); ?>" class="button button-secondary">🚀 Direct Access</a>
                <?php if (current_user_can('manage_woocommerce')): ?>
                    <a href="<?php echo admin_url('admin.php?page=test-dual-button-display'); ?>" class="button button-secondary">📊 Main Menu Access</a>
                <?php endif; ?>
            </p>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 4px;">
            <h2>🔧 Troubleshooting Steps</h2>
            <ol>
                <li><strong>Check User Role:</strong> Ensure you have at least 'Editor' or 'Administrator' role</li>
                <li><strong>Plugin Activation:</strong> Verify Zlaark Subscriptions plugin is active</li>
                <li><strong>WooCommerce:</strong> Ensure WooCommerce is installed and active</li>
                <li><strong>Clear Cache:</strong> Clear any caching plugins</li>
                <li><strong>Try Different Access:</strong> Use the Tools menu or Direct Access links above</li>
            </ol>
        </div>
    </div>
    <?php
}

// Add quick access via admin bar
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!current_user_can('read')) {
        return;
    }
    
    $wp_admin_bar->add_node(array(
        'id'    => 'zlaark-button-test',
        'title' => '🧪 Button Test',
        'href'  => admin_url('tools.php?page=test-dual-button-display'),
        'meta'  => array(
            'title' => 'Zlaark Dual Button Test'
        )
    ));
}, 100);

// Add dashboard widget for easy access
add_action('wp_dashboard_setup', function() {
    if (current_user_can('read')) {
        wp_add_dashboard_widget(
            'zlaark_button_test_widget',
            '🧪 Zlaark Button Test',
            function() {
                ?>
                <p>Quick access to dual button testing and diagnostics:</p>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=test-dual-button-display'); ?>" class="button button-primary">🛠️ Test Page</a>
                    <a href="<?php echo admin_url('admin.php?page=zlaark-menu-debug'); ?>" class="button button-secondary">🔍 Debug Info</a>
                </p>
                <p><small>User capabilities: 
                    <?php echo current_user_can('manage_woocommerce') ? 'Full Access ✅' : 'Limited Access ⚠️'; ?>
                </small></p>
                <?php
            }
        );
    }
});
