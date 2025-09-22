<?php
/**
 * Add to Cart Debug Test Script
 * Upload to WordPress root and access via browser (admin login required)
 */

// Load WordPress
require_once('wp-config.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Get subscription product for testing
$subscription_products = wc_get_products([
    'type' => 'subscription',
    'limit' => 1,
    'status' => 'publish'
]);

$test_product = !empty($subscription_products) ? $subscription_products[0] : null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Add to Cart Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .status-good { color: #28a745; font-weight: bold; }
        .status-bad { color: #dc3545; font-weight: bold; }
        .button { display: inline-block; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .debug-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Add to Cart Debug Test</h1>
        
        <?php if (!$test_product): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px;">
                <strong>⚠️ No Subscription Products Found</strong><br>
                Please create a subscription product first to test the add-to-cart functionality.
                <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>">Create Product</a>
            </div>
        <?php else: ?>
            
            <div class="debug-section">
                <h2>📦 Test Product: <?php echo $test_product->get_name(); ?></h2>
                <table>
                    <tr><td><strong>ID:</strong></td><td><?php echo $test_product->get_id(); ?></td></tr>
                    <tr><td><strong>Type:</strong></td><td><?php echo $test_product->get_type(); ?></td></tr>
                    <tr><td><strong>Class:</strong></td><td><?php echo get_class($test_product); ?></td></tr>
                    <tr><td><strong>Status:</strong></td><td><?php echo $test_product->get_status(); ?></td></tr>
                    <tr><td><strong>Is Purchasable:</strong></td><td><?php echo $test_product->is_purchasable() ? '<span class="status-good">✅ Yes</span>' : '<span class="status-bad">❌ No</span>'; ?></td></tr>
                    <tr><td><strong>Is In Stock:</strong></td><td><?php echo $test_product->is_in_stock() ? '<span class="status-good">✅ Yes</span>' : '<span class="status-bad">❌ No</span>'; ?></td></tr>
                    <tr><td><strong>Get Price:</strong></td><td>₹<?php echo $test_product->get_price(); ?></td></tr>
                    <tr><td><strong>Stock Status:</strong></td><td><?php echo method_exists($test_product, 'get_stock_status') ? $test_product->get_stock_status() : 'N/A'; ?></td></tr>
                    <tr><td><strong>Needs Shipping:</strong></td><td><?php echo method_exists($test_product, 'needs_shipping') ? ($test_product->needs_shipping() ? 'Yes' : 'No') : 'N/A'; ?></td></tr>
                    <tr><td><strong>Product URL:</strong></td><td><a href="<?php echo $test_product->get_permalink(); ?>" target="_blank">🔗 View Product Page</a></td></tr>
                </table>
            </div>
            
            <div class="debug-section">
                <h2>🎯 Template System Test</h2>
                <table>
                    <tr>
                        <td><strong>Template File Exists:</strong></td>
                        <td>
                            <?php 
                            $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
                            echo file_exists($template_path) ? '<span class="status-good">✅ Yes</span>' : '<span class="status-bad">❌ No</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Template Path:</strong></td>
                        <td><?php echo esc_html($template_path); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Template Readable:</strong></td>
                        <td><?php echo is_readable($template_path) ? '<span class="status-good">✅ Yes</span>' : '<span class="status-bad">❌ No</span>'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="debug-section">
                <h2>🪝 Hook System Test</h2>
                <table>
                    <?php
                    $hooks_to_check = [
                        'woocommerce_single_product_summary',
                        'woocommerce_template_single_add_to_cart',
                        'wc_get_template',
                        'woocommerce_locate_template',
                        'woocommerce_subscription_add_to_cart'
                    ];
                    
                    foreach ($hooks_to_check as $hook) {
                        $count = count($GLOBALS['wp_filter'][$hook]->callbacks ?? []);
                        $status = $count > 0 ? '<span class="status-good">✅ ' . $count . ' callbacks</span>' : '<span class="status-bad">❌ No callbacks</span>';
                        echo "<tr><td><strong>$hook:</strong></td><td>$status</td></tr>";
                    }
                    ?>
                </table>
            </div>
            
            <div class="debug-section">
                <h2>🧪 Manual Template Test</h2>
                <p>Testing if the subscription template can be loaded manually:</p>
                
                <?php
                // Test loading the template manually
                if (file_exists($template_path)) {
                    echo '<div style="border: 2px solid #007cba; padding: 15px; margin: 10px 0; border-radius: 4px;">';
                    echo '<h4>Manual Template Load Test:</h4>';
                    
                    // Set up global product for template
                    global $product;
                    $original_product = $product;
                    $product = $test_product;
                    
                    // Capture template output
                    ob_start();
                    try {
                        include $template_path;
                        $template_output = ob_get_clean();
                        
                        if (!empty($template_output)) {
                            echo '<p><span class="status-good">✅ Template loads successfully</span></p>';
                            echo '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0;">';
                            echo '<strong>Template Output Preview:</strong><br>';
                            echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">';
                            echo $template_output;
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<p><span class="status-bad">❌ Template loads but produces no output</span></p>';
                        }
                    } catch (Exception $e) {
                        ob_end_clean();
                        echo '<p><span class="status-bad">❌ Template error: ' . esc_html($e->getMessage()) . '</span></p>';
                    }
                    
                    // Restore original product
                    $product = $original_product;
                    
                    echo '</div>';
                } else {
                    echo '<p><span class="status-bad">❌ Template file not found</span></p>';
                }
                ?>
            </div>
            
            <div class="debug-section">
                <h2>🔍 WooCommerce Integration Test</h2>
                <table>
                    <tr>
                        <td><strong>WooCommerce Version:</strong></td>
                        <td><?php echo WC()->version; ?></td>
                    </tr>
                    <tr>
                        <td><strong>wc_get_template Function:</strong></td>
                        <td><?php echo function_exists('wc_get_template') ? '<span class="status-good">✅ Available</span>' : '<span class="status-bad">❌ Not Available</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Product Factory:</strong></td>
                        <td><?php echo class_exists('WC_Product_Factory') ? '<span class="status-good">✅ Available</span>' : '<span class="status-bad">❌ Not Available</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Theme Compatibility:</strong></td>
                        <td>
                            <?php 
                            $theme = wp_get_theme();
                            echo esc_html($theme->get('Name')) . ' v' . esc_html($theme->get('Version'));
                            
                            // Check if theme has WooCommerce support
                            if (current_theme_supports('woocommerce')) {
                                echo ' <span class="status-good">✅ WooCommerce Support</span>';
                            } else {
                                echo ' <span class="status-bad">⚠️ No WooCommerce Support</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="debug-section">
                <h2>📋 Troubleshooting Steps</h2>
                <ol>
                    <li><strong>Visit the product page:</strong> <a href="<?php echo $test_product->get_permalink(); ?>" target="_blank">Open Product Page</a></li>
                    <li><strong>Check for debug info:</strong> If WP_DEBUG is enabled, you should see debug information on the product page</li>
                    <li><strong>Inspect page source:</strong> Look for subscription add-to-cart forms or fallback messages</li>
                    <li><strong>Check browser console:</strong> Look for JavaScript errors that might prevent button display</li>
                    <li><strong>Test with different themes:</strong> Switch to a default WordPress theme to test compatibility</li>
                </ol>
            </div>
            
            <div class="debug-section">
                <h2>🚨 Emergency Actions</h2>
                <p>If the add-to-cart button still doesn't appear, the system includes multiple fallback methods:</p>
                <ul>
                    <li><strong>Template Override:</strong> Custom template loading at priority 28-30</li>
                    <li><strong>Direct Injection:</strong> Add-to-cart button injection at standard WooCommerce priority</li>
                    <li><strong>Fallback System:</strong> Emergency add-to-cart button with visual warning</li>
                    <li><strong>Frontend Force:</strong> Frontend class forces add-to-cart at priority 31</li>
                </ul>
            </div>
            
        <?php endif; ?>
        
        <p style="text-align: center; color: #666; font-size: 12px; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=zlaark-subscriptions-diagnostics'); ?>">← Back to Admin Diagnostics</a> | 
            <strong>Delete this file after testing</strong>
        </p>
    </div>
</body>
</html>
