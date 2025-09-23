<?php
/**
 * Comprehensive Fix for Dual Button Display Issues
 * 
 * This file contains multiple approaches to ensure the dual button system
 * displays correctly on subscription product pages.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ZlaarkDualButtonDisplayFix {
    
    public function __construct() {
        // Multiple hooks to ensure template loading
        add_action('init', array($this, 'init_fixes'), 20);
        add_action('wp', array($this, 'wp_fixes'));
        add_action('woocommerce_loaded', array($this, 'woocommerce_fixes'));
    }
    
    /**
     * Initialize fixes early
     */
    public function init_fixes() {
        // Force asset enqueuing
        add_action('wp_enqueue_scripts', array($this, 'force_enqueue_assets'), 5);
        
        // Add template debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'debug_template_loading'));
        }
    }
    
    /**
     * WordPress-specific fixes
     */
    public function wp_fixes() {
        if (!is_product()) {
            return;
        }
        
        // Force template loading for subscription products
        add_action('woocommerce_single_product_summary', array($this, 'force_dual_button_template'), 25);
        
        // Backup template loading
        add_action('woocommerce_single_product_summary', array($this, 'backup_template_loading'), 35);
    }
    
    /**
     * WooCommerce-specific fixes
     */
    public function woocommerce_fixes() {
        // Override template loading
        add_filter('wc_get_template', array($this, 'override_template_loading'), 20, 5);
        
        // Force subscription template
        add_filter('woocommerce_locate_template', array($this, 'force_subscription_template'), 20, 3);
    }
    
    /**
     * Force enqueue assets
     */
    public function force_enqueue_assets() {
        if (is_product() || is_cart() || is_checkout()) {
            // CSS
            wp_enqueue_style(
                'zlaark-subscriptions-frontend-fix',
                ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                ZLAARK_SUBSCRIPTIONS_VERSION . '-fix'
            );
            
            // JavaScript
            wp_enqueue_script(
                'zlaark-subscriptions-frontend-fix',
                ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                ZLAARK_SUBSCRIPTIONS_VERSION . '-fix',
                true
            );
            
            // Inline CSS fallback
            wp_add_inline_style('zlaark-subscriptions-frontend-fix', $this->get_inline_css());
            
            // Inline JS fallback
            wp_add_inline_script('zlaark-subscriptions-frontend-fix', $this->get_inline_js());
        }
    }
    
    /**
     * Force dual button template loading
     */
    public function force_dual_button_template() {
        global $product;
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        // Check if template was already loaded
        static $template_loaded = false;
        if ($template_loaded) {
            return;
        }
        
        $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
        
        if (file_exists($template_path)) {
            $template_loaded = true;
            
            // Remove default WooCommerce add-to-cart to prevent conflicts
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            
            // Load our template
            include $template_path;
            
            // Mark as loaded
            do_action('woocommerce_template_single_add_to_cart');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "<!-- Zlaark Fix: Forced dual button template load -->";
            }
        }
    }
    
    /**
     * Backup template loading method
     */
    public function backup_template_loading() {
        global $product;
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        // Only load if no add-to-cart template was loaded
        if (did_action('woocommerce_template_single_add_to_cart')) {
            return;
        }
        
        // Render basic dual button system
        $this->render_emergency_dual_buttons($product);
    }
    
    /**
     * Override template loading
     */
    public function override_template_loading($template, $template_name, $args, $template_path, $default_path) {
        if (strpos($template_name, 'single-product/add-to-cart/') === 0) {
            global $product;
            
            if ($product && $product->get_type() === 'subscription') {
                $subscription_template = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
                
                if (file_exists($subscription_template)) {
                    return $subscription_template;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Force subscription template
     */
    public function force_subscription_template($template, $template_name, $template_path) {
        if (strpos($template_name, 'single-product/add-to-cart/') === 0) {
            global $product;
            
            if ($product && $product->get_type() === 'subscription') {
                $subscription_template = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/' . $template_name;
                
                if (file_exists($subscription_template)) {
                    return $subscription_template;
                }
                
                // Fallback to subscription.php
                $fallback = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
                if (file_exists($fallback)) {
                    return $fallback;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Render emergency dual buttons
     */
    private function render_emergency_dual_buttons($product) {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return;
        }
        
        $has_trial = method_exists($product, 'has_trial') && $product->has_trial();
        $user_id = get_current_user_id();
        $trial_available = false;
        
        if ($has_trial && class_exists('ZlaarkSubscriptionsTrialService')) {
            try {
                $trial_service = ZlaarkSubscriptionsTrialService::instance();
                $eligibility = $trial_service->check_trial_eligibility($user_id, $product->get_id());
                $trial_available = $eligibility['eligible'];
            } catch (Exception $e) {
                $trial_available = false;
            }
        }
        
        ?>
        <div class="zlaark-emergency-dual-buttons" style="margin: 20px 0; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px;">
            <p style="margin: 0 0 15px 0; color: #856404; font-weight: bold;">⚠️ Emergency Dual Button System</p>
            
            <form class="cart" action="<?php echo esc_url($product->get_permalink()); ?>" method="post" enctype='multipart/form-data'>
                <input type="hidden" name="subscription_type" id="subscription_type" value="<?php echo ($has_trial && $trial_available) ? 'trial' : 'regular'; ?>" />
                
                <div class="subscription-purchase-options" style="display: grid; grid-template-columns: <?php echo $has_trial ? '1fr 1fr' : '1fr'; ?>; gap: 20px;">
                    <?php if ($has_trial): ?>
                        <div class="trial-cart">
                            <?php if ($trial_available): ?>
                                <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="trial-button emergency-button" data-subscription-type="trial" style="width: 100%; padding: 18px 20px; font-size: 16px; font-weight: bold; border-radius: 10px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                    <span>🎯</span>
                                    <span>
                                        <?php if ($product->get_trial_price() > 0): ?>
                                            <?php printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($product->get_trial_price())); ?>
                                        <?php else: ?>
                                            <?php _e('Start FREE Trial', 'zlaark-subscriptions'); ?>
                                        <?php endif; ?>
                                    </span>
                                </button>
                            <?php else: ?>
                                <div style="padding: 20px; background: #f8d7da; border: 2px solid #f5c6cb; border-radius: 10px; color: #721c24; text-align: center;">
                                    <span style="font-size: 20px;">🚫</span>
                                    <span style="font-weight: 500; font-size: 14px;"><?php _e('Trial Not Available', 'zlaark-subscriptions'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="regular-cart">
                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="regular-button emergency-button" data-subscription-type="regular" style="width: 100%; padding: 18px 20px; font-size: 16px; font-weight: bold; border-radius: 10px; background: linear-gradient(135deg, #007cba 0%, #0056b3 100%); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <span>🚀</span>
                            <span>
                                <?php printf(__('Start Subscription - %s %s', 'zlaark-subscriptions'), wc_price($product->get_recurring_price()), $product->get_billing_interval()); ?>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.emergency-button').on('click', function(e) {
                var subscriptionType = $(this).data('subscription-type');
                $('#subscription_type').val(subscriptionType);
                
                $('.emergency-button').removeClass('selected');
                $(this).addClass('selected');
            });
        });
        </script>
        <?php
        
        // Mark that we've loaded an add-to-cart template
        do_action('woocommerce_template_single_add_to_cart');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "<!-- Zlaark Fix: Emergency dual buttons rendered -->";
        }
    }
    
    /**
     * Get inline CSS
     */
    private function get_inline_css() {
        return '
        .emergency-button:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2) !important;
        }
        
        .emergency-button.selected {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2) !important;
        }
        
        @media (max-width: 768px) {
            .subscription-purchase-options {
                grid-template-columns: 1fr !important;
            }
        }
        ';
    }
    
    /**
     * Get inline JS
     */
    private function get_inline_js() {
        return '
        console.log("Zlaark Subscriptions: Dual button fix loaded");
        
        // Ensure buttons work even if main JS fails
        jQuery(document).ready(function($) {
            $(document).on("click", ".trial-button, .regular-button", function(e) {
                var subscriptionType = $(this).data("subscription-type");
                if (subscriptionType) {
                    $("#subscription_type").val(subscriptionType);
                    console.log("Zlaark: Subscription type set to", subscriptionType);
                }
            });
        });
        ';
    }
    
    /**
     * Debug template loading
     */
    public function debug_template_loading() {
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        ?>
        <div style="position: fixed; bottom: 20px; left: 20px; background: #000; color: #fff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; z-index: 9999; max-width: 300px;">
            <strong>Zlaark Debug:</strong><br>
            Product ID: <?php echo $product->get_id(); ?><br>
            Type: <?php echo $product->get_type(); ?><br>
            Template Action: <?php echo did_action('woocommerce_template_single_add_to_cart') ? 'Fired' : 'Not Fired'; ?><br>
            Buttons: <span id="debug-button-count">Checking...</span>
        </div>
        
        <script>
        setTimeout(function() {
            var buttons = document.querySelectorAll('.trial-button, .regular-button');
            document.getElementById('debug-button-count').textContent = buttons.length + ' found';
        }, 1000);
        </script>
        <?php
    }
}

// Initialize the fix
new ZlaarkDualButtonDisplayFix();
