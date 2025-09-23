<?php
/**
 * Frontend functionality
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend class
 */
class ZlaarkSubscriptionsFrontend {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsFrontend
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsFrontend
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add subscription info to single product page (higher priority than product type class)
        add_action('woocommerce_single_product_summary', array($this, 'display_subscription_info'), 26);

        // Add prominent trial information display after title
        add_action('woocommerce_single_product_summary', array($this, 'display_trial_highlight'), 7);

        // Add comprehensive trial information after price
        add_action('woocommerce_single_product_summary', array($this, 'display_comprehensive_trial_info'), 12);

        // Ensure add to cart button appears for subscription products
        add_action('woocommerce_single_product_summary', array($this, 'force_subscription_add_to_cart'), 31);

        // Debug add to cart issues (only if WP_DEBUG is enabled)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('woocommerce_single_product_summary', array($this, 'debug_add_to_cart_status'), 32);
        }
        
        // Modify add to cart behavior for subscription products
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_subscription_add_to_cart'), 10, 3);
        
        // Handle subscription product checkout
        add_action('woocommerce_checkout_process', array($this, 'validate_subscription_checkout'));
        
        // Add subscription shortcodes
        add_shortcode('zlaark_subscriptions_manage', array($this, 'subscription_management_shortcode'));
        add_shortcode('zlaark_user_subscriptions', array($this, 'user_subscriptions_shortcode'));
        add_shortcode('subscription_required', array($this, 'subscription_required_shortcode'));
        
        // Handle subscription actions
        add_action('wp_ajax_zlaark_cancel_subscription', array($this, 'handle_cancel_subscription'));
        add_action('wp_ajax_zlaark_pause_subscription', array($this, 'handle_pause_subscription'));
        add_action('wp_ajax_zlaark_resume_subscription', array($this, 'handle_resume_subscription'));
        
        // Restrict content based on subscription status
        add_filter('the_content', array($this, 'restrict_content_by_subscription'));
        
        // Add subscription status to user profile
        add_action('show_user_profile', array($this, 'show_user_subscription_status'));
        add_action('edit_user_profile', array($this, 'show_user_subscription_status'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on relevant pages
        if (is_product() || is_cart() || is_checkout() || is_account_page()) {
            wp_enqueue_style(
                'zlaark-subscriptions-frontend',
                ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                ZLAARK_SUBSCRIPTIONS_VERSION
            );
            
            wp_enqueue_script(
                'zlaark-subscriptions-frontend',
                ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                ZLAARK_SUBSCRIPTIONS_VERSION,
                true
            );
            
            wp_localize_script('zlaark-subscriptions-frontend', 'zlaark_subscriptions_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('zlaark_subscriptions_frontend_nonce'),
                'strings' => array(
                    'confirm_cancel' => __('Are you sure you want to cancel this subscription?', 'zlaark-subscriptions'),
                    'confirm_pause' => __('Are you sure you want to pause this subscription?', 'zlaark-subscriptions'),
                    'processing' => __('Processing...', 'zlaark-subscriptions'),
                )
            ));
        }
    }

    /**
     * Display prominent trial highlight on single product page
     */
    public function display_trial_highlight() {
        global $product;

        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }

        // Only show if product has a trial
        if (!method_exists($product, 'has_trial') || !$product->has_trial()) {
            return;
        }

        // Check if trial service is available
        if (!class_exists('ZlaarkSubscriptionsTrialService')) {
            return;
        }

        $trial_price = $product->get_trial_price();
        $trial_duration = $product->get_trial_duration();
        $trial_period = $product->get_trial_period();
        $recurring_price = method_exists($product, 'get_recurring_price') ? $product->get_recurring_price() : 0;
        $billing_interval = method_exists($product, 'get_billing_interval') ? $product->get_billing_interval() : '';

        ?>
        <div class="subscription-trial-banner">
            <div class="trial-banner-content">
                <?php if ($trial_price > 0): ?>
                    <div class="trial-offer-badge">
                        <span class="trial-label"><?php _e('Special Trial Offer', 'zlaark-subscriptions'); ?></span>
                        <span class="trial-price-large"><?php echo wc_price($trial_price); ?></span>
                        <span class="trial-duration-large"><?php printf(__('for %d %s', 'zlaark-subscriptions'), $trial_duration, $trial_period); ?></span>
                    </div>
                <?php else: ?>
                    <div class="trial-free-badge-large">
                        <span class="free-trial-label"><?php _e('FREE TRIAL', 'zlaark-subscriptions'); ?></span>
                        <span class="free-trial-duration"><?php printf(__('%d %s - No Payment Required!', 'zlaark-subscriptions'), $trial_duration, $trial_period); ?></span>
                    </div>
                <?php endif; ?>

                <div class="trial-then-info">
                    <?php printf(__('Then %s %s', 'zlaark-subscriptions'), wc_price($recurring_price), $billing_interval); ?>
                </div>
            </div>
        </div>

        <style>
        .subscription-trial-banner {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        .subscription-trial-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .trial-banner-content {
            position: relative;
            z-index: 1;
        }

        .trial-offer-badge .trial-label,
        .free-trial-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .trial-price-large {
            display: block;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .trial-duration-large,
        .free-trial-duration {
            display: block;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .free-trial-label {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .trial-then-info {
            font-size: 16px;
            opacity: 0.9;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 10px;
            margin-top: 10px;
        }
        </style>
        <?php
    }

    /**
     * Display comprehensive trial information after product price
     */
    public function display_comprehensive_trial_info() {
        global $product;

        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }

        // Get trial service and subscription options
        if (!class_exists('ZlaarkSubscriptionsTrialService')) {
            return; // Exit if trial service is not available
        }

        $trial_service = new ZlaarkSubscriptionsTrialService();
        $user_id = get_current_user_id();
        $subscription_options = $trial_service->get_subscription_options($product->get_id(), $user_id);

        ?>
        <div class="subscription-trial-info-section">
            <?php if (method_exists($product, 'has_trial') && $product->has_trial()): ?>
                <div class="trial-availability-status">
                    <?php if ($subscription_options['trial']['available']): ?>
                        <div class="trial-available-badge">
                            <span class="badge-icon">✅</span>
                            <span class="badge-text"><?php _e('Trial Available', 'zlaark-subscriptions'); ?></span>
                        </div>
                        <div class="trial-details">
                            <p class="trial-offer-text"><?php echo esc_html($subscription_options['trial']['description']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="trial-unavailable-badge">
                            <span class="badge-icon">❌</span>
                            <span class="badge-text"><?php _e('Trial Not Available', 'zlaark-subscriptions'); ?></span>
                        </div>
                        <div class="trial-reason">
                            <p class="trial-reason-text"><?php echo esc_html($subscription_options['trial']['description']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="subscription-pricing-summary">
                    <div class="pricing-comparison">
                        <?php if ($subscription_options['trial']['available']): ?>
                            <div class="pricing-option trial-pricing">
                                <div class="pricing-header">
                                    <h4><?php _e('Trial Option', 'zlaark-subscriptions'); ?></h4>
                                    <?php if ($subscription_options['trial']['price'] > 0): ?>
                                        <span class="price"><?php echo wc_price($subscription_options['trial']['price']); ?></span>
                                    <?php else: ?>
                                        <span class="price free"><?php _e('FREE', 'zlaark-subscriptions'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="pricing-details">
                                    <p><?php echo esc_html($subscription_options['trial']['description']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="pricing-option regular-pricing">
                            <div class="pricing-header">
                                <h4><?php _e('Full Subscription', 'zlaark-subscriptions'); ?></h4>
                                <span class="price"><?php echo wc_price($subscription_options['regular']['price']); ?></span>
                            </div>
                            <div class="pricing-details">
                                <p><?php echo esc_html($subscription_options['regular']['description']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- No trial available for this product -->
                <div class="no-trial-info">
                    <div class="no-trial-badge">
                        <span class="badge-icon">ℹ️</span>
                        <span class="badge-text"><?php _e('No Trial Period', 'zlaark-subscriptions'); ?></span>
                    </div>
                    <div class="subscription-only-details">
                        <p><?php printf(__('This subscription starts immediately at %s %s.', 'zlaark-subscriptions'), wc_price($product->get_recurring_price()), $product->get_billing_interval()); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .subscription-trial-info-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .trial-availability-status {
            margin-bottom: 20px;
        }

        .trial-available-badge,
        .trial-unavailable-badge,
        .no-trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .trial-available-badge {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .trial-unavailable-badge {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-trial-badge {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .trial-details,
        .trial-reason,
        .subscription-only-details {
            margin-top: 10px;
        }

        .trial-offer-text,
        .trial-reason-text {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
        }

        .subscription-pricing-summary {
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }

        .pricing-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .pricing-comparison {
                grid-template-columns: 1fr;
            }
        }

        .pricing-option {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
        }

        .trial-pricing {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .regular-pricing {
            border-color: #007cba;
            background: linear-gradient(135deg, #cce7f0 0%, #b3d9e8 100%);
        }

        .pricing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .pricing-header h4 {
            margin: 0;
            font-size: 16px;
            color: #495057;
        }

        .pricing-header .price {
            font-weight: bold;
            font-size: 18px;
            color: #007cba;
        }

        .pricing-header .price.free {
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
        }

        .pricing-details p {
            margin: 0;
            font-size: 13px;
            color: #6c757d;
            line-height: 1.4;
        }
        </style>
        <?php
    }

    /**
     * Display subscription info on product page
     */
    public function display_subscription_info() {
        global $product;
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        $trial_price = $product->get_trial_price();
        $trial_duration = $product->get_trial_duration();
        $trial_period = $product->get_trial_period();
        $recurring_price = $product->get_recurring_price();
        $billing_interval = $product->get_billing_interval();
        $signup_fee = $product->get_signup_fee();
        $max_length = $product->get_max_length();
        
        ?>
        <div class="subscription-details">
            <h3><?php _e('Subscription Details', 'zlaark-subscriptions'); ?></h3>
            
            <?php if ($product->has_trial()): ?>
                <div class="subscription-trial">
                    <strong><?php _e('Trial Period:', 'zlaark-subscriptions'); ?></strong>
                    <?php if ($trial_price > 0): ?>
                        <?php printf(
                            __('₹%s for %d %s', 'zlaark-subscriptions'),
                            number_format($trial_price, 2),
                            $trial_duration,
                            $trial_period
                        ); ?>
                    <?php else: ?>
                        <?php printf(
                            __('Free for %d %s', 'zlaark-subscriptions'),
                            $trial_duration,
                            $trial_period
                        ); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="subscription-recurring">
                <strong><?php _e('Recurring Payment:', 'zlaark-subscriptions'); ?></strong>
                <?php printf(
                    __('₹%s %s', 'zlaark-subscriptions'),
                    number_format($recurring_price, 2),
                    $billing_interval
                ); ?>
            </div>
            
            <?php if ($signup_fee > 0): ?>
                <div class="subscription-signup-fee">
                    <strong><?php _e('Sign-up Fee:', 'zlaark-subscriptions'); ?></strong>
                    <?php printf(__('₹%s (one-time)', 'zlaark-subscriptions'), number_format($signup_fee, 2)); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($max_length): ?>
                <div class="subscription-length">
                    <strong><?php _e('Subscription Length:', 'zlaark-subscriptions'); ?></strong>
                    <?php printf(
                        _n('%d billing cycle', '%d billing cycles', $max_length, 'zlaark-subscriptions'),
                        $max_length
                    ); ?>
                </div>
            <?php else: ?>
                <div class="subscription-length">
                    <strong><?php _e('Subscription Length:', 'zlaark-subscriptions'); ?></strong>
                    <?php _e('Unlimited', 'zlaark-subscriptions'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (is_user_logged_in()): ?>
                <?php $this->show_existing_subscription_notice($product->get_id()); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Show existing subscription notice
     *
     * @param int $product_id
     */
    private function show_existing_subscription_notice($product_id) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user_id);
        
        foreach ($subscriptions as $subscription) {
            if ($subscription->product_id == $product_id && in_array($subscription->status, array('active', 'trial'))) {
                ?>
                <div class="woocommerce-info">
                    <?php _e('You already have an active subscription for this product.', 'zlaark-subscriptions'); ?>
                    <a href="<?php echo wc_get_account_endpoint_url('subscriptions'); ?>">
                        <?php _e('Manage your subscriptions', 'zlaark-subscriptions'); ?>
                    </a>
                </div>
                <?php
                break;
            }
        }
    }
    
    /**
     * Validate subscription add to cart
     *
     * @param bool $passed
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function validate_subscription_add_to_cart($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'subscription') {
            return $passed;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wc_add_notice(__('You must be logged in to purchase a subscription.', 'zlaark-subscriptions'), 'error');
            return false;
        }
        
        // Check if user already has active subscription for this product
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $existing_subscriptions = $db->get_user_subscriptions($user_id);
        
        foreach ($existing_subscriptions as $subscription) {
            if ($subscription->product_id == $product_id && in_array($subscription->status, array('active', 'trial'))) {
                wc_add_notice(__('You already have an active subscription for this product.', 'zlaark-subscriptions'), 'error');
                return false;
            }
        }
        
        // Check if cart already contains subscription products
        foreach (WC()->cart->get_cart() as $cart_item) {
            $cart_product = $cart_item['data'];
            if ($cart_product && $cart_product->get_type() === 'subscription') {
                wc_add_notice(__('You can only purchase one subscription at a time. Please complete your current subscription purchase first.', 'zlaark-subscriptions'), 'error');
                return false;
            }
        }
        
        return $passed;
    }
    
    /**
     * Validate subscription checkout
     */
    public function validate_subscription_checkout() {
        $has_subscription = false;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['is_subscription']) && $cart_item['is_subscription']) {
                $has_subscription = true;
                break;
            }
        }
        
        if ($has_subscription) {
            // Ensure user is logged in
            if (!is_user_logged_in()) {
                wc_add_notice(__('You must be logged in to purchase a subscription.', 'zlaark-subscriptions'), 'error');
            }
            
            // Validate payment method supports subscriptions
            $chosen_payment_method = WC()->session->get('chosen_payment_method');
            if ($chosen_payment_method !== 'zlaark_razorpay') {
                wc_add_notice(__('Subscription products require Razorpay payment method.', 'zlaark-subscriptions'), 'error');
            }
        }
    }
    
    /**
     * Subscription management shortcode
     *
     * @param array $atts
     * @return string
     */
    public function subscription_management_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to manage your subscriptions.', 'zlaark-subscriptions') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user_id);
        
        if (empty($subscriptions)) {
            return '<p>' . __('You have no subscriptions.', 'zlaark-subscriptions') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="zlaark-subscriptions-management">
            <h3><?php _e('Your Subscriptions', 'zlaark-subscriptions'); ?></h3>
            
            <table class="shop_table shop_table_responsive my_account_orders">
                <thead>
                    <tr>
                        <th><?php _e('Subscription', 'zlaark-subscriptions'); ?></th>
                        <th><?php _e('Status', 'zlaark-subscriptions'); ?></th>
                        <th><?php _e('Next Payment', 'zlaark-subscriptions'); ?></th>
                        <th><?php _e('Total', 'zlaark-subscriptions'); ?></th>
                        <th><?php _e('Actions', 'zlaark-subscriptions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <?php $product = wc_get_product($subscription->product_id); ?>
                        <tr>
                            <td data-title="<?php _e('Subscription', 'zlaark-subscriptions'); ?>">
                                <a href="<?php echo $product ? $product->get_permalink() : '#'; ?>">
                                    <?php echo $product ? $product->get_name() : __('Product not found', 'zlaark-subscriptions'); ?>
                                </a>
                                <br>
                                <small><?php printf(__('ID: #%d', 'zlaark-subscriptions'), $subscription->id); ?></small>
                            </td>
                            <td data-title="<?php _e('Status', 'zlaark-subscriptions'); ?>">
                                <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                                    <?php echo $this->get_status_label($subscription->status); ?>
                                </span>
                            </td>
                            <td data-title="<?php _e('Next Payment', 'zlaark-subscriptions'); ?>">
                                <?php if ($subscription->next_payment_date): ?>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td data-title="<?php _e('Total', 'zlaark-subscriptions'); ?>">
                                ₹<?php echo number_format($subscription->recurring_price, 2); ?>
                            </td>
                            <td data-title="<?php _e('Actions', 'zlaark-subscriptions'); ?>">
                                <?php $this->render_subscription_actions($subscription); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * User subscriptions shortcode
     *
     * @param array $atts
     * @return string
     */
    public function user_subscriptions_shortcode($atts) {
        $atts = shortcode_atts(array(
            'status' => '',
            'limit' => -1
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your subscriptions.', 'zlaark-subscriptions') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user_id, $atts['status']);
        
        if ($atts['limit'] > 0) {
            $subscriptions = array_slice($subscriptions, 0, $atts['limit']);
        }
        
        if (empty($subscriptions)) {
            return '<p>' . __('No subscriptions found.', 'zlaark-subscriptions') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="zlaark-user-subscriptions">
            <?php foreach ($subscriptions as $subscription): ?>
                <?php $product = wc_get_product($subscription->product_id); ?>
                <div class="subscription-item">
                    <h4><?php echo $product ? $product->get_name() : __('Product not found', 'zlaark-subscriptions'); ?></h4>
                    <p>
                        <strong><?php _e('Status:', 'zlaark-subscriptions'); ?></strong>
                        <?php echo $this->get_status_label($subscription->status); ?>
                    </p>
                    <?php if ($subscription->next_payment_date): ?>
                        <p>
                            <strong><?php _e('Next Payment:', 'zlaark-subscriptions'); ?></strong>
                            <?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?>
                        </p>
                    <?php endif; ?>
                    <p>
                        <strong><?php _e('Amount:', 'zlaark-subscriptions'); ?></strong>
                        ₹<?php echo number_format($subscription->recurring_price, 2); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Subscription required shortcode
     *
     * @param array $atts
     * @param string $content
     * @return string
     */
    public function subscription_required_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'message' => __('You need an active subscription to access this content.', 'zlaark-subscriptions')
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="subscription-restriction">' .
                   __('Please log in to access this content.', 'zlaark-subscriptions') .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user_id, 'active');

        // Check if user has required subscription
        $has_access = false;

        if (!empty($atts['product_id'])) {
            $required_product_id = intval($atts['product_id']);
            foreach ($subscriptions as $subscription) {
                if ($subscription->product_id == $required_product_id) {
                    $has_access = true;
                    break;
                }
            }
        } else {
            // Any active subscription grants access
            $has_access = !empty($subscriptions);
        }

        if ($has_access) {
            return do_shortcode($content);
        } else {
            return '<div class="subscription-restriction">' . esc_html($atts['message']) . '</div>';
        }
    }

    /**
     * Get status label
     *
     * @param string $status
     * @return string
     */
    private function get_status_label($status) {
        $labels = array(
            'active'    => __('Active', 'zlaark-subscriptions'),
            'trial'     => __('Trial', 'zlaark-subscriptions'),
            'paused'    => __('Paused', 'zlaark-subscriptions'),
            'cancelled' => __('Cancelled', 'zlaark-subscriptions'),
            'expired'   => __('Expired', 'zlaark-subscriptions'),
            'failed'    => __('Failed', 'zlaark-subscriptions'),
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * Render subscription actions
     *
     * @param object $subscription
     */
    private function render_subscription_actions($subscription) {
        $actions = array();
        
        if (in_array($subscription->status, array('active', 'trial'))) {
            $actions['pause'] = array(
                'url' => '#',
                'name' => __('Pause', 'zlaark-subscriptions'),
                'class' => 'subscription-action',
                'data' => array(
                    'action' => 'pause',
                    'subscription-id' => $subscription->id
                )
            );
            
            $actions['cancel'] = array(
                'url' => '#',
                'name' => __('Cancel', 'zlaark-subscriptions'),
                'class' => 'subscription-action',
                'data' => array(
                    'action' => 'cancel',
                    'subscription-id' => $subscription->id
                )
            );
        }
        
        if ($subscription->status === 'paused') {
            $actions['resume'] = array(
                'url' => '#',
                'name' => __('Resume', 'zlaark-subscriptions'),
                'class' => 'subscription-action',
                'data' => array(
                    'action' => 'resume',
                    'subscription-id' => $subscription->id
                )
            );
        }
        
        foreach ($actions as $key => $action) {
            $data_attrs = '';
            if (!empty($action['data'])) {
                foreach ($action['data'] as $data_key => $data_value) {
                    $data_attrs .= ' data-' . esc_attr($data_key) . '="' . esc_attr($data_value) . '"';
                }
            }
            
            echo '<a href="' . esc_url($action['url']) . '" class="' . esc_attr($action['class']) . '"' . $data_attrs . '>' . esc_html($action['name']) . '</a>';
            
            if ($key !== array_key_last($actions)) {
                echo ' | ';
            }
        }
    }
    
    /**
     * Handle cancel subscription
     */
    public function handle_cancel_subscription() {
        if (!wp_verify_nonce($_POST['nonce'], 'zlaark_subscriptions_frontend_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'zlaark-subscriptions'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in', 'zlaark-subscriptions'));
        }
        
        $subscription_id = intval($_POST['subscription_id']);
        $user_id = get_current_user_id();
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription($subscription_id);
        
        if (!$subscription || $subscription->user_id != $user_id) {
            wp_send_json_error(__('Subscription not found', 'zlaark-subscriptions'));
        }
        
        if (!in_array($subscription->status, array('active', 'trial'))) {
            wp_send_json_error(__('Subscription cannot be cancelled', 'zlaark-subscriptions'));
        }
        
        $manager = ZlaarkSubscriptionsManager::instance();
        $result = $manager->update_subscription_status($subscription_id, 'cancelled', 'Cancelled by customer');
        
        if ($result) {
            wp_send_json_success(__('Subscription cancelled successfully', 'zlaark-subscriptions'));
        } else {
            wp_send_json_error(__('Failed to cancel subscription', 'zlaark-subscriptions'));
        }
    }
    
    /**
     * Handle pause subscription
     */
    public function handle_pause_subscription() {
        if (!wp_verify_nonce($_POST['nonce'], 'zlaark_subscriptions_frontend_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'zlaark-subscriptions'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in', 'zlaark-subscriptions'));
        }
        
        $subscription_id = intval($_POST['subscription_id']);
        $user_id = get_current_user_id();
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription($subscription_id);
        
        if (!$subscription || $subscription->user_id != $user_id) {
            wp_send_json_error(__('Subscription not found', 'zlaark-subscriptions'));
        }
        
        if (!in_array($subscription->status, array('active', 'trial'))) {
            wp_send_json_error(__('Subscription cannot be paused', 'zlaark-subscriptions'));
        }
        
        $manager = ZlaarkSubscriptionsManager::instance();
        $result = $manager->update_subscription_status($subscription_id, 'paused', 'Paused by customer');
        
        if ($result) {
            wp_send_json_success(__('Subscription paused successfully', 'zlaark-subscriptions'));
        } else {
            wp_send_json_error(__('Failed to pause subscription', 'zlaark-subscriptions'));
        }
    }
    
    /**
     * Handle resume subscription
     */
    public function handle_resume_subscription() {
        if (!wp_verify_nonce($_POST['nonce'], 'zlaark_subscriptions_frontend_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'zlaark-subscriptions'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in', 'zlaark-subscriptions'));
        }
        
        $subscription_id = intval($_POST['subscription_id']);
        $user_id = get_current_user_id();
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription($subscription_id);
        
        if (!$subscription || $subscription->user_id != $user_id) {
            wp_send_json_error(__('Subscription not found', 'zlaark-subscriptions'));
        }
        
        if ($subscription->status !== 'paused') {
            wp_send_json_error(__('Subscription is not paused', 'zlaark-subscriptions'));
        }
        
        $manager = ZlaarkSubscriptionsManager::instance();
        $result = $manager->update_subscription_status($subscription_id, 'active', 'Resumed by customer');
        
        if ($result) {
            wp_send_json_success(__('Subscription resumed successfully', 'zlaark-subscriptions'));
        } else {
            wp_send_json_error(__('Failed to resume subscription', 'zlaark-subscriptions'));
        }
    }
    
    /**
     * Restrict content based on subscription status
     *
     * @param string $content
     * @return string
     */
    public function restrict_content_by_subscription($content) {
        // Check for subscription restriction shortcode
        if (strpos($content, '[subscription_required') !== false) {
            $content = preg_replace_callback(
                '/\[subscription_required([^\]]*)\](.*?)\[\/subscription_required\]/s',
                array($this, 'process_subscription_restriction'),
                $content
            );
        }
        
        return $content;
    }
    
    /**
     * Process subscription restriction shortcode
     *
     * @param array $matches
     * @return string
     */
    private function process_subscription_restriction($matches) {
        $atts = shortcode_parse_atts($matches[1]);
        $restricted_content = $matches[2];
        
        if (!is_user_logged_in()) {
            return '<div class="subscription-restriction">' . 
                   __('Please log in to access this content.', 'zlaark-subscriptions') . 
                   '</div>';
        }
        
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user_id, 'active');
        
        // Check if user has required subscription
        $has_access = false;
        
        if (isset($atts['product_id'])) {
            $required_product_id = intval($atts['product_id']);
            foreach ($subscriptions as $subscription) {
                if ($subscription->product_id == $required_product_id) {
                    $has_access = true;
                    break;
                }
            }
        } else {
            // Any active subscription grants access
            $has_access = !empty($subscriptions);
        }
        
        if ($has_access) {
            return $restricted_content;
        } else {
            $message = isset($atts['message']) ? $atts['message'] : __('You need an active subscription to access this content.', 'zlaark-subscriptions');
            return '<div class="subscription-restriction">' . esc_html($message) . '</div>';
        }
    }
    
    /**
     * Show user subscription status in profile
     *
     * @param WP_User $user
     */
    public function show_user_subscription_status($user) {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user->ID);
        
        ?>
        <h3><?php _e('Subscription Status', 'zlaark-subscriptions'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Active Subscriptions', 'zlaark-subscriptions'); ?></label></th>
                <td>
                    <?php if (empty($subscriptions)): ?>
                        <p><?php _e('No subscriptions found.', 'zlaark-subscriptions'); ?></p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <?php $product = wc_get_product($subscription->product_id); ?>
                                <li>
                                    <strong><?php echo $product ? $product->get_name() : __('Product not found', 'zlaark-subscriptions'); ?></strong>
                                    - <?php echo $this->get_status_label($subscription->status); ?>
                                    (<?php printf(__('ID: #%d', 'zlaark-subscriptions'), $subscription->id); ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Force add to cart button for subscription products if not already displayed
     */
    public function force_subscription_add_to_cart() {
        global $product;

        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }

        // Only add if the product is purchasable and no add to cart button was rendered
        if ($product->is_purchasable() && $product->is_in_stock()) {
            // Check if WooCommerce's add to cart was already called
            if (!did_action('woocommerce_template_single_add_to_cart')) {
                ?>
                <div class="subscription-add-to-cart-forced">
                    <form class="cart" action="<?php echo esc_url($product->get_permalink()); ?>" method="post" enctype='multipart/form-data'>
                        <?php wp_nonce_field('woocommerce-add-to-cart', 'woocommerce-add-to-cart-nonce'); ?>

                        <div class="subscription-pricing-summary">
                            <?php if (method_exists($product, 'has_trial') && $product->has_trial()): ?>
                                <div class="trial-info">
                                    <?php
                                    $trial_price = $product->get_trial_price();
                                    $trial_duration = $product->get_trial_duration();
                                    $trial_period = $product->get_trial_period();

                                    if ($trial_price > 0) {
                                        printf(
                                            __('Start with %s for %d %s', 'zlaark-subscriptions'),
                                            wc_price($trial_price),
                                            $trial_duration,
                                            $trial_period
                                        );
                                    } else {
                                        printf(
                                            __('FREE trial for %d %s', 'zlaark-subscriptions'),
                                            $trial_duration,
                                            $trial_period
                                        );
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <div class="recurring-info">
                                <?php
                                printf(
                                    __('Then %s %s', 'zlaark-subscriptions'),
                                    wc_price($product->get_recurring_price()),
                                    $product->get_billing_interval()
                                );
                                ?>
                            </div>
                        </div>

                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt subscription-add-to-cart-button">
                            <?php
                            if (method_exists($product, 'has_trial') && $product->has_trial()) {
                                $trial_price = $product->get_trial_price();
                                if ($trial_price > 0) {
                                    printf(__('Start Trial - %s', 'zlaark-subscriptions'), wc_price($trial_price));
                                } else {
                                    echo esc_html__('Start FREE Trial', 'zlaark-subscriptions');
                                }
                            } else {
                                $recurring_price = method_exists($product, 'get_recurring_price') ? $product->get_recurring_price() : 0;
                                $billing_interval = method_exists($product, 'get_billing_interval') ? $product->get_billing_interval() : '';
                                if ($recurring_price > 0 && $billing_interval) {
                                    printf(__('Subscribe - %s %s', 'zlaark-subscriptions'), wc_price($recurring_price), $billing_interval);
                                } else {
                                    echo esc_html__('Start Subscription', 'zlaark-subscriptions');
                                }
                            }
                            ?>
                        </button>
                    </form>
                </div>

                <style>
                .subscription-add-to-cart-forced {
                    margin: 20px 0;
                    padding: 20px;
                    background: #f9f9f9;
                    border: 2px solid #007cba;
                    border-radius: 8px;
                }

                .subscription-pricing-summary {
                    margin-bottom: 15px;
                    text-align: center;
                }

                .trial-info, .recurring-info {
                    padding: 8px;
                    margin: 5px 0;
                    border-radius: 4px;
                }

                .trial-info {
                    background: #d4edda;
                    color: #155724;
                    font-weight: bold;
                }

                .recurring-info {
                    background: #cce7f0;
                    color: #004085;
                }
                </style>
                <?php
            }
        }
    }

    /**
     * Debug add to cart status for subscription products
     */
    public function debug_add_to_cart_status() {
        global $product;

        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }

        // Only show debug info if WP_DEBUG is enabled and user can manage options
        if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('manage_options')) {
            return;
        }

        $debug_info = array(
            'product_id' => $product->get_id(),
            'product_type' => $product->get_type(),
            'product_class' => get_class($product),
            'is_purchasable' => $product->is_purchasable(),
            'is_in_stock' => $product->is_in_stock(),
            'get_price' => $product->get_price(),
            'stock_status' => method_exists($product, 'get_stock_status') ? $product->get_stock_status() : 'unknown',
            'needs_shipping' => method_exists($product, 'needs_shipping') ? $product->needs_shipping() : 'unknown',
            'template_loaded' => did_action('woocommerce_template_single_add_to_cart'),
            'hooks_fired' => array(
                'woocommerce_single_product_summary' => did_action('woocommerce_single_product_summary'),
                'woocommerce_before_add_to_cart_form' => did_action('woocommerce_before_add_to_cart_form'),
                'woocommerce_after_add_to_cart_form' => did_action('woocommerce_after_add_to_cart_form')
            )
        );

        if (method_exists($product, 'has_trial')) {
            $debug_info['has_trial'] = $product->has_trial();
            if ($product->has_trial()) {
                $debug_info['trial_price'] = $product->get_trial_price();
                $debug_info['trial_duration'] = $product->get_trial_duration();
                $debug_info['trial_period'] = $product->get_trial_period();
            }
        }

        if (method_exists($product, 'get_recurring_price')) {
            $debug_info['recurring_price'] = $product->get_recurring_price();
            $debug_info['billing_interval'] = $product->get_billing_interval();
        }

        ?>
        <div style="background: #000; color: #fff; padding: 15px; margin: 20px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
            <h4 style="color: #fff; margin-top: 0;">🔧 Subscription Add-to-Cart Debug</h4>
            <table style="color: #fff; width: 100%;">
                <?php foreach ($debug_info as $key => $value): ?>
                    <tr>
                        <td style="padding: 2px 10px 2px 0; font-weight: bold;"><?php echo esc_html($key); ?>:</td>
                        <td style="padding: 2px 0;">
                            <?php
                            if (is_array($value)) {
                                echo '<pre>' . esc_html(print_r($value, true)) . '</pre>';
                            } elseif (is_bool($value)) {
                                echo $value ? '✅ true' : '❌ false';
                            } else {
                                echo esc_html($value);
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div style="margin-top: 10px; padding: 10px; background: #333; border-radius: 4px;">
                <strong>Template Check:</strong><br>
                <?php
                $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
                echo 'Template exists: ' . (file_exists($template_path) ? '✅ Yes' : '❌ No') . '<br>';
                echo 'Template path: ' . esc_html($template_path) . '<br>';

                // Check if WooCommerce tried to load any add-to-cart template
                global $wp_filter;
                $template_hooks = array('wc_get_template', 'woocommerce_locate_template');
                foreach ($template_hooks as $hook) {
                    $count = count($wp_filter[$hook]->callbacks ?? []);
                    echo "Hook $hook: $count callbacks<br>";
                }
                ?>
            </div>

            <button onclick="this.parentElement.style.display='none'" style="float: right; background: #fff; color: #000; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Hide Debug</button>
        </div>
        <?php
    }
}
