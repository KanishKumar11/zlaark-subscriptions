<?php
/**
 * Root Cause Analysis for AJAX Subscription Button Issues
 * 
 * This script performs comprehensive debugging to identify the actual underlying problems:
 * 1. Trial Button Stuck in Processing
 * 2. Subscription Button Visual Glitch (disappears/reappears)
 */

// Security check
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'zlaark2025') {
    die('Access denied. Add ?debug_key=zlaark2025 to the URL.');
}

echo "<h1>🔍 ROOT CAUSE ANALYSIS: AJAX SUBSCRIPTION BUTTONS</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Current user context
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>⚠️ CRITICAL ISSUES BEING INVESTIGATED</h3>";
echo "<p><strong>Issue 1:</strong> Trial Button Stuck in Processing (permanent loading state)</p>";
echo "<p><strong>Issue 2:</strong> Subscription Button Visual Glitch (disappears for 1 second)</p>";
echo "<p><strong>User Context:</strong> " . ($is_logged_in ? $current_user->user_login . " (ID: {$current_user->ID})" : 'Not logged in') . "</p>";
echo "</div>";

// ANALYSIS 1: AJAX Handler Registration
echo "<h2>1. 🔧 AJAX HANDLER REGISTRATION ANALYSIS</h2>";

$frontend_class_path = 'includes/frontend/class-zlaark-subscriptions-frontend.php';
$ajax_registration_issues = [];

if (file_exists($frontend_class_path)) {
    $frontend_content = file_get_contents($frontend_class_path);
    
    // Check AJAX hook registration
    $has_ajax_hook = strpos($frontend_content, 'wp_ajax_zlaark_add_subscription_to_cart') !== false;
    $has_nopriv_hook = strpos($frontend_content, 'wp_ajax_nopriv_zlaark_add_subscription_to_cart') !== false;
    $has_handler_method = strpos($frontend_content, 'function ajax_add_subscription_to_cart') !== false;
    
    echo "<h3>✅ AJAX Hook Registration</h3>";
    echo "<p>Logged-in user hook: " . ($has_ajax_hook ? '✅ REGISTERED' : '❌ MISSING') . "</p>";
    echo "<p>Non-logged-in user hook: " . ($has_nopriv_hook ? '✅ REGISTERED' : '❌ MISSING') . "</p>";
    echo "<p>Handler method exists: " . ($has_handler_method ? '✅ EXISTS' : '❌ MISSING') . "</p>";
    
    if (!$has_ajax_hook || !$has_nopriv_hook || !$has_handler_method) {
        $ajax_registration_issues[] = "AJAX handler registration incomplete";
    }
    
    // Check if class is instantiated
    if (class_exists('ZlaarkSubscriptionsFrontend')) {
        echo "<p>Frontend class loaded: ✅ YES</p>";
        
        // Check if hooks are actually registered
        $registered_actions = $GLOBALS['wp_filter']['wp_ajax_zlaark_add_subscription_to_cart'] ?? null;
        $registered_nopriv = $GLOBALS['wp_filter']['wp_ajax_nopriv_zlaark_add_subscription_to_cart'] ?? null;
        
        echo "<p>AJAX hook active: " . ($registered_actions ? '✅ YES' : '❌ NO') . "</p>";
        echo "<p>No-priv hook active: " . ($registered_nopriv ? '✅ YES' : '❌ NO') . "</p>";
        
        if (!$registered_actions || !$registered_nopriv) {
            $ajax_registration_issues[] = "AJAX hooks not properly registered in WordPress";
        }
    } else {
        echo "<p>Frontend class loaded: ❌ NO</p>";
        $ajax_registration_issues[] = "ZlaarkSubscriptionsFrontend class not loaded";
    }
} else {
    $ajax_registration_issues[] = "Frontend class file not found";
}

// ANALYSIS 2: JavaScript Implementation Issues
echo "<h2>2. ⚡ JAVASCRIPT IMPLEMENTATION ANALYSIS</h2>";

$template_path = 'templates/single-product/add-to-cart/subscription.php';
$js_implementation_issues = [];

