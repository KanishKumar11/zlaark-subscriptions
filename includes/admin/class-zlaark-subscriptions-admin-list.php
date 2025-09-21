<?php
/**
 * Admin list table for subscriptions
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Subscriptions list table class
 */
class ZlaarkSubscriptionsAdminList extends WP_List_Table {
    
    /**
     * Database instance
     *
     * @var ZlaarkSubscriptionsDatabase
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'subscription',
            'plural'   => 'subscriptions',
            'ajax'     => false
        ));
        
        $this->db = ZlaarkSubscriptionsDatabase::instance();
    }
    
    /**
     * Get columns
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'cb'              => '<input type="checkbox" />',
            'id'              => __('ID', 'zlaark-subscriptions'),
            'customer'        => __('Customer', 'zlaark-subscriptions'),
            'product'         => __('Product', 'zlaark-subscriptions'),
            'status'          => __('Status', 'zlaark-subscriptions'),
            'trial_end'       => __('Trial End', 'zlaark-subscriptions'),
            'next_payment'    => __('Next Payment', 'zlaark-subscriptions'),
            'recurring_price' => __('Recurring Price', 'zlaark-subscriptions'),
            'created'         => __('Created', 'zlaark-subscriptions'),
            'actions'         => __('Actions', 'zlaark-subscriptions'),
        );
    }
    
    /**
     * Get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'id'              => array('id', false),
            'customer'        => array('user_id', false),
            'status'          => array('status', false),
            'trial_end'       => array('trial_end_date', false),
            'next_payment'    => array('next_payment_date', false),
            'recurring_price' => array('recurring_price', false),
            'created'         => array('created_at', true),
        );
    }
    
    /**
     * Get bulk actions
     *
     * @return array
     */
    public function get_bulk_actions() {
        return array(
            'cancel'  => __('Cancel', 'zlaark-subscriptions'),
            'pause'   => __('Pause', 'zlaark-subscriptions'),
            'resume'  => __('Resume', 'zlaark-subscriptions'),
            'delete'  => __('Delete', 'zlaark-subscriptions'),
        );
    }
    
    /**
     * Get views
     *
     * @return array
     */
    protected function get_views() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        $current = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'all';
        
        $status_counts = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table 
            GROUP BY status
        ", ARRAY_A);
        
        $counts = array();
        $total = 0;
        
        foreach ($status_counts as $status) {
            $counts[$status['status']] = $status['count'];
            $total += $status['count'];
        }
        
