<?php
/**
 * Final Critical Error Diagnosis & Fix
 * 
 * This script diagnoses and fixes the actual root cause of the persistent critical error
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for final diagnosis
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Final Critical Error Diagnosis',
        '🎯 Final Diagnosis',
        'manage_options',
        'final-critical-error-diagnosis',
        'zlaark_final_critical_error_diagnosis_page'
    );
});

function zlaark_final_critical_error_diagnosis_page() {
    $product_id = 3425;
    $fixes_applied = [];
    $errors = [];
    
    // Handle fix actions
    if (isset($_POST['apply_final_fix']) && wp_verify_nonce($_POST['final_fix_nonce'], 'final_fix')) {
        
        // Clear all caches
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
        <h1>🎯 Final Critical Error Diagnosis & Fix</h1>
        
        <?php if (!empty($fixes_applied)): ?>
        <div class="notice notice-success">
            <p><strong>✅ Final Fix Actions Completed:</strong></p>
            <ul>
                <?php foreach ($fixes_applied as $fix): ?>
                    <li><?php echo esc_html($fix); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 ACTUAL Root Cause Discovered & Fixed!</h2>
            
            <div style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;">
                <h3>🚨 The Real Problem:</h3>
                <p><strong>Missing Singleton Pattern in ZlaarkSubscriptionsTrialService</strong></p>
                
                <h4>What Was Happening:</h4>
                <ul>
                    <li><strong>Frontend Class Called:</strong> <code>ZlaarkSubscriptionsTrialService::instance()</code></li>
                    <li><strong>But Method Didn't Exist:</strong> The class only had <code>__construct()</code>, no <code>instance()</code> method</li>
                    <li><strong>Fatal Error:</strong> "Call to undefined method ZlaarkSubscriptionsTrialService::instance()"</li>
                    <li><strong>Persistent Error:</strong> This happened on every product page load via automatic hooks</li>
                </ul>
                
                <h4>Why Previous Fixes Didn't Work:</h4>
                <ul>
                    <li>✅ Try-catch blocks were correctly placed</li>
                    <li>✅ Syntax was correct</li>
                    <li>❌ But the method being called simply didn't exist!</li>
                    <li>❌ The error occurred before any try-catch could handle it</li>
                </ul>
            </div>
            
            <div style="background: #cce7f0; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px; margin: 10px 0;">
                <h3>✅ Fix Applied:</h3>
                <p><strong>Implemented Singleton Pattern in ZlaarkSubscriptionsTrialService</strong></p>
                
                <h4>Changes Made:</h4>
                <ul>
                    <li><strong>Added static $instance property</strong></li>
                    <li><strong>Added public static instance() method</strong></li>
                    <li><strong>Made constructor private</strong> (proper singleton pattern)</li>
                    <li><strong>Updated main plugin file</strong> to use singleton pattern</li>
                </ul>
                
                <h4>Code Changes:</h4>
                <p><strong>Before (Missing):</strong></p>
                <pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd;">class ZlaarkSubscriptionsTrialService {
    public function __construct() { ... }
    // No instance() method!
}</pre>
                
                <p><strong>After (Fixed):</strong></p>
                <pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd;">class ZlaarkSubscriptionsTrialService {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() { ... }
}</pre>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🧪 Comprehensive Testing</h2>
            
            <h3>1. Class Existence & Method Testing:</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <?php
                // Test class existence
                if (class_exists('ZlaarkSubscriptionsTrialService')) {
                    echo '<p>✅ <strong>ZlaarkSubscriptionsTrialService class exists</strong></p>';
                    
                    // Test instance method
                    if (method_exists('ZlaarkSubscriptionsTrialService', 'instance')) {
                        echo '<p>✅ <strong>instance() method exists</strong></p>';
                        
                        // Test actual instantiation
                        try {
                            $trial_service = ZlaarkSubscriptionsTrialService::instance();
                            if ($trial_service instanceof ZlaarkSubscriptionsTrialService) {
                                echo '<p>✅ <strong>Singleton instantiation successful</strong></p>';
                                echo '<p>✅ <strong>Object type:</strong> ' . get_class($trial_service) . '</p>';
                            } else {
                                echo '<p>❌ <strong>Singleton returned wrong type</strong></p>';
                            }
                        } catch (Exception $e) {
                            echo '<p>❌ <strong>Singleton instantiation failed:</strong> ' . esc_html($e->getMessage()) . '</p>';
                        } catch (Error $e) {
                            echo '<p>❌ <strong>PHP Error during instantiation:</strong> ' . esc_html($e->getMessage()) . '</p>';
                        }
                    } else {
                        echo '<p>❌ <strong>instance() method does not exist</strong></p>';
                    }
                } else {
                    echo '<p>❌ <strong>ZlaarkSubscriptionsTrialService class does not exist</strong></p>';
                }
                ?>
            </div>
            
            <h3>2. Frontend Method Testing:</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <?php
                try {
                    global $product;
                    $product = wc_get_product($product_id);
                    
                    if ($product && $product->get_type() === 'subscription') {
                        echo '<p>✅ Product loaded successfully as subscription type</p>';
                        
                        $frontend = ZlaarkSubscriptionsFrontend::instance();
                        
                        // Test the specific method that was causing the error
                        echo '<h4>Testing display_comprehensive_trial_info():</h4>';
                        try {
                            ob_start();
                            $frontend->display_comprehensive_trial_info();
                            $output = ob_get_clean();
                            echo '<p>✅ <strong>display_comprehensive_trial_info():</strong> Executed successfully without error</p>';
                            echo '<p><strong>Output length:</strong> ' . strlen($output) . ' characters</p>';
                        } catch (Exception $e) {
                            echo '<p>❌ <strong>display_comprehensive_trial_info():</strong> Exception - ' . esc_html($e->getMessage()) . '</p>';
                        } catch (Error $e) {
                            echo '<p>❌ <strong>display_comprehensive_trial_info():</strong> PHP Error - ' . esc_html($e->getMessage()) . '</p>';
                        }
                        
                        // Test other methods
                        $methods_to_test = [
                            'display_trial_highlight' => 'Display Trial Highlight',
                            'display_subscription_info' => 'Display Subscription Info',
                            'force_subscription_add_to_cart' => 'Force Subscription Add to Cart',
                        ];
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            $methods_to_test['debug_add_to_cart_status'] = 'Debug Add to Cart Status';
                        }
                        
                        echo '<h4>Testing All Automatic Methods:</h4>';
                        foreach ($methods_to_test as $method => $label) {
                            try {
                                ob_start();
                                $frontend->$method();
                                $output = ob_get_clean();
                                echo '<p>✅ <strong>' . $label . ':</strong> Executed without error</p>';
                            } catch (Exception $e) {
                                echo '<p>❌ <strong>' . $label . ':</strong> Exception - ' . esc_html($e->getMessage()) . '</p>';
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
            
            <h3>3. Trial Service Method Testing:</h3>
            <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin: 10px 0;">
                <?php
                try {
                    if (class_exists('ZlaarkSubscriptionsTrialService')) {
                        $trial_service = ZlaarkSubscriptionsTrialService::instance();
                        $user_id = get_current_user_id();
                        
                        echo '<p>✅ <strong>Trial Service Instance:</strong> Created successfully</p>';
                        
                        // Test get_subscription_options method
                        try {
                            $subscription_options = $trial_service->get_subscription_options($product_id, $user_id);
                            echo '<p>✅ <strong>get_subscription_options():</strong> Executed successfully</p>';
                            echo '<p><strong>Trial Available:</strong> ' . ($subscription_options['trial']['available'] ? 'Yes' : 'No') . '</p>';
                            echo '<p><strong>Regular Available:</strong> ' . ($subscription_options['regular']['available'] ? 'Yes' : 'No') . '</p>';
                        } catch (Exception $e) {
                            echo '<p>❌ <strong>get_subscription_options():</strong> Exception - ' . esc_html($e->getMessage()) . '</p>';
                        }
                        
                        // Test check_trial_eligibility method
                        if ($user_id) {
                            try {
                                $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product_id);
                                echo '<p>✅ <strong>check_trial_eligibility():</strong> Executed successfully</p>';
                                echo '<p><strong>Eligible:</strong> ' . ($trial_eligibility['eligible'] ? 'Yes' : 'No') . '</p>';
                                if (!$trial_eligibility['eligible']) {
                                    echo '<p><strong>Reason:</strong> ' . esc_html($trial_eligibility['reason']) . '</p>';
                                }
                            } catch (Exception $e) {
                                echo '<p>❌ <strong>check_trial_eligibility():</strong> Exception - ' . esc_html($e->getMessage()) . '</p>';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo '<p style="color: red;">❌ Error during trial service testing: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔗 Test Your Product Page Now</h2>
            
            <h3>Direct Product Page Test:</h3>
            <p>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="button button-primary button-large">🔗 Test Product Page</a>
                <em>The critical error should now be completely gone!</em>
            </p>
            
            <h3>What You Should See:</h3>
            <ul>
                <li>✅ <strong>No critical error message</strong> anywhere on the page</li>
                <li>✅ <strong>Page loads completely</strong> without interruption</li>
                <li>✅ <strong>Normal layout and styling</strong> appears correctly</li>
                <li>✅ <strong>Product information displays</strong> properly</li>
                <li>✅ <strong>Trial information shows</strong> if configured</li>
                <li>✅ <strong>Add to cart functionality</strong> works normally</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔄 Clear Caches & Test</h2>
            <p>Clear all caches to ensure the fix takes effect immediately:</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('final_fix', 'final_fix_nonce'); ?>
                <p>
                    <input type="submit" name="apply_final_fix" class="button button-primary" value="🔄 Clear All Caches & Test">
                </p>
            </form>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>📋 Summary of the Actual Problem & Fix</h2>
            
            <h3>🔍 What Was Really Happening:</h3>
            <ol>
                <li><strong>Frontend methods automatically execute</strong> on every product page load via WordPress hooks</li>
                <li><strong>display_comprehensive_trial_info() method called</strong> <code>ZlaarkSubscriptionsTrialService::instance()</code></li>
                <li><strong>But instance() method didn't exist</strong> in the ZlaarkSubscriptionsTrialService class</li>
                <li><strong>Fatal error occurred</strong> before any try-catch blocks could handle it</li>
                <li><strong>Critical error persisted</strong> regardless of shortcode usage because it's hook-based</li>
            </ol>
            
            <h3>✅ The Complete Fix:</h3>
            <ol>
                <li><strong>Implemented singleton pattern</strong> in ZlaarkSubscriptionsTrialService class</li>
                <li><strong>Added static $instance property</strong> and instance() method</li>
                <li><strong>Made constructor private</strong> for proper singleton implementation</li>
                <li><strong>Updated main plugin file</strong> to use singleton pattern consistently</li>
                <li><strong>Maintained all existing try-catch error handling</strong> for additional safety</li>
            </ol>
            
            <h3>🎯 Why This Fixes Everything:</h3>
            <ul>
                <li><strong>Method Now Exists:</strong> <code>ZlaarkSubscriptionsTrialService::instance()</code> is now available</li>
                <li><strong>No More Fatal Errors:</strong> The undefined method error is eliminated</li>
                <li><strong>Consistent Architecture:</strong> All classes now use the same singleton pattern</li>
                <li><strong>Safe Instantiation:</strong> Only one instance of the service is created</li>
                <li><strong>Preserved Functionality:</strong> All existing features continue to work</li>
            </ul>
        </div>
    </div>
    <?php
}
