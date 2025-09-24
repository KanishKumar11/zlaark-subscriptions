<?php
/**
 * Critical Error Debug Script
 * 
 * This script will help identify the exact error causing the critical error.
 * Place this in your WordPress root directory and access it via browser.
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set up basic WordPress constants if not already defined
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

echo "<h1>🔧 Critical Error Debug Analysis</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Check if we can access WordPress
echo "<h2>1. WordPress Environment Check</h2>";

if (file_exists(ABSPATH . 'wp-config.php')) {
    echo "✅ wp-config.php found<br>";
    
    // Try to load WordPress configuration
    try {
        require_once(ABSPATH . 'wp-config.php');
        echo "✅ wp-config.php loaded successfully<br>";
        
        if (defined('WP_DEBUG')) {
            echo "WP_DEBUG: " . (WP_DEBUG ? 'Enabled' : 'Disabled') . "<br>";
        }
        if (defined('WP_DEBUG_LOG')) {
            echo "WP_DEBUG_LOG: " . (WP_DEBUG_LOG ? 'Enabled' : 'Disabled') . "<br>";
        }
        if (defined('WP_DEBUG_DISPLAY')) {
            echo "WP_DEBUG_DISPLAY: " . (WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled') . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error loading wp-config.php: " . $e->getMessage() . "<br>";
    } catch (Error $e) {
        echo "❌ Fatal error loading wp-config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ wp-config.php not found<br>";
}

// Check plugin directory
echo "<h2>2. Plugin Directory Check</h2>";

$plugin_dir = ABSPATH . 'wp-content/plugins/zlaark-subscriptions/';
if (is_dir($plugin_dir)) {
    echo "✅ Plugin directory found: $plugin_dir<br>";
    
    // Check main plugin file
    $main_file = $plugin_dir . 'zlaark-subscriptions.php';
    if (file_exists($main_file)) {
        echo "✅ Main plugin file found<br>";
        
        // Test PHP syntax of main file
        $output = shell_exec("php -l \"$main_file\" 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ Main plugin file syntax OK<br>";
        } else {
            echo "❌ Syntax error in main plugin file:<br>";
            echo "<pre>$output</pre>";
        }
    } else {
        echo "❌ Main plugin file not found<br>";
    }
} else {
    echo "❌ Plugin directory not found: $plugin_dir<br>";
}

// Test individual components
echo "<h2>3. Component Isolation Test</h2>";

if (is_dir($plugin_dir)) {
    // Test each new file individually
    $test_files = [
        'includes/admin/class-zlaark-subscriptions-shortcodes.php',
        'includes/elementor/class-zlaark-subscriptions-elementor.php',
        'includes/elementor/widgets/class-trial-button-widget.php',
        'includes/elementor/widgets/class-subscription-button-widget.php'
    ];
    
    foreach ($test_files as $test_file) {
        $full_path = $plugin_dir . $test_file;
        echo "Testing: $test_file<br>";
        
        if (file_exists($full_path)) {
            // Check syntax
            $output = shell_exec("php -l \"$full_path\" 2>&1");
            if (strpos($output, 'No syntax errors') !== false) {
                echo "&nbsp;&nbsp;✅ Syntax OK<br>";
                
                // Try to include the file in isolation
                try {
                    // Create a minimal environment
                    if (!defined('ZLAARK_SUBSCRIPTIONS_VERSION')) {
                        define('ZLAARK_SUBSCRIPTIONS_VERSION', '1.0.0');
                    }
                    if (!defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR')) {
                        define('ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR', $plugin_dir);
                    }
                    if (!defined('ZLAARK_SUBSCRIPTIONS_PLUGIN_URL')) {
                        define('ZLAARK_SUBSCRIPTIONS_PLUGIN_URL', '/wp-content/plugins/zlaark-subscriptions/');
                    }
                    
                    // Mock essential WordPress functions
                    if (!function_exists('add_action')) {
                        function add_action($hook, $callback, $priority = 10, $args = 1) { return true; }
                    }
                    if (!function_exists('add_filter')) {
                        function add_filter($hook, $callback, $priority = 10, $args = 1) { return true; }
                    }
                    if (!function_exists('__')) {
                        function __($text, $domain = 'default') { return $text; }
                    }
                    
                    // Try to include the file
                    ob_start();
                    include_once $full_path;
                    $output = ob_get_clean();
                    
                    echo "&nbsp;&nbsp;✅ File included successfully<br>";
                    if (!empty($output)) {
                        echo "&nbsp;&nbsp;Output: <pre>" . htmlspecialchars($output) . "</pre>";
                    }
                    
                } catch (ParseError $e) {
                    echo "&nbsp;&nbsp;❌ Parse Error: " . $e->getMessage() . " on line " . $e->getLine() . "<br>";
                } catch (Error $e) {
                    echo "&nbsp;&nbsp;❌ Fatal Error: " . $e->getMessage() . " on line " . $e->getLine() . "<br>";
                } catch (Exception $e) {
                    echo "&nbsp;&nbsp;❌ Exception: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "&nbsp;&nbsp;❌ Syntax Error:<br>";
                echo "&nbsp;&nbsp;<pre>" . htmlspecialchars($output) . "</pre>";
            }
        } else {
            echo "&nbsp;&nbsp;❌ File not found<br>";
        }
        echo "<br>";
    }
}

// Check for WordPress debug log
echo "<h2>4. WordPress Debug Log Check</h2>";

$debug_log_locations = [
    ABSPATH . 'wp-content/debug.log',
    ABSPATH . 'debug.log',
    ABSPATH . 'wp-content/uploads/debug.log'
];

foreach ($debug_log_locations as $log_file) {
    if (file_exists($log_file)) {
        echo "✅ Found debug log: $log_file<br>";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($log_file)) . "<br>";
        
        // Show last 10 lines
        $lines = file($log_file);
        if ($lines) {
            $last_lines = array_slice($lines, -10);
            echo "<strong>Last 10 lines:</strong><br>";
            echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;'>";
            foreach ($last_lines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        }
        echo "<br>";
    }
}

echo "<h2>5. Manual Plugin Test</h2>";
echo "<p>To test the plugin manually:</p>";
echo "<ol>";
echo "<li>Temporarily rename the plugin folder to disable it</li>";
echo "<li>Check if the critical error disappears</li>";
echo "<li>If it does, the error is definitely in our plugin</li>";
echo "<li>Then test individual components by commenting them out</li>";
echo "</ol>";

echo "<h2>6. Next Steps</h2>";
echo "<p>If no errors are shown above, the issue might be:</p>";
echo "<ul>";
echo "<li>A runtime error that only occurs in WordPress context</li>";
echo "<li>A memory limit issue</li>";
echo "<li>A conflict with another plugin</li>";
echo "<li>An issue with WordPress hooks or timing</li>";
echo "</ul>";

echo "<p><strong>Check the debug logs above for specific error messages!</strong></p>";
