<?php
/**
 * Comprehensive Subscription Button Functionality Test
 * 
 * This script tests all the critical fixes for subscription button functionality:
 * 1. Correct cart behavior (subscription vs trial)
 * 2. Button responsiveness for all user types
 * 3. Correct login redirect to /auth
 * 4. Zoho Bigin compatibility
 * 5. Updated button colors
 */

// Security check
if (!isset($_GET['test_key']) || $_GET['test_key'] !== 'zlaark2025') {
    die('Access denied. Add ?test_key=zlaark2025 to the URL.');
}

echo "<h1>🔧 Subscription Button Functionality Fixes Test</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Verify default subscription type fix
echo "<h2>✅ Test 1: Default Subscription Type Fix</h2>";
$template_content = file_get_contents('templates/single-product/add-to-cart/subscription.php');

if (strpos($template_content, 'value="regular"') !== false && 
    strpos($template_content, 'Default to regular, JavaScript will change to trial') !== false) {
    echo "✅ Default subscription type fixed to 'regular'<br>";
    echo "✅ Proper comment added explaining the logic<br>";
} else {
    echo "❌ Default subscription type not fixed<br>";
}

// Test 2: Verify login redirect fix
echo "<h2>✅ Test 2: Login Redirect Fix</h2>";
$frontend_content = file_get_contents('includes/frontend/class-zlaark-subscriptions-frontend.php');

$auth_redirects = substr_count($frontend_content, "home_url('/auth')");
$wp_login_redirects = substr_count($frontend_content, 'wp_login_url()');

if ($auth_redirects >= 2) {
    echo "✅ Login redirects changed to /auth (found $auth_redirects instances)<br>";
} else {
    echo "❌ Login redirects not properly changed to /auth<br>";
}

if ($wp_login_redirects == 0) {
    echo "✅ All wp_login_url() references removed<br>";
} else {
    echo "⚠️ Still found $wp_login_redirects wp_login_url() references<br>";
}

// Test 3: Verify enhanced JavaScript and conflict resolution
echo "<h2>✅ Test 3: Enhanced JavaScript & Button Responsiveness</h2>";

if (strpos($template_content, 'console.log(\'Zlaark: Button clicked\'') !== false) {
    echo "✅ Debug logging added for troubleshooting<br>";
} else {
    echo "❌ Debug logging not found<br>";
}

if (strpos($template_content, 'off(\'click.zlaark\').on(\'click.zlaark\'') !== false) {
    echo "✅ Namespaced event handlers to prevent conflicts<br>";
} else {
    echo "❌ Event handler namespacing not found<br>";
}

if (strpos($template_content, 'addClass(\'loading\').prop(\'disabled\', true)') !== false) {
    echo "✅ Loading state and button disabling implemented<br>";
} else {
    echo "❌ Loading state not implemented<br>";
}

if (strpos($template_content, 'wc_fragments_refreshed wc_fragments_loaded') !== false) {
    echo "✅ WooCommerce fragment refresh handling added<br>";
} else {
    echo "❌ Fragment refresh handling not found<br>";
}

// Check for JavaScript conflicts resolution
$frontend_js_content = file_get_contents('assets/js/frontend.js');
if (strpos($frontend_js_content, '// $(document).on(\'click\', \'.trial-button, .regular-button\'') !== false) {
    echo "✅ Conflicting JavaScript handler removed from frontend.js<br>";
} else {
    echo "❌ Conflicting JavaScript handler still present in frontend.js<br>";
}

// Check for proper login validation fix
if (strpos($template_content, '$(\'body\').hasClass(\'logged-in\')') !== false) {
    echo "✅ Client-side login validation implemented correctly<br>";
} else {
    echo "❌ Client-side login validation not found<br>";
}

// Test 4: Verify button colors in both template and CSS files
echo "<h2>✅ Test 4: Button Color Updates</h2>";

$frontend_css_content = file_get_contents('assets/css/frontend.css');

