<?php
/**
 * Emergency Fix for Critical Issues
 * 
 * This script provides immediate fixes for both the critical error and design issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for emergency fixes
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Emergency Fix Critical Issues',
        '🚨 Emergency Fix',
        'manage_options',
        'emergency-fix-critical-issues',
        'zlaark_emergency_fix_critical_issues_page'
    );
});

function zlaark_emergency_fix_critical_issues_page() {
    $product_id = 3425;
    $fixes_applied = [];
    $errors = [];
    
    // Handle fix actions
    if (isset($_POST['apply_emergency_fixes']) && wp_verify_nonce($_POST['emergency_fix_nonce'], 'emergency_fix')) {
        
        // Fix 1: Reset trial duration properly
        try {
            update_post_meta($product_id, '_subscription_trial_duration', 7);
            update_post_meta($product_id, '_subscription_trial_price', 99);
            update_post_meta($product_id, '_subscription_trial_period', 'day');
            $fixes_applied[] = 'Trial duration and price reset';
        } catch (Exception $e) {
            $errors[] = 'Failed to reset trial data: ' . $e->getMessage();
        }
        
        // Fix 2: Clear all caches
        try {
            wp_cache_flush();
            wc_delete_product_transients();
            wp_cache_delete('wc_product_' . $product_id, 'products');
            $fixes_applied[] = 'All caches cleared';
        } catch (Exception $e) {
            $errors[] = 'Failed to clear cache: ' . $e->getMessage();
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
        
        // Fix 4: Regenerate CSS/JS
        try {
            update_option('zlaark_subscriptions_css_version', time());
            update_option('zlaark_subscriptions_js_version', time());
            $fixes_applied[] = 'CSS/JS regeneration triggered';
        } catch (Exception $e) {
            $errors[] = 'Failed to regenerate assets: ' . $e->getMessage();
        }
        
        // Fix 5: Reset shortcode registration
        try {
            remove_shortcode('trial_button');
            remove_shortcode('subscription_button');
            
            if (class_exists('ZlaarkSubscriptionsFrontend')) {
                $frontend = ZlaarkSubscriptionsFrontend::instance();
                add_shortcode('trial_button', array($frontend, 'trial_button_shortcode'));
                add_shortcode('subscription_button', array($frontend, 'subscription_button_shortcode'));
                $fixes_applied[] = 'Shortcodes re-registered';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to reset shortcodes: ' . $e->getMessage();
        }
    }
    
    ?>
    <div class="wrap">
        <h1>🚨 Emergency Fix for Critical Issues</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <p><strong>✅ Fixes Applied Successfully:</strong></p>
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
            <h2>🔍 Current Status Check</h2>
            
            <?php
            // Check current status
            echo '<h3>1. Product Status</h3>';
            $product = wc_get_product($product_id);
            if ($product) {
                echo '<p>✅ Product exists: ' . esc_html($product->get_name()) . '</p>';
                echo '<p><strong>Class:</strong> ' . get_class($product) . '</p>';
                echo '<p><strong>Type:</strong> ' . $product->get_type() . '</p>';
                
                if (method_exists($product, 'has_trial')) {
                    echo '<p><strong>Has Trial:</strong> ' . ($product->has_trial() ? '✅ Yes' : '❌ No') . '</p>';
                    echo '<p><strong>Trial Duration:</strong> ' . $product->get_trial_duration() . '</p>';
                    echo '<p><strong>Trial Price:</strong> ' . $product->get_trial_price() . '</p>';
                } else {
                    echo '<p>❌ Trial methods not available</p>';
                }
            } else {
                echo '<p>❌ Product not found</p>';
            }
            
            echo '<h3>2. Shortcode Test (Safe Mode)</h3>';
            echo '<div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
            
            // Safe shortcode test
            try {
                echo '<p><strong>Trial Button:</strong></p>';
                $trial_result = do_shortcode('[trial_button product_id="' . $product_id . '"]');
                if ($trial_result && !strpos($trial_result, 'temporarily unavailable')) {
                    echo $trial_result;
                    echo '<p style="color: green;">✅ Trial button working</p>';
                } else {
                    echo $trial_result;
                    echo '<p style="color: red;">❌ Trial button has issues</p>';
                }
                
                echo '<hr>';
                
                echo '<p><strong>Subscription Button:</strong></p>';
                $sub_result = do_shortcode('[subscription_button product_id="' . $product_id . '"]');
                if ($sub_result && !strpos($sub_result, 'temporarily unavailable')) {
                    echo $sub_result;
                    echo '<p style="color: green;">✅ Subscription button working</p>';
                } else {
                    echo $sub_result;
                    echo '<p style="color: red;">❌ Subscription button has issues</p>';
                }
                
            } catch (Exception $e) {
                echo '<p style="color: red;">❌ Shortcode test failed: ' . esc_html($e->getMessage()) . '</p>';
            }
            
            echo '</div>';
            
            echo '<h3>3. Design Elements Test</h3>';
            echo '<div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
            echo '<button class="trial-button zlaark-trial-btn" style="margin: 5px; padding: 10px;">Trial Button Style Test</button>';
            echo '<button class="subscription-button zlaark-subscription-btn" style="margin: 5px; padding: 10px;">Subscription Button Style Test</button>';
            echo '<p><em>If these buttons look properly styled, CSS is loading correctly.</em></p>';
            echo '</div>';
            ?>
        </div>
        
        <?php if (empty($fixes_applied)): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Apply Emergency Fixes</h2>
            <p><strong>This will apply the following fixes:</strong></p>
            <ul>
                <li>✅ Reset trial duration to 7 days and price to ₹99</li>
                <li>✅ Clear all WordPress and WooCommerce caches</li>
                <li>✅ Force product type registration</li>
                <li>✅ Regenerate CSS and JavaScript files</li>
                <li>✅ Re-register shortcodes properly</li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('emergency_fix', 'emergency_fix_nonce'); ?>
                <p>
                    <input type="submit" name="apply_emergency_fixes" class="button button-primary button-large" value="🚀 Apply All Emergency Fixes" onclick="return confirm('Are you sure you want to apply all emergency fixes? This will reset various settings and clear caches.');">
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Manual Tests</h2>
            
            <h3>Test Your Product Page</h3>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button button-primary">🔗 Open Product Page</a>
                <em>Check if the design looks correct and shortcodes work</em>
            </p>
            
            <h3>Test Shortcodes in Post/Page</h3>
            <p>Try adding these shortcodes to a test post or page:</p>
            <code>[trial_button product_id="<?php echo $product_id; ?>"]</code><br>
            <code>[subscription_button product_id="<?php echo $product_id; ?>"]</code>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 What These Fixes Address</h2>
            
            <h3>🚨 Critical Error Fixes:</h3>
            <ul>
                <li><strong>Try-Catch Protection:</strong> Added error handling to prevent fatal errors</li>
                <li><strong>Safe Method Calls:</strong> Check if methods exist before calling them</li>
                <li><strong>Proper Error Logging:</strong> Log errors instead of crashing the site</li>
                <li><strong>Fallback Messages:</strong> Show user-friendly messages when errors occur</li>
            </ul>
            
            <h3>🎨 Design Issue Fixes:</h3>
            <ul>
                <li><strong>Cache Clearing:</strong> Removes stale cached data</li>
                <li><strong>Asset Regeneration:</strong> Forces CSS/JS to reload</li>
                <li><strong>Product Type Registration:</strong> Ensures subscription type is properly registered</li>
                <li><strong>Template Compatibility:</strong> Ensures templates load correctly</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔗 Additional Debug Tools</h2>
            <p>
                <a href="<?php echo admin_url('tools.php?page=debug-critical-error'); ?>" class="button">Debug Critical Error</a>
                <a href="<?php echo admin_url('tools.php?page=debug-product-page-design'); ?>" class="button">Debug Design Issues</a>
                <a href="<?php echo admin_url('tools.php?page=fix-trial-duration-now'); ?>" class="button">Fix Trial Duration</a>
            </p>
        </div>
    </div>
    <?php
}
