<?php
/**
 * Debug Log Viewer
 * 
 * This script helps view WordPress debug logs to identify critical errors.
 * Place this in your WordPress root directory and access via browser.
 */

// Security check
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'zlaark2025') {
    die('Access denied. Add ?debug_key=zlaark2025 to the URL.');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 WordPress Debug Log Viewer</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Define WordPress root
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Possible debug log locations
$debug_log_locations = [
    ABSPATH . 'wp-content/debug.log',
    ABSPATH . 'debug.log',
    ABSPATH . 'wp-content/uploads/debug.log',
    ABSPATH . 'wp-content/plugins/debug.log',
    '/tmp/wordpress_debug.log'
];

echo "<h2>Debug Log Locations</h2>";

$found_logs = [];
foreach ($debug_log_locations as $log_file) {
    if (file_exists($log_file)) {
        $found_logs[] = $log_file;
        echo "✅ Found: $log_file<br>";
        echo "&nbsp;&nbsp;Size: " . number_format(filesize($log_file)) . " bytes<br>";
        echo "&nbsp;&nbsp;Last modified: " . date('Y-m-d H:i:s', filemtime($log_file)) . "<br><br>";
    } else {
        echo "❌ Not found: $log_file<br>";
    }
}

if (empty($found_logs)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>⚠️ No debug logs found!</strong><br>";
    echo "This could mean:<br>";
    echo "• WP_DEBUG_LOG is not enabled<br>";
    echo "• No errors have occurred yet<br>";
    echo "• Logs are in a different location<br><br>";
    echo "To enable debug logging, add these lines to wp-config.php:<br>";
    echo "<code>define('WP_DEBUG', true);<br>";
    echo "define('WP_DEBUG_LOG', true);<br>";
    echo "define('WP_DEBUG_DISPLAY', false);</code>";
    echo "</div>";
}

// Show recent entries from each log
foreach ($found_logs as $log_file) {
    echo "<h2>Recent Entries: " . basename($log_file) . "</h2>";
    
    if (filesize($log_file) > 1024 * 1024) { // If larger than 1MB
        echo "<p><strong>⚠️ Large log file (" . number_format(filesize($log_file)) . " bytes). Showing last 50 lines only.</strong></p>";
    }
    
    // Get last 50 lines
    $lines = file($log_file);
    if ($lines) {
        $recent_lines = array_slice($lines, -50);
        
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<pre style='max-height: 400px; overflow-y: auto; font-size: 12px; line-height: 1.4;'>";
        
        foreach ($recent_lines as $line) {
            $line = htmlspecialchars($line);
            
            // Highlight Zlaark-related entries
            if (stripos($line, 'zlaark') !== false) {
                echo "<span style='background: yellow; font-weight: bold;'>$line</span>";
            }
            // Highlight fatal errors
            elseif (stripos($line, 'fatal error') !== false || stripos($line, 'parse error') !== false) {
                echo "<span style='background: #ffebee; color: #c62828; font-weight: bold;'>$line</span>";
            }
            // Highlight warnings
            elseif (stripos($line, 'warning') !== false) {
                echo "<span style='background: #fff3e0; color: #ef6c00;'>$line</span>";
            }
            // Highlight notices
            elseif (stripos($line, 'notice') !== false) {
                echo "<span style='color: #1976d2;'>$line</span>";
            }
            else {
                echo $line;
            }
        }
        
        echo "</pre>";
        echo "</div>";
    } else {
        echo "<p>❌ Could not read log file.</p>";
    }
}

// Show PHP error log if available
echo "<h2>PHP Error Log Check</h2>";
$php_error_log = ini_get('error_log');
if ($php_error_log && file_exists($php_error_log)) {
    echo "✅ PHP error log found: $php_error_log<br>";
    echo "Size: " . number_format(filesize($php_error_log)) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($php_error_log)) . "<br>";
    
    // Show recent PHP errors
    $php_lines = file($php_error_log);
    if ($php_lines) {
        $recent_php_lines = array_slice($php_lines, -20);
        echo "<h3>Recent PHP Errors (Last 20 lines)</h3>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto; font-size: 12px;'>";
        foreach ($recent_php_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    }
} else {
    echo "❌ PHP error log not found or not configured<br>";
    echo "PHP error_log setting: " . ($php_error_log ? $php_error_log : 'Not set') . "<br>";
}

// Instructions
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Look for Zlaark-related entries</strong> (highlighted in yellow above)</li>";
echo "<li><strong>Look for Fatal Errors</strong> (highlighted in red above)</li>";
echo "<li><strong>Check the timestamp</strong> of errors to see if they match when you experience the critical error</li>";
echo "<li><strong>Try accessing your WordPress site</strong> in another tab to generate fresh errors</li>";
echo "<li><strong>Refresh this page</strong> to see new log entries</li>";
echo "</ol>";

echo "<p><a href='?debug_key=zlaark2025&refresh=" . time() . "'>🔄 Refresh Log View</a></p>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>💡 Tip:</strong> If you don't see any Zlaark-related errors, try:<br>";
echo "1. Activating the plugin (if it's deactivated)<br>";
echo "2. Visiting a page on your WordPress site<br>";
echo "3. Refreshing this debug log viewer<br>";
echo "</div>";
