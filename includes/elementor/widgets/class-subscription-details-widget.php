<?php
/**
 * Subscription Details Elementor Widget
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Subscription Details Widget
 */
class ZlaarkSubscriptionsSubscriptionDetailsWidget extends ZlaarkSubscriptionsElementorWidget {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'zlaark-subscription-details';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Subscription Details', 'zlaark-subscriptions');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-info-circle';
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
            'show_trial',
            array(
                'label' => __('Show Trial Information', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_billing',
            array(
                'label' => __('Show Billing Information', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'custom_title',
            array(
                'label' => __('Custom Title', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => __('Leave empty to use product name', 'zlaark-subscriptions'),
                'description' => __('Override the default product name title', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'show_product_title',
            array(
                'label' => __('Show Product Title', 'zlaark-subscriptions'),
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
                    'show_product_title' => 'yes',
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
                    '{{WRAPPER}} .zlaark-subscription-details h3' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .zlaark-subscription-details h3',
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
                    '{{WRAPPER}} .zlaark-subscription-details h3' => 'text-align: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Section Headers Style
        $this->start_controls_section(
            'section_headers_style_section',
            array(
                'label' => __('Section Headers Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'section_header_color',
            array(
                'label' => __('Section Header Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#555555',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details h4' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'section_header_typography',
                'selector' => '{{WRAPPER}} .zlaark-subscription-details h4',
            )
        );
        
        $this->add_control(
            'section_spacing',
            array(
                'label' => __('Section Spacing', 'zlaark-subscriptions'),
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
                    'size' => 20,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .trial-details, {{WRAPPER}} .billing-details' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Content Style Section
        $this->start_controls_section(
            'content_style_section',
            array(
                'label' => __('Content Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'content_color',
            array(
                'label' => __('Content Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details ul li' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .zlaark-subscription-details ul li',
            )
        );
        
        $this->add_control(
            'label_color',
            array(
                'label' => __('Label Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details ul li strong' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'price_color',
            array(
                'label' => __('Price Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'list_item_spacing',
            array(
                'label' => __('List Item Spacing', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 30,
                    ),
                    'em' => array(
                        'min' => 0,
                        'max' => 2,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 8,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details ul li' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'list_style',
            array(
                'label' => __('List Style', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => array(
                    'none' => __('None', 'zlaark-subscriptions'),
                    'disc' => __('Disc', 'zlaark-subscriptions'),
                    'circle' => __('Circle', 'zlaark-subscriptions'),
                    'square' => __('Square', 'zlaark-subscriptions'),
                    'decimal' => __('Numbers', 'zlaark-subscriptions'),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details ul' => 'list-style-type: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'list_indent',
            array(
                'label' => __('List Indent', 'zlaark-subscriptions'),
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
                    'size' => 20,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-subscription-details ul' => 'padding-left: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'list_style!' => 'none',
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
            'show_trial' => $settings['show_trial'] === 'yes' ? 'true' : 'false',
            'show_billing' => $settings['show_billing'] === 'yes' ? 'true' : 'false'
        );
        
        ?>
        <div class="zlaark-widget-container zlaark-subscription-details-container">
            <?php
            // Render subscription details shortcode
            if (class_exists('ZlaarkSubscriptionsShortcodes')) {
                $shortcodes = ZlaarkSubscriptionsShortcodes::instance();
                $output = $shortcodes->subscription_details_shortcode($shortcode_atts);
                
                // Apply custom title if provided
                if (!empty($settings['custom_title'])) {
                    $product = wc_get_product($settings['product_id']);
                    if ($product) {
                        $output = str_replace('<h3>' . esc_html($product->get_name()) . '</h3>', '<h3>' . esc_html($settings['custom_title']) . '</h3>', $output);
                    }
                }
                
                // Hide product title if requested
                if ($settings['show_product_title'] !== 'yes') {
                    $output = preg_replace('/<h3[^>]*>.*?<\/h3>/', '', $output);
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
