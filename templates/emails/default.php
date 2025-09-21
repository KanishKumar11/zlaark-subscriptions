<?php
/**
 * Default email template
 *
 * @package ZlaarkSubscriptions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject ?? 'Subscription Notification'); ?></title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #0073aa;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: normal;
        }
        .email-body {
            padding: 30px 20px;
        }
        .email-body h2 {
            color: #0073aa;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .email-body p {
            margin-bottom: 15px;
        }
        .subscription-details {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .subscription-details h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #212529;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0073aa;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #005a87;
        }
        .button-secondary {
            background-color: #6c757d;
        }
        .button-secondary:hover {
            background-color: #5a6268;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .email-footer p {
            margin: 5px 0;
        }
        .email-footer a {
            color: #0073aa;
            text-decoration: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-trial {
            background-color: #cce7ff;
            color: #004085;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-expired {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .price {
            font-size: 18px;
            font-weight: 600;
            color: #0073aa;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .email-header,
            .email-body,
            .email-footer {
                padding: 20px 15px;
            }
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .detail-value {
                margin-top: 5px;
            }
            .button {
                display: block;
                text-align: center;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1><?php echo get_bloginfo('name'); ?></h1>
        </div>
        
        <div class="email-body">
            <h2><?php echo esc_html($subject ?? 'Subscription Notification'); ?></h2>
            
            <p><?php printf(__('Hello %s,', 'zlaark-subscriptions'), esc_html($user->display_name ?? 'Customer')); ?></p>
            
            <p><?php _e('This is a notification regarding your subscription.', 'zlaark-subscriptions'); ?></p>
            
            <?php if (isset($subscription) && isset($product)): ?>
                <div class="subscription-details">
                    <h3><?php _e('Subscription Details', 'zlaark-subscriptions'); ?></h3>
                    
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Product:', 'zlaark-subscriptions'); ?></span>
                        <span class="detail-value"><?php echo esc_html($product->get_name()); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Subscription ID:', 'zlaark-subscriptions'); ?></span>
                        <span class="detail-value">#<?php echo esc_html($subscription->id); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Status:', 'zlaark-subscriptions'); ?></span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo esc_attr($subscription->status); ?>">
                                <?php echo esc_html(ucfirst($subscription->status)); ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label"><?php _e('Amount:', 'zlaark-subscriptions'); ?></span>
                        <span class="detail-value price">₹<?php echo number_format($subscription->recurring_price, 2); ?></span>
                    </div>
                    
                    <?php if (!empty($subscription->next_payment_date)): ?>
                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Next Payment:', 'zlaark-subscriptions'); ?></span>
                            <span class="detail-value"><?php echo date_i18n(get_option('date_format'), strtotime($subscription->next_payment_date)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('subscriptions')); ?>" class="button">
                    <?php _e('Manage Your Subscriptions', 'zlaark-subscriptions'); ?>
                </a>
            </p>
            
            <p><?php _e('If you have any questions, please don\'t hesitate to contact us.', 'zlaark-subscriptions'); ?></p>
            
            <p><?php _e('Thank you for your business!', 'zlaark-subscriptions'); ?></p>
        </div>
        
        <div class="email-footer">
            <p><?php echo get_bloginfo('name'); ?></p>
            <p>
                <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_url(home_url()); ?></a>
            </p>
            <p><?php _e('You are receiving this email because you have an active subscription with us.', 'zlaark-subscriptions'); ?></p>
        </div>
    </div>
</body>
</html>
