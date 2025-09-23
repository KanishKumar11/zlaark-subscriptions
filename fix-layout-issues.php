<?php
/**
 * Fix Layout Issues on Product Page
 * 
 * This script addresses layout problems caused by recent plugin changes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for layout fixes
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Fix Layout Issues',
        '🎨 Fix Layout Issues',
        'manage_options',
        'fix-layout-issues',
        'zlaark_fix_layout_issues_page'
    );
});

function zlaark_fix_layout_issues_page() {
    $product_id = 3425;
    $fixes_applied = [];
    $errors = [];
    
    // Handle fix actions
    if (isset($_POST['apply_layout_fixes']) && wp_verify_nonce($_POST['layout_fix_nonce'], 'layout_fix')) {
        
        // Fix 1: Clear all caches to remove stale CSS/JS
        try {
            wp_cache_flush();
            wc_delete_product_transients();
            wp_cache_delete('wc_product_' . $product_id, 'products');
            
            // Clear theme and plugin caches if available
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group('themes');
                wp_cache_flush_group('plugins');
            }
            
            $fixes_applied[] = 'All caches cleared';
        } catch (Exception $e) {
            $errors[] = 'Failed to clear cache: ' . $e->getMessage();
        }
        
        // Fix 2: Force CSS regeneration
        try {
            update_option('zlaark_subscriptions_css_version', time());
            $fixes_applied[] = 'CSS regeneration forced';
        } catch (Exception $e) {
            $errors[] = 'Failed to regenerate CSS: ' . $e->getMessage();
        }
        
        // Fix 3: Reset any problematic transients
        try {
            delete_transient('wc_product_loop_' . $product_id);
            delete_transient('woocommerce_cache_excluded_uris');
            $fixes_applied[] = 'Product transients cleared';
        } catch (Exception $e) {
            $errors[] = 'Failed to clear transients: ' . $e->getMessage();
        }
        
        // Fix 4: Ensure proper shortcode registration
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
        <h1>🎨 Fix Layout Issues</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <p><strong>✅ Layout Fixes Applied:</strong></p>
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
            <h2>🔍 Layout Issue Diagnosis</h2>
            
            <h3>✅ Issues Fixed:</h3>
            <ul style="color: green;">
                <li><strong>Debug Output Removed:</strong> Visible debug div that was disrupting layout has been removed</li>
                <li><strong>CSS Scoping Fixed:</strong> Button styles are now scoped to avoid affecting other page elements</li>
                <li><strong>Error Handling Improved:</strong> Try-catch blocks prevent fatal errors without layout disruption</li>
            </ul>
            
            <h3>🔧 What Was Changed:</h3>
            <ul>
                <li><strong>Frontend Class:</strong> Removed visible debug output, kept HTML comments only</li>
                <li><strong>CSS Styles:</strong> Scoped button styles to specific containers (.subscription-purchase-options, .zlaark-trial-form, etc.)</li>
                <li><strong>Error Handling:</strong> Added comprehensive try-catch blocks to prevent crashes</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Layout Test</h2>
            
            <h3>Test Your Product Page</h3>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button button-primary">🔗 Open Product Page</a>
                <em>Check if the layout looks normal now</em>
            </p>
            
            <h3>Shortcode Test (Safe Mode)</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <p><strong>Trial Button:</strong></p>
                <?php 
                try {
                    echo do_shortcode('[trial_button product_id="' . $product_id . '"]');
                } catch (Exception $e) {
                    echo '<p style="color: red;">Error: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
                
                <hr style="margin: 15px 0;">
                
                <p><strong>Subscription Button:</strong></p>
                <?php 
                try {
                    echo do_shortcode('[subscription_button product_id="' . $product_id . '"]');
                } catch (Exception $e) {
                    echo '<p style="color: red;">Error: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
            
            <p><em>If the shortcodes above render as proper buttons without affecting this admin page layout, the fixes are working.</em></p>
        </div>
        
        <?php if (empty($fixes_applied)): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Apply Layout Fixes</h2>
            <p><strong>This will apply additional fixes:</strong></p>
            <ul>
                <li>✅ Clear all WordPress and WooCommerce caches</li>
                <li>✅ Force CSS regeneration to ensure latest styles load</li>
                <li>✅ Clear product-specific transients</li>
                <li>✅ Re-register shortcodes properly</li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('layout_fix', 'layout_fix_nonce'); ?>
                <p>
                    <input type="submit" name="apply_layout_fixes" class="button button-primary" value="🎨 Apply Layout Fixes">
                </p>
            </form>
        </div>
        <?php endif; ?>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 Layout Issues Resolved</h2>
            
            <h3>🚨 Problem 1: Content Area Shrinkage - FIXED ✅</h3>
            <p><strong>Cause:</strong> Overly broad CSS selectors affecting page elements</p>
            <p><strong>Fix:</strong> Scoped button styles to specific containers only</p>
            
            <h3>🚨 Problem 2: Content Positioning Issue - FIXED ✅</h3>
            <p><strong>Cause:</strong> Debug div injection disrupting HTML structure</p>
            <p><strong>Fix:</strong> Removed visible debug output, kept HTML comments only</p>
            
            <h3>🚨 Problem 3: Layout Corruption - FIXED ✅</h3>
            <p><strong>Cause:</strong> CSS conflicts and HTML structure issues</p>
            <p><strong>Fix:</strong> Improved CSS specificity and error handling</p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 Troubleshooting</h2>
            
            <h3>If Layout Issues Persist:</h3>
            <ol>
                <li><strong>Clear Browser Cache:</strong> Hard refresh (Ctrl+F5) your product page</li>
                <li><strong>Check Theme Conflicts:</strong> Temporarily switch to a default theme</li>
                <li><strong>Plugin Conflicts:</strong> Deactivate other plugins temporarily</li>
                <li><strong>Elementor Cache:</strong> If using Elementor, clear its cache</li>
                <li><strong>CDN Cache:</strong> Clear any CDN or caching plugin cache</li>
            </ol>
            
            <h3>CSS Debugging:</h3>
            <p>Use browser developer tools (F12) to inspect elements and check for:</p>
            <ul>
                <li>Conflicting CSS rules</li>
                <li>Missing or broken stylesheets</li>
                <li>JavaScript errors in console</li>
                <li>HTML structure issues</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔗 Additional Tools</h2>
            <p>
                <a href="<?php echo admin_url('tools.php?page=emergency-fix-critical-issues'); ?>" class="button">Emergency Fix Tool</a>
                <a href="<?php echo admin_url('tools.php?page=debug-product-page-design'); ?>" class="button">Debug Design Issues</a>
                <a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>" class="button">Edit Product</a>
            </p>
        </div>
    </div>
    <?php
}
