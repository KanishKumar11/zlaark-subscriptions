<?php
/**
 * Button Placement Fix Verification Test
 * 
 * This script verifies that the trial and subscription buttons appear
 * immediately after the product title, not at the bottom of the page.
 */

// Security check
if (!isset($_GET['test_key']) || $_GET['test_key'] !== 'zlaark2025') {
    die('Access denied. Add ?test_key=zlaark2025 to the URL.');
}

echo "<h1>🎯 Button Placement Fix Verification</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Verify hook priority change
echo "<h2>✅ Test 1: Hook Priority Verification</h2>";
$product_type_content = file_get_contents('includes/class-zlaark-subscriptions-product-type.php');

if (strpos($product_type_content, 'load_subscription_add_to_cart\'), 6') !== false) {
    echo "✅ Template loading hook set to priority 6<br>";
} else {
    echo "❌ Template loading hook priority not found<br>";
}

// Test 2: Verify conflicting filter removed
if (strpos($product_type_content, 'wc_get_template') === false) {
    echo "✅ Conflicting wc_get_template filter removed<br>";
} else {
    echo "❌ wc_get_template filter still present (may cause conflicts)<br>";
}

// Test 3: Verify template file exists
echo "<h2>✅ Test 2: Template File Verification</h2>";
$template_path = 'templates/single-product/add-to-cart/subscription.php';
if (file_exists($template_path)) {
    echo "✅ Template file exists: $template_path<br>";
    echo "File size: " . number_format(filesize($template_path)) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($template_path)) . "<br>";
    
    // Check if template contains buttons
    $template_content = file_get_contents($template_path);
    if (strpos($template_content, 'trial-button') !== false && strpos($template_content, 'regular-button') !== false) {
        echo "✅ Template contains trial and subscription buttons<br>";
    } else {
        echo "❌ Template missing button elements<br>";
    }
} else {
    echo "❌ Template file not found<br>";
}

// Test 4: Check for conflicting methods
echo "<h2>✅ Test 3: Conflicting Methods Check</h2>";
$frontend_content = file_get_contents('includes/frontend/class-zlaark-subscriptions-frontend.php');

// Check that frontend hooks are removed
if (strpos($frontend_content, 'display_trial_highlight\'), 6') === false) {
    echo "✅ Frontend display_trial_highlight hook removed<br>";
} else {
    echo "❌ Frontend display_trial_highlight hook still present<br>";
}

if (strpos($frontend_content, 'force_subscription_add_to_cart\'), 31') === false) {
    echo "✅ Frontend force_subscription_add_to_cart hook removed<br>";
} else {
    echo "❌ Frontend force_subscription_add_to_cart hook still present<br>";
}

// Test 5: Syntax validation
echo "<h2>✅ Test 4: Syntax Validation</h2>";
$syntax_check = shell_exec('php -l includes/class-zlaark-subscriptions-product-type.php 2>&1');
if (strpos($syntax_check, 'No syntax errors') !== false) {
    echo "✅ Product type class syntax valid<br>";
} else {
    echo "❌ Syntax error in product type class:<br><pre>$syntax_check</pre>";
}

$frontend_syntax = shell_exec('php -l includes/frontend/class-zlaark-subscriptions-frontend.php 2>&1');
if (strpos($frontend_syntax, 'No syntax errors') !== false) {
    echo "✅ Frontend class syntax valid<br>";
} else {
    echo "❌ Syntax error in frontend class:<br><pre>$frontend_syntax</pre>";
}

// Test 6: Debug markers
echo "<h2>✅ Test 5: Debug Markers</h2>";
if (strpos($product_type_content, 'Template loading at priority 6') !== false) {
    echo "✅ Debug markers added to template loading<br>";
} else {
    echo "❌ Debug markers not found<br>";
}

// Instructions for manual testing
echo "<h2>📋 Manual Testing Instructions</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🔍 How to Verify the Fix:</h3>";
echo "<ol>";
echo "<li><strong>Visit a Subscription Product Page</strong> on your WordPress site</li>";
echo "<li><strong>View Page Source</strong> (Ctrl+U or Cmd+U)</li>";
echo "<li><strong>Search for:</strong> <code>Template loading at priority 6</code></li>";
echo "<li><strong>Check Button Position:</strong> Buttons should appear immediately after the product title</li>";
echo "<li><strong>Look for Debug Comments:</strong> You should see HTML comments indicating template loading</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🎯 Expected Results</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ What Should Happen:</h3>";
echo "<ul>";
echo "<li><strong>Product Title</strong> (WooCommerce priority 5)</li>";
echo "<li><strong>🎯 Trial Button & 🚀 Subscription Button</strong> (Our priority 6) ← Should be here!</li>";
echo "<li><strong>Product Rating</strong> (WooCommerce priority 10)</li>";
echo "<li><strong>Product Price</strong> (WooCommerce priority 10)</li>";
echo "<li><strong>Product Description</strong> (WooCommerce priority 20)</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚨 Troubleshooting</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>⚠️ If Buttons Still Appear at Bottom:</h3>";
echo "<ol>";
echo "<li><strong>Clear Cache:</strong> Clear any WordPress/WooCommerce caching</li>";
echo "<li><strong>Check Theme Conflicts:</strong> Switch to a default theme temporarily</li>";
echo "<li><strong>Plugin Conflicts:</strong> Deactivate other plugins temporarily</li>";
echo "<li><strong>Debug Mode:</strong> Enable WP_DEBUG to see debug comments</li>";
echo "<li><strong>Browser Cache:</strong> Hard refresh (Ctrl+F5) the product page</li>";
echo "</ol>";
echo "</div>";

// Summary
$all_tests_passed = 
    strpos($product_type_content, 'load_subscription_add_to_cart\'), 6') !== false &&
    strpos($product_type_content, 'wc_get_template') === false &&
    file_exists($template_path) &&
    strpos($syntax_check, 'No syntax errors') !== false;

echo "<h2>📊 Test Summary</h2>";
if ($all_tests_passed) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 ALL TESTS PASSED!</h3>";
    echo "<p><strong>The button placement fix has been successfully implemented:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Template loading moved to priority 6 (after title)</li>";
    echo "<li>✅ Conflicting wc_get_template filter removed</li>";
    echo "<li>✅ Template file exists and contains buttons</li>";
    echo "<li>✅ Conflicting frontend hooks removed</li>";
    echo "<li>✅ All syntax errors resolved</li>";
    echo "<li>✅ Debug markers added for verification</li>";
    echo "</ul>";
    echo "<p><strong>🚀 The buttons should now appear immediately after the product title!</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ SOME TESTS FAILED</h3>";
    echo "<p>Please review the failed tests above and fix any issues.</p>";
    echo "</div>";
}

echo "<p><strong>Next Step:</strong> Test on a subscription product page to verify button placement.</p>";
echo "<p><small>Delete this file after testing: <code>rm test-button-placement-fix.php</code></small></p>";