        $views = array();
        
        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            remove_query_arg('status'),
            $current === 'all' ? 'current' : '',
            __('All', 'zlaark-subscriptions'),
            $total
        );
        
        $statuses = array(
            'active'    => __('Active', 'zlaark-subscriptions'),
            'trial'     => __('Trial', 'zlaark-subscriptions'),
            'paused'    => __('Paused', 'zlaark-subscriptions'),
            'cancelled' => __('Cancelled', 'zlaark-subscriptions'),
            'expired'   => __('Expired', 'zlaark-subscriptions'),
            'failed'    => __('Failed', 'zlaark-subscriptions'),
        );
        
        foreach ($statuses as $status => $label) {
            $count = isset($counts[$status]) ? $counts[$status] : 0;
            if ($count > 0) {
                $views[$status] = sprintf(
                    '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    add_query_arg('status', $status),
                    $current === $status ? 'current' : '',
                    $label,
                    $count
                );
            }
        }
        
        return $views;
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        global $wpdb;
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle status filter
        $status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        // Build query
        $table = $wpdb->prefix . 'zlaark_subscription_orders';
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s OR p.post_title LIKE %s OR s.id = %d)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = intval($search);
        }
        
        if (!empty($status) && $status !== 'all') {
            $where_conditions[] = "s.status = %s";
            $where_values[] = $status;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Get total items
        $total_query = "
            SELECT COUNT(s.id)
            FROM $table s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON s.product_id = p.ID
            $where_clause
        ";
        
        if (!empty($where_values)) {
            $total_items = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        } else {
            $total_items = $wpdb->get_var($total_query);
        }
        
        // Get items
        $items_query = "
            SELECT s.*, u.display_name, u.user_email, p.post_title
            FROM $table s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON s.product_id = p.ID
            $where_clause
            ORDER BY s.$orderby $order
            LIMIT %d OFFSET %d
        ";
        
        $query_values = array_merge($where_values, array($per_page, $offset));
        $items = $wpdb->get_results($wpdb->prepare($items_query, $query_values));
        
        $this->items = $items;
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    /**
     * Column checkbox
     *
     * @param object $item
     * @return string
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="subscription[]" value="%s" />', $item->id);
    }
    
    /**
     * Column ID
     *
     * @param object $item
     * @return string
     */
    public function column_id($item) {
        $edit_url = admin_url('admin.php?page=zlaark-subscriptions-edit&id=' . $item->id);
        return sprintf('<a href="%s"><strong>#%d</strong></a>', $edit_url, $item->id);
    }
    
    /**
     * Column customer
     *
     * @param object $item
     * @return string
     */
    public function column_customer($item) {
        $user_edit_url = admin_url('user-edit.php?user_id=' . $item->user_id);
        return sprintf(
            '<a href="%s">%s</a><br><small>%s</small>',
            $user_edit_url,
            esc_html($item->display_name),
            esc_html($item->user_email)
        );
    }
    
    /**
     * Column product
     *
     * @param object $item
     * @return string
     */
    public function column_product($item) {
        $product_edit_url = admin_url('post.php?post=' . $item->product_id . '&action=edit');
        return sprintf('<a href="%s">%s</a>', $product_edit_url, esc_html($item->post_title));
    }
    
    /**
     * Column status
     *
     * @param object $item
     * @return string
     */
    public function column_status($item) {
        $status_labels = array(
            'active'    => __('Active', 'zlaark-subscriptions'),
            'trial'     => __('Trial', 'zlaark-subscriptions'),
            'paused'    => __('Paused', 'zlaark-subscriptions'),
            'cancelled' => __('Cancelled', 'zlaark-subscriptions'),
            'expired'   => __('Expired', 'zlaark-subscriptions'),
            'failed'    => __('Failed', 'zlaark-subscriptions'),
        );
        
        $label = isset($status_labels[$item->status]) ? $status_labels[$item->status] : $item->status;
        return sprintf('<span class="subscription-status status-%s">%s</span>', $item->status, $label);
    }
    
    /**
     * Column trial end
     *
     * @param object $item
     * @return string
     */
    public function column_trial_end($item) {
        if (empty($item->trial_end_date)) {
            return '—';
        }
        
        $date = new DateTime($item->trial_end_date);
        return $date->format('M j, Y');
    }
    
    /**
     * Column next payment
     *
     * @param object $item
     * @return string
     */
    public function column_next_payment($item) {
        if (empty($item->next_payment_date)) {
            return '—';
        }
        
        $date = new DateTime($item->next_payment_date);
        $now = new DateTime();
        
        if ($date < $now) {
            return '<span class="overdue">' . $date->format('M j, Y') . '</span>';
        }
        
        return $date->format('M j, Y');
    }
    
    /**
     * Column recurring price
     *
     * @param object $item
     * @return string
     */
    public function column_recurring_price($item) {
        return '₹' . number_format($item->recurring_price, 2);
    }
    
    /**
     * Column created
     *
     * @param object $item
     * @return string
     */
    public function column_created($item) {
        $date = new DateTime($item->created_at);
        return $date->format('M j, Y');
    }
    
    /**
     * Column actions
     *
     * @param object $item
     * @return string
     */
    public function column_actions($item) {
        $actions = array();
        
        $edit_url = admin_url('admin.php?page=zlaark-subscriptions-edit&id=' . $item->id);
        $actions['edit'] = sprintf('<a href="%s">%s</a>', $edit_url, __('Edit', 'zlaark-subscriptions'));
        
        if ($item->status === 'active' || $item->status === 'trial') {
            $actions['cancel'] = sprintf(
                '<a href="%s" class="subscription-action" data-action="cancel" data-id="%d">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=zlaark-subscriptions&action=cancel&id=' . $item->id), 'cancel_subscription_' . $item->id),
                $item->id,
                __('Cancel', 'zlaark-subscriptions')
            );
        }
        
        if ($item->status === 'paused') {
            $actions['resume'] = sprintf(
                '<a href="%s" class="subscription-action" data-action="resume" data-id="%d">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=zlaark-subscriptions&action=resume&id=' . $item->id), 'resume_subscription_' . $item->id),
                $item->id,
                __('Resume', 'zlaark-subscriptions')
            );
        }
        
        return implode(' | ', $actions);
    }
    
    /**
     * Default column
     *
     * @param object $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name) {
        return isset($item->$column_name) ? $item->$column_name : '';
    }
}
