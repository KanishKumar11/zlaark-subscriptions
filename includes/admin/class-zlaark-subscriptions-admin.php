<?php
/**
 * Admin functionality
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class ZlaarkSubscriptionsAdmin {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsAdmin
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsAdmin
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Handle admin actions
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . ZLAARK_SUBSCRIPTIONS_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Add WooCommerce admin bar menu
        add_action('admin_bar_menu', array($this, 'admin_bar_menu'), 100);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Subscriptions', 'zlaark-subscriptions'),
            __('Subscriptions', 'zlaark-subscriptions'),
            'manage_woocommerce',
            'zlaark-subscriptions',
            array($this, 'subscriptions_page'),
            'dashicons-update',
            56
        );
        
        // Submenu pages
        add_submenu_page(
            'zlaark-subscriptions',
            __('All Subscriptions', 'zlaark-subscriptions'),
            __('All Subscriptions', 'zlaark-subscriptions'),
            'manage_woocommerce',
            'zlaark-subscriptions',
            array($this, 'subscriptions_page')
        );
        
        add_submenu_page(
            'zlaark-subscriptions',
            __('Add Subscription', 'zlaark-subscriptions'),
            __('Add Subscription', 'zlaark-subscriptions'),
            'manage_woocommerce',
            'zlaark-subscriptions-add',
            array($this, 'add_subscription_page')
        );
        
        add_submenu_page(
            'zlaark-subscriptions',
            __('Settings', 'zlaark-subscriptions'),
            __('Settings', 'zlaark-subscriptions'),
            'manage_options',
            'zlaark-subscriptions-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'zlaark-subscriptions',
            __('Reports', 'zlaark-subscriptions'),
            __('Reports', 'zlaark-subscriptions'),
            'manage_woocommerce',
            'zlaark-subscriptions-reports',
            array($this, 'reports_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook
     */
    public function admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'zlaark-subscriptions') === false && $hook !== 'dashboard_page_wc-admin') {
            return;
        }
        
        wp_enqueue_style('zlaark-subscriptions-admin', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/admin.css', array(), ZLAARK_SUBSCRIPTIONS_VERSION);
        wp_enqueue_script('zlaark-subscriptions-admin', ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ZLAARK_SUBSCRIPTIONS_VERSION, true);
        
        wp_localize_script('zlaark-subscriptions-admin', 'zlaark_subscriptions_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zlaark_subscriptions_admin_nonce'),
            'strings' => array(
                'confirm_cancel' => __('Are you sure you want to cancel this subscription?', 'zlaark-subscriptions'),
                'confirm_delete' => __('Are you sure you want to delete this subscription? This action cannot be undone.', 'zlaark-subscriptions'),
            )
        ));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'zlaark_subscriptions_dashboard_widget',
            __('Subscription Overview', 'zlaark-subscriptions'),
            array($this, 'dashboard_widget_content')
        );
    }
    
    /**
     * Dashboard widget content
     */
    public function dashboard_widget_content() {
        $db = ZlaarkSubscriptionsDatabase::instance();
        $stats = $db->get_subscription_stats();
        
        ?>
        <div class="subscription-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
                <span class="stat-label"><?php _e('Total Subscriptions', 'zlaark-subscriptions'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($stats['active']); ?></span>
                <span class="stat-label"><?php _e('Active', 'zlaark-subscriptions'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html($stats['trial']); ?></span>
                <span class="stat-label"><?php _e('Trial', 'zlaark-subscriptions'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">₹<?php echo esc_html(number_format($stats['mrr'], 2)); ?></span>
                <span class="stat-label"><?php _e('Monthly Recurring Revenue', 'zlaark-subscriptions'); ?></span>
            </div>
        </div>
        
        <div class="subscription-actions">
            <a href="<?php echo admin_url('admin.php?page=zlaark-subscriptions'); ?>" class="button">
                <?php _e('View All Subscriptions', 'zlaark-subscriptions'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=zlaark-subscriptions-reports'); ?>" class="button">
                <?php _e('View Reports', 'zlaark-subscriptions'); ?>
            </a>
        </div>
        <?php
    }
    
    /**
     * Subscriptions page
     */
    public function subscriptions_page() {
        $list_table = new ZlaarkSubscriptionsAdminList();
        $list_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Subscriptions', 'zlaark-subscriptions'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=zlaark-subscriptions-add'); ?>" class="page-title-action">
                <?php _e('Add New', 'zlaark-subscriptions'); ?>
            </a>
            <hr class="wp-header-end">
            
            <?php $list_table->views(); ?>
            
            <form method="post">
                <?php $list_table->search_box(__('Search subscriptions', 'zlaark-subscriptions'), 'subscription'); ?>
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Add subscription page
     */
    public function add_subscription_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Add New Subscription', 'zlaark-subscriptions'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('zlaark_add_subscription', 'zlaark_subscription_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php _e('Customer', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <select name="user_id" id="user_id" class="regular-text" required>
                                <option value=""><?php _e('Select a customer', 'zlaark-subscriptions'); ?></option>
                                <?php
                                $users = get_users(array('role' => 'customer'));
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="product_id"><?php _e('Subscription Product', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <select name="product_id" id="product_id" class="regular-text" required>
                                <option value=""><?php _e('Select a subscription product', 'zlaark-subscriptions'); ?></option>
                                <?php
                                $products = wc_get_products(array(
                                    'type' => 'subscription',
                                    'limit' => -1,
                                    'status' => 'publish'
                                ));
                                foreach ($products as $product) {
                                    echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <select name="status" id="status" class="regular-text">
                                <option value="trial"><?php _e('Trial', 'zlaark-subscriptions'); ?></option>
                                <option value="active" selected><?php _e('Active', 'zlaark-subscriptions'); ?></option>
                                <option value="paused"><?php _e('Paused', 'zlaark-subscriptions'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Subscription', 'zlaark-subscriptions')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = $this->get_settings();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Subscription Settings', 'zlaark-subscriptions'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('zlaark_subscription_settings', 'zlaark_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="trial_grace_period"><?php _e('Trial Grace Period (days)', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="trial_grace_period" id="trial_grace_period" value="<?php echo esc_attr($settings['trial_grace_period']); ?>" class="small-text" min="0" />
                            <p class="description"><?php _e('Number of days to allow after trial expiration before cancelling.', 'zlaark-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="failed_payment_retries"><?php _e('Failed Payment Retries', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="failed_payment_retries" id="failed_payment_retries" value="<?php echo esc_attr($settings['failed_payment_retries']); ?>" class="small-text" min="1" max="10" />
                            <p class="description"><?php _e('Number of times to retry failed payments.', 'zlaark-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="retry_interval"><?php _e('Retry Interval (days)', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="retry_interval" id="retry_interval" value="<?php echo esc_attr($settings['retry_interval']); ?>" class="small-text" min="1" />
                            <p class="description"><?php _e('Number of days between payment retry attempts.', 'zlaark-subscriptions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="auto_cancel_after_retries"><?php _e('Auto Cancel After Retries', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="auto_cancel_after_retries" id="auto_cancel_after_retries" value="yes" <?php checked($settings['auto_cancel_after_retries'], 'yes'); ?> />
                            <label for="auto_cancel_after_retries"><?php _e('Automatically cancel subscriptions after maximum retry attempts.', 'zlaark-subscriptions'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_notifications"><?php _e('Email Notifications', 'zlaark-subscriptions'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="email_notifications" id="email_notifications" value="yes" <?php checked($settings['email_notifications'], 'yes'); ?> />
                            <label for="email_notifications"><?php _e('Send email notifications for subscription events.', 'zlaark-subscriptions'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        $db = ZlaarkSubscriptionsDatabase::instance();
        $stats = $db->get_subscription_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Subscription Reports', 'zlaark-subscriptions'); ?></h1>
            
            <div class="subscription-reports">
                <div class="report-section">
                    <h2><?php _e('Overview', 'zlaark-subscriptions'); ?></h2>
                    <div class="report-stats">
                        <div class="stat-box">
                            <h3><?php echo esc_html($stats['total']); ?></h3>
                            <p><?php _e('Total Subscriptions', 'zlaark-subscriptions'); ?></p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo esc_html($stats['active']); ?></h3>
                            <p><?php _e('Active Subscriptions', 'zlaark-subscriptions'); ?></p>
                        </div>
                        <div class="stat-box">
                            <h3><?php echo esc_html($stats['trial']); ?></h3>
                            <p><?php _e('Trial Subscriptions', 'zlaark-subscriptions'); ?></p>
                        </div>
                        <div class="stat-box">
                            <h3>₹<?php echo esc_html(number_format($stats['mrr'], 2)); ?></h3>
                            <p><?php _e('Monthly Recurring Revenue', 'zlaark-subscriptions'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="report-section">
                    <h2><?php _e('Status Distribution', 'zlaark-subscriptions'); ?></h2>
                    <div class="status-chart">
                        <div class="status-item">
                            <span class="status-label"><?php _e('Active', 'zlaark-subscriptions'); ?></span>
                            <span class="status-count"><?php echo esc_html($stats['active']); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label"><?php _e('Trial', 'zlaark-subscriptions'); ?></span>
                            <span class="status-count"><?php echo esc_html($stats['trial']); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label"><?php _e('Cancelled', 'zlaark-subscriptions'); ?></span>
                            <span class="status-count"><?php echo esc_html($stats['cancelled']); ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label"><?php _e('Expired', 'zlaark-subscriptions'); ?></span>
                            <span class="status-count"><?php echo esc_html($stats['expired']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        // Handle add subscription
        if (isset($_POST['zlaark_subscription_nonce']) && wp_verify_nonce($_POST['zlaark_subscription_nonce'], 'zlaark_add_subscription')) {
            $this->handle_add_subscription();
        }
        
        // Handle settings save
        if (isset($_POST['zlaark_settings_nonce']) && wp_verify_nonce($_POST['zlaark_settings_nonce'], 'zlaark_subscription_settings')) {
            $this->save_settings();
        }
    }
    
    /**
     * Handle add subscription
     */
    private function handle_add_subscription() {
        $user_id = intval($_POST['user_id']);
        $product_id = intval($_POST['product_id']);
        $status = sanitize_text_field($_POST['status']);
        
        if (!$user_id || !$product_id) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Please select both a customer and a product.', 'zlaark-subscriptions') . '</p></div>';
            });
            return;
        }
        
        // Create manual subscription
        $db = ZlaarkSubscriptionsDatabase::instance();
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'subscription') {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Invalid subscription product.', 'zlaark-subscriptions') . '</p></div>';
            });
            return;
        }
        
        $subscription_data = array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'status' => $status,
            'trial_price' => $product->get_trial_price(),
            'recurring_price' => $product->get_recurring_price(),
            'billing_interval' => $product->get_billing_interval(),
            'max_cycles' => $product->get_max_length(),
        );
        
        $subscription_id = $db->create_subscription($subscription_data);
        
        if ($subscription_id) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('Subscription created successfully.', 'zlaark-subscriptions') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Failed to create subscription.', 'zlaark-subscriptions') . '</p></div>';
            });
        }
    }
    
    /**
     * Get settings
     *
     * @return array
     */
    private function get_settings() {
        return array(
            'trial_grace_period' => get_option('zlaark_subscriptions_trial_grace_period', 3),
            'failed_payment_retries' => get_option('zlaark_subscriptions_failed_payment_retries', 3),
            'retry_interval' => get_option('zlaark_subscriptions_retry_interval', 2),
            'auto_cancel_after_retries' => get_option('zlaark_subscriptions_auto_cancel_after_retries', 'yes'),
            'email_notifications' => get_option('zlaark_subscriptions_email_notifications', 'yes'),
        );
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $settings = array(
            'trial_grace_period' => intval($_POST['trial_grace_period']),
            'failed_payment_retries' => intval($_POST['failed_payment_retries']),
            'retry_interval' => intval($_POST['retry_interval']),
            'auto_cancel_after_retries' => isset($_POST['auto_cancel_after_retries']) ? 'yes' : 'no',
            'email_notifications' => isset($_POST['email_notifications']) ? 'yes' : 'no',
        );
        
        foreach ($settings as $key => $value) {
            update_option('zlaark_subscriptions_' . $key, $value);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'zlaark-subscriptions') . '</p></div>';
        });
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check for missing dependencies
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>' . __('Zlaark Subscriptions requires WooCommerce to be installed and active.', 'zlaark-subscriptions') . '</p></div>';
        }
    }
    
    /**
     * Plugin action links
     *
     * @param array $links
     * @return array
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=zlaark-subscriptions-settings') . '">' . __('Settings', 'zlaark-subscriptions') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add admin bar menu
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        $wp_admin_bar->add_menu(array(
            'id' => 'zlaark-subscriptions',
            'title' => __('Subscriptions', 'zlaark-subscriptions'),
            'href' => admin_url('admin.php?page=zlaark-subscriptions'),
        ));
    }
}
