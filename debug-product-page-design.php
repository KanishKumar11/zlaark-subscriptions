<?php
/**
 * Debug Product Page Design Issues
 * 
 * This script helps identify what's causing design issues on the product page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for debugging
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Product Page Design',
        'Debug Product Page Design',
        'manage_options',
        'debug-product-page-design',
        'zlaark_debug_product_page_design_page'
    );
});

function zlaark_debug_product_page_design_page() {
    $product_id = 3425;
    ?>
    <div class="wrap">
        <h1>🎨 Debug Product Page Design</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 Design Issue Investigation</h2>
            
            <?php
            echo '<h3>1. Check Product Page URL</h3>';
            $product = wc_get_product($product_id);
            if ($product) {
                $product_url = get_permalink($product_id);
                echo '<p><strong>Product URL:</strong> <a href="' . esc_url($product_url) . '" target="_blank">' . esc_url($product_url) . '</a></p>';
                echo '<p><strong>Product Name:</strong> ' . esc_html($product->get_name()) . '</p>';
                echo '<p><strong>Product Status:</strong> ' . $product->get_status() . '</p>';
            }
            
            echo '<h3>2. Check Template Files</h3>';
            $template_files = [
                'templates/single-product/add-to-cart/subscription.php',
                'assets/css/frontend.css',
                'assets/js/frontend.js'
            ];
            
            foreach ($template_files as $file) {
                $full_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . $file;
                if (file_exists($full_path)) {
                    echo '<p>✅ <strong>' . $file . '</strong> exists</p>';
                } else {
                    echo '<p>❌ <strong>' . $file . '</strong> missing</p>';
                }
            }
            
            echo '<h3>3. Check CSS/JS Enqueuing</h3>';
            
            // Check if styles are enqueued
            global $wp_styles, $wp_scripts;
            
            if (isset($wp_styles->registered['zlaark-subscriptions-frontend'])) {
                echo '<p>✅ Frontend CSS is registered</p>';
                $style = $wp_styles->registered['zlaark-subscriptions-frontend'];
                echo '<p><strong>CSS URL:</strong> ' . esc_url($style->src) . '</p>';
            } else {
                echo '<p>❌ Frontend CSS not registered</p>';
            }
            
            if (isset($wp_scripts->registered['zlaark-subscriptions-frontend'])) {
                echo '<p>✅ Frontend JS is registered</p>';
                $script = $wp_scripts->registered['zlaark-subscriptions-frontend'];
                echo '<p><strong>JS URL:</strong> ' . esc_url($script->src) . '</p>';
            } else {
                echo '<p>❌ Frontend JS not registered</p>';
            }
            
            echo '<h3>4. Check Theme Compatibility</h3>';
            $theme = wp_get_theme();
            echo '<p><strong>Active Theme:</strong> ' . $theme->get('Name') . ' v' . $theme->get('Version') . '</p>';
            
            // Check if theme has WooCommerce support
            if (current_theme_supports('woocommerce')) {
                echo '<p>✅ Theme supports WooCommerce</p>';
            } else {
                echo '<p>⚠️ Theme does not declare WooCommerce support</p>';
            }
            
            echo '<h3>5. Check Plugin Conflicts</h3>';
            $active_plugins = get_option('active_plugins');
            $potential_conflicts = [];
            
            foreach ($active_plugins as $plugin) {
                if (strpos($plugin, 'subscription') !== false || 
                    strpos($plugin, 'woocommerce') !== false ||
                    strpos($plugin, 'elementor') !== false ||
                    strpos($plugin, 'cache') !== false) {
                    $potential_conflicts[] = $plugin;
                }
            }
            
            if (!empty($potential_conflicts)) {
                echo '<p><strong>Potential conflicting plugins:</strong></p>';
                echo '<ul>';
                foreach ($potential_conflicts as $plugin) {
                    echo '<li>' . esc_html($plugin) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>✅ No obvious plugin conflicts detected</p>';
            }
            
            echo '<h3>6. Check Error Logs</h3>';
            
            // Check for recent PHP errors
            $error_log_path = ini_get('error_log');
            if ($error_log_path && file_exists($error_log_path)) {
                echo '<p><strong>Error log location:</strong> ' . esc_html($error_log_path) . '</p>';
                
                // Read last few lines of error log
                $lines = file($error_log_path);
                if ($lines) {
                    $recent_lines = array_slice($lines, -10);
                    $zlaark_errors = array_filter($recent_lines, function($line) {
                        return strpos($line, 'Zlaark') !== false || strpos($line, 'subscription') !== false;
                    });
                    
                    if (!empty($zlaark_errors)) {
                        echo '<p><strong>Recent Zlaark-related errors:</strong></p>';
                        echo '<pre style="background: #f0f0f0; padding: 10px; overflow-x: auto; font-size: 12px;">';
                        foreach ($zlaark_errors as $error) {
                            echo esc_html($error);
                        }
                        echo '</pre>';
                    } else {
                        echo '<p>✅ No recent Zlaark-related errors found</p>';
                    }
                }
            } else {
                echo '<p>⚠️ Error log not accessible</p>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Quick Tests</h2>
            
            <h3>Test 1: Basic Shortcode Rendering</h3>
            <div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <p><strong>Trial Button:</strong></p>
                <?php 
                try {
                    echo do_shortcode('[trial_button product_id="' . $product_id . '"]');
                } catch (Exception $e) {
                    echo '<p style="color: red;">Error: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
                
                <p><strong>Subscription Button:</strong></p>
                <?php 
                try {
                    echo do_shortcode('[subscription_button product_id="' . $product_id . '"]');
                } catch (Exception $e) {
                    echo '<p style="color: red;">Error: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
            
            <h3>Test 2: CSS Loading Test</h3>
            <div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <button class="trial-button zlaark-trial-btn" style="margin: 5px;">Test Trial Button Style</button>
                <button class="subscription-button zlaark-subscription-btn" style="margin: 5px;">Test Subscription Button Style</button>
                <p><em>If these buttons look unstyled, there's a CSS loading issue.</em></p>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Quick Fixes</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('design_fixes', 'design_fixes_nonce'); ?>
                
                <p>
                    <input type="submit" name="clear_all_cache" class="button" value="Clear All Cache">
                    <em>Clears WordPress cache, WooCommerce cache, and object cache</em>
                </p>
                
                <p>
                    <input type="submit" name="regenerate_css" class="button" value="Force CSS Regeneration">
                    <em>Forces CSS files to be regenerated</em>
                </p>
                
                <p>
                    <input type="submit" name="reset_product_cache" class="button" value="Reset Product Cache">
                    <em>Clears product-specific cache</em>
                </p>
            </form>
            
            <?php
            // Handle form submissions
            if (isset($_POST['clear_all_cache']) && wp_verify_nonce($_POST['design_fixes_nonce'], 'design_fixes')) {
                wp_cache_flush();
                wc_delete_product_transients();
                echo '<div class="notice notice-success"><p>✅ All cache cleared!</p></div>';
            }
            
            if (isset($_POST['regenerate_css']) && wp_verify_nonce($_POST['design_fixes_nonce'], 'design_fixes')) {
                // Force CSS regeneration by updating version
                update_option('zlaark_subscriptions_css_version', time());
                echo '<div class="notice notice-success"><p>✅ CSS regeneration forced!</p></div>';
            }
            
            if (isset($_POST['reset_product_cache']) && wp_verify_nonce($_POST['design_fixes_nonce'], 'design_fixes')) {
                wp_cache_delete('wc_product_' . $product_id, 'products');
                wc_delete_product_transients($product_id);
                echo '<div class="notice notice-success"><p>✅ Product cache reset!</p></div>';
            }
            ?>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>💡 Common Design Issues & Solutions</h2>
            <ul>
                <li><strong>CSS not loading:</strong> Check file permissions and URL accessibility</li>
                <li><strong>Theme conflicts:</strong> Test with a default theme (Twenty Twenty-Four)</li>
                <li><strong>Plugin conflicts:</strong> Deactivate other plugins temporarily</li>
                <li><strong>Cache issues:</strong> Clear all caches and try again</li>
                <li><strong>Template overrides:</strong> Check if theme has custom WooCommerce templates</li>
                <li><strong>Elementor conflicts:</strong> Check if Elementor is caching or overriding styles</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔗 Quick Links</h2>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button">View Product Page</a>
                <a href="<?php echo admin_url('post.php?post=' . $product_id . '&action=edit'); ?>" class="button">Edit Product</a>
                <a href="<?php echo admin_url('tools.php?page=debug-critical-error'); ?>" class="button">Debug Critical Error</a>
            </p>
        </div>
    </div>
    <?php
}
