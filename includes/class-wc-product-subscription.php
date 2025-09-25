<?php
/**
 * WooCommerce Subscription Product Class
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription product class
 */
class WC_Product_Subscription extends WC_Product {
    
    /**
     * Product type
     *
     * @var string
     */
    protected $product_type = 'subscription';
    
    /**
     * Constructor
     *
     * @param mixed $product
     */
    public function __construct($product = 0) {
        parent::__construct($product);
    }
    
    /**
     * Get product type
     *
     * @return string
     */
    public function get_type() {
        return 'subscription';
    }
    
    /**
     * Check if product is virtual
     *
     * @return bool
     */
    public function is_virtual($context = 'view') {
        return true; // Subscriptions are always virtual
    }
    
    /**
     * Check if product is downloadable
     *
     * @return bool
     */
    public function is_downloadable($context = 'view') {
        return false; // Subscriptions are not downloadable
    }
    
    /**
     * Check if product manages stock
     *
     * @return bool
     */
    public function managing_stock($context = 'view') {
        return false; // Subscriptions don't manage stock
    }
    
    /**
     * Check if product is sold individually
     *
     * @return bool
     */
    public function is_sold_individually($context = 'view') {
        return true; // Only one subscription per product
    }
    
    /**
     * Get trial price
     *
     * @param string $context
     * @return float
     */
    public function get_trial_price($context = 'view') {
        $price = $this->get_meta('_subscription_trial_price', true, $context);

        // Handle different data types and ensure we get a float
        if (is_numeric($price)) {
            return (float) $price;
        }

        // Fallback: try direct meta query if product meta fails
        if (empty($price) && $this->get_id()) {
            $price = get_post_meta($this->get_id(), '_subscription_trial_price', true);
            if (is_numeric($price)) {
                return (float) $price;
            }
        }

        return 0.0;
    }
    
    /**
     * Set trial price
     *
     * @param float $price
     */
    public function set_trial_price($price) {
        $this->update_meta_data('_subscription_trial_price', (float) $price);
    }
    
    /**
     * Get trial duration
     *
     * @param string $context
     * @return int
     */
    public function get_trial_duration($context = 'view') {
        $duration = $this->get_meta('_subscription_trial_duration', true, $context);

        // Handle different data types and ensure we get an integer
        if (is_numeric($duration)) {
            return (int) $duration;
        }

        // Fallback: try direct meta query if product meta fails
        if (empty($duration) && $this->get_id()) {
            $duration = get_post_meta($this->get_id(), '_subscription_trial_duration', true);
            if (is_numeric($duration)) {
                return (int) $duration;
            }
        }

        return 0;
    }
    
    /**
     * Set trial duration
     *
     * @param int $duration
     */
    public function set_trial_duration($duration) {
        $this->update_meta_data('_subscription_trial_duration', (int) $duration);
    }
    
    /**
     * Get trial period
     *
     * @param string $context
     * @return string
     */
    public function get_trial_period($context = 'view') {
        return $this->get_meta('_subscription_trial_period', true, $context) ?: 'day';
    }
    
    /**
     * Set trial period
     *
     * @param string $period
     */
    public function set_trial_period($period) {
        $this->update_meta_data('_subscription_trial_period', $period);
    }
    
    /**
     * Get recurring price
     *
     * @param string $context
     * @return float
     */
    public function get_recurring_price($context = 'view') {
        return (float) $this->get_meta('_subscription_recurring_price', true, $context);
    }
    
    /**
     * Set recurring price
     *
     * @param float $price
     */
    public function set_recurring_price($price) {
        $this->update_meta_data('_subscription_recurring_price', (float) $price);
    }
    
    /**
     * Get billing interval
     *
     * @param string $context
     * @return string
     */
    public function get_billing_interval($context = 'view') {
        return $this->get_meta('_subscription_billing_interval', true, $context) ?: 'monthly';
    }
    
    /**
     * Set billing interval
     *
     * @param string $interval
     */
    public function set_billing_interval($interval) {
        $this->update_meta_data('_subscription_billing_interval', $interval);
    }
    
    /**
     * Get maximum subscription length
     *
     * @param string $context
     * @return int|null
     */
    public function get_max_length($context = 'view') {
        $length = $this->get_meta('_subscription_max_length', true, $context);
        return !empty($length) ? (int) $length : null;
    }
    
    /**
     * Set maximum subscription length
     *
     * @param int|null $length
     */
    public function set_max_length($length) {
        $this->update_meta_data('_subscription_max_length', $length ? (int) $length : '');
    }
    
    /**
     * Get signup fee
     *
     * @param string $context
     * @return float
     */
    public function get_signup_fee($context = 'view') {
        return (float) $this->get_meta('_subscription_signup_fee', true, $context);
    }
    
    /**
     * Set signup fee
     *
     * @param float $fee
     */
    public function set_signup_fee($fee) {
        $this->update_meta_data('_subscription_signup_fee', (float) $fee);
    }
    
    /**
     * Check if trials are enabled for this product
     *
     * @param string $context
     * @return bool
     */
    public function is_trial_enabled($context = 'view') {
        $enabled = $this->get_meta('_subscription_trial_enabled', true, $context);

        // Default to 'yes' for backward compatibility if not set
        if (empty($enabled)) {
            return true;
        }

        return $enabled === 'yes';
    }

    /**
     * Set trial enabled status
     *
     * @param bool $enabled
     */
    public function set_trial_enabled($enabled) {
        $this->update_meta_data('_subscription_trial_enabled', $enabled ? 'yes' : 'no');
    }

