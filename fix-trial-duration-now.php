<?php
/**
 * Immediate Fix for Trial Duration Issue
 * 
 * This script immediately fixes the trial duration issue for product ID 3425
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for immediate fix
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Fix Trial Duration Now',
        'Fix Trial Duration Now',
        'manage_options',
        'fix-trial-duration-now',
        'zlaark_fix_trial_duration_now_page'
    );
});

function zlaark_fix_trial_duration_now_page() {
    $product_id = 3425; // Your specific product ID
    $fixed = false;
    $error_message = '';
    
    // Handle the fix action
    if (isset($_POST['apply_fix']) && wp_verify_nonce($_POST['fix_nonce'], 'fix_trial_duration')) {
        try {
            // Method 1: Direct meta update
            $result1 = update_post_meta($product_id, '_subscription_trial_duration', 7);
            
            // Method 2: Using product object
            $product = wc_get_product($product_id);
            if ($product && method_exists($product, 'set_trial_duration')) {
                $product->set_trial_duration(7);
                $product->save();
            }
            
            // Method 3: Force database update
            global $wpdb;
            $wpdb->query($wpdb->prepare("
                INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) 
                VALUES (%d, '_subscription_trial_duration', '7')
                ON DUPLICATE KEY UPDATE meta_value = '7'
            ", $product_id));
            
            // Clear all caches
            wp_cache_delete('wc_product_' . $product_id, 'products');
            wp_cache_flush();
            
            $fixed = true;
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
    
    ?>
    <div class="wrap">
        <h1>🚀 Fix Trial Duration Now</h1>
        
        <?php if ($fixed): ?>
        <div class="notice notice-success">
            <p><strong>✅ SUCCESS!</strong> Trial duration has been fixed for product ID <?php echo $product_id; ?>.</p>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="notice notice-error">
            <p><strong>❌ ERROR:</strong> <?php echo esc_html($error_message); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎯 Current Status Check</h2>
            
            <?php
            // Check current status
            $product = wc_get_product($product_id);
            if ($product) {
                echo '<p><strong>Product:</strong> ' . esc_html($product->get_name()) . '</p>';
                echo '<p><strong>Product Class:</strong> ' . get_class($product) . '</p>';
                echo '<p><strong>Product Type:</strong> ' . $product->get_type() . '</p>';
                
                // Check meta data
                $duration_meta = get_post_meta($product_id, '_subscription_trial_duration', true);
                $price_meta = get_post_meta($product_id, '_subscription_trial_price', true);
                
                echo '<p><strong>Trial Duration (meta):</strong> ' . var_export($duration_meta, true) . ' (Type: ' . gettype($duration_meta) . ')</p>';
                echo '<p><strong>Trial Price (meta):</strong> ' . var_export($price_meta, true) . ' (Type: ' . gettype($price_meta) . ')</p>';
                
                // Check product methods
                if (method_exists($product, 'get_trial_duration')) {
                    $duration_method = $product->get_trial_duration();
                    echo '<p><strong>Trial Duration (method):</strong> ' . var_export($duration_method, true) . ' (Type: ' . gettype($duration_method) . ')</p>';
                }
                
                if (method_exists($product, 'get_trial_price')) {
                    $price_method = $product->get_trial_price();
                    echo '<p><strong>Trial Price (method):</strong> ' . var_export($price_method, true) . ' (Type: ' . gettype($price_method) . ')</p>';
                }
                
                if (method_exists($product, 'has_trial')) {
                    $has_trial = $product->has_trial();
                    echo '<p><strong>Has Trial:</strong> ' . ($has_trial ? '✅ Yes' : '❌ No') . '</p>';
                }
                
                // Test shortcode
                echo '<h3>🧪 Shortcode Test:</h3>';
                echo '<div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
                echo do_shortcode('[trial_button product_id="' . $product_id . '"]');
                echo '</div>';
                
            } else {
                echo '<p style="color: red;">❌ Product not found!</p>';
            }
            ?>
        </div>
        
        <?php if (!$fixed): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Apply Fix</h2>
            <p>This will set the trial duration to 7 days for your Weight Management Program product.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('fix_trial_duration', 'fix_nonce'); ?>
                <p>
                    <input type="submit" name="apply_fix" class="button button-primary button-large" value="🚀 Fix Trial Duration Now" onclick="return confirm('Are you sure you want to apply the fix?');">
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>🔍 What This Fix Does</h2>
            <ul>
                <li><strong>Sets Trial Duration:</strong> Updates _subscription_trial_duration to 7</li>
                <li><strong>Multiple Methods:</strong> Uses both WordPress meta API and WooCommerce product API</li>
                <li><strong>Database Backup:</strong> Direct database update as fallback</li>
                <li><strong>Cache Clearing:</strong> Clears all relevant caches</li>
                <li><strong>Immediate Effect:</strong> Changes take effect immediately</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>📋 After Applying Fix</h2>
            <p>Once the fix is applied, you should see:</p>
            <ul>
                <li>✅ Trial Duration (method): 7</li>
                <li>✅ Has Trial: Yes</li>
                <li>✅ Trial button showing "Start Trial - ₹99.00"</li>
                <li>✅ Shortcode working on your product page</li>
            </ul>
            
            <p><strong>Test your shortcodes:</strong></p>
            <ul>
                <li><code>[trial_button]</code> - Should show trial button</li>
                <li><code>[subscription_button]</code> - Should show subscription button</li>
            </ul>
        </div>
    </div>
    <?php
}
