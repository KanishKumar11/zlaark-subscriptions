<?php
/**
 * Trial Eligibility Elementor Widget
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trial Eligibility Widget
 */
class ZlaarkSubscriptionsTrialEligibilityWidget extends ZlaarkSubscriptionsElementorWidget {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'zlaark-trial-eligibility';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Trial Eligibility Check', 'zlaark-subscriptions');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-check-circle';
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
            'show_reason',
            array(
                'label' => __('Show Reason', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Show the reason if user is not eligible for trial', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'custom_eligible_text',
            array(
                'label' => __('Custom Eligible Text', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => __('You are eligible for a free trial!', 'zlaark-subscriptions'),
                'description' => __('Leave empty to use default text', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'custom_not_eligible_text',
            array(
                'label' => __('Custom Not Eligible Text', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => __('Trial not available', 'zlaark-subscriptions'),
                'description' => __('Leave empty to use default text', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'show_icon',
            array(
                'label' => __('Show Status Icon', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'eligibility_style_section',
            array(
                'label' => __('Eligibility Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'alignment',
            array(
                'label' => __('Alignment', 'zlaark-subscriptions'),
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
                    '{{WRAPPER}} .trial-eligibility' => 'text-align: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .trial-eligibility .status-text',
            )
        );
        
        $this->add_control(
            'eligible_color',
            array(
                'label' => __('Eligible Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#28a745',
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility.eligible .status-text' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'not_eligible_color',
            array(
                'label' => __('Not Eligible Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility.not-eligible .status-text' => 'color: {{VALUE}};',
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
                    'size' => 20,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility .status-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_icon' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'icon_spacing',
            array(
                'label' => __('Icon Spacing', 'zlaark-subscriptions'),
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
                    '{{WRAPPER}} .trial-eligibility .status-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'show_icon' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'background_color',
            array(
                'label' => __('Background Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'border',
                'selector' => '{{WRAPPER}} .trial-eligibility',
            )
        );
        
        $this->add_control(
            'border_radius',
            array(
                'label' => __('Border Radius', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    'top' => '10',
                    'right' => '15',
                    'bottom' => '10',
                    'left' => '15',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'margin',
            array(
                'label' => __('Margin', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .trial-eligibility' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        // Reason Style Section
        $this->start_controls_section(
            'reason_style_section',
            array(
                'label' => __('Reason Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_reason' => 'yes',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'reason_typography',
                'selector' => '{{WRAPPER}} .eligibility-reason',
            )
        );
        
        $this->add_control(
            'reason_color',
            array(
                'label' => __('Reason Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => array(
                    '{{WRAPPER}} .eligibility-reason' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'reason_margin',
            array(
                'label' => __('Reason Margin', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'default' => array(
                    'top' => '5',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .eligibility-reason' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        $this->end_controls_section();
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
            'show_reason' => $settings['show_reason'] === 'yes' ? 'true' : 'false'
        );
        
        ?>
        <div class="zlaark-widget-container zlaark-trial-eligibility-container">
            <?php
            // Render trial eligibility shortcode
            if (class_exists('ZlaarkSubscriptionsShortcodes')) {
                $shortcodes = ZlaarkSubscriptionsShortcodes::instance();
                $output = $shortcodes->trial_eligibility_shortcode($shortcode_atts);
                
                // Apply custom text if provided
                if (!empty($settings['custom_eligible_text'])) {
                    $output = str_replace(__('You are eligible for a free trial!', 'zlaark-subscriptions'), esc_html($settings['custom_eligible_text']), $output);
                }
                if (!empty($settings['custom_not_eligible_text'])) {
                    $output = str_replace(__('Trial not available', 'zlaark-subscriptions'), esc_html($settings['custom_not_eligible_text']), $output);
                }
                
                // Hide icons if requested
                if ($settings['show_icon'] !== 'yes') {
                    $output = preg_replace('/<span class="status-icon">[^<]*<\/span>/', '', $output);
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