if (file_exists($template_path)) {
    $template_content = file_get_contents($template_path);
    
    // Check JavaScript structure
    $has_jquery_ready = strpos($template_content, 'jQuery(document).ready') !== false;
    $has_ajax_call = strpos($template_content, '$.ajax({') !== false;
    $has_click_handler = strpos($template_content, '.on(\'click.zlaark\'') !== false;
    $has_prevent_default = strpos($template_content, 'e.preventDefault()') !== false;
    
    echo "<h3>✅ JavaScript Structure</h3>";
    echo "<p>jQuery ready wrapper: " . ($has_jquery_ready ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>AJAX call implementation: " . ($has_ajax_call ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Click handler registration: " . ($has_click_handler ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Prevent default behavior: " . ($has_prevent_default ? '✅ YES' : '❌ NO') . "</p>";
    
    // Check for potential issues
    $product_id_extraction = strpos($template_content, '$button.val() || $button.closest(\'form\').find(\'[name="add-to-cart"]\').val()') !== false;
    $nonce_generation = strpos($template_content, 'wp_create_nonce(\'zlaark_subscriptions_frontend_nonce\')') !== false;
    $admin_ajax_url = strpos($template_content, 'admin_url(\'admin-ajax.php\')') !== false;
    
    echo "<h3>🔍 Potential Issue Points</h3>";
    echo "<p>Product ID extraction method: " . ($product_id_extraction ? '✅ COMPLEX METHOD' : '❌ SIMPLE METHOD') . "</p>";
    echo "<p>Nonce generation: " . ($nonce_generation ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Admin AJAX URL: " . ($admin_ajax_url ? '✅ YES' : '❌ NO') . "</p>";
    
    if (!$product_id_extraction) {
        $js_implementation_issues[] = "Product ID extraction may be unreliable";
    }
    if (!$nonce_generation) {
        $js_implementation_issues[] = "Nonce generation missing - security check will fail";
    }
    if (!$admin_ajax_url) {
        $js_implementation_issues[] = "AJAX URL not properly configured";
    }
} else {
    $js_implementation_issues[] = "Template file not found";
}

// ANALYSIS 3: Button HTML Structure Issues
echo "<h2>3. 🏗️ BUTTON HTML STRUCTURE ANALYSIS</h2>";

$html_structure_issues = [];

if (file_exists($template_path)) {
    // Check button structure
    $has_trial_button = strpos($template_content, 'class="trial-button"') !== false;
    $has_regular_button = strpos($template_content, 'class="regular-button"') !== false;
    $has_data_attributes = strpos($template_content, 'data-subscription-type=') !== false;
    $has_button_values = strpos($template_content, 'value="<?php echo esc_attr($product->get_id()); ?>"') !== false;
    
    echo "<h3>✅ Button HTML Structure</h3>";
    echo "<p>Trial button class: " . ($has_trial_button ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Regular button class: " . ($has_regular_button ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Data attributes: " . ($has_data_attributes ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Button values: " . ($has_button_values ? '✅ YES' : '❌ NO') . "</p>";
    
    // Check for form structure
    $has_form_wrapper = strpos($template_content, '<form class="cart"') !== false;
    $has_hidden_input = strpos($template_content, 'name="subscription_type"') !== false;
    
    echo "<p>Form wrapper: " . ($has_form_wrapper ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Hidden subscription type input: " . ($has_hidden_input ? '✅ YES' : '❌ NO') . "</p>";
    
    if (!$has_data_attributes || !$has_button_values) {
        $html_structure_issues[] = "Button attributes may be missing or malformed";
    }
    if (!$has_form_wrapper) {
        $html_structure_issues[] = "Form wrapper missing - may cause JavaScript issues";
    }
}

// ANALYSIS 4: WooCommerce Integration Issues
echo "<h2>4. 🛒 WOOCOMMERCE INTEGRATION ANALYSIS</h2>";

$wc_integration_issues = [];

if (class_exists('WooCommerce')) {
    echo "<p>WooCommerce active: ✅ YES</p>";
    
    if (WC()->cart) {
        echo "<p>WooCommerce cart available: ✅ YES</p>";
        
        // Check cart functionality
        try {
            $cart_count = WC()->cart->get_cart_contents_count();
            echo "<p>Cart functionality: ✅ WORKING (Current items: $cart_count)</p>";
        } catch (Exception $e) {
            echo "<p>Cart functionality: ❌ ERROR - " . $e->getMessage() . "</p>";
            $wc_integration_issues[] = "WooCommerce cart has errors";
        }
    } else {
        echo "<p>WooCommerce cart available: ❌ NO</p>";
        $wc_integration_issues[] = "WooCommerce cart not initialized";
    }
    
    // Check if subscription products exist
    $subscription_products = get_posts(array(
        'post_type' => 'product',
        'meta_query' => array(
            array(
                'key' => '_product_type',
                'value' => 'subscription',
                'compare' => '='
            )
        ),
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (!empty($subscription_products)) {
        echo "<p>Subscription products exist: ✅ YES</p>";
        $test_product = wc_get_product($subscription_products[0]->ID);
        echo "<p>Test product: {$test_product->get_name()} (ID: {$test_product->get_id()})</p>";
        echo "<p>Product type: {$test_product->get_type()}</p>";
        
        // Test product methods
        if (method_exists($test_product, 'get_recurring_price')) {
            echo "<p>Recurring price method: ✅ AVAILABLE</p>";
        } else {
            echo "<p>Recurring price method: ❌ MISSING</p>";
            $wc_integration_issues[] = "Subscription product methods not available";
        }
    } else {
        echo "<p>Subscription products exist: ❌ NO</p>";
        $wc_integration_issues[] = "No subscription products found for testing";
    }
} else {
    echo "<p>WooCommerce active: ❌ NO</p>";
    $wc_integration_issues[] = "WooCommerce not active";
}

// ANALYSIS 5: Nonce and Security Issues
echo "<h2>5. 🔐 NONCE AND SECURITY ANALYSIS</h2>";

$security_issues = [];

// Test nonce generation
$test_nonce = wp_create_nonce('zlaark_subscriptions_frontend_nonce');
if ($test_nonce) {
    echo "<p>Nonce generation: ✅ WORKING</p>";
    echo "<p>Generated nonce: <code>$test_nonce</code></p>";
    
    // Test nonce verification
    $nonce_valid = wp_verify_nonce($test_nonce, 'zlaark_subscriptions_frontend_nonce');
    echo "<p>Nonce verification: " . ($nonce_valid ? '✅ WORKING' : '❌ FAILING') . "</p>";
    
    if (!$nonce_valid) {
        $security_issues[] = "Nonce verification failing";
    }
} else {
    echo "<p>Nonce generation: ❌ FAILING</p>";
    $security_issues[] = "Cannot generate nonces";
}

// Check user permissions
if ($is_logged_in) {
    echo "<p>User logged in: ✅ YES</p>";
    echo "<p>User can purchase: " . (current_user_can('read') ? '✅ YES' : '❌ NO') . "</p>";
} else {
    echo "<p>User logged in: ❌ NO</p>";
    echo "<p>Note: AJAX should handle non-logged-in users with redirect</p>";
}

// ANALYSIS 6: Client-Side Debug Test
echo "<h2>6. 🌐 CLIENT-SIDE DEBUG TEST</h2>";
?>

<div id="debug-test-results">
    <h3>Real-time Debug Tests:</h3>
</div>

<script>
jQuery(document).ready(function($) {
    var results = $('#debug-test-results');
    
    console.log('=== ROOT CAUSE ANALYSIS DEBUG ===');
    
    // Test 1: Check if AJAX configuration is loaded
    if (typeof zlaark_subscriptions_frontend !== 'undefined') {
        results.append('<p>✅ AJAX configuration loaded</p>');
        results.append('<p>AJAX URL: ' + zlaark_subscriptions_frontend.ajax_url + '</p>');
        results.append('<p>Nonce available: ' + (zlaark_subscriptions_frontend.nonce ? '✅ YES' : '❌ NO') + '</p>');
    } else {
        results.append('<p>❌ AJAX configuration NOT loaded - This is likely the root cause!</p>');
    }
    
    // Test 2: Check button existence and attributes
    var trialButtons = $('.trial-button');
    var regularButtons = $('.regular-button');
    
    results.append('<p><strong>Button Detection:</strong></p>');
    results.append('<p>Trial buttons: ' + trialButtons.length + '</p>');
    results.append('<p>Regular buttons: ' + regularButtons.length + '</p>');
    
    if (trialButtons.length > 0) {
        var trialType = trialButtons.data('subscription-type');
        var trialValue = trialButtons.val();
        results.append('<p>Trial button data-subscription-type: ' + trialType + '</p>');
        results.append('<p>Trial button value: ' + trialValue + '</p>');
    }
    
    if (regularButtons.length > 0) {
        var regularType = regularButtons.data('subscription-type');
        var regularValue = regularButtons.val();
        results.append('<p>Regular button data-subscription-type: ' + regularType + '</p>');
        results.append('<p>Regular button value: ' + regularValue + '</p>');
    }
    
    // Test 3: Check for existing click handlers
    var hasExistingHandlers = false;
    if (trialButtons.length > 0) {
        var events = $._data(trialButtons[0], 'events');
        hasExistingHandlers = events && events.click;
        results.append('<p>Existing click handlers: ' + (hasExistingHandlers ? '✅ YES' : '❌ NO') + '</p>');
        
        if (hasExistingHandlers) {
            results.append('<p>Number of click handlers: ' + events.click.length + '</p>');
        }
    }
    
    // Test 4: Simulate AJAX call (without actually submitting)
    if (trialButtons.length > 0 && typeof zlaark_subscriptions_frontend !== 'undefined') {
        results.append('<p><strong>AJAX Test Simulation:</strong></p>');
        
        var testData = {
            action: 'zlaark_add_subscription_to_cart',
            product_id: trialButtons.val(),
            subscription_type: 'trial',
            quantity: 1,
            nonce: zlaark_subscriptions_frontend.nonce
        };
        
        results.append('<p>Test AJAX data prepared: ✅ YES</p>');
        results.append('<p>Product ID: ' + testData.product_id + '</p>');
        results.append('<p>Subscription type: ' + testData.subscription_type + '</p>');
        results.append('<p>Nonce: ' + testData.nonce.substring(0, 10) + '...</p>');
        
        // Add a test button to actually try the AJAX call
        results.append('<button id="test-ajax-call" style="background: #007cba; color: white; padding: 10px; border: none; border-radius: 5px; margin: 10px 0;">🧪 Test AJAX Call</button>');
        
        $('#test-ajax-call').on('click', function() {
            $(this).prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: zlaark_subscriptions_frontend.ajax_url,
                type: 'POST',
                data: testData,
                success: function(response) {
                    results.append('<p>✅ AJAX SUCCESS: ' + JSON.stringify(response) + '</p>');
                    $('#test-ajax-call').text('✅ Success!').css('background', '#28a745');
                },
                error: function(xhr, status, error) {
                    results.append('<p>❌ AJAX ERROR: ' + status + ' - ' + error + '</p>');
                    results.append('<p>Response: ' + xhr.responseText + '</p>');
                    $('#test-ajax-call').text('❌ Failed!').css('background', '#dc3545');
                }
            });
        });
    }
    
    console.log('=== ROOT CAUSE ANALYSIS COMPLETE ===');
});
</script>

<?php
// ROOT CAUSE SUMMARY
echo "<h2>🎯 ROOT CAUSE ANALYSIS SUMMARY</h2>";

$all_issues = array_merge($ajax_registration_issues, $js_implementation_issues, $html_structure_issues, $wc_integration_issues, $security_issues);

if (empty($all_issues)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ NO OBVIOUS ISSUES FOUND</h3>";
    echo "<p>All components appear to be properly configured. The issue may be:</p>";
    echo "<ul>";
    echo "<li>JavaScript execution timing issues</li>";
    echo "<li>Browser-specific compatibility problems</li>";
    echo "<li>Network connectivity issues</li>";
    echo "<li>Server-side processing delays</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🚨 POTENTIAL ROOT CAUSES IDENTIFIED</h3>";
    echo "<ul>";
    foreach ($all_issues as $issue) {
        echo "<li><strong>$issue</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🔧 RECOMMENDED DEBUGGING STEPS</h3>";
echo "<ol>";
echo "<li><strong>Check Browser Console:</strong> Look for JavaScript errors during button clicks</li>";
echo "<li><strong>Monitor Network Tab:</strong> Check if AJAX requests are being sent to admin-ajax.php</li>";
echo "<li><strong>Enable WP_DEBUG:</strong> Check server logs for PHP errors</li>";
echo "<li><strong>Test AJAX Endpoint:</strong> Use the test button above to verify AJAX functionality</li>";
echo "<li><strong>Check User Permissions:</strong> Ensure user can add products to cart</li>";
echo "</ol>";

echo "<p><small>Delete this file after debugging: <code>rm debug-ajax-root-cause.php</code></small></p>";
?>
