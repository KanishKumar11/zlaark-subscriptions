<?php
/**
 * Debug Trial Duration Issue
 * 
 * This script investigates why trial duration is showing as 0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for debugging
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Trial Duration',
        'Debug Trial Duration',
        'manage_options',
        'debug-trial-duration',
        'zlaark_debug_trial_duration_page'
    );
});

function zlaark_debug_trial_duration_page() {
    // Get the specific product ID from the user's issue
    $product_id = 3425;
    
    ?>
    <div class="wrap">
        <h1>🔍 Trial Duration Debug Tool</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>📊 Product ID: <?php echo $product_id; ?> Investigation</h2>
            
            <?php
            // Get product using different methods
            $product = wc_get_product($product_id);
            
            echo '<h3>🔧 Product Information:</h3>';
            echo '<p><strong>Product exists:</strong> ' . ($product ? '✅ Yes' : '❌ No') . '</p>';
            
            if ($product) {
                echo '<p><strong>Product name:</strong> ' . esc_html($product->get_name()) . '</p>';
                echo '<p><strong>Product class:</strong> ' . get_class($product) . '</p>';
                echo '<p><strong>Product type:</strong> ' . $product->get_type() . '</p>';
                
                echo '<h3>🎯 Trial Methods Test:</h3>';
                echo '<p><strong>Has get_trial_duration method:</strong> ' . (method_exists($product, 'get_trial_duration') ? '✅ Yes' : '❌ No') . '</p>';
                echo '<p><strong>Has get_trial_price method:</strong> ' . (method_exists($product, 'get_trial_price') ? '✅ Yes' : '❌ No') . '</p>';
                echo '<p><strong>Has has_trial method:</strong> ' . (method_exists($product, 'has_trial') ? '✅ Yes' : '❌ No') . '</p>';
                
                if (method_exists($product, 'get_trial_duration')) {
                    $trial_duration = $product->get_trial_duration();
                    echo '<p><strong>Trial duration result:</strong> ' . var_export($trial_duration, true) . ' (Type: ' . gettype($trial_duration) . ')</p>';
                }
                
                if (method_exists($product, 'get_trial_price')) {
                    $trial_price = $product->get_trial_price();
                    echo '<p><strong>Trial price result:</strong> ' . var_export($trial_price, true) . ' (Type: ' . gettype($trial_price) . ')</p>';
                }
                
                if (method_exists($product, 'has_trial')) {
                    $has_trial = $product->has_trial();
                    echo '<p><strong>Has trial result:</strong> ' . ($has_trial ? '✅ Yes' : '❌ No') . '</p>';
                }
                
                echo '<h3>🗄️ Direct Database Meta Query:</h3>';
                
                // Check meta data directly from database
                global $wpdb;
                $meta_results = $wpdb->get_results($wpdb->prepare("
                    SELECT meta_key, meta_value 
                    FROM {$wpdb->postmeta} 
                    WHERE post_id = %d 
                    AND meta_key LIKE '_subscription_%'
                    ORDER BY meta_key
                ", $product_id));
                
                if ($meta_results) {
                    echo '<table class="widefat">';
                    echo '<thead><tr><th>Meta Key</th><th>Meta Value</th><th>Type</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($meta_results as $meta) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($meta->meta_key) . '</strong></td>';
                        echo '<td>' . esc_html(var_export($meta->meta_value, true)) . '</td>';
                        echo '<td>' . gettype($meta->meta_value) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p style="color: red;">❌ No subscription meta data found in database!</p>';
                }
                
                echo '<h3>🔍 WordPress get_post_meta() Test:</h3>';
                $meta_keys = [
                    '_subscription_trial_duration',
                    '_subscription_trial_price',
                    '_subscription_trial_period',
                    '_subscription_recurring_price',
                    '_subscription_billing_interval'
                ];
                
                echo '<table class="widefat">';
                echo '<thead><tr><th>Meta Key</th><th>get_post_meta() Result</th><th>Type</th></tr></thead>';
                echo '<tbody>';
                foreach ($meta_keys as $key) {
                    $value = get_post_meta($product_id, $key, true);
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($key) . '</strong></td>';
                    echo '<td>' . esc_html(var_export($value, true)) . '</td>';
                    echo '<td>' . gettype($value) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                
                echo '<h3>🧪 Product get_meta() Test:</h3>';
                echo '<table class="widefat">';
                echo '<thead><tr><th>Meta Key</th><th>Product get_meta() Result</th><th>Type</th></tr></thead>';
                echo '<tbody>';
                foreach ($meta_keys as $key) {
                    $value = $product->get_meta($key, true);
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($key) . '</strong></td>';
                    echo '<td>' . esc_html(var_export($value, true)) . '</td>';
                    echo '<td>' . gettype($value) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                
                echo '<h3>🔄 Cache Test:</h3>';
                echo '<p>Clearing product cache and reloading...</p>';
                
                // Clear cache and reload
                wp_cache_delete('wc_product_' . $product_id, 'products');
                $reloaded_product = wc_get_product($product_id);
                
                echo '<p><strong>Reloaded product class:</strong> ' . get_class($reloaded_product) . '</p>';
                if (method_exists($reloaded_product, 'get_trial_duration')) {
                    $reloaded_duration = $reloaded_product->get_trial_duration();
                    echo '<p><strong>Reloaded trial duration:</strong> ' . var_export($reloaded_duration, true) . '</p>';
                }
                
                echo '<h3>🛠️ Manual Fix Test:</h3>';
                echo '<p>Testing manual meta data update...</p>';
                
                // Try to manually set the trial duration
                $manual_update_result = update_post_meta($product_id, '_subscription_trial_duration', 7);
                echo '<p><strong>Manual update result:</strong> ' . ($manual_update_result ? '✅ Success' : '❌ Failed') . '</p>';
                
                // Check if it worked
                $updated_value = get_post_meta($product_id, '_subscription_trial_duration', true);
                echo '<p><strong>Value after manual update:</strong> ' . var_export($updated_value, true) . '</p>';
                
                // Test with reloaded product
                wp_cache_delete('wc_product_' . $product_id, 'products');
                $test_product = wc_get_product($product_id);
                if (method_exists($test_product, 'get_trial_duration')) {
                    $test_duration = $test_product->get_trial_duration();
                    echo '<p><strong>Product method after manual update:</strong> ' . var_export($test_duration, true) . '</p>';
                    echo '<p><strong>Has trial after manual update:</strong> ' . ($test_product->has_trial() ? '✅ Yes' : '❌ No') . '</p>';
                }
                
            } else {
                echo '<p style="color: red;">❌ Product not found!</p>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Quick Fix Actions</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('fix_trial_duration', 'fix_trial_duration_nonce'); ?>
                
                <p>
                    <label for="trial_duration_value">Set Trial Duration:</label>
                    <input type="number" name="trial_duration_value" id="trial_duration_value" value="7" min="1" max="365">
                    <input type="submit" name="fix_trial_duration" class="button button-primary" value="Fix Trial Duration">
                </p>
                
                <p>
                    <input type="submit" name="clear_all_cache" class="button" value="Clear All Product Cache">
                </p>
            </form>
            
            <?php
            // Handle form submissions
            if (isset($_POST['fix_trial_duration']) && wp_verify_nonce($_POST['fix_trial_duration_nonce'], 'fix_trial_duration')) {
                $duration_value = intval($_POST['trial_duration_value']);
                
                // Update using multiple methods to ensure it sticks
                update_post_meta($product_id, '_subscription_trial_duration', $duration_value);
                
                // Also try using the product object
                $fix_product = wc_get_product($product_id);
                if ($fix_product && method_exists($fix_product, 'set_trial_duration')) {
                    $fix_product->set_trial_duration($duration_value);
                    $fix_product->save();
                }
                
                // Clear cache
                wp_cache_delete('wc_product_' . $product_id, 'products');
                
                echo '<div class="notice notice-success"><p>✅ Trial duration updated to ' . $duration_value . ' and cache cleared!</p></div>';
            }
            
            if (isset($_POST['clear_all_cache'])) {
                wp_cache_flush();
                echo '<div class="notice notice-success"><p>✅ All cache cleared!</p></div>';
            }
            ?>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>💡 Diagnostic Summary</h2>
            <p>This tool checks:</p>
            <ul>
                <li><strong>Product Loading:</strong> Whether the product loads correctly as WC_Product_Subscription</li>
                <li><strong>Method Availability:</strong> Whether trial-related methods exist</li>
                <li><strong>Database Values:</strong> Raw meta data from the database</li>
                <li><strong>WordPress Meta API:</strong> get_post_meta() results</li>
                <li><strong>Product Meta API:</strong> Product get_meta() results</li>
                <li><strong>Cache Issues:</strong> Whether clearing cache fixes the problem</li>
                <li><strong>Manual Fix:</strong> Whether manually setting the value works</li>
            </ul>
        </div>
    </div>
    
    <style>
    .wrap table.widefat td, .wrap table.widefat th {
        padding: 8px 12px;
        border-bottom: 1px solid #ddd;
    }
    .wrap table.widefat th {
        background: #f1f1f1;
        font-weight: bold;
    }
    </style>
    <?php
}
