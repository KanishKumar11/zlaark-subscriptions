<?php
/**
 * Shortcode Documentation and Management
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Documentation class
 */
class ZlaarkSubscriptionsShortcodes {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsShortcodes
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsShortcodes
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
        // Add shortcode documentation to admin
        add_action('admin_menu', array($this, 'add_shortcode_documentation_page'));
        
        // Enqueue admin scripts for shortcode documentation
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add additional shortcodes for enhanced functionality
        $this->register_additional_shortcodes();
    }
    
    /**
     * Add shortcode documentation page
     */
    public function add_shortcode_documentation_page() {
        add_submenu_page(
            'zlaark-subscriptions',
            __('Shortcodes', 'zlaark-subscriptions'),
            __('Shortcodes', 'zlaark-subscriptions'),
            'manage_options',
            'zlaark-subscriptions-shortcodes',
            array($this, 'shortcode_documentation_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'zlaark-subscriptions-shortcodes') !== false) {
            wp_enqueue_script('zlaark-shortcodes-admin', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/shortcodes-admin.js', array('jquery'), ZLAARK_SUBSCRIPTIONS_VERSION, true);
            wp_enqueue_style('zlaark-shortcodes-admin', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/shortcodes-admin.css', array(), ZLAARK_SUBSCRIPTIONS_VERSION);
        }
    }
    
    /**
     * Register additional shortcodes
     */
    private function register_additional_shortcodes() {
        add_shortcode('subscription_pricing', array($this, 'subscription_pricing_shortcode'));
        add_shortcode('trial_eligibility', array($this, 'trial_eligibility_shortcode'));
        add_shortcode('user_subscription_status', array($this, 'user_subscription_status_shortcode'));
        add_shortcode('subscription_details', array($this, 'subscription_details_shortcode'));
        add_shortcode('trial_countdown', array($this, 'trial_countdown_shortcode'));
    }
    
    /**
     * Get all available shortcodes with documentation
     */
    public function get_shortcode_documentation() {
        return array(
            'trial_button' => array(
                'name' => 'Trial Button',
                'description' => 'Displays a trial subscription button for a specific product',
                'usage' => '[trial_button product_id="123" text="Start Free Trial" class="my-trial-btn" style="background: #007cba;" redirect="/thank-you/"]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the subscription product'
                    ),
                    'text' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'Start Free Trial',
                        'description' => 'Custom button text'
                    ),
                    'class' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'trial-button zlaark-trial-btn',
                        'description' => 'Additional CSS classes'
                    ),
                    'style' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'Inline CSS styles'
                    ),
                    'redirect' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'URL to redirect after adding to cart'
                    )
                ),
                'example_output' => 'A styled button that adds the trial version of the product to cart'
            ),
            
            'subscription_button' => array(
                'name' => 'Subscription Button',
                'description' => 'Displays a regular subscription button for a specific product',
                'usage' => '[subscription_button product_id="123" text="Subscribe Now" class="my-sub-btn" style="background: #28a745;" redirect="/thank-you/"]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the subscription product'
                    ),
                    'text' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'Subscribe Now',
                        'description' => 'Custom button text'
                    ),
                    'class' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'subscription-button zlaark-subscription-btn',
                        'description' => 'Additional CSS classes'
                    ),
                    'style' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'Inline CSS styles'
                    ),
                    'redirect' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'URL to redirect after adding to cart'
                    )
                ),
                'example_output' => 'A styled button that adds the regular subscription to cart'
            ),
            
            'subscription_pricing' => array(
                'name' => 'Subscription Pricing Display',
                'description' => 'Shows pricing information for a subscription product',
                'usage' => '[subscription_pricing product_id="123" show_trial="true" show_regular="true" layout="table"]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the subscription product'
                    ),
                    'show_trial' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => 'true',
                        'description' => 'Whether to show trial pricing'
                    ),
                    'show_regular' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => 'true',
                        'description' => 'Whether to show regular pricing'
                    ),
                    'layout' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'list',
                        'description' => 'Display layout: "list", "table", or "cards"'
                    )
                ),
                'example_output' => 'A formatted display of subscription pricing options'
            ),
            
            'trial_eligibility' => array(
                'name' => 'Trial Eligibility Check',
                'description' => 'Shows whether the current user is eligible for a trial',
                'usage' => '[trial_eligibility product_id="123" show_reason="true"]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the subscription product'
                    ),
                    'show_reason' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => 'false',
                        'description' => 'Whether to show the reason if not eligible'
                    )
                ),
                'example_output' => 'Displays eligibility status and optional reason'
            ),
            
            'user_subscription_status' => array(
                'name' => 'User Subscription Status',
                'description' => 'Shows the current user\'s subscription status for a product',
                'usage' => '[user_subscription_status product_id="123" show_details="true"]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => false,
                        'type' => 'integer',
                        'description' => 'Specific product ID (optional, shows all if omitted)'
                    ),
                    'show_details' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => 'false',
                        'description' => 'Whether to show detailed subscription information'
                    )
                ),
                'example_output' => 'Current subscription status and optional details'
            ),
            
            'subscription_details' => array(
                'name' => 'Subscription Details',
                'description' => 'Displays detailed information about a subscription product',
                'usage' => '[subscription_details product_id="123" show_trial="true" show_billing="true"]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'The ID of the subscription product'
                    ),
                    'show_trial' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => 'true',
                        'description' => 'Whether to show trial information'
                    ),
                    'show_billing' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => 'true',
                        'description' => 'Whether to show billing information'
                    )
                ),
                'example_output' => 'Comprehensive subscription product details'
            ),
            
            'zlaark_subscriptions_manage' => array(
                'name' => 'Subscription Management',
                'description' => 'Displays a complete subscription management interface for logged-in users',
                'usage' => '[zlaark_subscriptions_manage]',
                'parameters' => array(),
                'example_output' => 'Full subscription management dashboard'
            ),
            
            'zlaark_user_subscriptions' => array(
                'name' => 'User Subscriptions List',
                'description' => 'Shows a list of user subscriptions with optional filtering',
                'usage' => '[zlaark_user_subscriptions status="active" limit="5"]',
                'parameters' => array(
                    'status' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'Filter by status: "active", "paused", "cancelled", "expired"'
                    ),
                    'limit' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => '-1',
                        'description' => 'Maximum number of subscriptions to show (-1 for all)'
                    )
                ),
                'example_output' => 'Filtered list of user subscriptions'
            ),
            
            'subscription_required' => array(
                'name' => 'Subscription Required Content',
                'description' => 'Restricts content to users with active subscriptions',
                'usage' => '[subscription_required product_id="123" message="Premium content requires subscription"]Content here[/subscription_required]',
                'parameters' => array(
                    'product_id' => array(
                        'required' => false,
                        'type' => 'integer',
                        'description' => 'Specific product subscription required (optional)'
                    ),
                    'message' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'You need an active subscription to access this content.',
                        'description' => 'Message shown to non-subscribers'
                    )
                ),
                'example_output' => 'Protected content or restriction message'
            )
        );
    }

    /**
     * Shortcode documentation page
     */
    public function shortcode_documentation_page() {
        $shortcodes = $this->get_shortcode_documentation();
        ?>
        <div class="wrap">
            <h1><?php _e('Zlaark Subscriptions - Shortcodes', 'zlaark-subscriptions'); ?></h1>

            <div class="zlaark-shortcodes-header">
                <p><?php _e('Use these shortcodes to display subscription functionality anywhere on your site.', 'zlaark-subscriptions'); ?></p>
                <div class="shortcode-search">
                    <input type="text" id="shortcode-search" placeholder="<?php _e('Search shortcodes...', 'zlaark-subscriptions'); ?>" />
                </div>
            </div>

            <div class="zlaark-shortcodes-grid">
                <?php foreach ($shortcodes as $shortcode_key => $shortcode): ?>
                    <div class="shortcode-card" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                        <div class="shortcode-header">
                            <h3><?php echo esc_html($shortcode['name']); ?></h3>
                            <button class="copy-shortcode-btn" data-shortcode="[<?php echo esc_attr($shortcode_key); ?>]">
                                <?php _e('Copy Basic', 'zlaark-subscriptions'); ?>
                            </button>
                        </div>

                        <div class="shortcode-description">
                            <p><?php echo esc_html($shortcode['description']); ?></p>
                        </div>

                        <div class="shortcode-usage">
                            <h4><?php _e('Usage Example:', 'zlaark-subscriptions'); ?></h4>
                            <div class="code-block">
                                <code><?php echo esc_html($shortcode['usage']); ?></code>
                                <button class="copy-usage-btn" data-usage="<?php echo esc_attr($shortcode['usage']); ?>">
                                    <?php _e('Copy', 'zlaark-subscriptions'); ?>
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($shortcode['parameters'])): ?>
                        <div class="shortcode-parameters">
                            <h4><?php _e('Parameters:', 'zlaark-subscriptions'); ?></h4>
                            <table class="parameters-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Parameter', 'zlaark-subscriptions'); ?></th>
                                        <th><?php _e('Required', 'zlaark-subscriptions'); ?></th>
                                        <th><?php _e('Type', 'zlaark-subscriptions'); ?></th>
                                        <th><?php _e('Default', 'zlaark-subscriptions'); ?></th>
                                        <th><?php _e('Description', 'zlaark-subscriptions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shortcode['parameters'] as $param_name => $param_info): ?>
                                    <tr>
                                        <td><code><?php echo esc_html($param_name); ?></code></td>
                                        <td><?php echo $param_info['required'] ? '<span class="required">Yes</span>' : '<span class="optional">No</span>'; ?></td>
                                        <td><?php echo esc_html($param_info['type']); ?></td>
                                        <td><?php echo isset($param_info['default']) ? '<code>' . esc_html($param_info['default']) . '</code>' : '-'; ?></td>
                                        <td><?php echo esc_html($param_info['description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <div class="shortcode-output">
                            <h4><?php _e('Expected Output:', 'zlaark-subscriptions'); ?></h4>
                            <p><em><?php echo esc_html($shortcode['example_output']); ?></em></p>
                        </div>

                        <div class="shortcode-actions">
                            <button class="button button-secondary preview-shortcode" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                                <?php _e('Preview', 'zlaark-subscriptions'); ?>
                            </button>
                            <button class="button button-primary generate-shortcode" data-shortcode="<?php echo esc_attr($shortcode_key); ?>">
                                <?php _e('Generate Custom', 'zlaark-subscriptions'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Shortcode Generator Modal -->
            <div id="shortcode-generator-modal" class="zlaark-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?php _e('Shortcode Generator', 'zlaark-subscriptions'); ?></h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="generator-form"></div>
                        <div class="generated-shortcode">
                            <h4><?php _e('Generated Shortcode:', 'zlaark-subscriptions'); ?></h4>
                            <div class="code-block">
                                <code id="generated-code"></code>
                                <button class="copy-generated-btn"><?php _e('Copy', 'zlaark-subscriptions'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Modal -->
            <div id="shortcode-preview-modal" class="zlaark-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?php _e('Shortcode Preview', 'zlaark-subscriptions'); ?></h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="preview-content"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Subscription pricing shortcode
     */
    public function subscription_pricing_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'show_trial' => 'true',
            'show_regular' => 'true',
            'layout' => 'list'
        ), $atts);

        if (empty($atts['product_id'])) {
            return '<p class="zlaark-error">' . __('Product ID is required for subscription pricing display.', 'zlaark-subscriptions') . '</p>';
        }

        $product = wc_get_product($atts['product_id']);
        if (!$product || $product->get_type() !== 'subscription') {
            return '<p class="zlaark-error">' . __('Invalid subscription product.', 'zlaark-subscriptions') . '</p>';
        }

        $show_trial = $atts['show_trial'] === 'true';
        $show_regular = $atts['show_regular'] === 'true';
        $layout = $atts['layout'];

        ob_start();
        ?>
        <div class="zlaark-subscription-pricing layout-<?php echo esc_attr($layout); ?>">
            <?php if ($layout === 'table'): ?>
                <table class="pricing-table">
                    <thead>
                        <tr>
                            <th><?php _e('Option', 'zlaark-subscriptions'); ?></th>
                            <th><?php _e('Price', 'zlaark-subscriptions'); ?></th>
                            <th><?php _e('Duration', 'zlaark-subscriptions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($show_trial && method_exists($product, 'has_trial') && $product->has_trial()): ?>
                        <tr class="trial-row">
                            <td><?php _e('Trial', 'zlaark-subscriptions'); ?></td>
                            <td><?php echo wc_price($product->get_trial_price()); ?></td>
                            <td><?php printf(__('%d %s', 'zlaark-subscriptions'), $product->get_trial_duration(), $product->get_trial_period()); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($show_regular): ?>
                        <tr class="regular-row">
                            <td><?php _e('Regular', 'zlaark-subscriptions'); ?></td>
                            <td><?php echo wc_price($product->get_recurring_price()); ?></td>
                            <td><?php echo esc_html($product->get_billing_interval()); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($layout === 'cards'): ?>
                <div class="pricing-cards">
                    <?php if ($show_trial && method_exists($product, 'has_trial') && $product->has_trial()): ?>
                    <div class="pricing-card trial-card">
                        <h4><?php _e('Trial Option', 'zlaark-subscriptions'); ?></h4>
                        <div class="price"><?php echo wc_price($product->get_trial_price()); ?></div>
                        <div class="duration"><?php printf(__('for %d %s', 'zlaark-subscriptions'), $product->get_trial_duration(), $product->get_trial_period()); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($show_regular): ?>
                    <div class="pricing-card regular-card">
                        <h4><?php _e('Regular Subscription', 'zlaark-subscriptions'); ?></h4>
                        <div class="price"><?php echo wc_price($product->get_recurring_price()); ?></div>
                        <div class="duration"><?php echo esc_html($product->get_billing_interval()); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: // list layout ?>
                <ul class="pricing-list">
                    <?php if ($show_trial && method_exists($product, 'has_trial') && $product->has_trial()): ?>
                    <li class="trial-option">
                        <strong><?php _e('Trial:', 'zlaark-subscriptions'); ?></strong>
                        <?php echo wc_price($product->get_trial_price()); ?>
                        <?php printf(__('for %d %s', 'zlaark-subscriptions'), $product->get_trial_duration(), $product->get_trial_period()); ?>
                    </li>
                    <?php endif; ?>
                    <?php if ($show_regular): ?>
                    <li class="regular-option">
                        <strong><?php _e('Regular:', 'zlaark-subscriptions'); ?></strong>
                        <?php echo wc_price($product->get_recurring_price()); ?>
                        <?php echo esc_html($product->get_billing_interval()); ?>
                    </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Trial eligibility shortcode
     */
    public function trial_eligibility_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'show_reason' => 'false'
        ), $atts);

        if (empty($atts['product_id'])) {
            return '<p class="zlaark-error">' . __('Product ID is required for trial eligibility check.', 'zlaark-subscriptions') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<div class="trial-eligibility not-logged-in">' .
                   '<span class="status-icon">❌</span>' .
                   '<span class="status-text">' . __('Please log in to check trial eligibility.', 'zlaark-subscriptions') . '</span>' .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $product_id = intval($atts['product_id']);
        $show_reason = $atts['show_reason'] === 'true';

        if (class_exists('ZlaarkSubscriptionsTrialService')) {
            $trial_service = ZlaarkSubscriptionsTrialService::instance();
            $eligibility = $trial_service->check_trial_eligibility($user_id, $product_id);

            $status_class = $eligibility['eligible'] ? 'eligible' : 'not-eligible';
            $status_icon = $eligibility['eligible'] ? '✅' : '❌';
            $status_text = $eligibility['eligible'] ?
                __('You are eligible for a free trial!', 'zlaark-subscriptions') :
                __('Trial not available', 'zlaark-subscriptions');

            ob_start();
            ?>
            <div class="trial-eligibility <?php echo esc_attr($status_class); ?>">
                <span class="status-icon"><?php echo $status_icon; ?></span>
                <span class="status-text"><?php echo esc_html($status_text); ?></span>
                <?php if (!$eligibility['eligible'] && $show_reason && !empty($eligibility['message'])): ?>
                    <div class="eligibility-reason"><?php echo esc_html($eligibility['message']); ?></div>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
        }

        return '<p class="zlaark-error">' . __('Trial service not available.', 'zlaark-subscriptions') . '</p>';
    }

    /**
     * User subscription status shortcode
     */
    public function user_subscription_status_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'show_details' => 'false'
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="subscription-status not-logged-in">' .
                   '<span class="status-icon">❌</span>' .
                   '<span class="status-text">' . __('Please log in to view subscription status.', 'zlaark-subscriptions') . '</span>' .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $product_id = !empty($atts['product_id']) ? intval($atts['product_id']) : null;
        $show_details = $atts['show_details'] === 'true';

        if (class_exists('ZlaarkSubscriptionsDatabase')) {
            $db = ZlaarkSubscriptionsDatabase::instance();

            if ($product_id) {
                // Check specific product subscription
                $subscription = $db->get_user_subscription($user_id, $product_id);

                if ($subscription) {
                    $status_class = 'status-' . $subscription->status;
                    $status_icon = $this->get_status_icon($subscription->status);
                    $status_text = $this->get_status_text($subscription->status);

                    ob_start();
                    ?>
                    <div class="subscription-status <?php echo esc_attr($status_class); ?>">
                        <span class="status-icon"><?php echo $status_icon; ?></span>
                        <span class="status-text"><?php echo esc_html($status_text); ?></span>
                        <?php if ($show_details): ?>
                            <div class="subscription-details">
                                <p><strong><?php _e('Started:', 'zlaark-subscriptions'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($subscription->start_date)); ?></p>
                                <?php if ($subscription->end_date): ?>
                                    <p><strong><?php _e('Ends:', 'zlaark-subscriptions'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($subscription->end_date)); ?></p>
                                <?php endif; ?>
                                <?php if ($subscription->next_payment_date): ?>
                                    <p><strong><?php _e('Next Payment:', 'zlaark-subscriptions'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    return ob_get_clean();
                } else {
                    return '<div class="subscription-status no-subscription">' .
                           '<span class="status-icon">❌</span>' .
                           '<span class="status-text">' . __('No active subscription for this product.', 'zlaark-subscriptions') . '</span>' .
                           '</div>';
                }
            } else {
                // Show all subscriptions
                $subscriptions = $db->get_user_subscriptions($user_id);

                if (empty($subscriptions)) {
                    return '<div class="subscription-status no-subscriptions">' .
                           '<span class="status-icon">❌</span>' .
                           '<span class="status-text">' . __('No subscriptions found.', 'zlaark-subscriptions') . '</span>' .
                           '</div>';
                }

                ob_start();
                ?>
                <div class="subscription-status-list">
                    <?php foreach ($subscriptions as $subscription): ?>
                        <?php
                        $product = wc_get_product($subscription->product_id);
                        $status_class = 'status-' . $subscription->status;
                        $status_icon = $this->get_status_icon($subscription->status);
                        $status_text = $this->get_status_text($subscription->status);
                        ?>
                        <div class="subscription-item <?php echo esc_attr($status_class); ?>">
                            <div class="subscription-header">
                                <h4><?php echo $product ? esc_html($product->get_name()) : __('Unknown Product', 'zlaark-subscriptions'); ?></h4>
                                <span class="status-badge">
                                    <span class="status-icon"><?php echo $status_icon; ?></span>
                                    <span class="status-text"><?php echo esc_html($status_text); ?></span>
                                </span>
                            </div>
                            <?php if ($show_details): ?>
                                <div class="subscription-details">
                                    <p><strong><?php _e('Started:', 'zlaark-subscriptions'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($subscription->start_date)); ?></p>
                                    <?php if ($subscription->end_date): ?>
                                        <p><strong><?php _e('Ends:', 'zlaark-subscriptions'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($subscription->end_date)); ?></p>
                                    <?php endif; ?>
                                    <?php if ($subscription->next_payment_date): ?>
                                        <p><strong><?php _e('Next Payment:', 'zlaark-subscriptions'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                return ob_get_clean();
            }
        }

        return '<p class="zlaark-error">' . __('Subscription database not available.', 'zlaark-subscriptions') . '</p>';
    }

    /**
     * Subscription details shortcode
     */
    public function subscription_details_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'show_trial' => 'true',
            'show_billing' => 'true'
        ), $atts);

        if (empty($atts['product_id'])) {
            return '<p class="zlaark-error">' . __('Product ID is required for subscription details.', 'zlaark-subscriptions') . '</p>';
        }

        $product = wc_get_product($atts['product_id']);
        if (!$product || $product->get_type() !== 'subscription') {
            return '<p class="zlaark-error">' . __('Invalid subscription product.', 'zlaark-subscriptions') . '</p>';
        }

        $show_trial = $atts['show_trial'] === 'true';
        $show_billing = $atts['show_billing'] === 'true';

        ob_start();
        ?>
        <div class="zlaark-subscription-details">
            <h3><?php echo esc_html($product->get_name()); ?></h3>

            <?php if ($show_trial && method_exists($product, 'has_trial') && $product->has_trial()): ?>
            <div class="trial-details">
                <h4><?php _e('Trial Information', 'zlaark-subscriptions'); ?></h4>
                <ul>
                    <li><strong><?php _e('Trial Price:', 'zlaark-subscriptions'); ?></strong> <?php echo wc_price($product->get_trial_price()); ?></li>
                    <li><strong><?php _e('Trial Duration:', 'zlaark-subscriptions'); ?></strong> <?php printf(__('%d %s', 'zlaark-subscriptions'), $product->get_trial_duration(), $product->get_trial_period()); ?></li>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($show_billing): ?>
            <div class="billing-details">
                <h4><?php _e('Billing Information', 'zlaark-subscriptions'); ?></h4>
                <ul>
                    <li><strong><?php _e('Regular Price:', 'zlaark-subscriptions'); ?></strong> <?php echo wc_price($product->get_recurring_price()); ?></li>
                    <li><strong><?php _e('Billing Interval:', 'zlaark-subscriptions'); ?></strong> <?php echo esc_html($product->get_billing_interval()); ?></li>
                    <?php if (method_exists($product, 'get_signup_fee') && $product->get_signup_fee() > 0): ?>
                        <li><strong><?php _e('Signup Fee:', 'zlaark-subscriptions'); ?></strong> <?php echo wc_price($product->get_signup_fee()); ?></li>
                    <?php endif; ?>
                    <?php if (method_exists($product, 'get_max_length') && $product->get_max_length() > 0): ?>
                        <li><strong><?php _e('Maximum Length:', 'zlaark-subscriptions'); ?></strong> <?php printf(__('%d payments', 'zlaark-subscriptions'), $product->get_max_length()); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get status icon for subscription status
     */
    private function get_status_icon($status) {
        $icons = array(
            'active' => '✅',
            'paused' => '⏸️',
            'cancelled' => '❌',
            'expired' => '⏰',
            'pending' => '⏳'
        );

        return isset($icons[$status]) ? $icons[$status] : '❓';
    }

    /**
     * Get status text for subscription status
     */
    private function get_status_text($status) {
        $texts = array(
            'active' => __('Active', 'zlaark-subscriptions'),
            'paused' => __('Paused', 'zlaark-subscriptions'),
            'cancelled' => __('Cancelled', 'zlaark-subscriptions'),
            'expired' => __('Expired', 'zlaark-subscriptions'),
            'pending' => __('Pending', 'zlaark-subscriptions')
        );

        return isset($texts[$status]) ? $texts[$status] : __('Unknown', 'zlaark-subscriptions');
    }
}
