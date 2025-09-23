<?php
/**
 * Test the shortcode fixes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for testing
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Test Shortcode Fixes',
        'Test Shortcode Fixes',
        'manage_options',
        'test-shortcode-fixes',
        'zlaark_test_shortcode_fixes_page'
    );
});

function zlaark_test_shortcode_fixes_page() {
    // Get a subscription product for testing
    $subscription_products = get_posts([
        'post_type' => 'product',
        'meta_query' => [
            [
                'key' => '_product_type',
                'value' => 'subscription'
            ]
        ],
        'posts_per_page' => 1
    ]);
    
    ?>
    <div class="wrap">
        <h1>🔧 Shortcode Fixes Test</h1>
        
        <?php if (!empty($subscription_products)): ?>
            <?php 
            $product_id = $subscription_products[0]->ID;
            $product = wc_get_product($product_id);
            ?>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <h2>📊 Product Information</h2>
                <p><strong>Product:</strong> <?php echo esc_html($product->get_name()); ?> (ID: <?php echo $product_id; ?>)</p>
                <p><strong>Product Class:</strong> <?php echo get_class($product); ?></p>
                <p><strong>Product Type:</strong> <?php echo $product->get_type(); ?></p>
                
                <?php
                // Force registration
                if (class_exists('ZlaarkSubscriptionsProductType')) {
                    $registration_result = ZlaarkSubscriptionsProductType::force_registration_for_diagnostics();
                    echo '<p><strong>Force Registration Result:</strong> ' . ($registration_result ? 'Success' : 'Failed') . '</p>';
                }
                
                // Reload product after registration
                wp_cache_delete('wc_product_' . $product_id, 'products');
                $reloaded_product = wc_get_product($product_id);
                ?>
                
                <p><strong>Reloaded Product Class:</strong> <?php echo get_class($reloaded_product); ?></p>
                <p><strong>Has Trial Method:</strong> <?php echo method_exists($reloaded_product, 'has_trial') ? '✅ Yes' : '❌ No'; ?></p>
                
                <?php if (method_exists($reloaded_product, 'has_trial')): ?>
                    <p><strong>Has Trial:</strong> <?php echo $reloaded_product->has_trial() ? '✅ Yes' : '❌ No'; ?></p>
                    <p><strong>Trial Duration:</strong> <?php echo $reloaded_product->get_trial_duration(); ?></p>
                    <p><strong>Trial Price:</strong> <?php echo wc_price($reloaded_product->get_trial_price()); ?></p>
                <?php endif; ?>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <h2>🎯 Trial Button Test</h2>
                <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                    <?php echo do_shortcode('[trial_button product_id="' . $product_id . '"]'); ?>
                </div>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <h2>🚀 Subscription Button Test</h2>
                <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                    <?php echo do_shortcode('[subscription_button product_id="' . $product_id . '"]'); ?>
                </div>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <h2>🔍 Raw Meta Data</h2>
                <table class="widefat">
                    <?php
                    $meta_keys = [
                        '_subscription_trial_price',
                        '_subscription_trial_duration', 
                        '_subscription_trial_period',
                        '_subscription_recurring_price',
                        '_subscription_billing_interval',
                        '_product_type'
                    ];
                    
                    foreach ($meta_keys as $key) {
                        $value = get_post_meta($product_id, $key, true);
                        echo '<tr><td><strong>' . esc_html($key) . ':</strong></td><td>' . esc_html(var_export($value, true)) . '</td></tr>';
                    }
                    ?>
                </table>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <h2>🎪 Product Type Registration Check</h2>
                <?php
                $product_types = wc_get_product_types();
                echo '<p><strong>Available Product Types:</strong></p>';
                echo '<ul>';
                foreach ($product_types as $type => $label) {
                    echo '<li>' . esc_html($type) . ' - ' . esc_html($label);
                    if ($type === 'subscription') {
                        echo ' ✅';
                    }
                    echo '</li>';
                }
                echo '</ul>';
                
                echo '<p><strong>Subscription Type Registered:</strong> ' . (isset($product_types['subscription']) ? '✅ Yes' : '❌ No') . '</p>';
                ?>
            </div>
            
        <?php else: ?>
        <div class="notice notice-warning">
            <p><strong>No subscription products found!</strong> Please create a subscription product first.</p>
        </div>
        <?php endif; ?>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 Expected Results</h2>
            <ul>
                <li><strong>Product Class:</strong> Should be "WC_Product_Subscription"</li>
                <li><strong>Has Trial Method:</strong> Should be "Yes"</li>
                <li><strong>Trial Button:</strong> Should show trial offer or eligibility message</li>
                <li><strong>Subscription Button:</strong> Should render as functional button with clean price text</li>
                <li><strong>Subscription Type:</strong> Should be registered in product types</li>
            </ul>
        </div>
    </div>
    
    <style>
    .wrap table.widefat td {
        padding: 8px 12px;
        border-bottom: 1px solid #ddd;
    }
    </style>
    <?php
}
