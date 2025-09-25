<?php
/**
 * Debug Script for Subscription Button Issues
 * 
 * This script helps diagnose and fix the critical button issues:
 * 1. Button colors not showing correctly
 * 2. Buttons stuck in loading state
 * 3. Cart addition failure for regular users
 */

// Security check
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'zlaark2025') {
    die('Access denied. Add ?debug_key=zlaark2025 to the URL.');
}

echo "<h1>🔧 Subscription Button Issues Debug</h1>";
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

// Test 1: CSS Color Issues
echo "<h2>🎨 CSS Color Diagnosis</h2>";

// Check if CSS files exist and have correct colors
$css_files = [
    'assets/css/frontend.css' => 'Frontend CSS',
    'templates/single-product/add-to-cart/subscription.php' => 'Template CSS'
];

foreach ($css_files as $file => $name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        echo "<h3>$name ($file)</h3>";
        
        // Check for new colors
        $has_green = strpos($content, '#28a745') !== false;
        $has_blue = strpos($content, '#007cba') !== false;
        
        // Check for old colors
        $has_old_purple = strpos($content, '#667eea') !== false;
        $has_old_pink = strpos($content, '#f093fb') !== false;
        
        echo "<p>✅ Green trial color (#28a745): " . ($has_green ? 'Found' : 'Missing') . "</p>";
        echo "<p>✅ Blue subscription color (#007cba): " . ($has_blue ? 'Found' : 'Missing') . "</p>";
        echo "<p>❌ Old purple color (#667eea): " . ($has_old_purple ? 'Still present' : 'Removed') . "</p>";
        echo "<p>❌ Old pink color (#f093fb): " . ($has_old_pink ? 'Still present' : 'Removed') . "</p>";
        
        if ($has_green && $has_blue && !$has_old_purple && !$has_old_pink) {
            echo "<p><strong>✅ Colors are correct in $name</strong></p>";
        } else {
            echo "<p><strong>❌ Color issues found in $name</strong></p>";
        }
    } else {
        echo "<p>❌ File not found: $file</p>";
    }
}

// Test 2: JavaScript Conflicts
echo "<h2>⚡ JavaScript Conflict Diagnosis</h2>";

$js_files = [
    'assets/js/frontend.js' => 'Frontend JavaScript',
    'templates/single-product/add-to-cart/subscription.php' => 'Template JavaScript'
];

foreach ($js_files as $file => $name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        echo "<h3>$name</h3>";
        
        if ($file === 'assets/js/frontend.js') {
            // Check if conflicting handler is removed
            $conflict_removed = strpos($content, '// $(document).on(\'click\', \'.trial-button, .regular-button\'') !== false;
            echo "<p>Conflicting handler removed: " . ($conflict_removed ? '✅ Yes' : '❌ No') . "</p>";
        }
        
        if ($file === 'templates/single-product/add-to-cart/subscription.php') {
            // Check for proper JavaScript implementation
            $has_debug_logging = strpos($content, 'console.log(\'Zlaark: Button clicked\'') !== false;
            $has_namespaced_events = strpos($content, '.off(\'click.zlaark\').on(\'click.zlaark\'') !== false;
            $has_loading_state = strpos($content, 'addClass(\'loading\')') !== false;
            $has_proper_login_check = strpos($content, '$(\'body\').hasClass(\'logged-in\')') !== false;
            
            echo "<p>Debug logging: " . ($has_debug_logging ? '✅ Present' : '❌ Missing') . "</p>";
            echo "<p>Namespaced events: " . ($has_namespaced_events ? '✅ Present' : '❌ Missing') . "</p>";
            echo "<p>Loading state: " . ($has_loading_state ? '✅ Present' : '❌ Missing') . "</p>";
            echo "<p>Proper login check: " . ($has_proper_login_check ? '✅ Present' : '❌ Missing') . "</p>";
        }
    }
}

// Test 3: Cart Processing Check
echo "<h2>🛒 Cart Processing Diagnosis</h2>";

// Check if trial service is loaded
if (class_exists('ZlaarkSubscriptionsTrialService')) {
    echo "<p>✅ Trial Service class loaded</p>";
    
    // Check if hooks are registered
    $trial_service = ZlaarkSubscriptionsTrialService::instance();
    echo "<p>✅ Trial Service instance created</p>";
} else {
    echo "<p>❌ Trial Service class not loaded</p>";
}

// Check WooCommerce status
if (class_exists('WooCommerce')) {
    echo "<p>✅ WooCommerce is active</p>";
    
    // Check if cart is available
    if (WC()->cart) {
        echo "<p>✅ WooCommerce cart is available</p>";
        echo "<p>Cart contents count: " . WC()->cart->get_cart_contents_count() . "</p>";
    } else {
        echo "<p>❌ WooCommerce cart not available</p>";
    }
} else {
    echo "<p>❌ WooCommerce is not active</p>";
}

// Test 4: Template Loading Check
echo "<h2>📄 Template Loading Diagnosis</h2>";

