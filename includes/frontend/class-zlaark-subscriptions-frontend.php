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
        
        // Modify add to cart behavior for subscription products
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_subscription_add_to_cart'), 10, 3);
        
        // Handle subscription product checkout
        add_action('woocommerce_checkout_process', array($this, 'validate_subscription_checkout'));
        
        // Add subscription shortcodes
        add_shortcode('zlaark_subscriptions_manage', array($this, 'subscription_management_shortcode'));
        add_shortcode('zlaark_user_subscriptions', array($this, 'user_subscriptions_shortcode'));
        
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
}
