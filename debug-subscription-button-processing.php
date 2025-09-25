<?php
/**
 * Debug Script for Subscription Button Processing Issues
 * 
 * This script helps diagnose and fix the critical button processing issues:
 * 1. Buttons getting stuck in "processing" state for non-admin users
 * 2. Products not being added to cart
 * 3. Users not being redirected to checkout
 * 4. Permission-related issues
 */

// Security check
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'zlaark2025') {
    die('Access denied. Add ?debug_key=zlaark2025 to the URL.');
}

echo "<h1>🔧 Subscription Button Processing Debug</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Check current user status
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$user_roles = $is_logged_in ? $current_user->roles : [];

echo "<h2>👤 Current User Status</h2>";
echo "<p><strong>Logged In:</strong> " . ($is_logged_in ? 'Yes' : 'No') . "</p>";
if ($is_logged_in) {
    echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
    echo "<p><strong>Username:</strong> " . $current_user->user_login . "</p>";
    echo "<p><strong>Roles:</strong> " . implode(', ', $user_roles) . "</p>";
    echo "<p><strong>Is Admin:</strong> " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";
}

// Test 1: Check JavaScript Conflicts
echo "<h2>1. ⚡ JavaScript Conflict Analysis</h2>";

// Check if template has the updated JavaScript
$template_path = 'templates/single-product/add-to-cart/subscription.php';
if (file_exists($template_path)) {
    $template_content = file_get_contents($template_path);
    
    // Check for new JavaScript features
    $has_prevent_default = strpos($template_content, 'e.preventDefault()') !== false;
    $has_form_submit = strpos($template_content, '$form[0].submit()') !== false;
    $has_loading_css = strpos($template_content, '.loading::after') !== false;
    $has_debug_logging = strpos($template_content, 'console.log(\'Zlaark: Button clicked\'') !== false;
    
    echo "<p>✅ Template has preventDefault: " . ($has_prevent_default ? 'Yes' : 'No') . "</p>";
    echo "<p>✅ Template has form.submit(): " . ($has_form_submit ? 'Yes' : 'No') . "</p>";
    echo "<p>✅ Template has loading CSS: " . ($has_loading_css ? 'Yes' : 'No') . "</p>";
    echo "<p>✅ Template has debug logging: " . ($has_debug_logging ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p>❌ Template file not found</p>";
}

// Check frontend.js conflicts
$frontend_js_path = 'assets/js/frontend.js';
if (file_exists($frontend_js_path)) {
    $frontend_js_content = file_get_contents($frontend_js_path);
    
    $single_button_disabled = strpos($frontend_js_content, '// $(document).on(\'click\', \'.single_add_to_cart_button\'') !== false;
    $dual_button_disabled = strpos($frontend_js_content, '// $(document).on(\'click\', \'.trial-button, .regular-button\'') !== false;
    
    echo "<p>✅ Single button handler disabled: " . ($single_button_disabled ? 'Yes' : 'No') . "</p>";
    echo "<p>✅ Dual button handler disabled: " . ($dual_button_disabled ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p>❌ Frontend JS file not found</p>";
}

// Test 2: Server-Side Processing Check
echo "<h2>2. 🖥️ Server-Side Processing Analysis</h2>";

// Check if validation hooks are registered
$validation_hook_registered = has_filter('woocommerce_add_to_cart_validation', array(ZlaarkSubscriptionsFrontend::instance(), 'validate_subscription_add_to_cart'));
echo "<p><strong>Add to cart validation hook registered:</strong> " . ($validation_hook_registered ? '✅ Yes' : '❌ No') . "</p>";

// Check if trial service is loaded
if (class_exists('ZlaarkSubscriptionsTrialService')) {
    echo "<p><strong>Trial Service:</strong> ✅ Loaded</p>";
    
    $trial_service = ZlaarkSubscriptionsTrialService::instance();
    $cart_data_hook = has_filter('woocommerce_add_cart_item_data', array($trial_service, 'add_trial_type_to_cart'));
    echo "<p><strong>Cart item data hook registered:</strong> " . ($cart_data_hook ? '✅ Yes' : '❌ No') . "</p>";
} else {
    echo "<p><strong>Trial Service:</strong> ❌ Not loaded</p>";
}

// Test 3: WooCommerce Integration Check
echo "<h2>3. 🛒 WooCommerce Integration Analysis</h2>";

if (class_exists('WooCommerce')) {
    echo "<p><strong>WooCommerce:</strong> ✅ Active</p>";
    
    if (WC()->cart) {
        echo "<p><strong>WooCommerce Cart:</strong> ✅ Available</p>";
        echo "<p><strong>Cart Contents:</strong> " . WC()->cart->get_cart_contents_count() . " items</p>";
        
        // Check cart contents for subscription products
        $has_subscription_in_cart = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['data']) && $cart_item['data']->get_type() === 'subscription') {
                $has_subscription_in_cart = true;
                echo "<p><strong>Subscription in cart:</strong> ✅ Yes (Product ID: " . $cart_item['product_id'] . ")</p>";
                if (isset($cart_item['subscription_type'])) {
                    echo "<p><strong>Subscription type:</strong> " . $cart_item['subscription_type'] . "</p>";
                }
                break;
            }
        }
        
        if (!$has_subscription_in_cart) {
            echo "<p><strong>Subscription in cart:</strong> ❌ No</p>";
        }
    } else {
        echo "<p><strong>WooCommerce Cart:</strong> ❌ Not available</p>";
    }
} else {
    echo "<p><strong>WooCommerce:</strong> ❌ Not active</p>";
}

