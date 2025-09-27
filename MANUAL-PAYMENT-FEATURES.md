# Manual Payment Features Documentation

## Overview

The Zlaark Subscriptions plugin now includes comprehensive manual payment functionality that allows customers to easily pay for failed or expired subscriptions through multiple channels.

## Features Implemented

### 1. My Account Dashboard Integration

- **Pay Now buttons** appear automatically for subscriptions with `failed` or `expired` status
- Buttons are available in both:
  - Subscription list table (compact view)
  - Individual subscription detail pages (detailed view with descriptions)
- Clicking redirects to secure Razorpay payment checkout

### 2. Email Integration

- **Payment failure emails** automatically include "Pay Now" buttons
- Direct links take customers straight to payment page
- Customizable email messaging through admin settings
- Only shows for subscriptions that need manual payment

### 3. Shortcode System

Use the `[zlaark_subscription_pay_button]` shortcode to display manual payment buttons anywhere on your site.

#### Shortcode Examples:

```php
// Basic usage - shows buttons for all failed subscriptions of logged-in user
[zlaark_subscription_pay_button]

// For specific subscription
[zlaark_subscription_pay_button subscription_id="123"]

// Custom button text and styling
[zlaark_subscription_pay_button text="Renew Now" class="custom-button" style="background: blue;"]

// Show amount on button
[zlaark_subscription_pay_button show_amount="yes"]

// Control which statuses show buttons
[zlaark_subscription_pay_button show_for_status="failed,expired"]
```

#### Shortcode Attributes:

- `subscription_id`: Specific subscription ID (optional - shows all if empty)
- `text`: Button text (default: "Pay Now")
- `class`: CSS class for button (default: "woocommerce-button button pay-now")
- `style`: Inline CSS styles
- `show_amount`: Show subscription amount on button ("yes"/"no")
- `show_for_status`: Comma-separated list of statuses to show for

### 4. Admin Settings

Navigate to **Subscriptions > Settings** in your WordPress admin to configure:

#### Manual Payment Settings:
- **Enable Manual Payments**: Turn the feature on/off globally
- **Payment Button Text**: Customize button text across the site
- **Email Message Text**: Customize the message in payment failure emails

### 5. Payment Processing Flow

1. **Customer clicks "Pay Now"** (from email, dashboard, or shortcode)
2. **System creates new WooCommerce order** for the subscription amount
3. **Redirects to Razorpay checkout** with secure payment processing
4. **On successful payment**:
   - Subscription status changes to "active"
   - Failed payment count resets to 0
   - Next payment date calculated
   - Customer receives confirmation email
   - Subscription continues normal billing cycle

## Usage Scenarios

### For Customers:

1. **Email Notification**: Receive payment failure email with direct "Pay Now" button
2. **Dashboard Access**: Login to My Account > Subscriptions to see failed subscriptions with payment buttons
3. **Shortcode Pages**: Visit any page with payment shortcodes to make payments

### For Site Owners:

1. **Embed in pages**: Use shortcodes in pages, posts, or widgets
2. **Custom themes**: Style buttons with CSS classes
3. **Admin control**: Enable/disable and customize messaging from admin panel

## Technical Implementation

### Database Changes:
- Manual payment orders are linked to subscriptions via `_subscription_manual_payment` meta
- Payment success automatically triggers subscription reactivation

### Email Templates:
- Payment buttons only appear for failed/expired subscriptions
- Uses customizable admin text settings
- Responsive design with prominent green buttons

### Security:
- All payment links include WordPress nonces
- User ownership validation on all subscription actions
- Razorpay secure payment processing

## CSS Styling

The plugin includes pre-styled CSS classes:

```css
.pay-now {
    background-color: #28a745;
    color: white;
    font-weight: 600;
}

.zlaark-failed-subscriptions {
    /* Container for multiple subscription buttons */
}

.subscription-payment-item {
    /* Individual subscription payment blocks */
}
```

## Hooks & Filters

### Actions:
- `zlaark_subscription_manual_payment_success`: Fired after successful manual payment
- `zlaark_subscription_status_changed`: Fired when subscription reactivated

### Filters:
- Use standard WooCommerce email filters to customize payment failure emails
- Use WordPress shortcode filters to modify shortcode output

## Best Practices

1. **Test payment flow** with Razorpay test mode before going live
2. **Customize email templates** to match your brand
3. **Use shortcodes strategically** on key pages like account dashboard
4. **Monitor failed payments** through admin reports
5. **Set appropriate retry intervals** to balance automation with manual options

## Troubleshooting

### Common Issues:

1. **Buttons not showing**: Check admin settings - ensure manual payments are enabled
2. **Payment redirect fails**: Verify Razorpay API credentials
3. **Email buttons missing**: Confirm subscription status is 'failed' or 'expired'
4. **Shortcode not working**: Ensure user is logged in for user-specific features

### Debug Steps:

1. Check WordPress error logs
2. Verify Razorpay webhook configuration  
3. Test with different subscription statuses
4. Confirm user permissions and subscription ownership

## Support

For technical support with manual payment features, check:
1. WordPress debug logs
2. Razorpay dashboard for payment status
3. WooCommerce order notes for payment tracking
4. Subscription admin panel for status changes