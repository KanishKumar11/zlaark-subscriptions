<?php
/**
 * Enable WordPress Debug Logging
 * 
 * This script helps enable WordPress debug logging to capture errors.
 * Run this once, then delete it.
 */

// Security check
if (!isset($_GET['enable_key']) || $_GET['enable_key'] !== 'zlaark2025') {
    die('Access denied. Add ?enable_key=zlaark2025 to the URL.');
}

echo "<h1>🔧 Enable WordPress Debug Logging</h1>";

// Define WordPress root
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

$wp_config_file = ABSPATH . 'wp-config.php';

if (!file_exists($wp_config_file)) {
    echo "❌ wp-config.php not found at: $wp_config_file<br>";
    exit;
}

echo "✅ Found wp-config.php<br>";

// Read current wp-config.php
$wp_config_content = file_get_contents($wp_config_file);

// Check if debug constants are already defined
$has_wp_debug = strpos($wp_config_content, "define('WP_DEBUG'") !== false || strpos($wp_config_content, 'define("WP_DEBUG"') !== false;
$has_wp_debug_log = strpos($wp_config_content, "define('WP_DEBUG_LOG'") !== false || strpos($wp_config_content, 'define("WP_DEBUG_LOG"') !== false;
$has_wp_debug_display = strpos($wp_config_content, "define('WP_DEBUG_DISPLAY'") !== false || strpos($wp_config_content, 'define("WP_DEBUG_DISPLAY"') !== false;

echo "<h2>Current Debug Settings</h2>";
echo "WP_DEBUG defined: " . ($has_wp_debug ? "✅ Yes" : "❌ No") . "<br>";
echo "WP_DEBUG_LOG defined: " . ($has_wp_debug_log ? "✅ Yes" : "❌ No") . "<br>";
echo "WP_DEBUG_DISPLAY defined: " . ($has_wp_debug_display ? "✅ Yes" : "❌ No") . "<br>";

if (defined('WP_DEBUG')) {
    echo "WP_DEBUG value: " . (WP_DEBUG ? "true" : "false") . "<br>";
}
if (defined('WP_DEBUG_LOG')) {
    echo "WP_DEBUG_LOG value: " . (WP_DEBUG_LOG ? "true" : "false") . "<br>";
}
if (defined('WP_DEBUG_DISPLAY')) {
    echo "WP_DEBUG_DISPLAY value: " . (WP_DEBUG_DISPLAY ? "true" : "false") . "<br>";
}

// If debug settings need to be added/updated
if (!$has_wp_debug || !$has_wp_debug_log || !$has_wp_debug_display) {
    echo "<h2>Adding Debug Settings</h2>";
    
    // Create backup
    $backup_file = $wp_config_file . '.backup.' . date('Y-m-d-H-i-s');
    if (copy($wp_config_file, $backup_file)) {
        echo "✅ Created backup: $backup_file<br>";
    } else {
        echo "❌ Could not create backup. Stopping for safety.<br>";
        exit;
    }
    
    // Debug constants to add
    $debug_constants = "
// Debug settings added by Zlaark debug script
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
";
    
    // Find the line with "/* That's all, stop editing!" or similar
    $insert_position = strpos($wp_config_content, "/* That's all, stop editing!");
    if ($insert_position === false) {
        $insert_position = strpos($wp_config_content, "/* That's all, stop editing!");
        if ($insert_position === false) {
            // Try to find the end of the file before the closing PHP tag
            $insert_position = strrpos($wp_config_content, '?>');
            if ($insert_position === false) {
                $insert_position = strlen($wp_config_content);
            }
        }
    }
    
    if ($insert_position !== false) {
        // Insert debug constants
        $new_content = substr($wp_config_content, 0, $insert_position) . 
                      $debug_constants . 
                      substr($wp_config_content, $insert_position);
        
        if (file_put_contents($wp_config_file, $new_content)) {
            echo "✅ Debug constants added to wp-config.php<br>";
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<strong>✅ Debug logging is now enabled!</strong><br>";
            echo "Debug log will be created at: " . ABSPATH . "wp-content/debug.log<br>";
            echo "You can now:<br>";
            echo "1. Try to reproduce the critical error<br>";
            echo "2. Check the debug log using the log viewer<br>";
            echo "3. Delete this script for security<br>";
            echo "</div>";
        } else {
            echo "❌ Could not write to wp-config.php. Check file permissions.<br>";
        }
    } else {
        echo "❌ Could not find insertion point in wp-config.php<br>";
    }
} else {
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>ℹ️ Debug constants are already defined.</strong><br>";
    echo "If debug logging is not working, check that WP_DEBUG and WP_DEBUG_LOG are set to true.<br>";
    echo "</div>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Delete this script for security: <code>rm enable-debug.php</code></li>";
echo "<li>Try to reproduce the critical error on your WordPress site</li>";
echo "<li>Use the debug log viewer to see error messages</li>";
echo "<li>Look for Zlaark-related errors in the logs</li>";
echo "</ol>";

echo "<p><a href='view-debug-log.php?debug_key=zlaark2025'>📋 View Debug Logs</a></p>";