// Test 4: Permission Analysis
echo "<h2>4. 🔐 Permission Analysis</h2>";

if ($is_logged_in) {
    // Test various capabilities
    $capabilities = [
        'read' => 'Basic read access',
        'edit_posts' => 'Edit posts',
        'manage_woocommerce' => 'Manage WooCommerce',
        'manage_options' => 'Manage options (admin)'
    ];
    
    foreach ($capabilities as $cap => $description) {
        $has_cap = current_user_can($cap);
        echo "<p><strong>$description ($cap):</strong> " . ($has_cap ? '✅ Yes' : '❌ No') . "</p>";
    }
    
    // Test subscription-specific permissions
    echo "<h3>Subscription-Specific Tests</h3>";
    
    // Get a test subscription product
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
        $test_product_id = $subscription_products[0]->ID;
        $test_product = wc_get_product($test_product_id);
        
        echo "<p><strong>Test Product:</strong> {$test_product->get_name()} (ID: {$test_product_id})</p>";
        
        // Test add to cart validation
        $validation_result = apply_filters('woocommerce_add_to_cart_validation', true, $test_product_id, 1);
        echo "<p><strong>Add to cart validation:</strong> " . ($validation_result ? '✅ Passed' : '❌ Failed') . "</p>";
        
        // Check for existing subscriptions
        if (class_exists('ZlaarkSubscriptionsDatabase')) {
            $db = ZlaarkSubscriptionsDatabase::instance();
            $existing_subscriptions = $db->get_user_subscriptions($current_user->ID);
            echo "<p><strong>Existing subscriptions:</strong> " . count($existing_subscriptions) . "</p>";
            
            foreach ($existing_subscriptions as $subscription) {
                if ($subscription->product_id == $test_product_id) {
                    echo "<p><strong>Has subscription for test product:</strong> ✅ Yes (Status: {$subscription->status})</p>";
                    break;
                }
            }
        }
    } else {
        echo "<p>❌ No subscription products found for testing</p>";
    }
} else {
    echo "<p>User not logged in - cannot test permissions</p>";
}

// Test 5: Form Submission Simulation
echo "<h2>5. 📝 Form Submission Simulation</h2>";

if ($is_logged_in && !empty($subscription_products)) {
    $test_product_id = $subscription_products[0]->ID;
    
    echo "<h3>Simulating form submission for Product ID: {$test_product_id}</h3>";
    
    // Simulate POST data
    $_POST['add-to-cart'] = $test_product_id;
    $_POST['subscription_type'] = 'regular';
    $_POST['quantity'] = 1;
    
    echo "<p><strong>Simulated POST data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Test cart item data processing
    if (class_exists('ZlaarkSubscriptionsTrialService')) {
        $trial_service = ZlaarkSubscriptionsTrialService::instance();
        $cart_item_data = $trial_service->add_trial_type_to_cart(array(), $test_product_id, 0);
        
        echo "<p><strong>Cart item data processing:</strong></p>";
        echo "<pre>" . print_r($cart_item_data, true) . "</pre>";
    }
    
    // Clean up POST data
    unset($_POST['add-to-cart'], $_POST['subscription_type'], $_POST['quantity']);
}