$template_path = 'templates/single-product/add-to-cart/subscription.php';
if (file_exists($template_path)) {
    echo "<p>✅ Subscription template exists</p>";
    echo "<p>Template size: " . number_format(filesize($template_path)) . " bytes</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($template_path)) . "</p>";
    
    // Check if template has the correct default value
    $template_content = file_get_contents($template_path);
    $correct_default = strpos($template_content, 'value="regular"') !== false;
    echo "<p>Correct default subscription type: " . ($correct_default ? '✅ Yes (regular)' : '❌ No') . "</p>";
} else {
    echo "<p>❌ Subscription template not found</p>";
}

// Test 5: Permissions and Capabilities
echo "<h2>🔐 Permissions Diagnosis</h2>";

if ($is_logged_in) {
    // Check various capabilities that might affect functionality
    $capabilities = [
        'read' => 'Basic read access',
        'edit_posts' => 'Edit posts',
        'manage_woocommerce' => 'Manage WooCommerce',
        'manage_options' => 'Manage options (admin)'
    ];
    
    foreach ($capabilities as $cap => $description) {
        $has_cap = current_user_can($cap);
        echo "<p>$description ($cap): " . ($has_cap ? '✅ Yes' : '❌ No') . "</p>";
    }
} else {
    echo "<p>User not logged in - cannot check capabilities</p>";
}

// Test 6: Browser/Client-side Checks
echo "<h2>🌐 Client-side Diagnosis</h2>";
?>

<script>
jQuery(document).ready(function($) {
    console.log('=== Zlaark Debug Script ===');
    
    // Check if jQuery is loaded
    if (typeof jQuery !== 'undefined') {
        $('#debug-results').append('<p>✅ jQuery is loaded (version: ' + jQuery.fn.jquery + ')</p>');
    } else {
        $('#debug-results').append('<p>❌ jQuery is not loaded</p>');
    }
    
    // Check if body has logged-in class
    var isLoggedInClass = $('body').hasClass('logged-in');
    $('#debug-results').append('<p>Body has logged-in class: ' + (isLoggedInClass ? '✅ Yes' : '❌ No') + '</p>');
    
    // Check if subscription buttons exist
    var trialButtons = $('.trial-button').length;
    var regularButtons = $('.regular-button').length;
    $('#debug-results').append('<p>Trial buttons found: ' + trialButtons + '</p>');
    $('#debug-results').append('<p>Regular buttons found: ' + regularButtons + '</p>');
    
    // Check if subscription form exists
    var subscriptionForms = $('form.cart').length;
    $('#debug-results').append('<p>Cart forms found: ' + subscriptionForms + '</p>');
    
    // Check if subscription_type input exists
    var subscriptionTypeInput = $('#subscription_type').length;
    var subscriptionTypeValue = $('#subscription_type').val();
    $('#debug-results').append('<p>Subscription type input found: ' + (subscriptionTypeInput ? '✅ Yes' : '❌ No') + '</p>');
    $('#debug-results').append('<p>Subscription type value: ' + subscriptionTypeValue + '</p>');
    
    // Test button click functionality
    if (trialButtons > 0 || regularButtons > 0) {
        $('#debug-results').append('<p><strong>Testing button functionality...</strong></p>');
        
        // Add test click handlers
        $('.trial-button, .regular-button').on('click.debug', function(e) {
            e.preventDefault(); // Prevent actual form submission during debug
            
            var $button = $(this);
            var subscriptionType = $button.data('subscription-type');
            
            $('#debug-results').append('<p>🔍 Button clicked: ' + subscriptionType + '</p>');
            $('#debug-results').append('<p>Button element: ' + $button[0].tagName + '.' + $button[0].className + '</p>');
            
            // Check if subscription type is set correctly
            setTimeout(function() {
                var newValue = $('#subscription_type').val();
                $('#debug-results').append('<p>Subscription type after click: ' + newValue + '</p>');
            }, 100);
        });
        
        $('#debug-results').append('<p>✅ Debug click handlers added - try clicking buttons to test</p>');
    }
    
    console.log('=== Debug Script Complete ===');
});
</script>

<div id="debug-results">
    <h3>Client-side Test Results:</h3>
</div>

<?php
// Final recommendations
echo "<h2>🎯 Recommendations</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<h3>To Fix Button Issues:</h3>";
echo "<ol>";
echo "<li><strong>Clear Browser Cache:</strong> Hard refresh (Ctrl+F5) the product page</li>";
echo "<li><strong>Clear WordPress Cache:</strong> If using caching plugins, clear all caches</li>";
echo "<li><strong>Check Browser Console:</strong> Open developer tools and look for JavaScript errors</li>";
echo "<li><strong>Test with Different Users:</strong> Try admin, regular user, and logged-out user</li>";
echo "<li><strong>Disable Other Plugins:</strong> Temporarily disable other plugins to check for conflicts</li>";
echo "<li><strong>Switch Theme:</strong> Temporarily switch to a default theme to check for theme conflicts</li>";
echo "</ol>";
echo "</div>";

echo "<p><small>Delete this file after debugging: <code>rm debug-button-issues.php</code></small></p>";
?>
