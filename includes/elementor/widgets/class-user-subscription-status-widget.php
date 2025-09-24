<?php
/**
 * User Subscription Status Elementor Widget
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Subscription Status Widget
 */
class ZlaarkSubscriptionsUserSubscriptionStatusWidget extends ZlaarkSubscriptionsElementorWidget {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'zlaark-user-subscription-status';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('User Subscription Status', 'zlaark-subscriptions');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-user-circle-o';
    }
    
    /**
     * Register widget controls
     */
    protected function _register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'display_mode',
            array(
                'label' => __('Display Mode', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'specific',
                'options' => array(
                    'specific' => __('Specific Product', 'zlaark-subscriptions'),
                    'all' => __('All Subscriptions', 'zlaark-subscriptions'),
                ),
                'description' => __('Show status for a specific product or all user subscriptions', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_product_selection_control();
        
        $this->add_control(
            'product_id_condition',
            array(
                'condition' => array(
                    'display_mode' => 'specific',
                ),
            )
        );
        
        $this->add_control(
            'show_details',
            array(
                'label' => __('Show Details', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Show detailed subscription information (dates, payment info)', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'title',
            array(
                'label' => __('Section Title', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Your Subscription Status', 'zlaark-subscriptions'),
                'placeholder' => __('Enter section title', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'show_title',
            array(
                'label' => __('Show Title', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'not_logged_in_message',
            array(
                'label' => __('Not Logged In Message', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Please log in to view your subscription status.', 'zlaark-subscriptions'),
                'description' => __('Message to show when user is not logged in', 'zlaark-subscriptions'),
            )
        );
        
        $this->end_controls_section();
        
        // Title Style Section
        $this->start_controls_section(
            'title_style_section',
            array(
                'label' => __('Title Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_title' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label' => __('Title Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status-title' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .subscription-status-title',
            )
        );
        
        $this->add_control(
            'title_alignment',
            array(
                'label' => __('Title Alignment', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'zlaark-subscriptions'),
                        'icon' => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'zlaark-subscriptions'),
                        'icon' => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'zlaark-subscriptions'),
                        'icon' => 'eicon-text-align-right',
                    ),
                ),
                'default' => 'left',
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status-title' => 'text-align: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Status Style Section
        $this->start_controls_section(
            'status_style_section',
            array(
                'label' => __('Status Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'status_typography',
                'selector' => '{{WRAPPER}} .subscription-status .status-text',
            )
        );
        
        $this->add_control(
            'active_color',
            array(
                'label' => __('Active Status Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#28a745',
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status.status-active .status-text' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'paused_color',
            array(
                'label' => __('Paused Status Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffc107',
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status.status-paused .status-text' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'cancelled_color',
            array(
                'label' => __('Cancelled Status Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status.status-cancelled .status-text' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'expired_color',
            array(
                'label' => __('Expired Status Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#6c757d',
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status.status-expired .status-text' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'icon_size',
            array(
                'label' => __('Icon Size', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range' => array(
                    'px' => array(
                        'min' => 10,
                        'max' => 50,
                    ),
                    'em' => array(
                        'min' => 0.5,
                        'max' => 3,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 18,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status .status-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'item_spacing',
            array(
                'label' => __('Item Spacing', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                    'em' => array(
                        'min' => 0,
                        'max' => 3,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 15,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .subscription-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'display_mode' => 'all',
                ),
            )
        );
        
        $this->add_control(
            'background_color',
            array(
                'label' => __('Background Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status, {{WRAPPER}} .subscription-item' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'border',
                'selector' => '{{WRAPPER}} .subscription-status, {{WRAPPER}} .subscription-item',
            )
        );
        
        $this->add_control(
            'border_radius',
            array(
                'label' => __('Border Radius', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status, {{WRAPPER}} .subscription-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'padding',
            array(
                'label' => __('Padding', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'default' => array(
                    'top' => '15',
                    'right' => '20',
                    'bottom' => '15',
                    'left' => '20',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .subscription-status, {{WRAPPER}} .subscription-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Add general styling controls
        $this->add_styling_controls();
    }
    
    /**
     * Render widget output on the frontend
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if ($settings['display_mode'] === 'specific' && empty($settings['product_id'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="zlaark-widget-container"><p>' . __('Please select a subscription product for specific mode.', 'zlaark-subscriptions') . '</p></div>';
            }
            return;
        }
        
        // Prepare shortcode attributes
        $shortcode_atts = array(
            'show_details' => $settings['show_details'] === 'yes' ? 'true' : 'false'
        );
        
        if ($settings['display_mode'] === 'specific') {
            $shortcode_atts['product_id'] = $settings['product_id'];
        }
        
        ?>
        <div class="zlaark-widget-container zlaark-user-subscription-status-container">
            <?php if ($settings['show_title'] === 'yes' && !empty($settings['title'])): ?>
                <h3 class="subscription-status-title"><?php echo esc_html($settings['title']); ?></h3>
            <?php endif; ?>
            
            <?php
            // Render user subscription status shortcode
            if (class_exists('ZlaarkSubscriptionsShortcodes')) {
                $shortcodes = ZlaarkSubscriptionsShortcodes::instance();
                $output = $shortcodes->user_subscription_status_shortcode($shortcode_atts);
                
                // Apply custom not logged in message if provided
                if (!empty($settings['not_logged_in_message'])) {
                    $output = str_replace(__('Please log in to view subscription status.', 'zlaark-subscriptions'), esc_html($settings['not_logged_in_message']), $output);
                }
                
                echo $output;
            } else {
                echo '<p class="zlaark-error">' . __('Zlaark Subscriptions plugin not properly loaded.', 'zlaark-subscriptions') . '</p>';
            }
            ?>
        </div>
        <?php
    }
}
