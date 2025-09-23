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

        // Handle manual initialization actions
        add_action('admin_post_zlaark_force_init', array($this, 'handle_force_initialization'));
        add_action('admin_post_zlaark_clear_cache', array($this, 'handle_clear_cache'));
        
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
            __('System Diagnostics', 'zlaark-subscriptions'),
            __('Diagnostics', 'zlaark-subscriptions'),
            'manage_options',
            'zlaark-subscriptions-diagnostics',
            array($this, 'diagnostics_page')
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

        // Check if this is a trial subscription and handle trial restrictions
        if ($status === 'trial' && method_exists($product, 'has_trial') && $product->has_trial()) {
            $trial_service = ZlaarkSubscriptionsTrialService::instance();
            $trial_eligibility = $trial_service->check_trial_eligibility($user_id, $product_id, true);

            if (!$trial_eligibility['eligible']) {
                add_action('admin_notices', function() use ($trial_eligibility) {
                    echo '<div class="notice notice-error"><p>' . sprintf(
                        __('Cannot create trial subscription: %s', 'zlaark-subscriptions'),
                        $trial_eligibility['message']
                    ) . '</p></div>';
                });
                return;
            }
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
            // If this is a trial subscription, record trial usage
            if ($status === 'trial') {
                $trial_history_id = $db->record_trial_usage($user_id, $product_id, $subscription_id);

                if ($trial_history_id) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Trial subscription created successfully and trial usage recorded.', 'zlaark-subscriptions') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-warning"><p>' . __('Subscription created but failed to record trial usage. Please check manually.', 'zlaark-subscriptions') . '</p></div>';
                    });
                }
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __('Subscription created successfully.', 'zlaark-subscriptions') . '</p></div>';
                });
            }
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

    /**
     * Display diagnostics page
     */
    public function diagnostics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('System Diagnostics', 'zlaark-subscriptions'); ?></h1>

            <div class="notice notice-info">
                <p><?php _e('Use this page to diagnose and fix subscription product initialization issues.', 'zlaark-subscriptions'); ?></p>
            </div>

            <!-- Manual Controls -->
            <div class="card" style="max-width: none;">
                <h2><?php _e('Manual Controls', 'zlaark-subscriptions'); ?></h2>
                <p><?php _e('Use these buttons to manually force initialization or clear caches.', 'zlaark-subscriptions'); ?></p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field('zlaark_force_init', 'zlaark_nonce'); ?>
                    <input type="hidden" name="action" value="zlaark_force_init">
                    <button type="submit" class="button button-primary">🔄 Force Re-Initialize</button>
                </form>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                    <?php wp_nonce_field('zlaark_clear_cache', 'zlaark_nonce'); ?>
                    <input type="hidden" name="action" value="zlaark_clear_cache">
                    <button type="submit" class="button">🗑️ Clear All Caches</button>
                </form>
            </div>

            <!-- System Status -->
            <div class="card" style="max-width: none;">
                <h2><?php _e('System Status', 'zlaark-subscriptions'); ?></h2>
                <?php $this->display_system_status(); ?>
            </div>

            <!-- Product Type Status -->
            <div class="card" style="max-width: none;">
                <h2><?php _e('Product Type Status', 'zlaark-subscriptions'); ?></h2>
                <?php $this->display_product_type_status(); ?>
            </div>

            <!-- Subscription Products Test -->
            <div class="card" style="max-width: none;">
                <h2><?php _e('Subscription Products Test', 'zlaark-subscriptions'); ?></h2>
                <?php $this->display_subscription_products_test(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display system status
     */
    private function display_system_status() {
        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>WordPress Version</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>WooCommerce Version</strong></td>
                    <td><?php echo class_exists('WooCommerce') ? WC()->version : '❌ Not Active'; ?></td>
                </tr>
                <tr>
                    <td><strong>Plugin Version</strong></td>
                    <td><?php echo defined('ZLAARK_SUBSCRIPTIONS_VERSION') ? ZLAARK_SUBSCRIPTIONS_VERSION : 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Debug Mode</strong></td>
                    <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                </tr>
                <tr>
                    <td><strong>Object Cache</strong></td>
                    <td><?php echo wp_using_ext_object_cache() ? '✅ Active' : '❌ Not Active'; ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Display product type status
     */
    private function display_product_type_status() {
        $product_types = wc_get_product_types();
        $subscription_registered = isset($product_types['subscription']);

        // Get detailed debug status
        $debug_status = array();
        if (class_exists('ZlaarkSubscriptionsProductType')) {
            $debug_status = ZlaarkSubscriptionsProductType::debug_registration_status();
        }

        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>Subscription Type Registered</strong></td>
                    <td><?php echo $subscription_registered ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Product Class Available</strong></td>
                    <td><?php echo class_exists('WC_Product_Subscription') ? '✅ Yes' : '❌ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Template File Exists</strong></td>
                    <td>
                        <?php
                        $template_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/single-product/add-to-cart/subscription.php';
                        echo file_exists($template_path) ? '✅ Yes' : '❌ No';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>All Product Types</strong></td>
                    <td><?php echo implode(', ', array_keys($product_types)); ?></td>
                </tr>
                <tr>
                    <td><strong>Filter Callbacks</strong></td>
                    <td>
                        <?php
                        $hooks = [
                            'product_type_selector' => count($GLOBALS['wp_filter']['product_type_selector']->callbacks ?? []),
                            'woocommerce_product_class' => count($GLOBALS['wp_filter']['woocommerce_product_class']->callbacks ?? [])
                        ];

                        foreach ($hooks as $hook => $count) {
                            $status = $count > 0 ? '✅' : '❌';
                            echo "<strong>$hook:</strong> $status $count callbacks<br>";
                        }
                        ?>
                    </td>
                </tr>
                <?php if (!empty($debug_status)): ?>
                <tr>
                    <td><strong>Debug Status</strong></td>
                    <td>
                        <strong>WC Active:</strong> <?php echo $debug_status['woocommerce_active'] ? '✅' : '❌'; ?><br>
                        <strong>wc_get_product_types exists:</strong> <?php echo $debug_status['product_types_function_exists'] ? '✅' : '❌'; ?><br>
                        <strong>Subscription in types:</strong> <?php echo $debug_status['subscription_in_types'] ? '✅' : '❌'; ?><br>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!$subscription_registered): ?>
        <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0;">
            <strong>⚠️ Product Type Not Registered</strong><br>
            The subscription product type is not appearing in WooCommerce's product types. This could be due to:
            <ul>
                <li>Plugin loading order issues</li>
                <li>WooCommerce not being fully loaded when registration occurs</li>
                <li>Caching issues</li>
                <li>Filter hooks not being called at the right time</li>
            </ul>
            Try using the "Force Re-Initialize" button above to fix this issue.
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Display subscription products test
     */
    private function display_subscription_products_test() {
        $subscription_products = wc_get_products([
            'type' => 'subscription',
            'limit' => 5,
            'status' => 'publish'
        ]);

        if (empty($subscription_products)) {
            echo '<p>❌ No subscription products found. <a href="' . admin_url('post-new.php?post_type=product') . '">Create one</a> to test.</p>';
            return;
        }

        echo '<p>✅ Found ' . count($subscription_products) . ' subscription product(s):</p>';

        foreach ($subscription_products as $product) {
            ?>
            <div style="border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 4px;">
                <h4><?php echo $product->get_name(); ?> (ID: <?php echo $product->get_id(); ?>)</h4>

                <table class="widefat" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td><strong>Type</strong></td>
                            <td><?php echo $product->get_type(); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Class</strong></td>
                            <td><?php echo get_class($product); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Is Purchasable</strong></td>
                            <td><?php echo method_exists($product, 'is_purchasable') && $product->is_purchasable() ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Is In Stock</strong></td>
                            <td><?php echo method_exists($product, 'is_in_stock') && $product->is_in_stock() ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Has Price Method</strong></td>
                            <td><?php echo method_exists($product, 'get_price') ? '✅ Yes' : '❌ No'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Product URL</strong></td>
                            <td><a href="<?php echo $product->get_permalink(); ?>" target="_blank">View Product</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }

    /**
     * Handle force initialization
     */
    public function handle_force_initialization() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['zlaark_nonce'], 'zlaark_force_init')) {
            wp_die('Unauthorized');
        }

        // Force re-initialization
        delete_transient('zlaark_subscriptions_init_status');

        // Clear WooCommerce product type cache
        wp_cache_delete('wc_product_types', 'woocommerce');
        delete_transient('wc_product_types');

        // Force product type registration with diagnostics
        $registration_result = ZlaarkSubscriptionsProductType::force_registration_for_diagnostics();

        // Also try the regular method
        if (class_exists('ZlaarkSubscriptionsProductType')) {
            ZlaarkSubscriptionsProductType::instance()->register_product_type_now();
        }

        // Clear any object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Add result to redirect
        $message = $registration_result ? 'force_init_success' : 'force_init_partial';
        wp_redirect(add_query_arg(['page' => 'zlaark-subscriptions-diagnostics', 'message' => $message], admin_url('admin.php')));
        exit;
    }

    /**
     * Handle clear cache
     */
    public function handle_clear_cache() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['zlaark_nonce'], 'zlaark_clear_cache')) {
            wp_die('Unauthorized');
        }

        // Clear all caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Clear transients
        delete_transient('zlaark_subscriptions_init_status');
        delete_transient('wc_product_types');

        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        wp_redirect(add_query_arg(['page' => 'zlaark-subscriptions-diagnostics', 'message' => 'cache_cleared'], admin_url('admin.php')));
        exit;
    }
}
