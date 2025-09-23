<?php
/**
 * CLI Test for Shortcode Issues
 */

require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== Shortcode Debug Test ===" . PHP_EOL;

// Get a subscription product
$products = get_posts([
    'post_type' => 'product',
    'meta_query' => [
        [
            'key' => '_product_type',
            'value' => 'subscription'
        ]
    ],
    'posts_per_page' => 1
]);

if (!empty($products)) {
    $product = wc_get_product($products[0]->ID);
    echo 'Product ID: ' . $product->get_id() . PHP_EOL;
    echo 'Product Name: ' . $product->get_name() . PHP_EOL;
    echo 'Product Type: ' . $product->get_type() . PHP_EOL;
    echo 'Has trial method: ' . (method_exists($product, 'has_trial') ? 'Yes' : 'No') . PHP_EOL;
    
    if (method_exists($product, 'has_trial')) {
        echo 'Has trial: ' . ($product->has_trial() ? 'Yes' : 'No') . PHP_EOL;
        echo 'Trial duration: ' . $product->get_trial_duration() . PHP_EOL;
        echo 'Trial price: ' . $product->get_trial_price() . PHP_EOL;
        echo 'Trial period: ' . $product->get_trial_period() . PHP_EOL;
    }
    
    echo 'Recurring price: ' . $product->get_recurring_price() . PHP_EOL;
    echo 'Billing interval: ' . $product->get_billing_interval() . PHP_EOL;
    echo 'Is purchasable: ' . ($product->is_purchasable() ? 'Yes' : 'No') . PHP_EOL;
    echo 'Is in stock: ' . ($product->is_in_stock() ? 'Yes' : 'No') . PHP_EOL;
    
    echo PHP_EOL . "=== Meta Data ===" . PHP_EOL;
    $meta_keys = [
        '_subscription_trial_price',
        '_subscription_trial_duration', 
        '_subscription_trial_period',
        '_subscription_recurring_price',
        '_subscription_billing_interval',
        '_product_type'
    ];
    
    foreach ($meta_keys as $key) {
        $value = get_post_meta($product->get_id(), $key, true);
        echo $key . ': ' . var_export($value, true) . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Testing Shortcodes ===" . PHP_EOL;
    
    // Test trial button
    echo "Trial button shortcode:" . PHP_EOL;
    $trial_output = do_shortcode('[trial_button product_id="' . $product->get_id() . '"]');
    echo $trial_output . PHP_EOL . PHP_EOL;
    
    // Test subscription button  
    echo "Subscription button shortcode:" . PHP_EOL;
    $sub_output = do_shortcode('[subscription_button product_id="' . $product->get_id() . '"]');
    echo $sub_output . PHP_EOL . PHP_EOL;
    
    // Check if trial service exists
    if (class_exists('ZlaarkSubscriptionsTrialService')) {
        echo "=== Trial Service Test ===" . PHP_EOL;
        try {
            $trial_service = ZlaarkSubscriptionsTrialService::instance();
            $user_id = 1; // Admin user
            $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product->get_id());
            echo "Trial eligibility: " . json_encode($trial_eligibility, JSON_PRETTY_PRINT) . PHP_EOL;
        } catch (Exception $e) {
            echo "Trial service error: " . $e->getMessage() . PHP_EOL;
        }
    } else {
        echo "ZlaarkSubscriptionsTrialService class not found!" . PHP_EOL;
    }
    
} else {
    echo 'No subscription products found' . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
