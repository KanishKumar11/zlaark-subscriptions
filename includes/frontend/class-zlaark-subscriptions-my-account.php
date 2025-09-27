<?php
/**
 * My Account integration for subscriptions
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * My Account class
 */
class ZlaarkSubscriptionsMyAccount {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsMyAccount
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsMyAccount
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
        // Add subscriptions tab to My Account
        add_filter('woocommerce_account_menu_items', array($this, 'add_subscriptions_tab'), 40);
        
        // Add subscriptions endpoint
        add_action('init', array($this, 'add_subscriptions_endpoint'));
        
        // Handle subscriptions endpoint content
        add_action('woocommerce_account_subscriptions_endpoint', array($this, 'subscriptions_endpoint_content'));
        
        // Add individual subscription endpoint
        add_action('init', array($this, 'add_view_subscription_endpoint'));
        add_action('woocommerce_account_view-subscription_endpoint', array($this, 'view_subscription_endpoint_content'));
        
        // Add pay subscription endpoint
        add_action('init', array($this, 'add_pay_subscription_endpoint'));
        add_action('woocommerce_account_pay-subscription_endpoint', array($this, 'pay_subscription_endpoint_content'));
        
        // Handle subscription actions
        add_action('template_redirect', array($this, 'handle_subscription_actions'));
        
        // Add subscription-related order actions
        add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_subscription_order_actions'), 10, 2);
        
