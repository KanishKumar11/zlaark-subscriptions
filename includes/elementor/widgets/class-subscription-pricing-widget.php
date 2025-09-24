<?php
/**
 * Subscription Pricing Elementor Widget
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription Pricing Widget
 */
class ZlaarkSubscriptionsSubscriptionPricingWidget extends ZlaarkSubscriptionsElementorWidget {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'zlaark-subscription-pricing';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Subscription Pricing', 'zlaark-subscriptions');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-price-table';
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
        
        $this->add_product_selection_control();
        
        $this->add_control(
            'layout',
            array(
                'label' => __('Layout', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'list',
                'options' => array(
                    'list' => __('List', 'zlaark-subscriptions'),
                    'table' => __('Table', 'zlaark-subscriptions'),
                    'cards' => __('Cards', 'zlaark-subscriptions'),
                ),
                'description' => __('Choose how to display the pricing information', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'show_trial',
            array(
                'label' => __('Show Trial Pricing', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_regular',
            array(
                'label' => __('Show Regular Pricing', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'title',
            array(
                'label' => __('Section Title', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Pricing Options', 'zlaark-subscriptions'),
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
                    '{{WRAPPER}} .pricing-title' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .pricing-title',
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
                    '{{WRAPPER}} .pricing-title' => 'text-align: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'title_margin',
            array(
                'label' => __('Title Margin', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'default' => array(
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '20',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .pricing-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Pricing Style Section
        $this->start_controls_section(
            'pricing_style_section',
            array(
                'label' => __('Pricing Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'pricing_text_color',
            array(
                'label' => __('Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-pricing' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'pricing_typography',
                'selector' => '{{WRAPPER}} .zlaark-subscription-pricing',
            )
        );
        
        $this->add_control(
            'price_color',
            array(
                'label' => __('Price Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-pricing .price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .zlaark-subscription-pricing .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'trial_highlight_color',
            array(
                'label' => __('Trial Highlight Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#28a745',
                'selectors' => array(
                    '{{WRAPPER}} .trial-option, {{WRAPPER}} .trial-card, {{WRAPPER}} .trial-row' => 'border-left-color: {{VALUE}};',
                ),
                'condition' => array(
                    'show_trial' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'card_background',
            array(
                'label' => __('Card Background', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .pricing-card' => 'background-color: {{VALUE}};',
                ),
                'condition' => array(
                    'layout' => 'cards',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .pricing-card',
                'condition' => array(
                    'layout' => 'cards',
                ),
            )
        );
        
        $this->add_control(
            'card_border_radius',
            array(
                'label' => __('Card Border Radius', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .pricing-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'condition' => array(
                    'layout' => 'cards',
                ),
            )
        );
        
        $this->add_control(
            'table_header_background',
            array(
                'label' => __('Table Header Background', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => array(
                    '{{WRAPPER}} .pricing-table th' => 'background-color: {{VALUE}};',
                ),
                'condition' => array(
                    'layout' => 'table',
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
        
        if (empty($settings['product_id'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="zlaark-widget-container"><p>' . __('Please select a subscription product.', 'zlaark-subscriptions') . '</p></div>';
            }
            return;
        }
        
        // Prepare shortcode attributes
        $shortcode_atts = array(
            'product_id' => $settings['product_id'],
            'layout' => $settings['layout'],
            'show_trial' => $settings['show_trial'] === 'yes' ? 'true' : 'false',
            'show_regular' => $settings['show_regular'] === 'yes' ? 'true' : 'false'
        );
        
        ?>
        <div class="zlaark-widget-container zlaark-subscription-pricing-container">
            <?php if ($settings['show_title'] === 'yes' && !empty($settings['title'])): ?>
                <h3 class="pricing-title"><?php echo esc_html($settings['title']); ?></h3>
            <?php endif; ?>
            
            <?php
            // Render subscription pricing shortcode
            if (class_exists('ZlaarkSubscriptionsShortcodes')) {
                $shortcodes = ZlaarkSubscriptionsShortcodes::instance();
                echo $shortcodes->subscription_pricing_shortcode($shortcode_atts);
            } else {
                echo '<p class="zlaark-error">' . __('Zlaark Subscriptions plugin not properly loaded.', 'zlaark-subscriptions') . '</p>';
            }
            ?>
        </div>
        <?php
    }
}
