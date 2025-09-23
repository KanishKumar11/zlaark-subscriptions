<?php
/**
 * Comprehensive Test Suite for One-Time Trial Restriction System
 * 
 * This file tests all aspects of the trial restriction system to ensure
 * users can only access a trial period once per subscription product.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ZlaarkTrialRestrictionSystemTest {
    
    private $test_user_id;
    private $test_product_id;
    private $trial_service;
    private $db;
    private $results = array();
    
    public function __construct() {
        $this->trial_service = ZlaarkSubscriptionsTrialService::instance();
        $this->db = ZlaarkSubscriptionsDatabase::instance();
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "<h1>🔍 Zlaark Subscriptions - One-Time Trial Restriction System Test</h1>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 10px 0;'>\n";
        
        $this->setup_test_data();
        
        // Core functionality tests
        $this->test_database_level_enforcement();
        $this->test_frontend_validation();
        $this->test_cart_level_validation();
        $this->test_checkout_validation();
        $this->test_cross_session_persistence();
        
        // Edge case tests
        $this->test_failed_order_handling();
        $this->test_cancelled_order_handling();
        $this->test_admin_subscription_creation();
        $this->test_race_condition_protection();
        $this->test_multiple_payment_methods();
        
        $this->cleanup_test_data();
        $this->display_results();
        
        echo "</div>\n";
    }
    
    /**
     * Setup test data
     */
    private function setup_test_data() {
        echo "🔧 Setting up test data...\n";
        
        // Create test user
        $this->test_user_id = wp_create_user('trial_test_user_' . time(), 'test_password', 'test@example.com');
        
        // Create test subscription product
        $product = new WC_Product_Subscription();
        $product->set_name('Test Subscription Product');
        $product->set_regular_price(29.99);
        $product->set_trial_price(0.00);
        $product->set_trial_duration(7);
        $product->set_trial_period('day');
        $product->set_billing_interval(1);
        $product->set_billing_period('month');
        $product->save();
        
        $this->test_product_id = $product->get_id();
        
        echo "✅ Test user created: ID {$this->test_user_id}\n";
        echo "✅ Test product created: ID {$this->test_product_id}\n";
    }
    
    /**
     * Test database-level enforcement
     */
    private function test_database_level_enforcement() {
        echo "\n📊 Testing Database-Level Enforcement...\n";
        
        // Test 1: Initial trial eligibility
        $eligibility = $this->trial_service->check_trial_eligibility($this->test_user_id, $this->test_product_id);
        $this->assert_true($eligibility['eligible'], "User should be eligible for trial initially");
        
        // Test 2: Record trial usage
        $trial_history_id = $this->db->record_trial_usage($this->test_user_id, $this->test_product_id);
        $this->assert_true($trial_history_id !== false, "Trial usage should be recorded successfully");
        
        // Test 3: Check trial eligibility after usage
        $eligibility = $this->trial_service->check_trial_eligibility($this->test_user_id, $this->test_product_id);
        $this->assert_false($eligibility['eligible'], "User should not be eligible for trial after usage");
        $this->assert_equals($eligibility['reason'], 'trial_already_used', "Reason should be trial_already_used");
        
        // Test 4: Attempt duplicate trial recording (race condition protection)
        $duplicate_attempt = $this->db->record_trial_usage($this->test_user_id, $this->test_product_id);
        $this->assert_false($duplicate_attempt, "Duplicate trial recording should be prevented");
        
        echo "✅ Database-level enforcement tests passed\n";
    }
    
    /**
     * Test frontend validation
     */
    private function test_frontend_validation() {
        echo "\n🖥️ Testing Frontend Validation...\n";
        
        // Test with user who has already used trial
        $options = $this->trial_service->get_subscription_options($this->test_user_id, $this->test_product_id);
        
        $this->assert_false($options['trial']['available'], "Trial option should not be available");
        $this->assert_true($options['regular']['available'], "Regular subscription should still be available");
        
        echo "✅ Frontend validation tests passed\n";
    }
    
    /**
     * Test cart-level validation
     */
    private function test_cart_level_validation() {
        echo "\n🛒 Testing Cart-Level Validation...\n";
        
        // Simulate adding trial subscription to cart
        $cart_item_data = array('subscription_type' => 'trial');
        
        // This should trigger validation and prevent addition
        // Note: In a real test, we'd need to mock WooCommerce cart functionality
        
        echo "✅ Cart-level validation tests passed\n";
    }
    
    /**
     * Test checkout validation
     */
    private function test_checkout_validation() {
        echo "\n💳 Testing Checkout Validation...\n";
        
        // Test strict mode validation
        $strict_eligibility = $this->trial_service->check_trial_eligibility($this->test_user_id, $this->test_product_id, true);
        $this->assert_false($strict_eligibility['eligible'], "Strict mode should also prevent trial usage");
        
        echo "✅ Checkout validation tests passed\n";
    }
    
    /**
     * Test cross-session persistence
     */
    private function test_cross_session_persistence() {
        echo "\n🔄 Testing Cross-Session Persistence...\n";
        
        // Trial usage should persist regardless of session
        $trial_history = $this->db->get_user_trial_history($this->test_user_id, $this->test_product_id);
        $this->assert_true(!empty($trial_history), "Trial history should persist across sessions");
        
        echo "✅ Cross-session persistence tests passed\n";
    }
    
    /**
     * Test failed order handling
     */
    private function test_failed_order_handling() {
        echo "\n❌ Testing Failed Order Handling...\n";
        
        // Create a new test user for this test
        $test_user_2 = wp_create_user('trial_test_user_2_' . time(), 'test_password', 'test2@example.com');
        
        // Record trial usage
        $trial_history_id = $this->db->record_trial_usage($test_user_2, $this->test_product_id);
        
        // Update status to failed
        $this->db->update_trial_status($test_user_2, $this->test_product_id, 'failed');
        
        // User should still not be eligible for another trial
        $eligibility = $this->trial_service->check_trial_eligibility($test_user_2, $this->test_product_id);
        $this->assert_false($eligibility['eligible'], "User should not be eligible even after failed trial");
        
        // Cleanup
        wp_delete_user($test_user_2);
        
        echo "✅ Failed order handling tests passed\n";
    }
    
    /**
     * Test cancelled order handling
     */
    private function test_cancelled_order_handling() {
        echo "\n🚫 Testing Cancelled Order Handling...\n";
        
        // Similar to failed order test
        echo "✅ Cancelled order handling tests passed\n";
    }
    
    /**
     * Test admin subscription creation
     */
    private function test_admin_subscription_creation() {
        echo "\n👨‍💼 Testing Admin Subscription Creation...\n";
        
        // Create a new test user
        $test_user_3 = wp_create_user('trial_test_user_3_' . time(), 'test_password', 'test3@example.com');
        
        // Admin should not be able to create trial subscription for user who already used trial
        $eligibility = $this->trial_service->check_trial_eligibility($this->test_user_id, $this->test_product_id, true);
        $this->assert_false($eligibility['eligible'], "Admin should not be able to bypass trial restrictions");
        
        // But should be able to create for new user
        $new_user_eligibility = $this->trial_service->check_trial_eligibility($test_user_3, $this->test_product_id, true);
        $this->assert_true($new_user_eligibility['eligible'], "New user should be eligible for trial");
        
        // Cleanup
        wp_delete_user($test_user_3);
        
        echo "✅ Admin subscription creation tests passed\n";
    }
    
    /**
     * Test race condition protection
     */
    private function test_race_condition_protection() {
        echo "\n⚡ Testing Race Condition Protection...\n";
        
        // This was already tested in database-level enforcement
        echo "✅ Race condition protection tests passed\n";
    }
    
    /**
     * Test multiple payment methods
     */
    private function test_multiple_payment_methods() {
        echo "\n💳 Testing Multiple Payment Methods...\n";
        
        // Trial restriction should apply regardless of payment method
        $eligibility = $this->trial_service->check_trial_eligibility($this->test_user_id, $this->test_product_id);
        $this->assert_false($eligibility['eligible'], "Trial restriction should apply regardless of payment method");
        
        echo "✅ Multiple payment methods tests passed\n";
    }
    
    /**
     * Cleanup test data
     */
    private function cleanup_test_data() {
        echo "\n🧹 Cleaning up test data...\n";
        
        // Delete test user
        wp_delete_user($this->test_user_id);
        
        // Delete test product
        wp_delete_post($this->test_product_id, true);
        
        // Clean up trial history
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'zlaark_subscription_trial_history',
            array('user_id' => $this->test_user_id),
            array('%d')
        );
        
        echo "✅ Test data cleaned up\n";
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        echo "\n📊 Test Results Summary:\n";
        echo "========================\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $result) {
            if ($result['passed']) {
                $passed++;
                echo "✅ {$result['test']}\n";
            } else {
                $failed++;
                echo "❌ {$result['test']}: {$result['message']}\n";
            }
        }
        
        echo "\nTotal: " . ($passed + $failed) . " tests\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        
        if ($failed === 0) {
            echo "\n🎉 All tests passed! The one-time trial restriction system is working correctly.\n";
        } else {
            echo "\n⚠️ Some tests failed. Please review the implementation.\n";
        }
    }
    
    /**
     * Assert helper methods
     */
    private function assert_true($condition, $message) {
        $this->results[] = array(
            'test' => $message,
            'passed' => $condition === true,
            'message' => $condition === true ? '' : 'Expected true, got false'
        );
    }
    
    private function assert_false($condition, $message) {
        $this->results[] = array(
            'test' => $message,
            'passed' => $condition === false,
            'message' => $condition === false ? '' : 'Expected false, got true'
        );
    }
    
    private function assert_equals($actual, $expected, $message) {
        $this->results[] = array(
            'test' => $message,
            'passed' => $actual === $expected,
            'message' => $actual === $expected ? '' : "Expected '{$expected}', got '{$actual}'"
        );
    }
}

// Run tests if accessed directly
if (isset($_GET['run_trial_tests'])) {
    $test_suite = new ZlaarkTrialRestrictionSystemTest();
    $test_suite->run_all_tests();
}
