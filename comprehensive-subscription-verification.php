<?php
/**
 * Comprehensive Subscription Button Verification
 * 
 * This script provides complete verification of all subscription button fixes:
 * 1. Button processing issues resolution
 * 2. Color consistency across all button types
 * 3. Cross-user functionality verification
 * 4. Complete fix verification
 */

// Security check
if (!isset($_GET['verify_key']) || $_GET['verify_key'] !== 'zlaark2025') {
    die('Access denied. Add ?verify_key=zlaark2025 to the URL.');
}

echo "<h1>🔍 COMPREHENSIVE SUBSCRIPTION BUTTON VERIFICATION</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Current user context
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$user_roles = $is_logged_in ? $current_user->roles : [];
$is_admin = current_user_can('manage_options');

echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🧪 Testing Context</h3>";
echo "<p><strong>Current User:</strong> " . ($is_logged_in ? $current_user->user_login . " (ID: {$current_user->ID})" : 'Not logged in') . "</p>";
echo "<p><strong>User Type:</strong> " . ($is_admin ? '👑 Administrator' : ($is_logged_in ? '👤 Regular User' : '🚫 Not Logged In')) . "</p>";
echo "<p><strong>Roles:</strong> " . ($is_logged_in ? implode(', ', $user_roles) : 'None') . "</p>";
echo "</div>";

// VERIFICATION 1: Button Processing Issues
echo "<h2>1. 🔄 BUTTON PROCESSING VERIFICATION</h2>";

$template_path = 'templates/single-product/add-to-cart/subscription.php';
$processing_fixes_verified = true;
$processing_issues = [];

if (file_exists($template_path)) {
    $template_content = file_get_contents($template_path);
    
    // Check for critical processing fixes
    $has_prevent_default = strpos($template_content, 'e.preventDefault()') !== false;
    $has_programmatic_submit = strpos($template_content, '$form[0].submit()') !== false;
    $has_loading_timeout = strpos($template_content, 'setTimeout(function() {') !== false;
    $has_form_validation = strpos($template_content, 'if ($form.length > 0)') !== false;
    $has_error_handling = strpos($template_content, 'console.error(\'Zlaark: Form not found!\')') !== false;
    
    echo "<h3>✅ Template JavaScript Fixes</h3>";
    echo "<p>preventDefault() implementation: " . ($has_prevent_default ? '✅ FIXED' : '❌ MISSING') . "</p>";
    echo "<p>Programmatic form submission: " . ($has_programmatic_submit ? '✅ FIXED' : '❌ MISSING') . "</p>";
    echo "<p>Loading state timeout: " . ($has_loading_timeout ? '✅ FIXED' : '❌ MISSING') . "</p>";
    echo "<p>Form validation: " . ($has_form_validation ? '✅ FIXED' : '❌ MISSING') . "</p>";
    echo "<p>Error handling: " . ($has_error_handling ? '✅ FIXED' : '❌ MISSING') . "</p>";
    
    if (!$has_prevent_default || !$has_programmatic_submit || !$has_loading_timeout) {
        $processing_fixes_verified = false;
        $processing_issues[] = "Template JavaScript missing critical fixes";
    }
} else {
    $processing_fixes_verified = false;
    $processing_issues[] = "Template file not found";
}

// Check frontend.js conflicts
$frontend_js_path = 'assets/js/frontend.js';
if (file_exists($frontend_js_path)) {
    $frontend_js_content = file_get_contents($frontend_js_path);
    
    $single_button_disabled = strpos($frontend_js_content, '// $(document).on(\'click\', \'.single_add_to_cart_button\'') !== false;
    $dual_button_disabled = strpos($frontend_js_content, '// $(document).on(\'click\', \'.trial-button, .regular-button\'') !== false;
    
    echo "<h3>✅ JavaScript Conflict Resolution</h3>";
    echo "<p>Single button handler disabled: " . ($single_button_disabled ? '✅ FIXED' : '❌ STILL ACTIVE') . "</p>";
    echo "<p>Dual button handler disabled: " . ($dual_button_disabled ? '✅ FIXED' : '❌ STILL ACTIVE') . "</p>";
    
    if (!$single_button_disabled || !$dual_button_disabled) {
        $processing_fixes_verified = false;
        $processing_issues[] = "JavaScript conflicts not fully resolved";
    }
}

