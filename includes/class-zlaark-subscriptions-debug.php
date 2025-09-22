<?php
/**
 * Debug and diagnostic utilities for Zlaark Subscriptions
 *
 * @package ZlaarkSubscriptions
 * @version 1.0.2
 */

defined('ABSPATH') || exit;

class ZlaarkSubscriptionsDebug {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add debug information to frontend if debug mode is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'frontend_debug_info'));
        }
        
        // Add debug shortcode
        add_shortcode('zlaark_debug', array($this, 'debug_shortcode'));
        
        // Add AJAX handler for frontend diagnostics
        add_action('wp_ajax_zlaark_frontend_debug', array($this, 'ajax_frontend_debug'));
        add_action('wp_ajax_nopriv_zlaark_frontend_debug', array($this, 'ajax_frontend_debug'));
    }
    
    /**
     * Display frontend debug information
     */
    public function frontend_debug_info() {
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        ?>
        <div id="zlaark-debug-info" style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #fff; padding: 10px; border-radius: 4px; font-size: 12px; z-index: 9999; max-width: 300px;">
            <strong>🔧 Subscription Debug Info</strong><br>
            <strong>Product ID:</strong> <?php echo $product->get_id(); ?><br>
            <strong>Type:</strong> <?php echo $product->get_type(); ?><br>
            <strong>Class:</strong> <?php echo get_class($product); ?><br>
            <strong>Purchasable:</strong> <?php echo $product->is_purchasable() ? '✅' : '❌'; ?><br>
            <strong>In Stock:</strong> <?php echo $product->is_in_stock() ? '✅' : '❌'; ?><br>
            <strong>Add to Cart Action:</strong> <?php echo did_action('woocommerce_template_single_add_to_cart') ? '✅' : '❌'; ?><br>
            <button onclick="this.parentElement.style.display='none'" style="float: right; background: #fff; color: #000; border: none; padding: 2px 6px; cursor: pointer;">×</button>
        </div>
        <?php
    }
    
    /**
     * Debug shortcode
     */
    public function debug_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '';
        }
        
        $atts = shortcode_atts([
            'type' => 'basic'
        ], $atts);
        
        ob_start();
        
        switch ($atts['type']) {
            case 'product':
                $this->display_product_debug();
                break;
            case 'system':
                $this->display_system_debug();
                break;
            default:
                $this->display_basic_debug();
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Display basic debug information
     */
    private function display_basic_debug() {
        ?>
        <div class="zlaark-debug-basic" style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <h4>🔧 Zlaark Subscriptions Debug</h4>
            <p><strong>Plugin Version:</strong> <?php echo defined('ZLAARK_SUBSCRIPTIONS_VERSION') ? ZLAARK_SUBSCRIPTIONS_VERSION : 'Unknown'; ?></p>
            <p><strong>WooCommerce:</strong> <?php echo class_exists('WooCommerce') ? '✅ Active (v' . WC()->version . ')' : '❌ Not Active'; ?></p>
            <p><strong>Product Types:</strong> <?php echo isset(wc_get_product_types()['subscription']) ? '✅ Subscription Registered' : '❌ Not Registered'; ?></p>
            <p><strong>Product Class:</strong> <?php echo class_exists('WC_Product_Subscription') ? '✅ Available' : '❌ Not Available'; ?></p>
        </div>
        <?php
    }
    
    /**
     * Display product debug information
     */
    private function display_product_debug() {
        global $product;
        
        if (!$product) {
            echo '<p>❌ No product found on this page.</p>';
            return;
        }
        
        ?>
        <div class="zlaark-debug-product" style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <h4>🛍️ Product Debug: <?php echo $product->get_name(); ?></h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td><strong>ID:</strong></td><td><?php echo $product->get_id(); ?></td></tr>
                <tr><td><strong>Type:</strong></td><td><?php echo $product->get_type(); ?></td></tr>
                <tr><td><strong>Class:</strong></td><td><?php echo get_class($product); ?></td></tr>
                <tr><td><strong>Status:</strong></td><td><?php echo $product->get_status(); ?></td></tr>
                <tr><td><strong>Purchasable:</strong></td><td><?php echo $product->is_purchasable() ? '✅ Yes' : '❌ No'; ?></td></tr>
                <tr><td><strong>In Stock:</strong></td><td><?php echo $product->is_in_stock() ? '✅ Yes' : '❌ No'; ?></td></tr>
                
                <?php if ($product->get_type() === 'subscription'): ?>
                    <tr><td><strong>Recurring Price:</strong></td><td>₹<?php echo method_exists($product, 'get_recurring_price') ? $product->get_recurring_price() : 'N/A'; ?></td></tr>
                    <tr><td><strong>Trial Price:</strong></td><td>₹<?php echo method_exists($product, 'get_trial_price') ? $product->get_trial_price() : 'N/A'; ?></td></tr>
                    <tr><td><strong>Has Trial:</strong></td><td><?php echo method_exists($product, 'has_trial') && $product->has_trial() ? '✅ Yes' : '❌ No'; ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }
    
    /**
     * Display system debug information
     */
    private function display_system_debug() {
        ?>
        <div class="zlaark-debug-system" style="background: #f0f0f0; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <h4>⚙️ System Debug</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td><strong>WordPress:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                <tr><td><strong>WooCommerce:</strong></td><td><?php echo class_exists('WooCommerce') ? WC()->version : 'Not Active'; ?></td></tr>
                <tr><td><strong>PHP:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                <tr><td><strong>Debug Mode:</strong></td><td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled'; ?></td></tr>
                <tr><td><strong>Theme:</strong></td><td><?php echo get_template(); ?></td></tr>
                <tr><td><strong>Active Plugins:</strong></td><td><?php echo count(get_option('active_plugins', [])); ?></td></tr>
            </table>
            
            <h5>Hook Status:</h5>
            <table style="width: 100%; border-collapse: collapse;">
                <?php
                $hooks = [
                    'woocommerce_single_product_summary',
                    'woocommerce_template_single_add_to_cart',
                    'product_type_selector',
                    'woocommerce_product_class'
                ];
                
                foreach ($hooks as $hook) {
                    $count = count($GLOBALS['wp_filter'][$hook]->callbacks ?? []);
                    echo "<tr><td><strong>$hook:</strong></td><td>$count callbacks</td></tr>";
                }
                ?>
            </table>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for frontend debug
     */
    public function ajax_frontend_debug() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error('No product ID provided');
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error('Product not found');
        }
        
        $debug_info = [
            'product_id' => $product->get_id(),
            'product_type' => $product->get_type(),
            'product_class' => get_class($product),
            'is_purchasable' => $product->is_purchasable(),
            'is_in_stock' => $product->is_in_stock(),
            'has_price_method' => method_exists($product, 'get_price'),
            'add_to_cart_rendered' => did_action('woocommerce_template_single_add_to_cart') > 0,
            'subscription_methods' => []
        ];
        
        if ($product->get_type() === 'subscription') {
            $debug_info['subscription_methods'] = [
                'has_trial' => method_exists($product, 'has_trial') ? $product->has_trial() : false,
                'recurring_price' => method_exists($product, 'get_recurring_price') ? $product->get_recurring_price() : null,
                'trial_price' => method_exists($product, 'get_trial_price') ? $product->get_trial_price() : null,
            ];
        }
        
        wp_send_json_success($debug_info);
    }
    
    /**
     * Log debug message
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Zlaark Subscriptions [$level]: $message");
        }
    }
    
    /**
     * Check if subscription system is properly initialized
     */
    public static function is_system_healthy() {
        $checks = [
            'woocommerce_active' => class_exists('WooCommerce'),
            'product_type_registered' => isset(wc_get_product_types()['subscription']),
            'product_class_available' => class_exists('WC_Product_Subscription'),
            'template_exists' => file_exists(ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php')
        ];
        
        return !in_array(false, $checks, true);
    }
}
