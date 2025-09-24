<?php
/**
 * Hook Execution Debug Tool
 * 
 * This script helps debug the exact order of hook execution on single product pages
 * to identify why buttons are appearing at the bottom instead of after the title.
 */

// Security check
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'zlaark2025') {
    die('Access denied. Add ?debug_key=zlaark2025 to the URL.');
}

// Enable WordPress environment
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "<h1>🔍 Hook Execution Debug Tool</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Check if we're debugging a specific product
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) {
        echo "<p>❌ Product ID $product_id not found.</p>";
        exit;
    }
    
    echo "<h2>📦 Product Information</h2>";
    echo "<p><strong>Product ID:</strong> {$product->get_id()}</p>";
    echo "<p><strong>Product Name:</strong> {$product->get_name()}</p>";
    echo "<p><strong>Product Type:</strong> {$product->get_type()}</p>";
    echo "<p><strong>Is Subscription:</strong> " . ($product->get_type() === 'subscription' ? 'Yes' : 'No') . "</p>";
}

echo "<h2>🎯 Hook Analysis</h2>";

// Get all hooks registered for woocommerce_single_product_summary
global $wp_filter;
$hook_name = 'woocommerce_single_product_summary';

if (isset($wp_filter[$hook_name])) {
    echo "<h3>Registered Hooks for '$hook_name':</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Priority</th><th>Function/Method</th><th>Class</th><th>Accepted Args</th></tr>";
    
    $hooks = $wp_filter[$hook_name]->callbacks;
    ksort($hooks); // Sort by priority
    
    foreach ($hooks as $priority => $callbacks) {
        foreach ($callbacks as $callback_id => $callback_info) {
            $function_name = '';
            $class_name = '';
            
            if (is_array($callback_info['function'])) {
                if (is_object($callback_info['function'][0])) {
                    $class_name = get_class($callback_info['function'][0]);
                    $function_name = $callback_info['function'][1];
                } else {
                    $class_name = $callback_info['function'][0];
                    $function_name = $callback_info['function'][1];
                }
            } else {
                $function_name = $callback_info['function'];
            }
            
            // Highlight Zlaark-related hooks
            $row_style = '';
            if (stripos($class_name, 'zlaark') !== false || stripos($function_name, 'zlaark') !== false) {
                $row_style = 'background-color: #fff3cd; font-weight: bold;';
            }
            
            echo "<tr style='$row_style'>";
            echo "<td>$priority</td>";
            echo "<td>$function_name</td>";
            echo "<td>$class_name</td>";
            echo "<td>{$callback_info['accepted_args']}</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p>❌ No hooks found for '$hook_name'</p>";
}

// Check for Zlaark-specific classes and their hook registrations
echo "<h2>🔍 Zlaark Class Analysis</h2>";

$zlaark_classes = [
    'ZlaarkSubscriptionsProductType',
    'ZlaarkSubscriptionsFrontend'
];

foreach ($zlaark_classes as $class_name) {
    echo "<h3>$class_name</h3>";
    if (class_exists($class_name)) {
        echo "✅ Class exists<br>";
        
        // Try to get instance if it's a singleton
        if (method_exists($class_name, 'instance')) {
            try {
                $instance = $class_name::instance();
                echo "✅ Singleton instance available<br>";
            } catch (Exception $e) {
                echo "❌ Error getting instance: " . $e->getMessage() . "<br>";
            }
        }
        
        // Check if specific methods exist
        $methods_to_check = [
            'load_subscription_add_to_cart',
            'display_trial_highlight',
            'display_comprehensive_trial_info',
            'display_subscription_info'
        ];
        
        foreach ($methods_to_check as $method) {
            if (method_exists($class_name, $method)) {
                echo "✅ Method '$method' exists<br>";
            }
        }
    } else {
        echo "❌ Class does not exist<br>";
    }
}

// Check template file
echo "<h2>📄 Template File Analysis</h2>";
$template_path = 'templates/single-product/add-to-cart/subscription.php';
if (file_exists($template_path)) {
    echo "✅ Template file exists: $template_path<br>";
    echo "File size: " . number_format(filesize($template_path)) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($template_path)) . "<br>";
} else {
    echo "❌ Template file not found: $template_path<br>";
}

// Check for hook execution order in real-time
echo "<h2>🚀 Real-Time Hook Execution Test</h2>";

if ($product_id) {
    echo "<p>Testing hook execution for product ID: $product_id</p>";
    
    // Add temporary debug hooks at various priorities
    $debug_priorities = [5, 6, 7, 8, 10, 20, 25, 30, 35];
    
    foreach ($debug_priorities as $priority) {
        add_action('woocommerce_single_product_summary', function() use ($priority) {
            echo "<!-- DEBUG: Hook executed at priority $priority -->";
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px; font-size: 12px;'>DEBUG: Priority $priority executed</div>";
            }
        }, $priority);
    }
    
    echo "<p>Debug hooks added. Visit the product page to see execution order.</p>";
    echo "<p><a href='" . get_permalink($product_id) . "' target='_blank'>🔗 View Product Page</a></p>";
} else {
    echo "<p>❌ No product ID specified. Add &product_id=123 to test a specific product.</p>";
}

// Instructions
echo "<h2>📋 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Check Hook Registration:</strong> Look for Zlaark hooks in the table above</li>";
echo "<li><strong>Verify Priority 6:</strong> Confirm load_subscription_add_to_cart is at priority 6</li>";
echo "<li><strong>Test Product Page:</strong> Visit a subscription product page to see debug output</li>";
echo "<li><strong>Check for Conflicts:</strong> Look for other hooks at similar priorities</li>";
echo "<li><strong>Template Loading:</strong> Verify the template file exists and is accessible</li>";
echo "</ol>";

echo "<p><strong>Usage:</strong> Add &product_id=123 to test a specific subscription product.</p>";
echo "<p><a href='?debug_key=zlaark2025&product_id='>🔄 Refresh Analysis</a></p>";
