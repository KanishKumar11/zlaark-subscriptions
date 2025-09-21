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
        // Add subscription product type with multiple hook priorities
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 10);
        add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 20);

        // Force registration on admin_init for admin pages
        add_action('admin_init', array($this, 'force_product_type_registration'));

        // Add subscription product data tabs
        add_filter('woocommerce_product_data_tabs', array($this, 'add_subscription_product_data_tab'));

        // Add subscription product data panels
        add_action('woocommerce_product_data_panels', array($this, 'add_subscription_product_data_panel'));

        // Save subscription product data
        add_action('woocommerce_process_product_meta', array($this, 'save_subscription_product_data'));

        // Modify product class for subscription products
        add_filter('woocommerce_product_class', array($this, 'get_subscription_product_class'), 10, 2);

        // Hide/show fields based on product type
        add_action('admin_footer', array($this, 'subscription_product_type_js'));

        // Validate subscription product data
        add_action('woocommerce_admin_process_product_object', array($this, 'validate_subscription_product_data'));

        // Add subscription info to product display
        add_action('woocommerce_single_product_summary', array($this, 'display_subscription_info'), 25);

        // Modify add to cart button for subscription products
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'subscription_add_to_cart_text'), 10, 2);
        add_filter('woocommerce_product_add_to_cart_url', array($this, 'subscription_add_to_cart_url'), 10, 2);
    }
    
    /**
     * Force product type registration on admin pages
     */
    public function force_product_type_registration() {
        if (is_admin() && class_exists('WooCommerce')) {
            // Ensure our product type is registered
            add_filter('product_type_selector', array($this, 'add_subscription_product_type'), 999);
        }
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
        
        $trial_price = $product->get_meta('_subscription_trial_price');
        $trial_duration = $product->get_meta('_subscription_trial_duration');
        $trial_period = $product->get_meta('_subscription_trial_period');
        $recurring_price = $product->get_meta('_subscription_recurring_price');
        $billing_interval = $product->get_meta('_subscription_billing_interval');
        $signup_fee = $product->get_meta('_subscription_signup_fee');
        
        echo '<div class="subscription-info">';
        
        if (!empty($trial_price) && !empty($trial_duration)) {
            echo '<p class="subscription-trial">';
            printf(
                __('Trial: ₹%s for %d %s', 'zlaark-subscriptions'),
                number_format($trial_price, 2),
                $trial_duration,
                $trial_period
            );
            echo '</p>';
        }
        
        if (!empty($recurring_price)) {
            echo '<p class="subscription-recurring">';
            printf(
                __('Then: ₹%s %s', 'zlaark-subscriptions'),
                number_format($recurring_price, 2),
                $billing_interval
            );
            echo '</p>';
        }
        
        if (!empty($signup_fee)) {
            echo '<p class="subscription-signup-fee">';
            printf(
                __('Sign-up fee: ₹%s', 'zlaark-subscriptions'),
                number_format($signup_fee, 2)
            );
            echo '</p>';
        }
        
        echo '</div>';
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
            return __('Subscribe Now', 'zlaark-subscriptions');
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
}