// Test 6: Client-Side Debug
echo "<h2>6. 🌐 Client-Side Debug</h2>";
?>

<script>
jQuery(document).ready(function($) {
    console.log('=== Subscription Button Processing Debug ===');
    
    var debugResults = $('#debug-results');
    
    // Check if jQuery is loaded
    if (typeof jQuery !== 'undefined') {
        debugResults.append('<p>✅ jQuery loaded (version: ' + jQuery.fn.jquery + ')</p>');
    } else {
        debugResults.append('<p>❌ jQuery not loaded</p>');
    }
    
    // Check if body has logged-in class
    var isLoggedInClass = $('body').hasClass('logged-in');
    debugResults.append('<p>Body has logged-in class: ' + (isLoggedInClass ? '✅ Yes' : '❌ No') + '</p>');
    
    // Check for subscription buttons
    var trialButtons = $('.trial-button').length;
    var regularButtons = $('.regular-button').length;
    var cartForms = $('form.cart').length;
    
    debugResults.append('<p>Trial buttons found: ' + trialButtons + '</p>');
    debugResults.append('<p>Regular buttons found: ' + regularButtons + '</p>');
    debugResults.append('<p>Cart forms found: ' + cartForms + '</p>');
    
    // Check for subscription type input
    var subscriptionTypeInput = $('#subscription_type');
    if (subscriptionTypeInput.length > 0) {
        debugResults.append('<p>✅ Subscription type input found</p>');
        debugResults.append('<p>Current value: ' + subscriptionTypeInput.val() + '</p>');
    } else {
        debugResults.append('<p>❌ Subscription type input not found</p>');
    }
    
    // Test button click functionality (without actually submitting)
    if (trialButtons > 0 || regularButtons > 0) {
        debugResults.append('<p><strong>Testing button click handlers...</strong></p>');
        
        // Add test click handlers
        $('.trial-button, .regular-button').on('click.debug', function(e) {
            e.preventDefault(); // Prevent actual submission during debug
            e.stopPropagation();
            
            var $button = $(this);
            var subscriptionType = $button.data('subscription-type');
            
            debugResults.append('<p>🔍 Button clicked: ' + subscriptionType + '</p>');
            debugResults.append('<p>Button classes: ' + $button[0].className + '</p>');
            
            // Check if subscription type gets set
            setTimeout(function() {
                var newValue = $('#subscription_type').val();
                debugResults.append('<p>Subscription type after click: ' + newValue + '</p>');
            }, 100);
            
            // Remove debug handler after first click
            $('.trial-button, .regular-button').off('click.debug');
            debugResults.append('<p>✅ Button click test completed - handlers removed</p>');
        });
        
        debugResults.append('<p>✅ Debug click handlers added - try clicking a button to test</p>');
    }
    
    console.log('=== Debug Script Complete ===');
});
</script>

<div id="debug-results">
    <h3>Client-side Debug Results:</h3>
</div>

<?php
// Final Recommendations
echo "<h2>🎯 Troubleshooting Recommendations</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<h3>If buttons are still getting stuck:</h3>";
echo "<ol>";
echo "<li><strong>Clear all caches:</strong> Browser cache, WordPress cache, CDN cache</li>";
echo "<li><strong>Check browser console:</strong> Look for JavaScript errors during button clicks</li>";
echo "<li><strong>Enable WP_DEBUG:</strong> Check error logs for server-side issues</li>";
echo "<li><strong>Test with different users:</strong> Compare admin vs non-admin behavior</li>";
echo "<li><strong>Disable other plugins:</strong> Check for plugin conflicts</li>";
echo "<li><strong>Test in incognito mode:</strong> Rule out browser extension conflicts</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Debug Log Locations:</strong></p>";
echo "<ul>";
echo "<li><strong>WordPress Debug Log:</strong> /wp-content/debug.log</li>";
echo "<li><strong>Browser Console:</strong> F12 → Console tab</li>";
echo "<li><strong>Network Tab:</strong> F12 → Network tab (check for failed requests)</li>";
echo "</ul>";

echo "<p><small>Delete this file after debugging: <code>rm debug-subscription-button-processing.php</code></small></p>";
?>
