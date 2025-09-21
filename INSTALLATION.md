# Zlaark Subscriptions - Installation Guide

## Prerequisites

Before installing the Zlaark Subscriptions plugin, ensure your system meets the following requirements:

### System Requirements
- **WordPress**: 6.0 or higher
- **WooCommerce**: 7.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)

### Required Accounts
- **Razorpay Account**: Sign up at [razorpay.com](https://razorpay.com) for payment processing

## Installation Steps

### Step 1: Download and Install Plugin

1. **Download the Plugin**
   - Download the `zlaark-subscriptions.zip` file
   - Or clone from repository: `git clone https://github.com/zlaark/zlaark-subscriptions.git`

2. **Upload to WordPress**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Select the `zlaark-subscriptions.zip` file
   - Click "Install Now"

3. **Activate Plugin**
   - Click "Activate Plugin" after installation
   - The plugin will automatically create necessary database tables

### Step 2: Configure Razorpay Payment Gateway

1. **Get Razorpay API Keys**
   - Log in to your Razorpay Dashboard
   - Go to Settings → API Keys
   - Generate API keys (Key ID and Key Secret)
   - Note down both Test and Live keys

2. **Configure Payment Gateway**
   - Go to WooCommerce → Settings → Payments
   - Find "Razorpay (Subscriptions)" and click "Set up"
   - Configure the following settings:
     - **Enable/Disable**: Check to enable
     - **Title**: "Razorpay" (or your preferred name)
     - **Description**: Payment description for customers
     - **Test Mode**: Enable for testing, disable for live
     - **Key ID**: Your Razorpay Key ID
     - **Key Secret**: Your Razorpay Key Secret
   - Save changes

### Step 3: Set Up Webhooks

1. **Get Webhook URL**
   - Go to Subscriptions → Settings in WordPress admin
   - Copy the webhook URL: `https://yoursite.com/zlaark-subscriptions/webhook/`
   - Copy the webhook secret from settings

2. **Configure Razorpay Webhooks**
   - In Razorpay Dashboard, go to Settings → Webhooks
   - Click "Create New Webhook"
   - Enter your webhook URL
   - Select the following events:
     - `payment.captured`
     - `payment.failed`
     - `subscription.charged`
     - `subscription.halted`
     - `subscription.cancelled`
   - Enter the webhook secret from plugin settings
   - Save the webhook

### Step 4: Configure Plugin Settings

1. **Basic Settings**
   - Go to Subscriptions → Settings
   - Configure:
     - **Trial Grace Period**: Days to allow after trial expiration (default: 3)
     - **Failed Payment Retries**: Number of retry attempts (default: 3)
     - **Retry Interval**: Days between retries (default: 3)
     - **Auto Cancel**: Enable to auto-cancel after max retries
     - **Email Notifications**: Enable email notifications

2. **Email Settings**
   - Configure email templates and recipients
   - Test email functionality

### Step 5: Test Installation

1. **Create Test Subscription Product**
   - Go to Products → Add New
   - Select "Subscription" as product type
   - Configure:
     - **Trial Price**: ₹1 (for testing)
     - **Trial Duration**: 1 day
     - **Recurring Price**: ₹100
     - **Billing Interval**: Monthly
   - Publish the product

2. **Test Purchase Flow**
   - Enable Razorpay test mode
   - Create a test customer account
   - Purchase the subscription product
   - Use Razorpay test card: 4111 1111 1111 1111
   - Verify subscription is created in admin

3. **Test Webhook**
   - Go to Subscriptions → Settings
   - Click "Test Webhook" button
   - Verify webhook is working correctly

## Post-Installation Configuration

### Cron Jobs Setup

The plugin uses WordPress cron for automated tasks. For better reliability, set up server-level cron:

1. **Disable WordPress Cron** (optional but recommended)
   Add to `wp-config.php`:
   ```php
   define('DISABLE_WP_CRON', true);
   ```

2. **Set Up Server Cron**
   Add to your server's crontab:
   ```bash
   */15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
   ```

### Email Configuration

Ensure WordPress can send emails:

1. **Test Email Functionality**
   - Install a plugin like "WP Mail SMTP" if needed
   - Configure SMTP settings for reliable email delivery

2. **Customize Email Templates**
   - Email templates are in `templates/emails/` directory
   - Copy to your theme to customize: `your-theme/zlaark-subscriptions/emails/`

### Security Considerations

1. **SSL Certificate**
   - Ensure your site has a valid SSL certificate
   - Razorpay requires HTTPS for webhooks

2. **File Permissions**
   - Ensure proper file permissions on plugin directory
   - Webhook endpoint should be accessible

## Troubleshooting

### Common Issues

1. **Plugin Activation Fails**
   - Check PHP version (must be 8.0+)
   - Ensure WooCommerce is active
   - Check for plugin conflicts

2. **Database Tables Not Created**
   - Check database permissions
   - Manually run: `wp eval "ZlaarkSubscriptionsInstall::install();"`

3. **Webhooks Not Working**
   - Verify webhook URL is accessible
   - Check webhook secret matches
   - Review webhook logs in admin

4. **Payments Failing**
   - Verify Razorpay API keys
   - Check test/live mode settings
   - Review payment gateway logs

### Debug Mode

Enable debug mode for troubleshooting:

1. **WordPress Debug**
   Add to `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Plugin Debug**
   The plugin logs important events to WordPress debug log

### Getting Help

1. **Check Logs**
   - WordPress debug log: `/wp-content/debug.log`
   - Plugin logs in admin: Subscriptions → Logs

2. **Webhook Logs**
   - Review webhook logs in admin
   - Check Razorpay webhook logs

3. **Support**
   - Check documentation and FAQ
   - Contact support with specific error messages

## Uninstallation

To completely remove the plugin:

1. **Deactivate Plugin**
   - Go to Plugins → Installed Plugins
   - Deactivate "Zlaark Subscriptions"

2. **Delete Plugin Data** (optional)
   - The plugin preserves data by default
   - To remove all data, add this to `wp-config.php` before deletion:
     ```php
     define('ZLAARK_SUBSCRIPTIONS_REMOVE_DATA', true);
     ```

3. **Delete Plugin**
   - Click "Delete" on the plugin
   - This will remove all plugin files and optionally data

## Next Steps

After successful installation:

1. **Create Subscription Products**
   - Set up your subscription offerings
   - Configure pricing and trial periods

2. **Customize Appearance**
   - Style subscription elements to match your theme
   - Customize email templates

3. **Set Up Analytics**
   - Monitor subscription metrics in admin dashboard
   - Set up additional tracking if needed

4. **Go Live**
   - Switch Razorpay to live mode
   - Update webhook URLs if needed
   - Test with real payments

For detailed usage instructions, see the main README.md file.
