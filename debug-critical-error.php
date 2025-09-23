<?php
/**
 * Debug Critical Error in Trial Button Shortcode
 * 
 * This script helps identify what's causing the fatal error
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for debugging
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Critical Error',
        'Debug Critical Error',
        'manage_options',
        'debug-critical-error',
        'zlaark_debug_critical_error_page'
    );
});

function zlaark_debug_critical_error_page() {
    ?>
    <div class="wrap">
        <h1>🚨 Debug Critical Error</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔍 Step-by-Step Error Detection</h2>
            
            <?php
            $product_id = 3425;
            $errors = array();
            $step = 1;
            
            echo '<h3>Step ' . $step++ . ': Check if WooCommerce is active</h3>';
            if (class_exists('WooCommerce')) {
                echo '<p>✅ WooCommerce is active</p>';
            } else {
                echo '<p>❌ WooCommerce is not active</p>';
                $errors[] = 'WooCommerce not active';
            }
            
            echo '<h3>Step ' . $step++ . ': Check if product exists</h3>';
            try {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo '<p>✅ Product exists: ' . esc_html($product->get_name()) . '</p>';
                    echo '<p>Product class: ' . get_class($product) . '</p>';
                } else {
                    echo '<p>❌ Product not found</p>';
                    $errors[] = 'Product not found';
                }
            } catch (Exception $e) {
                echo '<p>❌ Error loading product: ' . esc_html($e->getMessage()) . '</p>';
                $errors[] = 'Product loading error: ' . $e->getMessage();
            }
            
            echo '<h3>Step ' . $step++ . ': Check ZlaarkSubscriptionsProductType class</h3>';
            if (class_exists('ZlaarkSubscriptionsProductType')) {
                echo '<p>✅ ZlaarkSubscriptionsProductType class exists</p>';
                
                try {
                    $result = ZlaarkSubscriptionsProductType::force_registration_for_diagnostics();
                    echo '<p>✅ force_registration_for_diagnostics() executed successfully</p>';
                } catch (Exception $e) {
                    echo '<p>❌ Error in force_registration_for_diagnostics(): ' . esc_html($e->getMessage()) . '</p>';
                    $errors[] = 'force_registration_for_diagnostics error: ' . $e->getMessage();
                }
            } else {
                echo '<p>❌ ZlaarkSubscriptionsProductType class not found</p>';
                $errors[] = 'ZlaarkSubscriptionsProductType class not found';
            }
            
            echo '<h3>Step ' . $step++ . ': Check trial methods</h3>';
            if (isset($product) && $product) {
                if (method_exists($product, 'has_trial')) {
                    echo '<p>✅ has_trial method exists</p>';
                    try {
                        $has_trial = $product->has_trial();
                        echo '<p>✅ has_trial() executed: ' . ($has_trial ? 'true' : 'false') . '</p>';
                    } catch (Exception $e) {
                        echo '<p>❌ Error in has_trial(): ' . esc_html($e->getMessage()) . '</p>';
                        $errors[] = 'has_trial error: ' . $e->getMessage();
                    }
                } else {
                    echo '<p>❌ has_trial method not found</p>';
                    $errors[] = 'has_trial method not found';
                }
                
                if (method_exists($product, 'get_trial_duration')) {
                    echo '<p>✅ get_trial_duration method exists</p>';
                    try {
                        $duration = $product->get_trial_duration();
                        echo '<p>✅ get_trial_duration() executed: ' . var_export($duration, true) . '</p>';
                    } catch (Exception $e) {
                        echo '<p>❌ Error in get_trial_duration(): ' . esc_html($e->getMessage()) . '</p>';
                        $errors[] = 'get_trial_duration error: ' . $e->getMessage();
                    }
                } else {
                    echo '<p>❌ get_trial_duration method not found</p>';
                    $errors[] = 'get_trial_duration method not found';
                }
                
                if (method_exists($product, 'get_trial_price')) {
                    echo '<p>✅ get_trial_price method exists</p>';
                    try {
                        $price = $product->get_trial_price();
                        echo '<p>✅ get_trial_price() executed: ' . var_export($price, true) . '</p>';
                    } catch (Exception $e) {
                        echo '<p>❌ Error in get_trial_price(): ' . esc_html($e->getMessage()) . '</p>';
                        $errors[] = 'get_trial_price error: ' . $e->getMessage();
                    }
                } else {
                    echo '<p>❌ get_trial_price method not found</p>';
                    $errors[] = 'get_trial_price method not found';
                }
            }
            
            echo '<h3>Step ' . $step++ . ': Check ZlaarkSubscriptionsTrialService</h3>';
            if (class_exists('ZlaarkSubscriptionsTrialService')) {
                echo '<p>✅ ZlaarkSubscriptionsTrialService class exists</p>';
                try {
                    $trial_service = ZlaarkSubscriptionsTrialService::instance();
                    echo '<p>✅ Trial service instance created</p>';
                    
                    $user_id = get_current_user_id();
                    if ($user_id) {
                        $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product_id);
                        echo '<p>✅ Trial eligibility check completed</p>';
                    } else {
                        echo '<p>⚠️ No user logged in for eligibility check</p>';
                    }
                } catch (Exception $e) {
                    echo '<p>❌ Error in trial service: ' . esc_html($e->getMessage()) . '</p>';
                    $errors[] = 'Trial service error: ' . $e->getMessage();
                }
            } else {
                echo '<p>❌ ZlaarkSubscriptionsTrialService class not found</p>';
                $errors[] = 'ZlaarkSubscriptionsTrialService class not found';
            }
            
            echo '<h3>Step ' . $step++ . ': Test shortcode execution (safe mode)</h3>';
            try {
                // Test shortcode in safe mode
                ob_start();
                $shortcode_result = do_shortcode('[trial_button product_id="' . $product_id . '"]');
                $output = ob_get_clean();
                
                if ($shortcode_result) {
                    echo '<p>✅ Shortcode executed successfully</p>';
                    echo '<div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
                    echo $shortcode_result;
                    echo '</div>';
                } else {
                    echo '<p>❌ Shortcode returned empty result</p>';
                    $errors[] = 'Shortcode returned empty result';
                }
                
                if ($output) {
                    echo '<p>⚠️ Shortcode produced output: ' . esc_html($output) . '</p>';
                }
                
            } catch (Exception $e) {
                echo '<p>❌ Fatal error in shortcode: ' . esc_html($e->getMessage()) . '</p>';
                $errors[] = 'Shortcode fatal error: ' . $e->getMessage();
            } catch (Error $e) {
                echo '<p>❌ PHP Error in shortcode: ' . esc_html($e->getMessage()) . '</p>';
                $errors[] = 'Shortcode PHP error: ' . $e->getMessage();
            }
            
            echo '<h3>📋 Error Summary</h3>';
            if (empty($errors)) {
                echo '<p style="color: green;">✅ No errors detected! The shortcode should work.</p>';
            } else {
                echo '<p style="color: red;">❌ Errors found:</p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li style="color: red;">' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🔧 Safe Shortcode Test</h2>
            <p>Testing a minimal version of the shortcode:</p>
            
            <?php
            // Create a minimal safe version of the shortcode
            try {
                echo '<div style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
                
                // Minimal shortcode test
                $safe_product = wc_get_product($product_id);
                if ($safe_product && $safe_product->get_type() === 'subscription') {
                    echo '<p>✅ Product loaded safely</p>';
                    
                    if (method_exists($safe_product, 'has_trial') && $safe_product->has_trial()) {
                        echo '<p>✅ Product has trial</p>';
                        echo '<button type="button" class="button">Safe Trial Button Test</button>';
                    } else {
                        echo '<p>❌ Product does not have trial</p>';
                    }
                } else {
                    echo '<p>❌ Product not valid for trial</p>';
                }
                
                echo '</div>';
            } catch (Exception $e) {
                echo '<p style="color: red;">❌ Even safe test failed: ' . esc_html($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>💡 Next Steps</h2>
            <p>Based on the errors found above:</p>
            <ul>
                <li>If class/method errors: Check if files are properly loaded</li>
                <li>If product errors: Verify product type and meta data</li>
                <li>If shortcode errors: Check for infinite loops or memory issues</li>
                <li>If no errors found: The issue might be theme/plugin conflicts</li>
            </ul>
        </div>
    </div>
    <?php
}
