<?php
/**
 * Comprehensive Debug Script for Dual Button Display Issues
 * 
 * This script diagnoses why the dual button system is not displaying
 * on the subscription product page.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ZlaarkDualButtonDebugger {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'debug_output'));
        add_action('wp_head', array($this, 'debug_styles'));
    }
    
    /**
     * Add debug styles
     */
    public function debug_styles() {
        if (!is_product()) {
            return;
        }
        
        ?>
        <style>
        .zlaark-debug-panel {
            position: fixed;
            top: 50px;
            right: 20px;
            width: 400px;
            max-height: 80vh;
            overflow-y: auto;
            background: #1e1e1e;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        
        .zlaark-debug-panel h3 {
            color: #4CAF50;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .zlaark-debug-section {
            margin-bottom: 15px;
            padding: 10px;
            background: #2d2d2d;
            border-radius: 4px;
        }
        
        .zlaark-debug-section h4 {
            color: #FFC107;
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        
        .debug-success { color: #4CAF50; }
        .debug-error { color: #F44336; }
        .debug-warning { color: #FF9800; }
        .debug-info { color: #2196F3; }
        
        .debug-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            z-index: 10000;
        }
        </style>
        <script>
        function toggleDebugPanel() {
            var panel = document.querySelector('.zlaark-debug-panel');
            if (panel) {
                panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            }
        }
        </script>
        <?php
    }
    
    /**
     * Main debug output
     */
    public function debug_output() {
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        ?>
        <button class="debug-toggle" onclick="toggleDebugPanel()">🐛 Debug</button>
        <div class="zlaark-debug-panel" style="display: none;">
            <h3>🔍 Zlaark Dual Button Debug Panel</h3>
            
            <?php $this->debug_product_info($product); ?>
            <?php $this->debug_template_loading(); ?>
            <?php $this->debug_css_js_loading(); ?>
            <?php $this->debug_trial_service(); ?>
            <?php $this->debug_dom_elements(); ?>
            <?php $this->debug_hooks_and_actions(); ?>
            <?php $this->debug_recommendations(); ?>
        </div>
        <?php
    }
    
    /**
     * Debug product information
     */
    private function debug_product_info($product) {
        ?>
        <div class="zlaark-debug-section">
            <h4>📦 Product Information</h4>
            <?php if ($product): ?>
                <div class="debug-success">✅ Product exists: ID <?php echo $product->get_id(); ?></div>
                <div class="<?php echo $product->get_type() === 'subscription' ? 'debug-success' : 'debug-error'; ?>">
                    <?php echo $product->get_type() === 'subscription' ? '✅' : '❌'; ?> Product type: <?php echo $product->get_type(); ?>
                </div>
                <div class="<?php echo $product->is_purchasable() ? 'debug-success' : 'debug-error'; ?>">
                    <?php echo $product->is_purchasable() ? '✅' : '❌'; ?> Is purchasable: <?php echo $product->is_purchasable() ? 'Yes' : 'No'; ?>
                </div>
                <div class="<?php echo $product->is_in_stock() ? 'debug-success' : 'debug-error'; ?>">
                    <?php echo $product->is_in_stock() ? '✅' : '❌'; ?> In stock: <?php echo $product->is_in_stock() ? 'Yes' : 'No'; ?>
                </div>
                
                <?php if ($product->get_type() === 'subscription'): ?>
                    <div class="<?php echo method_exists($product, 'has_trial') ? 'debug-success' : 'debug-error'; ?>">
                        <?php echo method_exists($product, 'has_trial') ? '✅' : '❌'; ?> Has trial method: <?php echo method_exists($product, 'has_trial') ? 'Yes' : 'No'; ?>
                    </div>
                    <?php if (method_exists($product, 'has_trial')): ?>
                        <div class="<?php echo $product->has_trial() ? 'debug-success' : 'debug-warning'; ?>">
                            <?php echo $product->has_trial() ? '✅' : '⚠️'; ?> Trial enabled: <?php echo $product->has_trial() ? 'Yes' : 'No'; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="debug-error">❌ No product found</div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Debug template loading
     */
    private function debug_template_loading() {
        ?>
        <div class="zlaark-debug-section">
            <h4>📄 Template Loading</h4>
            <?php
            $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
            ?>
            <div class="<?php echo file_exists($template_path) ? 'debug-success' : 'debug-error'; ?>">
                <?php echo file_exists($template_path) ? '✅' : '❌'; ?> Template file exists
            </div>
            <div class="debug-info">📁 Path: <?php echo esc_html($template_path); ?></div>
            
            <?php
            $template_loaded = did_action('woocommerce_template_single_add_to_cart');
            ?>
            <div class="<?php echo $template_loaded ? 'debug-success' : 'debug-warning'; ?>">
                <?php echo $template_loaded ? '✅' : '⚠️'; ?> WC template action fired: <?php echo $template_loaded ? 'Yes' : 'No'; ?>
            </div>
            
            <?php
            // Check if our template hooks are registered
            global $wp_filter;
            $wc_get_template_hooks = isset($wp_filter['wc_get_template']) ? count($wp_filter['wc_get_template']->callbacks) : 0;
            ?>
            <div class="debug-info">🔗 wc_get_template hooks: <?php echo $wc_get_template_hooks; ?></div>
        </div>
        <?php
    }
    
    /**
     * Debug CSS and JS loading
     */
    private function debug_css_js_loading() {
        ?>
        <div class="zlaark-debug-section">
            <h4>🎨 CSS & JS Loading</h4>
            <?php
            $css_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/frontend.css';
            $js_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/frontend.js';
            ?>
            <div class="debug-info">📄 CSS URL: <?php echo esc_html($css_path); ?></div>
            <div class="debug-info">📄 JS URL: <?php echo esc_html($js_path); ?></div>
            
            <div class="debug-warning">⚠️ Check browser dev tools for 404 errors</div>
            <div class="debug-info">💡 jQuery loaded: <?php echo wp_script_is('jquery', 'done') ? 'Yes' : 'No'; ?></div>
        </div>
        <?php
    }
    
    /**
     * Debug trial service
     */
    private function debug_trial_service() {
        ?>
        <div class="zlaark-debug-section">
            <h4>🎯 Trial Service</h4>
            <?php
            $trial_service_exists = class_exists('ZlaarkSubscriptionsTrialService');
            ?>
            <div class="<?php echo $trial_service_exists ? 'debug-success' : 'debug-error'; ?>">
                <?php echo $trial_service_exists ? '✅' : '❌'; ?> Trial service class exists
            </div>
            
            <?php if ($trial_service_exists): ?>
                <?php
                global $product;
                if ($product && $product->get_type() === 'subscription') {
                    $user_id = get_current_user_id();
                    ?>
                    <div class="debug-info">👤 User ID: <?php echo $user_id; ?></div>
                    
                    <?php if ($user_id): ?>
                        <?php
                        try {
                            $trial_service = ZlaarkSubscriptionsTrialService::instance();
                            $eligibility = $trial_service->check_trial_eligibility($user_id, $product->get_id());
                            ?>
                            <div class="<?php echo $eligibility['eligible'] ? 'debug-success' : 'debug-warning'; ?>">
                                <?php echo $eligibility['eligible'] ? '✅' : '⚠️'; ?> Trial eligible: <?php echo $eligibility['eligible'] ? 'Yes' : 'No'; ?>
                            </div>
                            <?php if (!$eligibility['eligible']): ?>
                                <div class="debug-info">📝 Reason: <?php echo esc_html($eligibility['reason']); ?></div>
                            <?php endif; ?>
                        <?php } catch (Exception $e) { ?>
                            <div class="debug-error">❌ Trial service error: <?php echo esc_html($e->getMessage()); ?></div>
                        <?php } ?>
                    <?php else: ?>
                        <div class="debug-warning">⚠️ User not logged in</div>
                    <?php endif; ?>
                <?php } ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Debug DOM elements
     */
    private function debug_dom_elements() {
        ?>
        <div class="zlaark-debug-section">
            <h4>🌐 DOM Elements</h4>
            <div class="debug-info">Use browser dev tools to check for:</div>
            <div class="debug-info">• .subscription-purchase-options</div>
            <div class="debug-info">• .trial-button</div>
            <div class="debug-info">• .regular-button</div>
            <div class="debug-info">• #subscription_type input</div>
            
            <script>
            // Check for elements in DOM
            setTimeout(function() {
                var elements = [
                    '.subscription-purchase-options',
                    '.trial-button',
                    '.regular-button',
                    '#subscription_type'
                ];
                
                elements.forEach(function(selector) {
                    var element = document.querySelector(selector);
                    var debugElement = document.querySelector('.dom-check-' + selector.replace(/[^a-zA-Z0-9]/g, ''));
                    if (debugElement) {
                        if (element) {
                            debugElement.innerHTML = '✅ Found: ' + selector;
                            debugElement.className += ' debug-success';
                        } else {
                            debugElement.innerHTML = '❌ Missing: ' + selector;
                            debugElement.className += ' debug-error';
                        }
                    }
                });
            }, 1000);
            </script>
            
            <div class="dom-check-subscriptionpurchaseoptions">🔍 Checking...</div>
            <div class="dom-check-trialbutton">🔍 Checking...</div>
            <div class="dom-check-regularbutton">🔍 Checking...</div>
            <div class="dom-check-subscriptiontype">🔍 Checking...</div>
        </div>
        <?php
    }
    
    /**
     * Debug hooks and actions
     */
    private function debug_hooks_and_actions() {
        ?>
        <div class="zlaark-debug-section">
            <h4>🔗 Hooks & Actions</h4>
            <?php
            global $wp_filter;
            
            $important_hooks = array(
                'woocommerce_single_product_summary',
                'wc_get_template',
                'woocommerce_template_single_add_to_cart'
            );
            
            foreach ($important_hooks as $hook) {
                $count = isset($wp_filter[$hook]) ? count($wp_filter[$hook]->callbacks) : 0;
                ?>
                <div class="debug-info">🔗 <?php echo $hook; ?>: <?php echo $count; ?> callbacks</div>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Debug recommendations
     */
    private function debug_recommendations() {
        ?>
        <div class="zlaark-debug-section">
            <h4>💡 Recommendations</h4>
            <div class="debug-info">1. Check browser console for JavaScript errors</div>
            <div class="debug-info">2. Verify CSS is loading (check Network tab)</div>
            <div class="debug-info">3. Ensure product is subscription type</div>
            <div class="debug-info">4. Check if template is being overridden by theme</div>
            <div class="debug-info">5. Verify user permissions and login status</div>
        </div>
        <?php
    }
}

// Initialize debugger on product pages
if (is_product()) {
    new ZlaarkDualButtonDebugger();
}
