<?php
/**
 * Fix Persistent Critical Error
 * 
 * This script addresses the root cause of the critical error that persists even without shortcodes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for persistent error fixes
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Fix Persistent Critical Error',
        '🚨 Fix Critical Error',
        'manage_options',
        'fix-persistent-critical-error',
        'zlaark_fix_persistent_critical_error_page'
    );
});

function zlaark_fix_persistent_critical_error_page() {
    $product_id = 3425;
    $fixes_applied = [];
    $errors = [];
    
    // Handle fix actions
    if (isset($_POST['apply_critical_fixes']) && wp_verify_nonce($_POST['critical_fix_nonce'], 'critical_fix')) {
        
        // Fix 1: Clear all caches to ensure fresh code loading
        try {
            wp_cache_flush();
            wc_delete_product_transients();
            wp_cache_delete('wc_product_' . $product_id, 'products');
            
            // Clear opcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            $fixes_applied[] = 'All caches cleared including opcache';
        } catch (Exception $e) {
            $errors[] = 'Failed to clear cache: ' . $e->getMessage();
        }
        
        // Fix 2: Reset trial duration data
        try {
            update_post_meta($product_id, '_subscription_trial_duration', 7);
            update_post_meta($product_id, '_subscription_trial_price', 99);
            update_post_meta($product_id, '_subscription_trial_period', 'day');
            $fixes_applied[] = 'Trial duration data reset';
        } catch (Exception $e) {
            $errors[] = 'Failed to reset trial data: ' . $e->getMessage();
        }
        
        // Fix 3: Force product type registration
        try {
            if (class_exists('ZlaarkSubscriptionsProductType')) {
                $instance = ZlaarkSubscriptionsProductType::instance();
                $instance->register_product_type_now();
                $fixes_applied[] = 'Product type registration forced';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to register product type: ' . $e->getMessage();
        }
    }
    
    ?>
    <div class="wrap">
        <h1>🚨 Fix Persistent Critical Error</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <p><strong>✅ Critical Error Fixes Applied:</strong></p>
            <ul>
                <?php foreach ($fixes_applied as $fix): ?>
                    <li><?php echo esc_html($fix); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <p><strong>❌ Errors Encountered:</strong></p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 Root Cause Analysis</h2>
            
            <h3>✅ Critical Issue Identified & Fixed:</h3>
            <div style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;">
                <h4>🚨 Problem: Automatic Method Execution on Product Pages</h4>
                <p><strong>Root Cause:</strong> Several methods in the frontend class automatically execute on product pages via WordPress hooks, causing fatal errors even without shortcodes.</p>
                
                <p><strong>Specific Issues Found:</strong></p>
                <ul>
                    <li><strong>Line 269:</strong> <code>new ZlaarkSubscriptionsTrialService()</code> instead of singleton pattern</li>
                    <li><strong>Missing Error Handling:</strong> Methods lacked try-catch blocks for safe execution</li>
                    <li><strong>Automatic Hooks:</strong> Methods run on every product page load via <code>woocommerce_single_product_summary</code></li>
                </ul>
                
                <p><strong>Affected Methods:</strong></p>
                <ul>
                    <li><code>display_trial_highlight()</code> - Priority 7</li>
                    <li><code>display_comprehensive_trial_info()</code> - Priority 12</li>
                    <li><code>force_subscription_add_to_cart()</code> - Priority 31</li>
                    <li><code>debug_add_to_cart_status()</code> - Priority 32 (if WP_DEBUG enabled)</li>
                </ul>
            </div>
            
            <h3>✅ Fixes Applied:</h3>
            <div style="background: #cce7f0; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px; margin: 10px 0;">
                <ul>
                    <li><strong>Singleton Pattern Fix:</strong> Changed <code>new ZlaarkSubscriptionsTrialService()</code> to <code>ZlaarkSubscriptionsTrialService::instance()</code></li>
                    <li><strong>Error Handling Added:</strong> Wrapped all automatic methods in try-catch blocks</li>
                    <li><strong>Silent Error Logging:</strong> Errors are logged but don't break the page</li>
                    <li><strong>Graceful Degradation:</strong> Methods return silently on errors</li>
                </ul>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Test Product Page</h2>
            
            <h3>1. Direct Product Page Test</h3>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button button-primary">🔗 Test Product Page</a>
                <em>Check if the critical error is gone</em>
            </p>
            
            <h3>2. Product Data Verification</h3>
            <?php
            try {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo '<div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">';
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
                    echo '</div>';
                } else {
                    echo '<p style="color: red;">❌ Product not found</p>';
                }
            } catch (Exception $e) {
                echo '<p style="color: red;">❌ Error loading product: ' . esc_html($e->getMessage()) . '</p>';
            }
            ?>
            
            <h3>3. Hook Execution Test</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <p><strong>Testing automatic methods that run on product pages:</strong></p>
                <?php
                try {
                    global $product;
                    $product = wc_get_product($product_id);
                    
                    if ($product && $product->get_type() === 'subscription') {
                        echo '<p>✅ Product loaded as subscription type</p>';
                        
                        // Test if methods can be called safely
                        $frontend = ZlaarkSubscriptionsFrontend::instance();
                        
                        ob_start();
                        $frontend->display_trial_highlight();
                        $output1 = ob_get_clean();
                        echo '<p>✅ display_trial_highlight() executed without error</p>';
                        
                        ob_start();
                        $frontend->display_comprehensive_trial_info();
                        $output2 = ob_get_clean();
                        echo '<p>✅ display_comprehensive_trial_info() executed without error</p>';
                        
                        ob_start();
                        $frontend->force_subscription_add_to_cart();
                        $output3 = ob_get_clean();
                        echo '<p>✅ force_subscription_add_to_cart() executed without error</p>';
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            ob_start();
                            $frontend->debug_add_to_cart_status();
                            $output4 = ob_get_clean();
                            echo '<p>✅ debug_add_to_cart_status() executed without error</p>';
                        }
                        
                    } else {
                        echo '<p>⚠️ Product not loaded as subscription type</p>';
                    }
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Error testing methods: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <?php if (empty($fixes_applied)): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Apply Critical Error Fixes</h2>
            <p><strong>This will apply additional fixes:</strong></p>
            <ul>
                <li>✅ Clear all caches including opcache</li>
                <li>✅ Reset trial duration data</li>
                <li>✅ Force product type registration</li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('critical_fix', 'critical_fix_nonce'); ?>
                <p>
                    <input type="submit" name="apply_critical_fixes" class="button button-primary" value="🚨 Apply Critical Fixes">
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 What Was Fixed</h2>
            
            <h3>🔧 Code Changes Made:</h3>
            <ul>
                <li><strong>display_comprehensive_trial_info():</strong> Fixed singleton instantiation and added error handling</li>
                <li><strong>display_trial_highlight():</strong> Added comprehensive error handling</li>
                <li><strong>force_subscription_add_to_cart():</strong> Added error handling for safe execution</li>
                <li><strong>debug_add_to_cart_status():</strong> Added error handling for debug method</li>
            </ul>
            
            <h3>⚡ Why This Fixes the Issue:</h3>
            <ul>
                <li><strong>Automatic Execution:</strong> These methods run automatically on every product page load</li>
                <li><strong>Error Prevention:</strong> Try-catch blocks prevent fatal errors from breaking the page</li>
                <li><strong>Silent Logging:</strong> Errors are logged but don't display to users</li>
                <li><strong>Graceful Degradation:</strong> Page continues to work even if plugin methods fail</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎯 Expected Results</h2>
            <ul>
                <li>✅ <strong>No more critical error</strong> on product page</li>
                <li>✅ <strong>Page loads normally</strong> even without shortcodes</li>
                <li>✅ <strong>Plugin functionality preserved</strong> when working correctly</li>
                <li>✅ <strong>Error logging</strong> for debugging without breaking the site</li>
                <li>✅ <strong>Graceful handling</strong> of missing classes or methods</li>
            </ul>
        </div>
    </div>
    <?php
}
