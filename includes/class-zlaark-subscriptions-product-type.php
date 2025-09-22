<?php
/**
 * Custom subscription product type
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription product type class
 */
class ZlaarkSubscriptionsProductType {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsProductType
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsProductType
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
        // Force early product type registration
        $this->force_product_type_registration();

        // Add subscription product type with multiple hook priorities and early registration
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 5);
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 10);
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 20);
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 999);

        // Force registration on multiple hooks
        add_action('init', array($this, 'force_product_type_registration'), 5);
        add_action('admin_init', array($this, 'force_product_type_registration'));
        add_action('wp_loaded', array($this, 'force_product_type_registration'));

        // Modify product class for subscription products (early priority)
        add_filter('woocommerce_product_class', array($this, 'get_subscription_product_class'), 5, 2);

        // Add subscription product data tabs
        add_filter('woocommerce_product_data_tabs', array($this, 'add_subscription_product_data_tab'));

        // Add subscription product data panels
        add_action('woocommerce_product_data_panels', array($this, 'add_subscription_product_data_panel'));

        // Save subscription product data
        add_action('woocommerce_process_product_meta', array($this, 'save_subscription_product_data'));

        // Hide/show fields based on product type
        add_action('admin_footer', array($this, 'subscription_product_type_js'));

        // Validate subscription product data
        add_action('woocommerce_admin_process_product_object', array($this, 'validate_subscription_product_data'));

        // Add subscription info to product display
        add_action('woocommerce_single_product_summary', array($this, 'display_subscription_info'), 25);

        // Modify add to cart button for subscription products
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'subscription_add_to_cart_text'), 10, 2);
        add_filter('woocommerce_product_add_to_cart_url', array($this, 'subscription_add_to_cart_url'), 10, 2);

        // Ensure subscription products show add to cart button
        add_filter('woocommerce_is_purchasable', array($this, 'subscription_is_purchasable'), 10, 2);
        add_filter('woocommerce_product_supports', array($this, 'subscription_product_supports'), 10, 3);

        // Handle template loading for subscription products
        add_filter('wc_get_template', array($this, 'subscription_add_to_cart_template'), 10, 5);

        // Multiple fallback methods for add to cart button
        add_action('woocommerce_single_product_summary', array($this, 'ensure_subscription_add_to_cart'), 30);
        add_action('woocommerce_single_product_summary', array($this, 'emergency_add_to_cart_fallback'), 35);
    }
    
    /**
     * Force product type registration everywhere
     */
    public function force_product_type_registration() {
        static $forced = false;

        if ($forced) {
            return;
        }

        if (class_exists('WooCommerce')) {
            // Force registration with multiple priorities
            add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 999);

            // Also register directly with WooCommerce if possible
            if (function_exists('wc_get_product_types')) {
                $types = wc_get_product_types();
                if (!isset($types['subscription'])) {
                    // Try to register directly
                    global $woocommerce;
                    if (isset($woocommerce->product_factory)) {
                        // Force the product type to be recognized
                        $this->register_subscription_product_type_directly();
                    }
                }
            }

            $forced = true;

            // Log for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Zlaark Subscriptions: Forced product type registration');
            }
        }
    }

    /**
     * Register subscription product type directly with WooCommerce
     */
    private function register_subscription_product_type_directly() {
        // This is a more aggressive approach to ensure the product type is registered
        if (class_exists('WC_Product_Factory')) {
            // Force load our product class
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-wc-product-subscription.php';

            // Register the product type in WooCommerce's internal registry
            add_filter('woocommerce_data_stores', array($this, 'register_subscription_data_store'));
        }
    }

    /**
     * Register subscription data store
     */
    public function register_subscription_data_store($stores) {
        $stores['product-subscription'] = 'WC_Product_Data_Store_CPT';
        return $stores;
    }

    /**
     * Add subscription product type to selector
     *
     * @param array $types
     * @return array
     */
    public function add_subscription_product_type($types) {
        if (!isset($types['subscription'])) {
            $types['subscription'] = __('Subscription', 'zlaark-subscriptions');
        }
        return $types;
    }
    
    /**
     * Add subscription product data tab
     *
     * @param array $tabs
     * @return array
     */
    public function add_subscription_product_data_tab($tabs) {
        $tabs['subscription'] = array(
            'label'    => __('Subscription', 'zlaark-subscriptions'),
            'target'   => 'subscription_product_data',
            'class'    => array('show_if_subscription'),
            'priority' => 25,
        );
        return $tabs;
    }
    
    /**
     * Add subscription product data panel
     */
    public function add_subscription_product_data_panel() {
        global $post;
        
        $product = wc_get_product($post->ID);
        
        ?>
        <div id="subscription_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // Trial price
                woocommerce_wp_text_input(array(
                    'id'          => '_subscription_trial_price',
                    'label'       => __('Trial Price (₹)', 'zlaark-subscriptions'),
                    'placeholder' => '0.00',
                    'desc_tip'    => true,
                    'description' => __('Amount charged during the trial period.', 'zlaark-subscriptions'),
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'step' => '0.01',
                        'min'  => '0'
                    ),
                    'value' => $product ? $product->get_meta('_subscription_trial_price') : ''
                ));
                
                // Trial duration
                woocommerce_wp_text_input(array(
                    'id'          => '_subscription_trial_duration',
                    'label'       => __('Trial Duration', 'zlaark-subscriptions'),
                    'placeholder' => '7',
                    'desc_tip'    => true,
                    'description' => __('Length of the trial period.', 'zlaark-subscriptions'),
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'min' => '1'
                    ),
                    'value' => $product ? $product->get_meta('_subscription_trial_duration') : ''
                ));
                
                // Trial period
                woocommerce_wp_select(array(
                    'id'          => '_subscription_trial_period',
                    'label'       => __('Trial Period', 'zlaark-subscriptions'),
                    'options'     => array(
                        'day'   => __('Day(s)', 'zlaark-subscriptions'),
                        'week'  => __('Week(s)', 'zlaark-subscriptions'),
                        'month' => __('Month(s)', 'zlaark-subscriptions'),
                    ),
                    'value' => $product ? $product->get_meta('_subscription_trial_period') : 'day'
                ));
                ?>
            </div>
            
            <div class="options_group">
                <?php
                // Recurring price
                woocommerce_wp_text_input(array(
                    'id'          => '_subscription_recurring_price',
                    'label'       => __('Recurring Price (₹)', 'zlaark-subscriptions'),
                    'placeholder' => '0.00',
                    'desc_tip'    => true,
                    'description' => __('Amount charged for each billing cycle after trial.', 'zlaark-subscriptions'),
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'step' => '0.01',
                        'min'  => '0'
                    ),
                    'value' => $product ? $product->get_meta('_subscription_recurring_price') : ''
                ));
                
                // Billing interval
                woocommerce_wp_select(array(
                    'id'          => '_subscription_billing_interval',
                    'label'       => __('Billing Interval', 'zlaark-subscriptions'),
                    'options'     => array(
                        'weekly'  => __('Weekly', 'zlaark-subscriptions'),
                        'monthly' => __('Monthly', 'zlaark-subscriptions'),
                        'yearly'  => __('Yearly', 'zlaark-subscriptions'),
                    ),
                    'value' => $product ? $product->get_meta('_subscription_billing_interval') : 'monthly'
                ));
                
                // Maximum subscription length
                woocommerce_wp_text_input(array(
                    'id'          => '_subscription_max_length',
                    'label'       => __('Maximum Subscription Length', 'zlaark-subscriptions'),
                    'placeholder' => __('Unlimited', 'zlaark-subscriptions'),
                    'desc_tip'    => true,
                    'description' => __('Number of billing cycles. Leave empty for unlimited.', 'zlaark-subscriptions'),
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'min' => '1'
                    ),
                    'value' => $product ? $product->get_meta('_subscription_max_length') : ''
                ));
                ?>
            </div>
            
            <div class="options_group">
                <?php
                // Sign-up fee
                woocommerce_wp_text_input(array(
                    'id'          => '_subscription_signup_fee',
                    'label'       => __('Sign-up Fee (₹)', 'zlaark-subscriptions'),
                    'placeholder' => '0.00',
                    'desc_tip'    => true,
                    'description' => __('One-time fee charged at subscription start (in addition to trial price).', 'zlaark-subscriptions'),
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'step' => '0.01',
                        'min'  => '0'
                    ),
                    'value' => $product ? $product->get_meta('_subscription_signup_fee') : ''
                ));
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save subscription product data
     *
     * @param int $post_id
     */
    public function save_subscription_product_data($post_id) {
        $product = wc_get_product($post_id);
        
        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }
        
        // Save subscription fields
        $fields = array(
            '_subscription_trial_price',
            '_subscription_trial_duration',
            '_subscription_trial_period',
            '_subscription_recurring_price',
            '_subscription_billing_interval',
            '_subscription_max_length',
            '_subscription_signup_fee'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                $product->update_meta_data($field, $value);
            }
        }
        
        $product->save();
    }
    
    /**
     * Get subscription product class
     *
     * @param string $classname
     * @param string $product_type
     * @return string
     */
    public function get_subscription_product_class($classname, $product_type) {
        if ($product_type === 'subscription') {
            require_once ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/class-wc-product-subscription.php';
            return 'WC_Product_Subscription';
        }
        return $classname;
    }
    
    /**
     * Add JavaScript for subscription product type
     */
    public function subscription_product_type_js() {
        global $post, $pagenow;
        
        if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
            return;
        }
        
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show/hide fields based on product type
            $('select#product-type').change(function() {
                var product_type = $(this).val();
                
                if (product_type === 'subscription') {
                    $('.show_if_subscription').show();
                    $('.hide_if_subscription').hide();
                    $('#_manage_stock').prop('checked', false).change();
                    $('#_downloadable').prop('checked', false).change();
                    $('#_virtual').prop('checked', true).change();
                } else {
                    $('.show_if_subscription').hide();
                    $('.hide_if_subscription').show();
                }
            }).change();
            
            // Validate trial price vs recurring price
            $('#_subscription_trial_price, #_subscription_recurring_price').on('blur', function() {
                var trial_price = parseFloat($('#_subscription_trial_price').val()) || 0;
                var recurring_price = parseFloat($('#_subscription_recurring_price').val()) || 0;
                
                if (trial_price > recurring_price && recurring_price > 0) {
                    alert('<?php echo esc_js(__('Trial price should not be higher than recurring price.', 'zlaark-subscriptions')); ?>');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Validate subscription product data
     *
     * @param WC_Product $product
     */
    public function validate_subscription_product_data($product) {
        if ($product->get_type() !== 'subscription') {
            return;
        }
        
        $errors = array();
        
        // Validate recurring price
        $recurring_price = $product->get_meta('_subscription_recurring_price');
        if (empty($recurring_price) || $recurring_price <= 0) {
            $errors[] = __('Recurring price is required and must be greater than 0.', 'zlaark-subscriptions');
        }
        
        // Validate trial duration
        $trial_duration = $product->get_meta('_subscription_trial_duration');
        if (!empty($trial_duration) && $trial_duration <= 0) {
            $errors[] = __('Trial duration must be greater than 0.', 'zlaark-subscriptions');
        }
        
        // Validate trial price vs recurring price
        $trial_price = $product->get_meta('_subscription_trial_price');
        if (!empty($trial_price) && !empty($recurring_price) && $trial_price > $recurring_price) {
            $errors[] = __('Trial price should not be higher than recurring price.', 'zlaark-subscriptions');
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                WC_Admin_Meta_Boxes::add_error($error);
            }
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

        // Use product methods instead of direct meta queries
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
        </div>
        <?php
    }
    
    /**
     * Modify add to cart button text for subscription products
     *
     * @param string $text
     * @param WC_Product $product
     * @return string
     */
    public function subscription_add_to_cart_text($text, $product) {
        if ($product && $product->get_type() === 'subscription') {
            if ($product->has_trial()) {
                return __('Start Trial', 'zlaark-subscriptions');
            } else {
                return __('Start Subscription', 'zlaark-subscriptions');
            }
        }
        return $text;
    }
    
    /**
     * Modify add to cart URL for subscription products
     *
     * @param string $url
     * @param WC_Product $product
     * @return string
     */
    public function subscription_add_to_cart_url($url, $product) {
        if ($product && $product->get_type() === 'subscription') {
            return $product->get_permalink();
        }
        return $url;
    }

    /**
     * Make subscription products purchasable
     *
     * @param bool $purchasable
     * @param WC_Product $product
     * @return bool
     */
    public function subscription_is_purchasable($purchasable, $product) {
        if ($product && $product->get_type() === 'subscription') {
            return $product->get_recurring_price() > 0;
        }
        return $purchasable;
    }

    /**
     * Add support for subscription product features
     *
     * @param bool $supports
     * @param string $feature
     * @param WC_Product $product
     * @return bool
     */
    public function subscription_product_supports($supports, $feature, $product) {
        if ($product && $product->get_type() === 'subscription') {
            switch ($feature) {
                case 'ajax_add_to_cart':
                    return false; // Subscriptions need full checkout
                default:
                    return $supports;
            }
        }
        return $supports;
    }

    /**
     * Load custom template for subscription add to cart
     *
     * @param string $template
     * @param string $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public function subscription_add_to_cart_template($template, $template_name, $args, $template_path, $default_path) {
        // Only modify add-to-cart templates for subscription products
        if (strpos($template_name, 'single-product/add-to-cart/') === 0) {
            global $product;

            if ($product && $product->get_type() === 'subscription') {
                $subscription_template = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';

                if (file_exists($subscription_template)) {
                    return $subscription_template;
                }
            }
        }

        return $template;
    }

    /**
     * Ensure subscription products have add to cart button (fallback)
     */
    public function ensure_subscription_add_to_cart() {
        global $product;

        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }

        // Check if add to cart button was already rendered
        if (did_action('woocommerce_single_product_summary') && !did_action('woocommerce_template_single_add_to_cart')) {
            // Force render the add to cart button
            if ($product->is_purchasable() && $product->is_in_stock()) {
                ?>
                <div class="subscription-add-to-cart-fallback">
                    <form class="cart" action="<?php echo esc_url($product->get_permalink()); ?>" method="post" enctype='multipart/form-data'>
                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt subscription-add-to-cart-button">
                            <?php
                            if (method_exists($product, 'has_trial') && $product->has_trial()) {
                                echo esc_html__('Start Trial', 'zlaark-subscriptions');
                            } else {
                                echo esc_html__('Start Subscription', 'zlaark-subscriptions');
                            }
                            ?>
                        </button>
                    </form>
                </div>
                <?php
            }
        }
    }

    /**
     * Emergency fallback for add to cart button
     */
    public function emergency_add_to_cart_fallback() {
        global $product;

        if (!$product || $product->get_type() !== 'subscription') {
            return;
        }

        // Only show if no other add to cart button was rendered
        if (!did_action('woocommerce_template_single_add_to_cart')) {
            ?>
            <div class="emergency-subscription-add-to-cart" style="margin: 20px 0; padding: 20px; background: #ffebee; border: 2px solid #f44336; border-radius: 8px;">
                <h4 style="color: #d32f2f; margin-top: 0;">⚠️ Emergency Add to Cart</h4>
                <p style="color: #666;">The normal add to cart system failed to load. Using emergency fallback.</p>

                <form class="cart" action="<?php echo esc_url($product->get_permalink()); ?>" method="post" enctype='multipart/form-data'>
                    <?php wp_nonce_field('woocommerce-add-to-cart', 'woocommerce-add-to-cart-nonce'); ?>

                    <div style="margin-bottom: 15px; padding: 10px; background: white; border-radius: 4px;">
                        <?php if (method_exists($product, 'get_recurring_price')): ?>
                            <strong>Price: ₹<?php echo number_format($product->get_recurring_price(), 2); ?> <?php echo $product->get_billing_interval(); ?></strong>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt" style="width: 100%; padding: 15px; font-size: 16px; background: #f44336; border-color: #f44336;">
                        🚨 Emergency: Start Subscription
                    </button>
                </form>

                <p style="font-size: 12px; color: #999; margin-bottom: 0;">
                    If you see this, please contact the site administrator about the subscription system initialization issue.
                </p>
            </div>
            <?php
        }
    }
}
