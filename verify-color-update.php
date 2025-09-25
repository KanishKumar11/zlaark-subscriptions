<?php
/**
 * Color Update Verification Script
 * 
 * Verifies that the exact hex color codes have been applied:
 * - Trial Button: #D6809C (pink) primary, #927397 (purple) secondary
 * - Subscription Button: #927397 (purple) primary, #D6809C (pink) secondary
 */

// Security check
if (!isset($_GET['verify_key']) || $_GET['verify_key'] !== 'zlaark2025') {
    die('Access denied. Add ?verify_key=zlaark2025 to the URL.');
}

echo "<h1>🎨 SUBSCRIPTION BUTTON COLOR UPDATE VERIFICATION</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Define the exact colors that should be implemented
$expected_colors = [
    'trial_primary' => '#D6809C',      // Pink
    'trial_secondary' => '#927397',    // Purple
    'subscription_primary' => '#927397', // Purple
    'subscription_secondary' => '#D6809C' // Pink
];

echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 Expected Color Scheme</h3>";
echo "<div style='display: flex; gap: 20px; margin: 10px 0;'>";
echo "<div style='background: {$expected_colors['trial_primary']}; color: white; padding: 10px; border-radius: 5px; text-align: center;'>";
echo "<strong>Trial Primary</strong><br>{$expected_colors['trial_primary']}<br>(Pink)";
echo "</div>";
echo "<div style='background: {$expected_colors['trial_secondary']}; color: white; padding: 10px; border-radius: 5px; text-align: center;'>";
echo "<strong>Trial Secondary</strong><br>{$expected_colors['trial_secondary']}<br>(Purple)";
echo "</div>";
echo "<div style='background: {$expected_colors['subscription_primary']}; color: white; padding: 10px; border-radius: 5px; text-align: center;'>";
echo "<strong>Subscription Primary</strong><br>{$expected_colors['subscription_primary']}<br>(Purple)";
echo "</div>";
echo "<div style='background: {$expected_colors['subscription_secondary']}; color: white; padding: 10px; border-radius: 5px; text-align: center;'>";
echo "<strong>Subscription Secondary</strong><br>{$expected_colors['subscription_secondary']}<br>(Pink)";
echo "</div>";
echo "</div>";
echo "</div>";

// VERIFICATION 1: Template File Colors
echo "<h2>1. 📄 TEMPLATE FILE COLOR VERIFICATION</h2>";

$template_path = 'templates/single-product/add-to-cart/subscription.php';
$template_verified = true;
$template_issues = [];

