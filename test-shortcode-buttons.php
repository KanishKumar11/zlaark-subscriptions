<?php
/**
 * Test Page for Shortcode Buttons
 * 
 * This creates a test page to demonstrate the new trial and subscription button shortcodes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu for testing
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Test Shortcode Buttons',
        'Test Shortcode Buttons',
        'manage_options',
        'test-shortcode-buttons',
        'zlaark_test_shortcode_buttons_page'
    );
});

function zlaark_test_shortcode_buttons_page() {
    // Get a subscription product for testing
    $subscription_products = get_posts([
        'post_type' => 'product',
        'meta_query' => [
            [
                'key' => '_product_type',
                'value' => 'subscription'
            ]
        ],
        'posts_per_page' => 1
    ]);
    
    $product_id = !empty($subscription_products) ? $subscription_products[0]->ID : '';
    
    ?>
    <div class="wrap">
        <h1>🧪 Shortcode Button Testing</h1>
        
        <div class="notice notice-info">
            <p><strong>New Shortcodes Available!</strong> You can now use individual trial and subscription buttons anywhere on your site.</p>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>📋 Available Shortcodes</h2>
            
            <h3>1. Trial Button Shortcode</h3>
            <p><strong>Usage:</strong> <code>[trial_button]</code></p>
            <p><strong>With parameters:</strong> <code>[trial_button product_id="123" text="Start Free Trial" class="my-trial-btn"]</code></p>
            
            <h3>2. Subscription Button Shortcode</h3>
            <p><strong>Usage:</strong> <code>[subscription_button]</code></p>
            <p><strong>With parameters:</strong> <code>[subscription_button product_id="123" text="Subscribe Now" class="my-sub-btn"]</code></p>
            
            <h3>Parameters Available:</h3>
            <ul>
                <li><strong>product_id:</strong> (optional) Subscription product ID</li>
                <li><strong>text:</strong> (optional) Custom button text</li>
                <li><strong>class:</strong> (optional) Additional CSS classes</li>
                <li><strong>style:</strong> (optional) Inline CSS styles</li>
                <li><strong>redirect:</strong> (optional) Redirect URL after adding to cart</li>
            </ul>
        </div>
        
        <?php if ($product_id): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎯 Live Demo (Product ID: <?php echo $product_id; ?>)</h2>
            
            <h3>Default Buttons:</h3>
            <div style="margin: 20px 0;">
                <?php echo do_shortcode('[trial_button product_id="' . $product_id . '"]'); ?>
                <?php echo do_shortcode('[subscription_button product_id="' . $product_id . '"]'); ?>
            </div>
            
            <h3>Custom Styled Buttons:</h3>
            <div style="margin: 20px 0;">
                <?php echo do_shortcode('[trial_button product_id="' . $product_id . '" text="🎯 Try It FREE!" style="background: linear-gradient(45deg, #ff6b6b, #ee5a24); padding: 15px 30px; font-size: 18px; border-radius: 25px;"]'); ?>
                <?php echo do_shortcode('[subscription_button product_id="' . $product_id . '" text="🚀 Subscribe & Save!" style="background: linear-gradient(45deg, #5f27cd, #341f97); padding: 15px 30px; font-size: 18px; border-radius: 25px;"]'); ?>
            </div>
            
            <h3>Buttons with Custom Classes:</h3>
            <div style="margin: 20px 0;">
                <?php echo do_shortcode('[trial_button product_id="' . $product_id . '" class="button button-primary button-large" text="Large Trial Button"]'); ?>
                <?php echo do_shortcode('[subscription_button product_id="' . $product_id . '" class="button button-secondary button-large" text="Large Subscribe Button"]'); ?>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>📝 Copy & Paste Examples</h2>
            
            <h3>Basic Usage:</h3>
            <textarea readonly style="width: 100%; height: 60px; font-family: monospace; padding: 10px; background: #f5f5f5;">
[trial_button product_id="<?php echo $product_id; ?>"]
[subscription_button product_id="<?php echo $product_id; ?>"]
            </textarea>
            
            <h3>With Custom Text:</h3>
            <textarea readonly style="width: 100%; height: 60px; font-family: monospace; padding: 10px; background: #f5f5f5;">
[trial_button product_id="<?php echo $product_id; ?>" text="Start Your 7-Day Free Trial"]
[subscription_button product_id="<?php echo $product_id; ?>" text="Join Premium Today"]
            </textarea>
            
            <h3>With Custom Styling:</h3>
            <textarea readonly style="width: 100%; height: 80px; font-family: monospace; padding: 10px; background: #f5f5f5;">
[trial_button product_id="<?php echo $product_id; ?>" style="background: #28a745; padding: 20px; font-size: 20px; border-radius: 10px;"]
[subscription_button product_id="<?php echo $product_id; ?>" style="background: #007cba; padding: 20px; font-size: 20px; border-radius: 10px;"]
            </textarea>
        </div>
        
        <?php else: ?>
        <div class="notice notice-warning">
            <p><strong>No subscription products found!</strong> Please create a subscription product first to test the shortcodes.</p>
            <p><a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">Create Subscription Product</a></p>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>💡 Usage Tips</h2>
            <ul>
                <li><strong>Context-Aware:</strong> If you don't specify a product_id, the shortcode will automatically use the current product (great for product pages)</li>
                <li><strong>Trial Eligibility:</strong> The trial button automatically checks if the user is eligible for a trial</li>
                <li><strong>Responsive:</strong> Buttons automatically adapt to mobile screens</li>
                <li><strong>Customizable:</strong> Use CSS classes and inline styles to match your theme</li>
                <li><strong>Form Integration:</strong> Buttons submit proper WooCommerce forms with nonce security</li>
            </ul>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px;">
            <h2>🎨 CSS Classes Available</h2>
            <p>The shortcode buttons come with these default classes you can style:</p>
            <ul>
                <li><code>.zlaark-trial-btn</code> - Trial button</li>
                <li><code>.zlaark-subscription-btn</code> - Subscription button</li>
                <li><code>.zlaark-trial-form</code> - Trial button form wrapper</li>
                <li><code>.zlaark-subscription-form</code> - Subscription button form wrapper</li>
            </ul>
        </div>
        
        <div style="background: #e7f3ff; padding: 20px; margin: 20px 0; border: 1px solid #b3d9ff; border-radius: 8px;">
            <h2>🚀 Where to Use These Shortcodes</h2>
            <ul>
                <li><strong>Posts & Pages:</strong> Add buttons anywhere in your content</li>
                <li><strong>Widgets:</strong> Use in text widgets in sidebars</li>
                <li><strong>Theme Templates:</strong> Add via do_shortcode() in PHP</li>
                <li><strong>Page Builders:</strong> Use in Elementor, Gutenberg, etc.</li>
                <li><strong>Email Templates:</strong> Include in email campaigns (HTML)</li>
                <li><strong>Landing Pages:</strong> Perfect for conversion-focused pages</li>
            </ul>
        </div>
    </div>
    
    <style>
    .wrap textarea {
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .wrap code {
        background: #f1f1f1;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: monospace;
    }
    </style>
    <?php
}