        // Modify order details for subscription orders
        add_action('woocommerce_order_details_after_order_table', array($this, 'show_subscription_details_in_order'));
    }
    
    /**
     * Add subscriptions tab to My Account menu
     *
     * @param array $items
     * @return array
     */
    public function add_subscriptions_tab($items) {
        // Insert subscriptions tab after orders
        $new_items = array();
        
        foreach ($items as $key => $item) {
            $new_items[$key] = $item;
            
            if ($key === 'orders') {
                $new_items['subscriptions'] = __('Subscriptions', 'zlaark-subscriptions');
            }
        }
        
        return $new_items;
    }
    
    /**
     * Add subscriptions endpoint
     */
    public function add_subscriptions_endpoint() {
        add_rewrite_endpoint('subscriptions', EP_ROOT | EP_PAGES);
    }
    
    /**
     * Add view subscription endpoint
     */
    public function add_view_subscription_endpoint() {
        add_rewrite_endpoint('view-subscription', EP_ROOT | EP_PAGES);
    }
    
    /**
     * Subscriptions endpoint content
     */
    public function subscriptions_endpoint_content() {
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscriptions = $db->get_user_subscriptions($user_id);
        
        // Handle pagination
        $per_page = 10;
        $current_page = max(1, get_query_var('paged'));
        $offset = ($current_page - 1) * $per_page;
        $total_subscriptions = count($subscriptions);
        $subscriptions = array_slice($subscriptions, $offset, $per_page);
        
        ?>
        <div class="woocommerce-MyAccount-subscriptions">
            <?php if (empty($subscriptions) && $current_page === 1): ?>
                <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
                    <?php _e('You have no subscriptions yet.', 'zlaark-subscriptions'); ?>
                </div>
            <?php else: ?>
                <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
                    <thead>
                        <tr>
                            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-subscription-id">
                                <span class="nobr"><?php _e('Subscription', 'zlaark-subscriptions'); ?></span>
                            </th>
                            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-subscription-status">
                                <span class="nobr"><?php _e('Status', 'zlaark-subscriptions'); ?></span>
                            </th>
                            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-subscription-next-payment">
                                <span class="nobr"><?php _e('Next Payment', 'zlaark-subscriptions'); ?></span>
                            </th>
                            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-subscription-total">
                                <span class="nobr"><?php _e('Total', 'zlaark-subscriptions'); ?></span>
                            </th>
                            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-subscription-actions">
                                <span class="nobr"><?php _e('Actions', 'zlaark-subscriptions'); ?></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <?php $product = wc_get_product($subscription->product_id); ?>
                            <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr($subscription->status); ?> order">
                                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-id" data-title="<?php _e('Subscription', 'zlaark-subscriptions'); ?>">
                                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('view-subscription', $subscription->id)); ?>">
                                        <?php printf(__('#%d', 'zlaark-subscriptions'), $subscription->id); ?>
                                    </a>
                                    <?php if ($product): ?>
                                        <br><small><?php echo esc_html($product->get_name()); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-status" data-title="<?php _e('Status', 'zlaark-subscriptions'); ?>">
                                    <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                                        <?php echo esc_html($this->get_status_label($subscription->status)); ?>
                                    </span>
                                </td>
                                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-next-payment" data-title="<?php _e('Next Payment', 'zlaark-subscriptions'); ?>">
                                    <?php if ($subscription->next_payment_date && in_array($subscription->status, array('active', 'trial'))): ?>
                                        <time datetime="<?php echo esc_attr($subscription->next_payment_date); ?>">
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date))); ?>
                                        </time>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-total" data-title="<?php _e('Total', 'zlaark-subscriptions'); ?>">
                                    <span class="woocommerce-Price-amount amount">
                                        <bdi>
                                            <span class="woocommerce-Price-currencySymbol">₹</span><?php echo number_format($subscription->recurring_price, 2); ?>
                                        </bdi>
                                    </span>
                                    <small class="subscription-interval"><?php echo esc_html($subscription->billing_interval); ?></small>
                                </td>
                                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-actions" data-title="<?php _e('Actions', 'zlaark-subscriptions'); ?>">
                                    <?php $this->render_subscription_actions($subscription); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_subscriptions > $per_page): ?>
                    <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
                        <?php
                        echo paginate_links(array(
                            'base' => esc_url_raw(add_query_arg('paged', '%#%', false)),
                            'format' => '',
                            'current' => $current_page,
                            'total' => ceil($total_subscriptions / $per_page),
                            'prev_text' => __('&larr; Previous', 'zlaark-subscriptions'),
                            'next_text' => __('Next &rarr;', 'zlaark-subscriptions'),
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * View subscription endpoint content
     */
    public function view_subscription_endpoint_content() {
        $subscription_id = get_query_var('view-subscription');
        
        if (empty($subscription_id)) {
            return;
        }
        
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription($subscription_id);
        
        if (!$subscription || $subscription->user_id != $user_id) {
            wc_print_notice(__('Subscription not found.', 'zlaark-subscriptions'), 'error');
            return;
        }
        
        $product = wc_get_product($subscription->product_id);
        $order = wc_get_order($subscription->order_id);
        $payment_history = $db->get_payment_history($subscription_id);
        
        ?>
        <div class="woocommerce-MyAccount-subscription-details">
            <h2><?php printf(__('Subscription #%d', 'zlaark-subscriptions'), $subscription->id); ?></h2>
            
            <div class="subscription-overview">
                <div class="subscription-info">
                    <h3><?php _e('Subscription Details', 'zlaark-subscriptions'); ?></h3>
                    <table class="woocommerce-table woocommerce-table--subscription-details shop_table subscription_details">
                        <tbody>
                            <tr>
                                <th><?php _e('Status:', 'zlaark-subscriptions'); ?></th>
                                <td>
                                    <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                                        <?php echo esc_html($this->get_status_label($subscription->status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Product:', 'zlaark-subscriptions'); ?></th>
                                <td>
                                    <?php if ($product): ?>
                                        <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                            <?php echo esc_html($product->get_name()); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php _e('Product not found', 'zlaark-subscriptions'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Recurring Amount:', 'zlaark-subscriptions'); ?></th>
                                <td>₹<?php echo number_format($subscription->recurring_price, 2); ?> <?php echo esc_html($subscription->billing_interval); ?></td>
                            </tr>
                            <?php if ($subscription->trial_end_date): ?>
                                <tr>
                                    <th><?php _e('Trial End Date:', 'zlaark-subscriptions'); ?></th>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->trial_end_date)); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($subscription->next_payment_date && in_array($subscription->status, array('active', 'trial'))): ?>
                                <tr>
                                    <th><?php _e('Next Payment Date:', 'zlaark-subscriptions'); ?></th>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?php _e('Start Date:', 'zlaark-subscriptions'); ?></th>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->created_at)); ?></td>
                            </tr>
                            <?php if ($subscription->max_cycles): ?>
                                <tr>
                                    <th><?php _e('Billing Cycles:', 'zlaark-subscriptions'); ?></th>
                                    <td><?php printf(__('%d of %d', 'zlaark-subscriptions'), $subscription->current_cycle, $subscription->max_cycles); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="subscription-actions">
                    <h3><?php _e('Subscription Actions', 'zlaark-subscriptions'); ?></h3>
                    <?php $this->render_detailed_subscription_actions($subscription); ?>
                </div>
            </div>
            
            <?php if (!empty($payment_history)): ?>
                <div class="subscription-payment-history">
                    <h3><?php _e('Payment History', 'zlaark-subscriptions'); ?></h3>
                    <table class="woocommerce-table woocommerce-table--payment-history shop_table payment_history">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'zlaark-subscriptions'); ?></th>
                                <th><?php _e('Amount', 'zlaark-subscriptions'); ?></th>
                                <th><?php _e('Status', 'zlaark-subscriptions'); ?></th>
                                <th><?php _e('Payment Method', 'zlaark-subscriptions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_history as $payment): ?>
                                <tr>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($payment->payment_date)); ?></td>
                                    <td>₹<?php echo number_format($payment->amount, 2); ?></td>
                                    <td>
                                        <span class="payment-status status-<?php echo esc_attr($payment->status); ?>">
                                            <?php echo esc_html(ucfirst($payment->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(ucfirst($payment->payment_method)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
                <div class="subscription-related-orders">
                    <h3><?php _e('Related Orders', 'zlaark-subscriptions'); ?></h3>
                    <p>
                        <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                            <?php printf(__('View Initial Order #%d', 'zlaark-subscriptions'), $order->get_id()); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Handle subscription actions
     */
    public function handle_subscription_actions() {
        if (!is_account_page() || !is_user_logged_in()) {
            return;
        }
        
        $action = isset($_GET['subscription_action']) ? sanitize_text_field($_GET['subscription_action']) : '';
        $subscription_id = isset($_GET['subscription_id']) ? intval($_GET['subscription_id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        
        if (empty($action) || empty($subscription_id) || !wp_verify_nonce($nonce, 'subscription_action_' . $action . '_' . $subscription_id)) {
            return;
        }
        
        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription($subscription_id);
        
        if (!$subscription || $subscription->user_id != $user_id) {
            wc_add_notice(__('Subscription not found.', 'zlaark-subscriptions'), 'error');
            return;
        }
        
        $manager = ZlaarkSubscriptionsManager::instance();
        
        switch ($action) {
            case 'cancel':
                if (in_array($subscription->status, array('active', 'trial'))) {
                    $result = $manager->update_subscription_status($subscription_id, 'cancelled', 'Cancelled by customer');
                    if ($result) {
                        wc_add_notice(__('Subscription cancelled successfully.', 'zlaark-subscriptions'), 'success');
                    } else {
                        wc_add_notice(__('Failed to cancel subscription.', 'zlaark-subscriptions'), 'error');
                    }
                }
                break;
                
            case 'pause':
                if (in_array($subscription->status, array('active', 'trial'))) {
                    $result = $manager->update_subscription_status($subscription_id, 'paused', 'Paused by customer');
                    if ($result) {
                        wc_add_notice(__('Subscription paused successfully.', 'zlaark-subscriptions'), 'success');
                    } else {
                        wc_add_notice(__('Failed to pause subscription.', 'zlaark-subscriptions'), 'error');
                    }
                }
                break;
                
            case 'resume':
                if ($subscription->status === 'paused') {
                    $result = $manager->update_subscription_status($subscription_id, 'active', 'Resumed by customer');
                    if ($result) {
                        wc_add_notice(__('Subscription resumed successfully.', 'zlaark-subscriptions'), 'success');
                    } else {
                        wc_add_notice(__('Failed to resume subscription.', 'zlaark-subscriptions'), 'error');
                    }
                }
                break;
                
            case 'pay_now':
                if (in_array($subscription->status, array('failed', 'expired'))) {
                    // Create manual payment order
                    $payment_url = $manager->create_manual_payment_order($subscription_id);
                    if ($payment_url) {
                        wp_redirect($payment_url);
                        exit;
                    } else {
                        wc_add_notice(__('Failed to create payment order. Please try again.', 'zlaark-subscriptions'), 'error');
                    }
                }
                break;
        }
        
        // Redirect to remove query parameters
        wp_redirect(wc_get_account_endpoint_url('subscriptions'));
        exit;
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
        
        // View action
        $actions['view'] = array(
            'url' => wc_get_account_endpoint_url('view-subscription', $subscription->id),
            'name' => __('View', 'zlaark-subscriptions'),
            'class' => 'woocommerce-button button view'
        );
        
        // Status-specific actions
        if (in_array($subscription->status, array('active', 'trial'))) {
            $actions['pause'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'pause',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_pause_' . $subscription->id
                ),
                'name' => __('Pause', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button pause'
            );
            
            $actions['cancel'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'cancel',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_cancel_' . $subscription->id
                ),
                'name' => __('Cancel', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button cancel'
            );
        }
        
        if ($subscription->status === 'paused') {
            $actions['resume'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'resume',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_resume_' . $subscription->id
                ),
                'name' => __('Resume', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button resume'
            );
        }
        
        // Pay Now action for failed/expired subscriptions
        if (in_array($subscription->status, array('failed', 'expired'))) {
            $actions['pay_now'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'pay_now',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_pay_now_' . $subscription->id
                ),
                'name' => __('Pay Now', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button pay-now'
            );
        }
        
        foreach ($actions as $key => $action) {
            echo '<a href="' . esc_url($action['url']) . '" class="' . esc_attr($action['class']) . '">' . esc_html($action['name']) . '</a>';
            if ($key !== array_key_last($actions)) {
                echo ' ';
            }
        }
    }
    
    /**
     * Render detailed subscription actions
     *
     * @param object $subscription
     */
    private function render_detailed_subscription_actions($subscription) {
        $actions = array();
        
        if (in_array($subscription->status, array('active', 'trial'))) {
            $actions['pause'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'pause',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_pause_' . $subscription->id
                ),
                'name' => __('Pause Subscription', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button pause',
                'description' => __('Temporarily pause your subscription. You can resume it later.', 'zlaark-subscriptions')
            );
            
            $actions['cancel'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'cancel',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_cancel_' . $subscription->id
                ),
                'name' => __('Cancel Subscription', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button cancel',
                'description' => __('Permanently cancel your subscription. This action cannot be undone.', 'zlaark-subscriptions')
            );
        }
        
        if ($subscription->status === 'paused') {
            $actions['resume'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'resume',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_resume_' . $subscription->id
                ),
                'name' => __('Resume Subscription', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button resume',
                'description' => __('Resume your paused subscription and continue billing.', 'zlaark-subscriptions')
            );
        }
        
        // Pay Now action for failed/expired subscriptions
        if (in_array($subscription->status, array('failed', 'expired'))) {
            $actions['pay_now'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(array(
                        'subscription_action' => 'pay_now',
                        'subscription_id' => $subscription->id
                    )),
                    'subscription_action_pay_now_' . $subscription->id
                ),
                'name' => __('Pay Now', 'zlaark-subscriptions'),
                'class' => 'woocommerce-button button pay-now',
                'description' => __('Make a manual payment to reactivate your subscription.', 'zlaark-subscriptions')
            );
        }
        
        if (empty($actions)) {
            echo '<p>' . __('No actions available for this subscription.', 'zlaark-subscriptions') . '</p>';
            return;
        }
        
        foreach ($actions as $action) {
            echo '<div class="subscription-action-item">';
            echo '<a href="' . esc_url($action['url']) . '" class="' . esc_attr($action['class']) . '">' . esc_html($action['name']) . '</a>';
            if (!empty($action['description'])) {
                echo '<p class="description">' . esc_html($action['description']) . '</p>';
            }
            echo '</div>';
        }
    }
    
    /**
     * Add subscription order actions
     *
     * @param array $actions
     * @param WC_Order $order
     * @return array
     */
    public function add_subscription_order_actions($actions, $order) {
        // Check if order has subscription
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription_by_order($order->get_id());
        
        if ($subscription) {
            $actions['view-subscription'] = array(
                'url' => wc_get_account_endpoint_url('view-subscription', $subscription->id),
                'name' => __('View Subscription', 'zlaark-subscriptions')
            );
        }
        
        return $actions;
    }
    
    /**
     * Show subscription details in order
     *
     * @param WC_Order $order
     */
    public function show_subscription_details_in_order($order) {
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription_by_order($order->get_id());
        
        if (!$subscription) {
            return;
        }
        
        ?>
        <section class="woocommerce-order-subscription-details">
            <h2 class="woocommerce-order-subscription-details__title"><?php _e('Related Subscription', 'zlaark-subscriptions'); ?></h2>
            <table class="woocommerce-table woocommerce-table--order-subscription-details shop_table order_subscription_details">
                <tbody>
                    <tr>
                        <th><?php _e('Subscription:', 'zlaark-subscriptions'); ?></th>
                        <td>
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('view-subscription', $subscription->id)); ?>">
                                <?php printf(__('#%d', 'zlaark-subscriptions'), $subscription->id); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Status:', 'zlaark-subscriptions'); ?></th>
                        <td>
                            <span class="subscription-status status-<?php echo esc_attr($subscription->status); ?>">
                                <?php echo esc_html($this->get_status_label($subscription->status)); ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($subscription->next_payment_date && in_array($subscription->status, array('active', 'trial'))): ?>
                        <tr>
                            <th><?php _e('Next Payment:', 'zlaark-subscriptions'); ?></th>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        <?php
    }

    /**
     * Add pay subscription endpoint
     */
    public function add_pay_subscription_endpoint() {
        add_rewrite_endpoint('pay-subscription', EP_ROOT | EP_PAGES);
    }

    /**
     * Pay subscription endpoint content
     *
     * @param int $subscription_id
     */
    public function pay_subscription_endpoint_content($subscription_id) {
        if (!$subscription_id) {
            wc_print_notice(__('Invalid subscription ID.', 'zlaark-subscriptions'), 'error');
            return;
        }

        $user_id = get_current_user_id();
        $db = ZlaarkSubscriptionsDatabase::instance();
        $subscription = $db->get_subscription($subscription_id);

        if (!$subscription || $subscription->user_id != $user_id) {
            wc_print_notice(__('Subscription not found.', 'zlaark-subscriptions'), 'error');
            return;
        }

        if (!in_array($subscription->status, array('failed', 'expired'))) {
            wc_print_notice(__('This subscription does not require manual payment.', 'zlaark-subscriptions'), 'error');
            return;
        }

        // Check if manual payments are enabled
        if (get_option('zlaark_subscriptions_enable_manual_payments', 'yes') !== 'yes') {
            wc_print_notice(__('Manual payments are currently disabled.', 'zlaark-subscriptions'), 'error');
            return;
        }

        $product = wc_get_product($subscription->product_id);
        if (!$product) {
            wc_print_notice(__('Product not found.', 'zlaark-subscriptions'), 'error');
            return;
        }

        // Create payment order
        $manager = ZlaarkSubscriptionsManager::instance();
        $payment_url = $manager->create_manual_payment_order($subscription_id);

        if ($payment_url) {
            wp_redirect($payment_url);
            exit;
        } else {
            wc_print_notice(__('Failed to create payment order. Please try again.', 'zlaark-subscriptions'), 'error');
        }

        // Show payment form as fallback
        $this->render_manual_payment_form($subscription, $product);
    }

    /**
     * Render manual payment form
     *
     * @param object $subscription
     * @param WC_Product $product
     */
    private function render_manual_payment_form($subscription, $product) {
        ?>
        <div class="woocommerce-subscription-payment">
            <h2><?php printf(__('Manual Payment for %s', 'zlaark-subscriptions'), esc_html($product->get_name())); ?></h2>
            
            <div class="subscription-payment-details">
                <table class="woocommerce-table shop_table subscription_details">
                    <tbody>
                        <tr>
                            <th><?php _e('Subscription:', 'zlaark-subscriptions'); ?></th>
                            <td><?php echo esc_html($product->get_name()); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Amount:', 'zlaark-subscriptions'); ?></th>
                            <td class="amount">₹<?php echo number_format($subscription->recurring_price, 2); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Status:', 'zlaark-subscriptions'); ?></th>
                            <td><?php echo esc_html($this->get_status_label($subscription->status)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="payment-actions">
                <p><?php echo esc_html(get_option('zlaark_subscriptions_manual_payment_email_text', __('Your subscription payment has failed. Complete the payment below to reactivate your subscription immediately.', 'zlaark-subscriptions'))); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('manual_subscription_payment', 'manual_payment_nonce'); ?>
                    <input type="hidden" name="subscription_id" value="<?php echo esc_attr($subscription->id); ?>" />
                    <input type="hidden" name="action" value="create_manual_payment" />
                    
                    <p>
                        <button type="submit" class="woocommerce-button button pay-button">
                            <?php echo esc_html(get_option('zlaark_subscriptions_manual_payment_button_text', __('Pay Now', 'zlaark-subscriptions'))); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}
