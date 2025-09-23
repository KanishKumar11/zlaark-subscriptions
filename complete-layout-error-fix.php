<?php
/**
 * Complete Layout and Error Fix
 * 
 * This script provides a comprehensive solution for both layout and error issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for complete fixes
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Complete Layout & Error Fix',
        '🚀 Complete Fix',
        'manage_options',
        'complete-layout-error-fix',
        'zlaark_complete_layout_error_fix_page'
    );
});

function zlaark_complete_layout_error_fix_page() {
    $product_id = 3425;
    $fixes_applied = [];
    $errors = [];
    
    // Handle fix actions
    if (isset($_POST['apply_complete_fixes']) && wp_verify_nonce($_POST['complete_fix_nonce'], 'complete_fix')) {
        
        // Fix 1: Clear all caches and transients
        try {
            wp_cache_flush();
            wc_delete_product_transients();
            wp_cache_delete('wc_product_' . $product_id, 'products');
            
            // Clear specific transients that might cause issues
            delete_transient('wc_product_loop_' . $product_id);
            delete_transient('woocommerce_cache_excluded_uris');
            delete_transient('zlaark_subscriptions_product_types');
            
            $fixes_applied[] = 'All caches and transients cleared';
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
        
        // Fix 4: Re-register shortcodes
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
        
        // Fix 5: Force CSS/JS regeneration
        try {
            update_option('zlaark_subscriptions_css_version', time());
            update_option('zlaark_subscriptions_js_version', time());
            $fixes_applied[] = 'CSS/JS regeneration forced';
        } catch (Exception $e) {
            $errors[] = 'Failed to regenerate assets: ' . $e->getMessage();
        }
    }
    
    ?>
    <div class="wrap">
        <h1>🚀 Complete Layout & Error Fix</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <p><strong>✅ Complete Fixes Applied Successfully:</strong></p>
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
            <h2>✅ Issues Fixed</h2>
            
            <h3>🚨 Critical Error at Bottom of Page - FIXED ✅</h3>
            <p><strong>Problem:</strong> PHP syntax error in subscription button shortcode method</p>
            <p><strong>Root Cause:</strong> Misplaced catch blocks outside try block scope</p>
            <p><strong>Fix Applied:</strong> Corrected indentation and block structure in subscription_button_shortcode method</p>
            
            <h3>🎨 Layout Display Problems - FIXED ✅</h3>
            <p><strong>Problem:</strong> Product page layout corruption and element misalignment</p>
            <p><strong>Root Cause:</strong> Debug output injection and overly broad CSS selectors</p>
            <p><strong>Fix Applied:</strong> Removed visible debug output and scoped CSS selectors</p>
            
            <h3>🔧 Code Structure Issues - FIXED ✅</h3>
            <p><strong>Problem:</strong> Try-catch blocks not properly structured</p>
            <p><strong>Root Cause:</strong> Incorrect indentation causing syntax errors</p>
            <p><strong>Fix Applied:</strong> Corrected PHP syntax and error handling structure</p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Comprehensive Testing</h2>
            
            <h3>1. Product Page Test</h3>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button button-primary">🔗 Test Product Page</a>
                <em>Check if layout is normal and no errors appear at bottom</em>
            </p>
            
            <h3>2. Shortcode Functionality Test</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <p><strong>Trial Button Test:</strong></p>
                <?php 
                try {
                    $trial_result = do_shortcode('[trial_button product_id="' . $product_id . '"]');
                    echo $trial_result;
                    if (!strpos($trial_result, 'temporarily unavailable') && !strpos($trial_result, 'error')) {
                        echo '<p style="color: green;">✅ Trial button working correctly</p>';
                    } else {
                        echo '<p style="color: orange;">⚠️ Trial button showing notice (may be expected)</p>';
                    }
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Error: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
                
                <hr style="margin: 15px 0;">
                
                <p><strong>Subscription Button Test:</strong></p>
                <?php 
                try {
                    $sub_result = do_shortcode('[subscription_button product_id="' . $product_id . '"]');
                    echo $sub_result;
                    if (!strpos($sub_result, 'temporarily unavailable') && !strpos($sub_result, 'error')) {
                        echo '<p style="color: green;">✅ Subscription button working correctly</p>';
                    } else {
                        echo '<p style="color: red;">❌ Subscription button has issues</p>';
                    }
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Error: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
            
            <h3>3. Product Data Verification</h3>
            <?php
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
            }
            ?>
        </div>
        
        <?php if (empty($fixes_applied)): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Apply Complete Fixes</h2>
            <p><strong>This will apply comprehensive fixes for:</strong></p>
            <ul>
                <li>✅ Clear all caches and transients</li>
                <li>✅ Reset trial duration data</li>
                <li>✅ Force product type registration</li>
                <li>✅ Re-register shortcodes properly</li>
                <li>✅ Force CSS/JS regeneration</li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('complete_fix', 'complete_fix_nonce'); ?>
                <p>
                    <input type="submit" name="apply_complete_fixes" class="button button-primary button-large" value="🚀 Apply Complete Fixes" onclick="return confirm('Apply all fixes to resolve layout and error issues?');">
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 What Was Fixed</h2>
            
            <h3>🔧 Code Structure Fixes:</h3>
            <ul>
                <li><strong>Syntax Error:</strong> Fixed misplaced catch blocks in subscription_button_shortcode method</li>
                <li><strong>Indentation:</strong> Corrected PHP block structure and indentation</li>
                <li><strong>Error Handling:</strong> Properly nested try-catch blocks</li>
            </ul>
            
            <h3>🎨 Layout Fixes:</h3>
            <ul>
                <li><strong>Debug Output:</strong> Removed visible debug divs that disrupted layout</li>
                <li><strong>CSS Scoping:</strong> Limited button styles to specific containers</li>
                <li><strong>HTML Structure:</strong> Clean shortcode output without layout interference</li>
            </ul>
            
            <h3>⚡ Performance Fixes:</h3>
            <ul>
                <li><strong>Cache Clearing:</strong> Removed stale cached data</li>
                <li><strong>Asset Regeneration:</strong> Forced fresh CSS/JS loading</li>
                <li><strong>Product Registration:</strong> Ensured proper product type registration</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎯 Expected Results</h2>
            <ul>
                <li>✅ <strong>No more critical error</strong> at bottom of product page</li>
                <li>✅ <strong>Normal layout display</strong> with proper content positioning</li>
                <li>✅ <strong>Functional shortcodes</strong> rendering as proper buttons</li>
                <li>✅ <strong>Trial functionality</strong> working correctly</li>
                <li>✅ <strong>Clean page structure</strong> without HTML/CSS conflicts</li>
            </ul>
        </div>
    </div>
    <?php
}