    /**
     * Check if product has trial (updated to include trial enabled check)
     *
     * @return bool
     */
    public function has_trial() {
        return $this->is_trial_enabled() && $this->get_trial_duration() > 0;
    }

    /**
     * Get the product price for WooCommerce
     *
     * @param string $context
     * @return string
     */
    public function get_price($context = 'view') {
        // For subscriptions, the initial price is what matters for purchasability
        if ($this->has_trial()) {
            return $this->get_trial_price();
        } else {
            return $this->get_recurring_price();
        }
    }

    /**
     * Check if the subscription product is purchasable
     *
     * @return bool
     */
    public function is_purchasable() {
        $purchasable = true;

        // Must have a recurring price
        if ($this->get_recurring_price() <= 0) {
            $purchasable = false;
        }

        // Must be published
        if ($this->get_status() !== 'publish') {
            $purchasable = false;
        }

        // Subscriptions are always "in stock" since they don't manage inventory
        // No need to check is_in_stock() for subscriptions

        return apply_filters('woocommerce_is_purchasable', $purchasable, $this);
    }

    /**
     * Check if subscription is in stock (always true for subscriptions)
     *
     * @return bool
     */
    public function is_in_stock() {
        return true; // Subscriptions don't manage stock
    }

    /**
     * Get stock status (always 'instock' for subscriptions)
     *
     * @return string
     */
    public function get_stock_status($context = 'view') {
        return 'instock';
    }

    /**
     * Check if product needs shipping (subscriptions are virtual)
     *
     * @return bool
     */
    public function needs_shipping() {
        return false;
    }


    
    /**
     * Get trial period in days
     *
     * @return int
     */
    public function get_trial_period_in_days() {
        $duration = $this->get_trial_duration();
        $period = $this->get_trial_period();
        
        switch ($period) {
            case 'week':
                return $duration * 7;
            case 'month':
                return $duration * 30;
            default:
                return $duration;
        }
    }
    
    /**
     * Get billing interval in days
     *
     * @return int
     */
    public function get_billing_interval_in_days() {
        $interval = $this->get_billing_interval();
        
        switch ($interval) {
            case 'weekly':
                return 7;
            case 'yearly':
                return 365;
            default:
                return 30; // monthly
        }
    }
    
    /**
     * Get initial payment amount (trial price + signup fee)
     *
     * @return float
     */
    public function get_initial_payment() {
        return $this->get_trial_price() + $this->get_signup_fee();
    }
    
    /**
     * Get subscription price for display
     *
     * @return string
     */
    public function get_price_html($price = '') {
        $price_html = '';
        
        if ($this->has_trial()) {
            $trial_price = $this->get_trial_price();
            $trial_duration = $this->get_trial_duration();
            $trial_period = $this->get_trial_period();
            
            if ($trial_price > 0) {
                $price_html .= wc_price($trial_price) . ' ';
                $price_html .= sprintf(
                    _n('for %d %s', 'for %d %ss', $trial_duration, 'zlaark-subscriptions'),
                    $trial_duration,
                    $trial_period
                );
                $price_html .= ', ';
            } else {
                $price_html .= sprintf(
                    _n('%d %s free trial', '%d %ss free trial', $trial_duration, 'zlaark-subscriptions'),
                    $trial_duration,
                    $trial_period
                );
                $price_html .= ', ';
            }
        }

        // Only show "then" if there's a trial
        if ($this->has_trial()) {
            $price_html .= __('then ', 'zlaark-subscriptions');
        }

        $price_html .= wc_price($this->get_recurring_price());
        $price_html .= ' ' . $this->get_billing_interval();
        
        $signup_fee = $this->get_signup_fee();
        if ($signup_fee > 0) {
            $price_html .= ' ' . sprintf(
                __('and a %s sign-up fee', 'zlaark-subscriptions'),
                wc_price($signup_fee)
            );
        }
        
        return apply_filters('woocommerce_subscription_price_html', $price_html, $this);
    }
    
    /**
     * Get add to cart button text
     *
     * @return string
     */
    public function add_to_cart_text() {
        if ($this->is_purchasable() && $this->is_in_stock()) {
            return __('Subscribe Now', 'zlaark-subscriptions');
        } else {
            return __('Read more', 'zlaark-subscriptions');
        }
    }
    
    /**
     * Get add to cart URL
     *
     * @return string
     */
    public function add_to_cart_url() {
        return $this->get_permalink();
    }
    
    /**
     * Check if product supports a feature
     *
     * @param string $feature
     * @return bool
     */
    public function supports($feature) {
        $features = array(
            'ajax_add_to_cart' => false, // Subscriptions need custom checkout
        );
        
        return isset($features[$feature]) ? $features[$feature] : parent::supports($feature);
    }
    
    /**
     * Validate subscription data
     *
     * @return bool|WP_Error
     */
    public function validate() {
        $errors = new WP_Error();
        
        // Check recurring price
        if ($this->get_recurring_price() <= 0) {
            $errors->add('invalid_recurring_price', __('Recurring price must be greater than 0.', 'zlaark-subscriptions'));
        }
        
        // Check trial data consistency
        if ($this->get_trial_duration() > 0 && empty($this->get_trial_period())) {
            $errors->add('invalid_trial_period', __('Trial period is required when trial duration is set.', 'zlaark-subscriptions'));
        }
        
        // Check trial price vs recurring price
        if ($this->get_trial_price() > $this->get_recurring_price()) {
            $errors->add('invalid_trial_price', __('Trial price should not be higher than recurring price.', 'zlaark-subscriptions'));
        }
        
        if ($errors->has_errors()) {
            return $errors;
        }
        
        return true;
    }
}
