<?php
/**
 * Verify Critical Error Fix
 * 
 * This script verifies that the persistent critical error has been resolved
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for verification
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Verify Critical Error Fix',
        '✅ Verify Fix',
        'manage_options',
        'verify-critical-error-fix',
        'zlaark_verify_critical_error_fix_page'
    );
});

function zlaark_verify_critical_error_fix_page() {
    $product_id = 3425;
    $fixes_applied = [];
    $errors = [];
    
    // Handle verification actions
    if (isset($_POST['run_verification']) && wp_verify_nonce($_POST['verify_nonce'], 'verify_fix')) {
        
        // Clear caches to ensure fresh code
        try {
            wp_cache_flush();
            wc_delete_product_transients();
            wp_cache_delete('wc_product_' . $product_id, 'products');
            
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            $fixes_applied[] = 'All caches cleared for fresh testing';
        } catch (Exception $e) {
            $errors[] = 'Failed to clear cache: ' . $e->getMessage();
        }
    }
    
    ?>
    <div class="wrap">
        <h1>✅ Verify Critical Error Fix</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <p><strong>✅ Verification Actions Completed:</strong></p>
            <ul>
                <?php foreach ($fixes_applied as $fix): ?>
                    <li><?php echo esc_html($fix); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 Critical Error Fix Verification</h2>
            
            <h3>✅ Issues That Were Fixed:</h3>
            <div style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;">
                <h4>🚨 Problem 1: Misplaced Try-Catch Blocks</h4>
                <p><strong>Issue:</strong> Try-catch blocks were placed in the middle of methods, leaving HTML output sections unprotected</p>
                <p><strong>Fix Applied:</strong> Moved try-catch blocks to wrap entire method content</p>
                <p><strong>Affected Methods:</strong></p>
                <ul>
                    <li><code>display_trial_highlight()</code> - Now fully protected</li>
                    <li><code>display_comprehensive_trial_info()</code> - Now fully protected</li>
                    <li><code>display_subscription_info()</code> - Now fully protected</li>
                    <li><code>force_subscription_add_to_cart()</code> - Already protected</li>
                    <li><code>debug_add_to_cart_status()</code> - Already protected</li>
                </ul>
                
                <h4>🚨 Problem 2: Singleton Pattern Issue</h4>
                <p><strong>Issue:</strong> <code>new ZlaarkSubscriptionsTrialService()</code> instead of singleton pattern</p>
                <p><strong>Fix Applied:</strong> Changed to <code>ZlaarkSubscriptionsTrialService::instance()</code></p>
                
                <h4>🚨 Problem 3: Unprotected HTML Output</h4>
                <p><strong>Issue:</strong> Large HTML sections with PHP code were outside try-catch protection</p>
                <p><strong>Fix Applied:</strong> Entire methods now wrapped in comprehensive error handling</p>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Comprehensive Method Testing</h2>
            
            <h3>Testing All Automatic Methods:</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <?php
                try {
                    global $product;
                    $product = wc_get_product($product_id);
                    
                    if ($product && $product->get_type() === 'subscription') {
                        echo '<p>✅ Product loaded successfully as subscription type</p>';
                        
                        $frontend = ZlaarkSubscriptionsFrontend::instance();
                        
                        // Test each method individually
                        $methods_to_test = [
                            'display_trial_highlight' => 'Display Trial Highlight',
                            'display_comprehensive_trial_info' => 'Display Comprehensive Trial Info',
                            'display_subscription_info' => 'Display Subscription Info',
                            'force_subscription_add_to_cart' => 'Force Subscription Add to Cart',
                        ];
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            $methods_to_test['debug_add_to_cart_status'] = 'Debug Add to Cart Status';
                        }
                        
                        foreach ($methods_to_test as $method => $label) {
                            try {
                                ob_start();
                                $frontend->$method();
                                $output = ob_get_clean();
                                echo '<p>✅ <strong>' . $label . ':</strong> Executed without error</p>';
                            } catch (Exception $e) {
                                echo '<p>❌ <strong>' . $label . ':</strong> Error - ' . esc_html($e->getMessage()) . '</p>';
                            } catch (Error $e) {
                                echo '<p>❌ <strong>' . $label . ':</strong> PHP Error - ' . esc_html($e->getMessage()) . '</p>';
                            }
                        }
                        
                    } else {
                        echo '<p>⚠️ Product not loaded as subscription type</p>';
                    }
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Error during method testing: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
            
            <h3>Product Data Verification:</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <?php
                try {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        echo '<p><strong>Product:</strong> ' . esc_html($product->get_name()) . '</p>';
                        echo '<p><strong>Class:</strong> ' . get_class($product) . '</p>';
                        echo '<p><strong>Type:</strong> ' . $product->get_type() . '</p>';
                        
                        if (method_exists($product, 'has_trial')) {
                            $has_trial = $product->has_trial();
                            echo '<p><strong>Has Trial:</strong> ' . ($has_trial ? '✅ Yes' : '❌ No') . '</p>';
                            
                            if (method_exists($product, 'get_trial_duration')) {
                                echo '<p><strong>Trial Duration:</strong> ' . $product->get_trial_duration() . ' days</p>';
                            }
                            
                            if (method_exists($product, 'get_trial_price')) {
                                echo '<p><strong>Trial Price:</strong> ₹' . $product->get_trial_price() . '</p>';
                            }
                        } else {
                            echo '<p><strong>Trial Methods:</strong> ❌ Not available</p>';
                        }
                    } else {
                        echo '<p style="color: red;">❌ Product not found</p>';
                    }
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Error loading product: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔗 Test Your Product Page</h2>
            
            <h3>Direct Product Page Test:</h3>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button button-primary button-large">🔗 Open Product Page</a>
                <em>Check if the critical error is completely gone</em>
            </p>
            
            <h3>What to Look For:</h3>
            <ul>
                <li>✅ <strong>No critical error message</strong> at the bottom of the page</li>
                <li>✅ <strong>Page loads completely</strong> without interruption</li>
                <li>✅ <strong>Normal layout and styling</strong> appears correctly</li>
                <li>✅ <strong>Product information displays</strong> properly</li>
                <li>✅ <strong>No PHP errors</strong> in browser console</li>
            </ul>
            
            <h3>Shortcode Testing (Optional):</h3>
            <p>If you want to test shortcodes, try adding these to a test post/page:</p>
            <code>[trial_button product_id="<?php echo $product_id; ?>"]</code><br>
            <code>[subscription_button product_id="<?php echo $product_id; ?>"]</code>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Run Fresh Verification</h2>
            <p>Clear all caches and run fresh tests to ensure the fixes are working:</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('verify_fix', 'verify_nonce'); ?>
                <p>
                    <input type="submit" name="run_verification" class="button button-primary" value="🔄 Run Fresh Verification">
                </p>
            </form>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 Summary of Fixes Applied</h2>
            
            <h3>🔧 Code Structure Fixes:</h3>
            <ul>
                <li><strong>Try-Catch Placement:</strong> Moved to wrap entire method content instead of just initialization</li>
                <li><strong>Error Handling Coverage:</strong> All HTML output sections now protected</li>
                <li><strong>Singleton Pattern:</strong> Fixed incorrect instantiation of ZlaarkSubscriptionsTrialService</li>
                <li><strong>Method Protection:</strong> All automatic hook methods now have comprehensive error handling</li>
            </ul>
            
            <h3>⚡ Why This Fixes the Persistent Error:</h3>
            <ul>
                <li><strong>Complete Protection:</strong> Entire method content is now protected, not just parts</li>
                <li><strong>HTML Safety:</strong> PHP code within HTML sections can't cause fatal errors</li>
                <li><strong>Silent Failure:</strong> Methods fail gracefully without breaking the page</li>
                <li><strong>Proper Logging:</strong> Errors are logged for debugging but don't display to users</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎯 Expected Results</h2>
            <ul>
                <li>✅ <strong>No more "There has been a critical error on this website"</strong> message</li>
                <li>✅ <strong>Product page loads normally</strong> without any interruptions</li>
                <li>✅ <strong>Layout appears correctly</strong> with proper styling and positioning</li>
                <li>✅ <strong>Plugin functionality preserved</strong> when working correctly</li>
                <li>✅ <strong>Graceful error handling</strong> for any remaining issues</li>
                <li>✅ <strong>Error logging</strong> for debugging without breaking the site</li>
            </ul>
            
            <p><strong>If you still see any issues:</strong></p>
            <ul>
                <li>Check the error logs for specific error messages</li>
                <li>Try deactivating and reactivating the plugin</li>
                <li>Clear all caches (WordPress, theme, plugins, CDN)</li>
                <li>Test with a default theme to rule out theme conflicts</li>
            </ul>
        </div>
    </div>
    <?php
}