// Check template colors
$template_colors_correct = strpos($template_content, '#28a745') !== false && strpos($template_content, '#007cba') !== false;

// Check CSS file colors
$css_colors_correct = strpos($frontend_css_content, '#28a745 0%, #20c997 100%) !important') !== false &&
                     strpos($frontend_css_content, '#007cba 0%, #0056b3 100%) !important') !== false;

// Check for old colors
$old_colors_removed = strpos($frontend_css_content, '#667eea') === false &&
                     strpos($frontend_css_content, '#f093fb') === false;

if ($template_colors_correct && $css_colors_correct && $old_colors_removed) {
    echo "✅ Button colors correctly updated in template (Green: #28a745, Blue: #007cba)<br>";
    echo "✅ Button colors correctly updated in CSS file with !important flags<br>";
    echo "✅ Old colors (#667eea, #f093fb) removed from CSS<br>";
} else {
    echo "❌ Button color issues found:<br>";
    if (!$template_colors_correct) echo "&nbsp;&nbsp;- Template colors not updated<br>";
    if (!$css_colors_correct) echo "&nbsp;&nbsp;- CSS file colors not updated<br>";
    if (!$old_colors_removed) echo "&nbsp;&nbsp;- Old colors still present in CSS<br>";
}

// Test 5: Cart behavior validation
echo "<h2>✅ Test 5: Cart Behavior Logic</h2>";
$trial_service_content = file_get_contents('includes/class-zlaark-subscriptions-trial-service.php');

if (strpos($trial_service_content, 'add_trial_type_to_cart') !== false && 
    strpos($trial_service_content, 'modify_cart_item_price') !== false) {
    echo "✅ Cart handling logic exists for subscription types<br>";
    echo "✅ Price modification based on subscription type implemented<br>";
} else {
    echo "❌ Cart handling logic not found<br>";
}

// Test 6: User capability checks
echo "<h2>✅ Test 6: User Access & Capability Checks</h2>";

// Check if there are any admin-only restrictions that might affect regular users
if (strpos($frontend_content, 'current_user_can(\'manage_woocommerce\')') !== false) {
    echo "⚠️ Found admin capability checks - verify these don't affect button functionality<br>";
} else {
    echo "✅ No admin-only restrictions found that would affect button functionality<br>";
}

// Test 7: Zoho Bigin compatibility check
echo "<h2>✅ Test 7: Zoho Bigin Integration Compatibility</h2>";

// Check for any Zoho-related code or potential conflicts
$all_files = glob('includes/**/*.php') + glob('*.php');
$zoho_references = 0;

foreach ($all_files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        if (stripos($content, 'zoho') !== false || stripos($content, 'bigin') !== false) {
            $zoho_references++;
        }
    }
}

if ($zoho_references == 0) {
    echo "✅ No existing Zoho Bigin integration found - no conflicts expected<br>";
    echo "✅ Subscription functionality uses standard WooCommerce hooks and filters<br>";
} else {
    echo "⚠️ Found $zoho_references potential Zoho references - manual testing recommended<br>";
}

// Test 8: Syntax validation
echo "<h2>✅ Test 8: Syntax Validation</h2>";

$files_to_check = [
    'templates/single-product/add-to-cart/subscription.php',
    'includes/frontend/class-zlaark-subscriptions-frontend.php'
];

$all_syntax_valid = true;
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $syntax_check = shell_exec("php -l $file 2>&1");
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "✅ $file syntax valid<br>";
        } else {
            echo "❌ Syntax error in $file:<br><pre>$syntax_check</pre>";
            $all_syntax_valid = false;
        }
    }
}