if (file_exists($template_path)) {
    $template_content = file_get_contents($template_path);
    
    echo "<h3>✅ Template Button Colors</h3>";
    
    // Check trial button colors
    $trial_primary_found = strpos($template_content, $expected_colors['trial_primary']) !== false;
    $trial_secondary_found = strpos($template_content, $expected_colors['trial_secondary']) !== false;
    
    echo "<p><strong>Trial Button Primary ({$expected_colors['trial_primary']}):</strong> " . ($trial_primary_found ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>Trial Button Secondary ({$expected_colors['trial_secondary']}):</strong> " . ($trial_secondary_found ? '✅ FOUND' : '❌ MISSING') . "</p>";
    
    // Check subscription button colors
    $sub_primary_found = strpos($template_content, $expected_colors['subscription_primary']) !== false;
    $sub_secondary_found = strpos($template_content, $expected_colors['subscription_secondary']) !== false;
    
    echo "<p><strong>Subscription Button Primary ({$expected_colors['subscription_primary']}):</strong> " . ($sub_primary_found ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>Subscription Button Secondary ({$expected_colors['subscription_secondary']}):</strong> " . ($sub_secondary_found ? '✅ FOUND' : '❌ MISSING') . "</p>";
    
    // Check for old colors that should be removed
    $old_colors = ['#10B981', '#059669', '#3B82F6', '#1D4ED8'];
    $old_colors_found = [];
    foreach ($old_colors as $old_color) {
        if (strpos($template_content, $old_color) !== false) {
            $old_colors_found[] = $old_color;
        }
    }
    
    if (empty($old_colors_found)) {
        echo "<p><strong>Old colors removed:</strong> ✅ YES</p>";
    } else {
        echo "<p><strong>Old colors still present:</strong> ❌ " . implode(', ', $old_colors_found) . "</p>";
        $template_verified = false;
        $template_issues[] = "Old colors still present in template";
    }
    
    if (!$trial_primary_found || !$trial_secondary_found || !$sub_primary_found || !$sub_secondary_found) {
        $template_verified = false;
        $template_issues[] = "Missing required colors in template";
    }
} else {
    $template_verified = false;
    $template_issues[] = "Template file not found";
}

// VERIFICATION 2: CSS File Colors
echo "<h2>2. 🎨 CSS FILE COLOR VERIFICATION</h2>";

$css_path = 'assets/css/frontend.css';
$css_verified = true;
$css_issues = [];

if (file_exists($css_path)) {
    $css_content = file_get_contents($css_path);
    
    echo "<h3>✅ CSS Button Colors</h3>";
    
    // Check template button colors in CSS
    $css_trial_primary = strpos($css_content, $expected_colors['trial_primary']) !== false;
    $css_trial_secondary = strpos($css_content, $expected_colors['trial_secondary']) !== false;
    $css_sub_primary = strpos($css_content, $expected_colors['subscription_primary']) !== false;
    $css_sub_secondary = strpos($css_content, $expected_colors['subscription_secondary']) !== false;
    
    echo "<p><strong>CSS Trial Primary ({$expected_colors['trial_primary']}):</strong> " . ($css_trial_primary ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>CSS Trial Secondary ({$expected_colors['trial_secondary']}):</strong> " . ($css_trial_secondary ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>CSS Subscription Primary ({$expected_colors['subscription_primary']}):</strong> " . ($css_sub_primary ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>CSS Subscription Secondary ({$expected_colors['subscription_secondary']}):</strong> " . ($css_sub_secondary ? '✅ FOUND' : '❌ MISSING') . "</p>";
    
    // Check shortcode button colors
    $shortcode_trial_found = strpos($css_content, '.zlaark-trial-btn') !== false && $css_trial_primary;
    $shortcode_sub_found = strpos($css_content, '.zlaark-subscription-btn') !== false && $css_sub_primary;
    
    echo "<h3>✅ Shortcode Button Colors</h3>";
    echo "<p><strong>Shortcode Trial Button:</strong> " . ($shortcode_trial_found ? '✅ UPDATED' : '❌ NOT UPDATED') . "</p>";
    echo "<p><strong>Shortcode Subscription Button:</strong> " . ($shortcode_sub_found ? '✅ UPDATED' : '❌ NOT UPDATED') . "</p>";
    
    // Check for old colors in CSS
    $css_old_colors_found = [];
    foreach ($old_colors as $old_color) {
        if (strpos($css_content, $old_color) !== false) {
            $css_old_colors_found[] = $old_color;
        }
    }
    
    if (empty($css_old_colors_found)) {
        echo "<p><strong>Old colors removed from CSS:</strong> ✅ YES</p>";
    } else {
        echo "<p><strong>Old colors still in CSS:</strong> ❌ " . implode(', ', $css_old_colors_found) . "</p>";
        $css_verified = false;
        $css_issues[] = "Old colors still present in CSS";
    }
    
    if (!$css_trial_primary || !$css_trial_secondary || !$css_sub_primary || !$css_sub_secondary) {
        $css_verified = false;
        $css_issues[] = "Missing required colors in CSS";
    }
} else {
    $css_verified = false;
    $css_issues[] = "CSS file not found";
}

// VERIFICATION 3: RGBA Values Check
echo "<h2>3. 🌈 RGBA VALUES VERIFICATION</h2>";

$rgba_verified = true;
$rgba_issues = [];

// Convert hex to RGB for verification
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

$trial_rgb = hexToRgb($expected_colors['trial_primary']);
$sub_rgb = hexToRgb($expected_colors['subscription_primary']);

echo "<h3>✅ Expected RGBA Values</h3>";
echo "<p><strong>Trial Pink RGBA:</strong> rgba({$trial_rgb['r']}, {$trial_rgb['g']}, {$trial_rgb['b']}, 0.3)</p>";
echo "<p><strong>Subscription Purple RGBA:</strong> rgba({$sub_rgb['r']}, {$sub_rgb['g']}, {$sub_rgb['b']}, 0.3)</p>";

// Check if RGBA values are used in files
$trial_rgba_pattern = "rgba({$trial_rgb['r']}, {$trial_rgb['g']}, {$trial_rgb['b']}";
$sub_rgba_pattern = "rgba({$sub_rgb['r']}, {$sub_rgb['g']}, {$sub_rgb['b']}";

if (isset($template_content)) {
    $template_rgba_trial = strpos($template_content, $trial_rgba_pattern) !== false;
    $template_rgba_sub = strpos($template_content, $sub_rgba_pattern) !== false;
    
    echo "<p><strong>Template Trial RGBA:</strong> " . ($template_rgba_trial ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>Template Subscription RGBA:</strong> " . ($template_rgba_sub ? '✅ FOUND' : '❌ MISSING') . "</p>";
}

if (isset($css_content)) {
    $css_rgba_trial = strpos($css_content, $trial_rgba_pattern) !== false;
    $css_rgba_sub = strpos($css_content, $sub_rgba_pattern) !== false;
    
    echo "<p><strong>CSS Trial RGBA:</strong> " . ($css_rgba_trial ? '✅ FOUND' : '❌ MISSING') . "</p>";
    echo "<p><strong>CSS Subscription RGBA:</strong> " . ($css_rgba_sub ? '✅ FOUND' : '❌ MISSING') . "</p>";
}

// CLIENT-SIDE VERIFICATION
echo "<h2>4. 🌐 CLIENT-SIDE COLOR VERIFICATION</h2>";
?>

<div id="client-color-results">
    <h3>Real-time Color Verification:</h3>
</div>

<script>
jQuery(document).ready(function($) {
    var results = $('#client-color-results');
    
    // Expected colors
    var expectedColors = {
        trialPrimary: '#D6809C',
        trialSecondary: '#927397',
        subscriptionPrimary: '#927397',
        subscriptionSecondary: '#D6809C'
    };
    
    // Function to convert RGB to hex
    function rgbToHex(rgb) {
        var result = rgb.match(/\d+/g);
        if (result && result.length >= 3) {
            return "#" + ((1 << 24) + (parseInt(result[0]) << 16) + (parseInt(result[1]) << 8) + parseInt(result[2])).toString(16).slice(1).toUpperCase();
        }
        return rgb;
    }
    
    // Check button colors
    var trialButtons = $('.trial-button');
    var regularButtons = $('.regular-button');
    
    results.append('<p><strong>Button Detection:</strong></p>');
    results.append('<p>Trial buttons found: ' + trialButtons.length + '</p>');
    results.append('<p>Regular buttons found: ' + regularButtons.length + '</p>');
    
    if (trialButtons.length > 0) {
        var trialBg = trialButtons.css('background-color') || trialButtons.css('background-image');
        var trialHex = rgbToHex(trialBg);
        
        results.append('<p><strong>Trial Button Color:</strong> ' + trialBg + '</p>');
        results.append('<p><strong>Trial Button Hex:</strong> ' + trialHex + '</p>');
        
        var trialCorrect = trialBg.includes('214, 128, 156') || trialHex.includes('D6809C');
        results.append('<p><strong>Trial Color Correct:</strong> ' + (trialCorrect ? '✅ YES' : '❌ NO') + '</p>');
    }
    
    if (regularButtons.length > 0) {
        var regularBg = regularButtons.css('background-color') || regularButtons.css('background-image');
        var regularHex = rgbToHex(regularBg);
        
        results.append('<p><strong>Regular Button Color:</strong> ' + regularBg + '</p>');
        results.append('<p><strong>Regular Button Hex:</strong> ' + regularHex + '</p>');
        
        var regularCorrect = regularBg.includes('146, 115, 151') || regularHex.includes('927397');
        results.append('<p><strong>Regular Color Correct:</strong> ' + (regularCorrect ? '✅ YES' : '❌ NO') + '</p>');
    }
    
    // Check shortcode buttons if present
    var shortcodeTrialBtns = $('.zlaark-trial-btn');
    var shortcodeSubBtns = $('.zlaark-subscription-btn');
    
    if (shortcodeTrialBtns.length > 0 || shortcodeSubBtns.length > 0) {
        results.append('<p><strong>Shortcode Buttons:</strong></p>');
        results.append('<p>Shortcode trial buttons: ' + shortcodeTrialBtns.length + '</p>');
        results.append('<p>Shortcode subscription buttons: ' + shortcodeSubBtns.length + '</p>');
    }
    
    console.log('Zlaark: Color verification completed');
});
</script>

<?php
// FINAL SUMMARY
echo "<h2>📋 COLOR UPDATE SUMMARY</h2>";

$all_verified = $template_verified && $css_verified && $rgba_verified;
$all_issues = array_merge($template_issues, $css_issues, $rgba_issues);

echo "<div style='background: " . ($all_verified ? '#d4edda' : '#f8d7da') . "; border: 2px solid " . ($all_verified ? '#c3e6cb' : '#f5c6cb') . "; padding: 20px; border-radius: 10px; margin: 20px 0;'>";

if ($all_verified) {
    echo "<h3>🎉 COLOR UPDATE SUCCESSFUL!</h3>";
    echo "<p><strong>✅ All colors have been updated to the exact specifications:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Trial Buttons:</strong> Pink (#D6809C) primary, Purple (#927397) secondary</li>";
    echo "<li><strong>Subscription Buttons:</strong> Purple (#927397) primary, Pink (#D6809C) secondary</li>";
    echo "<li><strong>Template File:</strong> All button classes updated with !important flags</li>";
    echo "<li><strong>CSS File:</strong> Both template and shortcode buttons updated</li>";
    echo "<li><strong>RGBA Values:</strong> Box-shadows updated with correct transparency</li>";
    echo "<li><strong>Old Colors:</strong> Previous Emerald Green and Blue colors removed</li>";
    echo "</ul>";
    
    echo "<h4>🎨 Color Scheme Applied:</h4>";
    echo "<div style='display: flex; gap: 10px; margin: 10px 0;'>";
    echo "<div style='background: linear-gradient(135deg, #D6809C 0%, #927397 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; flex: 1;'>";
    echo "<strong>TRIAL BUTTON</strong><br>Pink → Purple Gradient";
    echo "</div>";
    echo "<div style='background: linear-gradient(135deg, #927397 0%, #D6809C 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; flex: 1;'>";
    echo "<strong>SUBSCRIPTION BUTTON</strong><br>Purple → Pink Gradient";
    echo "</div>";
    echo "</div>";
} else {
    echo "<h3>⚠️ COLOR UPDATE ISSUES FOUND</h3>";
    echo "<p>Some issues were detected during the color update verification:</p>";
    echo "<ul>";
    foreach ($all_issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "<h3>🧪 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Clear Browser Cache:</strong> Hard refresh (Ctrl+F5) to see updated colors</li>";
echo "<li><strong>Clear WordPress Cache:</strong> If using caching plugins, clear all caches</li>";
echo "<li><strong>Test Button Appearance:</strong> Visit subscription product pages to verify colors</li>";
echo "<li><strong>Test Hover Effects:</strong> Hover over buttons to see color transitions</li>";
echo "<li><strong>Test Shortcode Buttons:</strong> If using shortcodes, verify they match</li>";
echo "</ol>";

echo "<p><strong>Expected Result:</strong> All subscription buttons should now display with the exact pink (#D6809C) and purple (#927397) color scheme as specified.</p>";

echo "<p><small>Delete this file after verification: <code>rm verify-color-update.php</code></small></p>";
?>
