# Zlaark Subscriptions

A comprehensive WooCommerce subscription plugin with paid trials and Razorpay integration. This plugin functions independently without requiring the official WooCommerce Subscriptions plugin.

## Features

### Core Functionality
- **Custom Subscription Product Type**: Add subscription products with trial periods, recurring billing, and flexible pricing
- **Razorpay Integration**: Seamless payment processing with support for cards, UPI, net banking, and wallets
- **Trial Management**: Automated trial period handling with customizable duration and pricing
- **Subscription Lifecycle**: Complete management of subscription states (active, trial, paused, cancelled, expired)
- **Payment Processing**: Automated recurring payments with retry logic for failed transactions

### Admin Features
- **Comprehensive Dashboard**: Subscription overview with key metrics and analytics
- **Subscription Management**: Full CRUD operations for subscriptions with bulk actions
- **Payment History**: Detailed payment tracking and history for each subscription
- **Webhook Integration**: Secure webhook handling for real-time payment updates
- **Email Notifications**: Customizable email templates for all subscription events
- **Cron Jobs**: Automated background processing for trials, payments, and notifications

### Customer Features
- **My Account Integration**: Dedicated subscription management in WooCommerce My Account
- **Self-Service Options**: Pause, resume, and cancel subscriptions
- **Payment History**: View complete payment history and upcoming payments
- **Content Restriction**: Restrict content based on active subscription status
- **Email Notifications**: Receive updates on subscription status changes

## Requirements

- WordPress 6.0 or higher
- WooCommerce 7.0 or higher
- PHP 8.0 or higher
- Razorpay account for payment processing

## Installation

1. **Download the Plugin**
   - Download the plugin ZIP file
   - Upload to your WordPress site via Admin > Plugins > Add New > Upload Plugin

2. **Activate the Plugin**
   - Activate the plugin through the WordPress admin interface
   - The plugin will automatically create necessary database tables

3. **Configure Razorpay**
   - Go to WooCommerce > Settings > Payments
   - Configure the Razorpay (Subscriptions) payment gateway
   - Add your Razorpay API keys (test/live)

4. **Set Up Webhooks**
   - In your Razorpay dashboard, configure webhooks
   - Use the webhook URL: `https://yoursite.com/zlaark-subscriptions/webhook/`
   - Add the webhook secret from plugin settings

## Configuration

### Basic Settings

Navigate to **Subscriptions > Settings** to configure:

- **Trial Grace Period**: Days to allow after trial expiration
- **Failed Payment Retries**: Number of retry attempts for failed payments
- **Retry Interval**: Days between retry attempts
- **Auto Cancel**: Automatically cancel after max retries
- **Email Notifications**: Enable/disable email notifications

### Razorpay Configuration

1. **API Keys**
   - Get your API keys from Razorpay Dashboard
   - Configure test keys for development
   - Switch to live keys for production

2. **Webhook Setup**
   - Create webhook in Razorpay Dashboard
   - URL: `https://yoursite.com/zlaark-subscriptions/webhook/`
   - Events: `payment.captured`, `payment.failed`, `subscription.charged`, `subscription.halted`, `subscription.cancelled`
   - Secret: Copy from plugin settings

## Creating Subscription Products

1. **Add New Product**
   - Go to Products > Add New
   - Select "Subscription" as product type

2. **Configure Subscription Settings**
   - **Trial Price**: Amount charged during trial (can be 0 for free trial)
   - **Trial Duration**: Length of trial period
   - **Recurring Price**: Amount charged for each billing cycle
   - **Billing Interval**: Weekly, Monthly, or Yearly
   - **Maximum Length**: Optional limit on billing cycles
   - **Sign-up Fee**: One-time fee at subscription start

3. **Product Settings**
   - Subscription products are automatically set as virtual
   - Stock management is disabled for subscription products
   - Standard WooCommerce fields (images, description, etc.) work normally

## Usage

### For Customers

1. **Purchasing Subscriptions**
   - Browse subscription products
   - View trial and recurring pricing details
   - Complete purchase using Razorpay payment gateway

2. **Managing Subscriptions**
   - Access "Subscriptions" tab in My Account
   - View subscription status and payment history
   - Pause, resume, or cancel subscriptions
   - Update payment methods

### For Administrators

1. **Subscription Management**
   - View all subscriptions in admin dashboard
   - Filter by status, customer, or product
   - Perform bulk actions (cancel, pause, resume)
   - View detailed subscription information

2. **Payment Monitoring**
   - Track payment history for each subscription
   - Monitor failed payments and retry attempts
   - View webhook logs for debugging

3. **Customer Support**
   - Manually create subscriptions for customers
   - Update subscription status and billing dates
   - Send custom notifications to subscribers

## Shortcodes

### `[zlaark_subscriptions_manage]`
Display subscription management interface for logged-in users.

### `[zlaark_user_subscriptions]`
Show user's subscriptions with optional parameters:
- `status`: Filter by subscription status
- `limit`: Limit number of subscriptions shown

### `[subscription_required]`
Restrict content to active subscribers:
```
[subscription_required product_id="123" message="Custom message"]
Restricted content here
[/subscription_required]
```

## Hooks and Filters

### Actions
- `zlaark_subscription_created`: Fired when subscription is created
- `zlaark_subscription_status_changed`: Fired when status changes
- `zlaark_subscription_renewed`: Fired on successful renewal
- `zlaark_subscription_payment_failed`: Fired on payment failure

### Filters
- `zlaark_subscriptions_email_recipient`: Modify email recipient
- `zlaark_subscriptions_email_subject`: Modify email subject
- `zlaark_subscriptions_email_message`: Modify email content

## Troubleshooting

### Common Issues

1. **Webhooks Not Working**
   - Verify webhook URL is accessible
   - Check webhook secret matches plugin settings
   - Review webhook logs in admin

2. **Payment Failures**
   - Verify Razorpay API keys are correct
   - Check customer payment method validity
   - Review payment retry settings

3. **Email Notifications Not Sending**
   - Verify email notifications are enabled
   - Check WordPress email configuration
   - Review email templates

### Debug Mode

Enable WordPress debug mode to see detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and documentation:
- Check the plugin documentation
- Review webhook and payment logs
- Contact support with specific error messages

## Changelog

### Version 1.0.0
- Initial release
- Complete subscription management system
- Razorpay payment integration
- Trial period support
- Email notification system
- Admin dashboard and customer interface
- Webhook integration
- Content restriction features

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Zlaark for comprehensive WooCommerce subscription management.