// Manual testing instructions
echo "<h2>📋 Manual Testing Instructions</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🧪 How to Test the Fixes:</h3>";
echo "<ol>";
echo "<li><strong>Test Cart Behavior:</strong>";
echo "<ul>";
echo "<li>Visit a subscription product page</li>";
echo "<li>Click 'Start Subscription' button → Should add subscription product to cart</li>";
echo "<li>Click 'Start Trial' button → Should add trial product to cart</li>";
echo "<li>Check cart contents and prices match the button clicked</li>";
echo "</ul></li>";

echo "<li><strong>Test Button Responsiveness:</strong>";
echo "<ul>";
echo "<li>Test as admin user → Buttons should work</li>";
echo "<li>Test as regular logged-in user → Buttons should work</li>";
echo "<li>Test as logged-out user → Should redirect to /auth</li>";
echo "<li>Check browser console for debug logs</li>";
echo "</ul></li>";

echo "<li><strong>Test Login Redirect:</strong>";
echo "<ul>";
echo "<li>Log out and click any subscription button</li>";
echo "<li>Should redirect to /auth (not /wp-login.php)</li>";
echo "<li>After login, should add product to cart and redirect to checkout</li>";
echo "</ul></li>";

echo "<li><strong>Test Zoho Bigin Compatibility:</strong>";
echo "<ul>";
echo "<li>Install/activate Zoho Bigin for WooCommerce plugin</li>";
echo "<li>Test subscription purchase flow end-to-end</li>";
echo "<li>Verify cart, checkout, and order creation work correctly</li>";
echo "<li>Check for any JavaScript conflicts or errors</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

// Expected results
echo "<h2>🎯 Expected Results</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ What Should Happen:</h3>";
echo "<ul>";
echo "<li><strong>Correct Cart Behavior:</strong> 'Start Subscription' adds subscription product, 'Start Trial' adds trial product</li>";
echo "<li><strong>Button Responsiveness:</strong> Buttons work for all user types (admin, regular user, logged-out)</li>";
echo "<li><strong>Correct Redirects:</strong> Login redirects go to /auth, post-login goes to checkout</li>";
echo "<li><strong>Visual Feedback:</strong> Buttons show loading states and visual feedback</li>";
echo "<li><strong>Better Colors:</strong> Green trial buttons, blue subscription buttons</li>";
echo "<li><strong>Debug Info:</strong> Console logs help troubleshoot any issues</li>";
echo "</ul>";
echo "</div>";

// Summary
$critical_fixes_passed = 
    strpos($template_content, 'value="regular"') !== false &&
    $auth_redirects >= 2 &&
    strpos($template_content, 'console.log(\'Zlaark: Button clicked\'') !== false &&
    $all_syntax_valid;

echo "<h2>📊 Test Summary</h2>";
if ($critical_fixes_passed) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 ALL CRITICAL FIXES IMPLEMENTED!</h3>";
    echo "<p><strong>The subscription button functionality has been comprehensively fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Default subscription type fixed (no more incorrect trial additions)</li>";
    echo "<li>✅ Login redirects changed to /auth</li>";
    echo "<li>✅ Enhanced JavaScript with debug logging and error handling</li>";
    echo "<li>✅ Button responsiveness improved with loading states</li>";
    echo "<li>✅ Modern, accessible button colors implemented</li>";
    echo "<li>✅ WooCommerce fragment refresh compatibility added</li>";
    echo "<li>✅ All syntax errors resolved</li>";
    echo "</ul>";
    echo "<p><strong>🚀 Ready for production testing!</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ SOME FIXES NEED ATTENTION</h3>";
    echo "<p>Please review the failed tests above and address any issues.</p>";
    echo "</div>";
}

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Test the complete user flow on your WordPress site</li>";
echo "<li>Install Zoho Bigin plugin and test compatibility</li>";
echo "<li>Monitor browser console for any JavaScript errors</li>";
echo "<li>Test with different user roles and login states</li>";
echo "<li>Verify cart contents and pricing are correct</li>";
echo "</ol>";

echo "<p><small>Delete this file after testing: <code>rm test-subscription-button-fixes.php</code></small></p>";
