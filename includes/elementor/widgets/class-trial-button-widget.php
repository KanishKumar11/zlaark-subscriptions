<?php
/**
 * Trial Button Elementor Widget
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trial Button Widget
 */
class ZlaarkSubscriptionsTrialButtonWidget extends ZlaarkSubscriptionsElementorWidget {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'zlaark-trial-button';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Trial Button', 'zlaark-subscriptions');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-button';
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
            'button_text',
            array(
                'label' => __('Button Text', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Start Free Trial', 'zlaark-subscriptions'),
                'placeholder' => __('Enter button text', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'redirect_url',
            array(
                'label' => __('Redirect URL', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-site.com/thank-you', 'zlaark-subscriptions'),
                'description' => __('Optional URL to redirect after adding to cart', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'show_eligibility',
            array(
                'label' => __('Show Eligibility Check', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'zlaark-subscriptions'),
                'label_off' => __('Hide', 'zlaark-subscriptions'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Display trial eligibility status above the button', 'zlaark-subscriptions'),
            )
        );
        
        $this->end_controls_section();
        
        // Button Style Section
        $this->start_controls_section(
            'button_style_section',
            array(
                'label' => __('Button Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .zlaark-trial-btn',
            )
        );
        
        $this->start_controls_tabs('button_style_tabs');
        
        // Normal State
        $this->start_controls_tab(
            'button_normal',
            array(
                'label' => __('Normal', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'button_text_color',
            array(
                'label' => __('Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'button_background_color',
            array(
                'label' => __('Background Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_tab();
        
        // Hover State
        $this->start_controls_tab(
            'button_hover',
            array(
                'label' => __('Hover', 'zlaark-subscriptions'),
            )
        );
        
        $this->add_control(
            'button_hover_text_color',
            array(
                'label' => __('Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'button_hover_background_color',
            array(
                'label' => __('Background Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .zlaark-trial-btn',
            )
        );
        
        $this->add_control(
            'button_border_radius',
            array(
                'label' => __('Border Radius', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'button_padding',
            array(
                'label' => __('Padding', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'default' => array(
                    'top' => '12',
                    'right' => '24',
                    'bottom' => '12',
                    'left' => '24',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'button_margin',
            array(
                'label' => __('Margin', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'button_width',
            array(
                'label' => __('Width', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => array(
                    'auto' => __('Auto', 'zlaark-subscriptions'),
                    'full' => __('Full Width', 'zlaark-subscriptions'),
                    'custom' => __('Custom', 'zlaark-subscriptions'),
                ),
            )
        );
        
        $this->add_control(
            'button_custom_width',
            array(
                'label' => __('Custom Width', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', '%'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 1000,
                    ),
                    '%' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'condition' => array(
                    'button_width' => 'custom',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-btn' => 'width: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'button_alignment',
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
                'default' => 'center',
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-trial-button-container' => 'text-align: {{VALUE}};',
                ),
                'condition' => array(
                    'button_width!' => 'full',
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
            'text' => $settings['button_text'],
            'class' => 'zlaark-trial-btn elementor-trial-btn'
        );
        
        if (!empty($settings['redirect_url']['url'])) {
            $shortcode_atts['redirect'] = $settings['redirect_url']['url'];
        }
        
        // Apply width class
        if ($settings['button_width'] === 'full') {
            $shortcode_atts['class'] .= ' full-width';
        }
        
        ?>
        <div class="zlaark-widget-container zlaark-trial-button-container">
            <?php if ($settings['show_eligibility'] === 'yes'): ?>
                <div class="trial-eligibility-check">
                    <?php
                    if (class_exists('ZlaarkSubscriptionsShortcodes')) {
                        $shortcodes = ZlaarkSubscriptionsShortcodes::instance();
                        echo $shortcodes->trial_eligibility_shortcode(array(
                            'product_id' => $settings['product_id'],
                            'show_reason' => 'true'
                        ));
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Render trial button shortcode
            if (class_exists('ZlaarkSubscriptionsFrontend')) {
                $frontend = ZlaarkSubscriptionsFrontend::instance();
                echo $frontend->trial_button_shortcode($shortcode_atts);
            } else {
                echo '<p class="zlaark-error">' . __('Zlaark Subscriptions plugin not properly loaded.', 'zlaark-subscriptions') . '</p>';
            }
            ?>
        </div>
        <?php
    }
}
