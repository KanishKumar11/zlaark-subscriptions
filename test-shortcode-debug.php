<?php
/**
 * Debug Script for Shortcode Issues
 * 
 * This script helps debug the trial and subscription button shortcode issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for debugging
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Shortcodes',
        'Debug Shortcodes',
        'manage_options',
        'debug-shortcodes',
        'zlaark_debug_shortcodes_page'
    );
});

function zlaark_debug_shortcodes_page() {
    // Get a subscription product for testing
    $subscription_products = get_posts([
        'post_type' => 'product',
        'meta_query' => [
            [
                'key' => '_product_type',
                'value' => 'subscription'
            ]
        ],
        'posts_per_page' => 5
    ]);
    
    ?>
    <div class="wrap">
        <h1>🔍 Shortcode Debug Tool</h1>
        
        <div class="notice notice-info">
            <p><strong>Debug Information:</strong> This tool helps diagnose issues with trial and subscription button shortcodes.</p>
        </div>
        
        <?php if (!empty($subscription_products)): ?>
            <?php foreach ($subscription_products as $post_product): ?>
                <?php 
                $product = wc_get_product($post_product->ID);
                if (!$product) continue;
                ?>
                
                <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                    <h2>🧪 Product: <?php echo esc_html($product->get_name()); ?> (ID: <?php echo $product->get_id(); ?>)</h2>
                    
                    <h3>📊 Product Information:</h3>
                    <table class="widefat">
                        <tr>
                            <td><strong>Product Type:</strong></td>
                            <td><?php echo esc_html($product->get_type()); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Has Trial Method:</strong></td>
                            <td><?php echo method_exists($product, 'has_trial') ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                        <?php if (method_exists($product, 'has_trial')): ?>
                        <tr>
                            <td><strong>Has Trial:</strong></td>
                            <td><?php echo $product->has_trial() ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Trial Duration:</strong></td>
                            <td><?php echo method_exists($product, 'get_trial_duration') ? $product->get_trial_duration() : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Trial Price:</strong></td>
                            <td><?php echo method_exists($product, 'get_trial_price') ? wc_price($product->get_trial_price()) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Trial Period:</strong></td>
                            <td><?php echo method_exists($product, 'get_trial_period') ? $product->get_trial_period() : 'N/A'; ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Recurring Price:</strong></td>
                            <td><?php echo method_exists($product, 'get_recurring_price') ? wc_price($product->get_recurring_price()) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Billing Interval:</strong></td>
                            <td><?php echo method_exists($product, 'get_billing_interval') ? $product->get_billing_interval() : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Is Purchasable:</strong></td>
                            <td><?php echo $product->is_purchasable() ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Is In Stock:</strong></td>
                            <td><?php echo $product->is_in_stock() ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                    </table>
                    
                    <h3>🎯 Trial Button Test:</h3>
                    <div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px;">
                        <p><strong>Shortcode:</strong> <code>[trial_button product_id="<?php echo $product->get_id(); ?>"]</code></p>
                        <p><strong>Output:</strong></p>
                        <div style="border: 1px solid #ddd; padding: 10px; background: #fff;">
                            <?php echo do_shortcode('[trial_button product_id="' . $product->get_id() . '"]'); ?>
                        </div>
                    </div>
                    
                    <h3>🚀 Subscription Button Test:</h3>
                    <div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px;">
                        <p><strong>Shortcode:</strong> <code>[subscription_button product_id="<?php echo $product->get_id(); ?>"]</code></p>
                        <p><strong>Output:</strong></p>
                        <div style="border: 1px solid #ddd; padding: 10px; background: #fff;">
                            <?php echo do_shortcode('[subscription_button product_id="' . $product->get_id() . '"]'); ?>
                        </div>
                    </div>
                    
                    <h3>🔧 Raw Meta Data:</h3>
                    <div style="background: #f0f0f0; padding: 10px; font-family: monospace; font-size: 12px; overflow-x: auto;">
                        <?php
                        $meta_keys = [
                            '_subscription_trial_price',
                            '_subscription_trial_duration', 
                            '_subscription_trial_period',
                            '_subscription_recurring_price',
                            '_subscription_billing_interval',
                            '_subscription_max_length',
                            '_subscription_signup_fee',
                            '_product_type'
                        ];
                        
                        foreach ($meta_keys as $key) {
                            $value = get_post_meta($product->get_id(), $key, true);
                            echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html(var_export($value, true)) . '<br>';
                        }
                        ?>
                    </div>
                    
                    <?php if (class_exists('ZlaarkSubscriptionsTrialService')): ?>
                    <h3>🎪 Trial Service Test:</h3>
                    <div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px;">
                        <?php
                        try {
                            $trial_service = ZlaarkSubscriptionsTrialService::instance();
                            $user_id = get_current_user_id();
                            $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product->get_id());
                            
                            echo '<p><strong>Trial Eligibility Check:</strong></p>';
                            echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">';
                            echo esc_html(json_encode($trial_eligibility, JSON_PRETTY_PRINT));
                            echo '</pre>';
                        } catch (Exception $e) {
                            echo '<p style="color: red;"><strong>Trial Service Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
                        }
                        ?>
                    </div>
                    <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><strong>Warning:</strong> ZlaarkSubscriptionsTrialService class not found!</p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
        <div class="notice notice-warning">
            <p><strong>No subscription products found!</strong> Please create a subscription product first.</p>
            <p><a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">Create Subscription Product</a></p>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 Global Context Test</h2>
            <p>Testing shortcodes without product_id parameter (should auto-detect from context):</p>
            
            <h3>Without Product Context:</h3>
            <div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px;">
                <p><strong>Trial Button:</strong> <?php echo do_shortcode('[trial_button]'); ?></p>
                <p><strong>Subscription Button:</strong> <?php echo do_shortcode('[subscription_button]'); ?></p>
            </div>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>💡 Troubleshooting Tips</h2>
            <ul>
                <li><strong>Trial Button Shows "No Trial":</strong> Check if trial duration > 0 and trial service is working</li>
                <li><strong>HTML Shows as Text:</strong> Check for WordPress content filtering or theme conflicts</li>
                <li><strong>Buttons Don't Work:</strong> Verify WooCommerce cart functionality and nonce validation</li>
                <li><strong>Context Issues:</strong> Make sure shortcodes are used on product pages or with explicit product_id</li>
            </ul>
        </div>
    </div>
    
    <style>
    .wrap table.widefat td {
        padding: 8px 12px;
        border-bottom: 1px solid #ddd;
    }
    .wrap .notice.inline {
        display: inline-block;
        margin: 10px 0;
        padding: 10px 15px;
    }
    </style>
    <?php
}
