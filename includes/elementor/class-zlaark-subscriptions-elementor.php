<?php
/**
 * Elementor Integration for Zlaark Subscriptions
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementor Integration class
 */
class ZlaarkSubscriptionsElementor {
    
    /**
     * Instance
     *
     * @var ZlaarkSubscriptionsElementor
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return ZlaarkSubscriptionsElementor
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
        // Check if Elementor is active
        add_action('plugins_loaded', array($this, 'check_elementor'));
        
        // Initialize Elementor widgets
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        
        // Add widget categories
        add_action('elementor/elements/categories_registered', array($this, 'add_widget_categories'));
        
        // Enqueue Elementor editor scripts
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
    }
    
    /**
     * Check if Elementor is active
     */
    public function check_elementor() {
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Elementor is loaded, proceed with initialization
        $this->init_elementor_integration();
    }
    
    /**
     * Initialize Elementor integration
     */
    private function init_elementor_integration() {
        // Additional initialization if needed
    }
    
    /**
     * Add widget categories
     */
    public function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'zlaark-subscriptions',
            array(
                'title' => __('Zlaark Subscriptions', 'zlaark-subscriptions'),
                'icon' => 'fa fa-credit-card',
            )
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        // Include widget files
        $widget_files = array(
            'trial-button',
            'subscription-button',
            'subscription-pricing',
            'trial-eligibility',
            'user-subscription-status',
            'subscription-details'
        );
        
        foreach ($widget_files as $widget_file) {
            $file_path = ZLAARK_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/elementor/widgets/class-' . $widget_file . '-widget.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Register the widget
                $widget_class = 'ZlaarkSubscriptions' . str_replace('-', '', ucwords($widget_file, '-')) . 'Widget';
                if (class_exists($widget_class)) {
                    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $widget_class());
                }
            }
        }
    }
    
    /**
     * Enqueue editor scripts
     */
    public function enqueue_editor_scripts() {
        wp_enqueue_script(
            'zlaark-subscriptions-elementor-editor',
            ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/js/elementor-editor.js',
            array('jquery'),
            ZLAARK_SUBSCRIPTIONS_VERSION,
            true
        );
        
        wp_enqueue_style(
            'zlaark-subscriptions-elementor-editor',
            ZLAARK_SUBSCRIPTIONS_PLUGIN_URL . 'assets/css/elementor-editor.css',
            array(),
            ZLAARK_SUBSCRIPTIONS_VERSION
        );
    }
}

/**
 * Base Widget Class for Zlaark Subscriptions
 */
abstract class ZlaarkSubscriptionsElementorWidget extends \Elementor\Widget_Base {
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return array('zlaark-subscriptions');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-button';
    }
    
    /**
     * Get subscription products for select control
     */
    protected function get_subscription_products() {
        $products = array();
        
        $subscription_products = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_product_type',
                    'value' => 'subscription'
                )
            ),
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($subscription_products as $product) {
            $products[$product->ID] = $product->post_title . ' (ID: ' . $product->ID . ')';
        }
        
        return $products;
    }
    
    /**
     * Add common controls
     */
    protected function add_product_selection_control() {
        $this->add_control(
            'product_id',
            array(
                'label' => __('Subscription Product', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_subscription_products(),
                'default' => '',
                'description' => __('Select the subscription product to display', 'zlaark-subscriptions'),
            )
        );
    }
    
    /**
     * Add styling controls
     */
    protected function add_styling_controls() {
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Style', 'zlaark-subscriptions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'text_color',
            array(
                'label' => __('Text Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}}' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'typography',
                'selector' => '{{WRAPPER}}',
            )
        );
        
        $this->add_control(
            'background_color',
            array(
                'label' => __('Background Color', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-widget-container' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'border',
                'selector' => '{{WRAPPER}} .zlaark-widget-container',
            )
        );
        
        $this->add_control(
            'border_radius',
            array(
                'label' => __('Border Radius', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-widget-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'padding',
            array(
                'label' => __('Padding', 'zlaark-subscriptions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%', 'em'),
                'selectors' => array(
                    '{{WRAPPER}} .zlaark-widget-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .zlaark-widget-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="zlaark-widget-container">
            <div class="elementor-widget-placeholder">
                <i class="eicon-button" aria-hidden="true"></i>
                <div class="elementor-widget-placeholder-title"><?php echo $this->get_title(); ?></div>
            </div>
        </div>
        <?php
    }
}