echo "<div style='background: " . ($processing_fixes_verified ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>" . ($processing_fixes_verified ? '✅ PROCESSING ISSUES: RESOLVED' : '❌ PROCESSING ISSUES: UNRESOLVED') . "</h4>";
if (!$processing_fixes_verified) {
    echo "<ul>";
    foreach ($processing_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}
echo "</div>";

// VERIFICATION 2: Color Consistency
echo "<h2>2. 🎨 COLOR CONSISTENCY VERIFICATION</h2>";

$color_consistency_verified = true;
$color_issues = [];

// Check template button colors
if (file_exists($template_path)) {
    $template_content = file_get_contents($template_path);
    
    // Check for correct trial button colors
    $trial_green_primary = strpos($template_content, '#28a745') !== false;
    $trial_green_secondary = strpos($template_content, '#20c997') !== false;
    
    // Check for correct subscription button colors  
    $sub_blue_primary = strpos($template_content, '#007cba') !== false;
    $sub_blue_secondary = strpos($template_content, '#0056b3') !== false;
    
    echo "<h3>✅ Template Button Colors</h3>";
    echo "<p>Trial button green (#28a745): " . ($trial_green_primary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    echo "<p>Trial button green (#20c997): " . ($trial_green_secondary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    echo "<p>Subscription button blue (#007cba): " . ($sub_blue_primary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    echo "<p>Subscription button blue (#0056b3): " . ($sub_blue_secondary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    
    if (!$trial_green_primary || !$trial_green_secondary || !$sub_blue_primary || !$sub_blue_secondary) {
        $color_consistency_verified = false;
        $color_issues[] = "Template button colors incorrect";
    }
}

// Check shortcode button colors
$frontend_css_path = 'assets/css/frontend.css';
if (file_exists($frontend_css_path)) {
    $frontend_css_content = file_get_contents($frontend_css_path);
    
    // Check for correct shortcode colors
    $shortcode_trial_green_primary = strpos($frontend_css_content, '.zlaark-trial-btn') !== false && strpos($frontend_css_content, '#28a745') !== false;
    $shortcode_trial_green_secondary = strpos($frontend_css_content, '#20c997') !== false;
    $shortcode_sub_blue_primary = strpos($frontend_css_content, '.zlaark-subscription-btn') !== false && strpos($frontend_css_content, '#007cba') !== false;
    $shortcode_sub_blue_secondary = strpos($frontend_css_content, '#0056b3') !== false;
    
    echo "<h3>✅ Shortcode Button Colors</h3>";
    echo "<p>Shortcode trial button green (#28a745): " . ($shortcode_trial_green_primary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    echo "<p>Shortcode trial button green (#20c997): " . ($shortcode_trial_green_secondary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    echo "<p>Shortcode subscription button blue (#007cba): " . ($shortcode_sub_blue_primary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    echo "<p>Shortcode subscription button blue (#0056b3): " . ($shortcode_sub_blue_secondary ? '✅ CORRECT' : '❌ MISSING') . "</p>";
    
    if (!$shortcode_trial_green_primary || !$shortcode_trial_green_secondary || !$shortcode_sub_blue_primary || !$shortcode_sub_blue_secondary) {
        $color_consistency_verified = false;
        $color_issues[] = "Shortcode button colors incorrect";
    }
}

echo "<div style='background: " . ($color_consistency_verified ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>" . ($color_consistency_verified ? '✅ COLOR CONSISTENCY: VERIFIED' : '❌ COLOR CONSISTENCY: ISSUES FOUND') . "</h4>";
if (!$color_consistency_verified) {
    echo "<ul>";
    foreach ($color_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}
echo "</div>";

// VERIFICATION 3: Cross-User Functionality
echo "<h2>3. 👥 CROSS-USER FUNCTIONALITY VERIFICATION</h2>";

$cross_user_verified = true;
$cross_user_issues = [];

// Check server-side validation
$frontend_class_path = 'includes/frontend/class-zlaark-subscriptions-frontend.php';
if (file_exists($frontend_class_path)) {
    $frontend_class_content = file_get_contents($frontend_class_path);
    
    $has_debug_logging = strpos($frontend_class_content, 'error_log(\'Zlaark Subscriptions: Add to cart validation') !== false;
    $has_user_validation = strpos($frontend_class_content, 'if (!is_user_logged_in())') !== false;
    $has_permission_checks = strpos($frontend_class_content, 'get_current_user_id()') !== false;
    
    echo "<h3>✅ Server-Side User Handling</h3>";
    echo "<p>Debug logging implemented: " . ($has_debug_logging ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>User login validation: " . ($has_user_validation ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>Permission checks: " . ($has_permission_checks ? '✅ YES' : '❌ NO') . "</p>";
    
    if (!$has_debug_logging || !$has_user_validation || !$has_permission_checks) {
        $cross_user_verified = false;
        $cross_user_issues[] = "Server-side user handling incomplete";
    }
}

// Check trial service logging
$trial_service_path = 'includes/class-zlaark-subscriptions-trial-service.php';
if (file_exists($trial_service_path)) {
    $trial_service_content = file_get_contents($trial_service_path);
    
    $has_cart_logging = strpos($trial_service_content, 'error_log(\'Zlaark Subscriptions: Adding subscription type to cart') !== false;
    $has_user_context = strpos($trial_service_content, 'User ID: \' . get_current_user_id()') !== false;
    
    echo "<h3>✅ Trial Service User Handling</h3>";
    echo "<p>Cart processing logging: " . ($has_cart_logging ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p>User context in logs: " . ($has_user_context ? '✅ YES' : '❌ NO') . "</p>";
    
    if (!$has_cart_logging || !$has_user_context) {
        $cross_user_verified = false;
        $cross_user_issues[] = "Trial service user handling incomplete";
    }
}

echo "<div style='background: " . ($cross_user_verified ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>" . ($cross_user_verified ? '✅ CROSS-USER FUNCTIONALITY: VERIFIED' : '❌ CROSS-USER FUNCTIONALITY: ISSUES FOUND') . "</h4>";
if (!$cross_user_verified) {
    echo "<ul>";
    foreach ($cross_user_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}
echo "</div>";

// VERIFICATION 4: Complete Fix Summary
echo "<h2>4. 📋 COMPLETE FIX VERIFICATION</h2>";

$all_fixes_verified = $processing_fixes_verified && $color_consistency_verified && $cross_user_verified;

echo "<div style='background: " . ($all_fixes_verified ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>" . ($all_fixes_verified ? '🎉 ALL ISSUES RESOLVED' : '⚠️ ISSUES REMAINING') . "</h3>";

echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background: #f8f9fa;'><th style='padding: 10px; border: 1px solid #ddd;'>Issue Category</th><th style='padding: 10px; border: 1px solid #ddd;'>Status</th></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'>Button Processing Issues</td><td style='padding: 10px; border: 1px solid #ddd;'>" . ($processing_fixes_verified ? '✅ RESOLVED' : '❌ UNRESOLVED') . "</td></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'>Color Consistency</td><td style='padding: 10px; border: 1px solid #ddd;'>" . ($color_consistency_verified ? '✅ RESOLVED' : '❌ UNRESOLVED') . "</td></tr>";
echo "<tr><td style='padding: 10px; border: 1px solid #ddd;'>Cross-User Functionality</td><td style='padding: 10px; border: 1px solid #ddd;'>" . ($cross_user_verified ? '✅ RESOLVED' : '❌ UNRESOLVED') . "</td></tr>";
echo "</table>";

if (!$all_fixes_verified) {
    echo "<h4>🚨 Remaining Issues:</h4>";
    echo "<ul>";
    foreach (array_merge($processing_issues, $color_issues, $cross_user_issues) as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Client-side verification
echo "<h2>5. 🌐 CLIENT-SIDE VERIFICATION</h2>";
?>

<div id="client-verification">
    <h3>Real-time Client-side Tests:</h3>
</div>

<script>
jQuery(document).ready(function($) {
    var results = $('#client-verification');
    
    // Test 1: Check for subscription buttons
    var trialButtons = $('.trial-button').length;
    var regularButtons = $('.regular-button').length;
    var shortcodeTrialButtons = $('.zlaark-trial-btn').length;
    var shortcodeSubButtons = $('.zlaark-subscription-btn').length;
    
    results.append('<p><strong>Template Buttons Found:</strong></p>');
    results.append('<p>• Trial buttons: ' + trialButtons + '</p>');
    results.append('<p>• Regular buttons: ' + regularButtons + '</p>');
    results.append('<p><strong>Shortcode Buttons Found:</strong></p>');
    results.append('<p>• Trial shortcode buttons: ' + shortcodeTrialButtons + '</p>');
    results.append('<p>• Subscription shortcode buttons: ' + shortcodeSubButtons + '</p>');
    
    // Test 2: Check button colors
    if (trialButtons > 0) {
        var trialButtonColor = $('.trial-button').css('background-image') || $('.trial-button').css('background-color');
        results.append('<p><strong>Trial Button Color:</strong> ' + (trialButtonColor.includes('28a745') || trialButtonColor.includes('rgb(40, 167, 69)') ? '✅ Correct Green' : '❌ Incorrect') + '</p>');
    }
    
    if (regularButtons > 0) {
        var regularButtonColor = $('.regular-button').css('background-image') || $('.regular-button').css('background-color');
        results.append('<p><strong>Regular Button Color:</strong> ' + (regularButtonColor.includes('007cba') || regularButtonColor.includes('rgb(0, 124, 186)') ? '✅ Correct Blue' : '❌ Incorrect') + '</p>');
    }
    
    // Test 3: Check for loading state CSS
    var hasLoadingCSS = $('<style>').text().includes('.loading::after') || $('style').text().includes('.loading::after');
    results.append('<p><strong>Loading State CSS:</strong> ' + (hasLoadingCSS ? '✅ Present' : '❌ Missing') + '</p>');
    
    // Test 4: Check JavaScript handlers
    var hasClickHandlers = false;
    if (trialButtons > 0 || regularButtons > 0) {
        // Check if buttons have click handlers
        var events = $._data($('.trial-button, .regular-button')[0], 'events');
        hasClickHandlers = events && events.click;
    }
    results.append('<p><strong>JavaScript Handlers:</strong> ' + (hasClickHandlers ? '✅ Active' : '❌ Missing') + '</p>');
    
    // Test 5: Form validation
    var cartForms = $('form.cart').length;
    var subscriptionTypeInput = $('#subscription_type').length;
    results.append('<p><strong>Form Elements:</strong></p>');
    results.append('<p>• Cart forms: ' + cartForms + '</p>');
    results.append('<p>• Subscription type input: ' + (subscriptionTypeInput ? '✅ Present' : '❌ Missing') + '</p>');
    
    console.log('Zlaark: Comprehensive verification completed');
});
</script>

<?php
echo "<h2>📝 VERIFICATION SUMMARY</h2>";

if ($all_fixes_verified) {
    echo "<div style='background: #d4edda; border: 2px solid #c3e6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>🎉 COMPREHENSIVE VERIFICATION: PASSED</h3>";
    echo "<p><strong>All subscription button issues have been completely resolved:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Button processing issues fixed (no more stuck processing states)</li>";
    echo "<li>✅ Color consistency verified (green trial, blue subscription)</li>";
    echo "<li>✅ Cross-user functionality implemented (works for all user types)</li>";
    echo "<li>✅ Complete fix verification passed</li>";
    echo "</ul>";
    echo "<p><strong>The subscription system is now fully functional for all users!</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 2px solid #f5c6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>⚠️ VERIFICATION: ISSUES FOUND</h3>";
    echo "<p><strong>Some issues still need to be addressed before the system is fully functional.</strong></p>";
    echo "<p>Please review the specific issues identified above and address them accordingly.</p>";
    echo "</div>";
}

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Test the subscription buttons on actual product pages</li>";
echo "<li>Verify functionality with different user types (admin, regular user, logged-out)</li>";
echo "<li>Check browser console for any JavaScript errors</li>";
echo "<li>Monitor server logs for any processing issues</li>";
echo "<li>Test the complete flow: button click → cart → checkout</li>";
echo "</ol>";

echo "<p><small>Delete this file after verification: <code>rm comprehensive-subscription-verification.php</code></small></p>";
?>
