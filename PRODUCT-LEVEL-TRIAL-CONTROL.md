# Product-Level Trial Control

This document describes the new product-level trial control functionality that allows administrators to enable or disable trial functionality on a per-product basis.

## Overview

The product-level trial control feature provides granular control over which subscription products offer trials. This allows you to:

- Enable trials for some subscription products while disabling them for others
- Maintain a clean user experience where trial-disabled products simply don't show trial options
- Control trial availability without affecting the product's trial configuration (price, duration, etc.)

## Features

### 1. Product Setting

A new checkbox option has been added to the WooCommerce product admin panel under the subscription settings tab:

- **Label**: "Enable Trial for this Product"
- **Description**: "Check this box to enable trial functionality for this subscription product. When unchecked, only the regular subscription option will be available."
- **Location**: Subscription product data panel, above trial price field
- **Default**: Enabled (for backward compatibility)

### 2. Template Logic

The subscription product template (`templates/single-product/add-to-cart/subscription.php`) now:

- Checks if trials are enabled for the specific product before displaying trial buttons
- Only shows the trial button if both the product has trial enabled AND the user is eligible for trials
- Displays only the regular subscription button when trials are disabled for that product

### 3. Shortcode Behavior

The trial button shortcodes have been updated:

- `[trial_button]` and `[zlaark_trial_button]` check if the specified product has trials enabled
- Return empty output (display nothing) when trials are disabled for the product
- Show appropriate error message in admin/debug mode when trials are disabled

### 4. Database Integration

The trial enable/disable setting is:

- Saved as product metadata (`_subscription_trial_enabled`)
- Retrieved efficiently during template rendering
- Properly handled in the product save process
- Included in product validation

### 5. Backward Compatibility

- Trial functionality is enabled by default for existing products
- Migration runs automatically to set the flag for existing subscription products
- Only requires explicit disabling for products where trials should not be available

## Usage

### Admin Panel

1. Go to **Products** → **Edit Product** (for a subscription product)
2. Click on the **Subscription** tab in the product data panel
3. Check or uncheck **"Enable Trial for this Product"**
4. Configure trial settings (price, duration, period) as needed
5. Save the product

**Note**: When "Enable Trial for this Product" is unchecked, the trial configuration fields (price, duration, period) are hidden but preserved.

### Template Usage

The template automatically respects the trial enabled setting. No additional code is required.

### Shortcode Usage

```php
// This will show the trial button only if trials are enabled for product ID 123
[trial_button product_id="123" text="Start Free Trial"]

// This will show nothing if trials are disabled for the product
[zlaark_trial_button product_id="123"]
```

### Programmatic Usage

```php
// Check if trials are enabled for a product
$product = wc_get_product($product_id);
if ($product->is_trial_enabled()) {
    // Trials are enabled for this product
}

// Set trial enabled status
$product->set_trial_enabled(true);  // Enable trials
$product->set_trial_enabled(false); // Disable trials
$product->save();
```

## API Reference

### New Product Methods

#### `is_trial_enabled($context = 'view')`

**Description**: Check if trials are enabled for this product.

**Parameters**:
- `$context` (string): The context for the check ('view' or 'edit')

**Returns**: `bool` - True if trials are enabled, false otherwise

**Example**:
```php
$product = wc_get_product(123);
if ($product->is_trial_enabled()) {
    echo "Trials are enabled for this product";
}
```

#### `set_trial_enabled($enabled)`

**Description**: Set trial enabled status for this product.

**Parameters**:
- `$enabled` (bool): True to enable trials, false to disable

**Example**:
```php
$product = wc_get_product(123);
$product->set_trial_enabled(false); // Disable trials
$product->save();
```

#### `has_trial()` (Updated)

**Description**: Check if product has trial (now includes trial enabled check).

**Returns**: `bool` - True if trials are enabled AND trial configuration exists

**Example**:
```php
$product = wc_get_product(123);
if ($product->has_trial()) {
    echo "Product has active trial functionality";
}
```

### Database Schema

The trial enabled setting is stored as product metadata:

- **Meta Key**: `_subscription_trial_enabled`
- **Meta Value**: `'yes'` or `'no'`
- **Default**: `'yes'` (for backward compatibility)

### Hooks and Filters

The existing WooCommerce and plugin hooks continue to work. The trial enabled check is integrated into the existing flow.

## Migration

### Automatic Migration

When the plugin is updated, an automatic migration runs that:

1. Finds all existing subscription products
2. Sets `_subscription_trial_enabled` to `'yes'` for products that don't have this setting
3. Ensures backward compatibility
4. Logs the migration process (if WP_DEBUG is enabled)

### Manual Migration

If needed, you can manually set the trial enabled status:

```php
// Enable trials for a specific product
update_post_meta($product_id, '_subscription_trial_enabled', 'yes');

// Disable trials for a specific product
update_post_meta($product_id, '_subscription_trial_enabled', 'no');
```

## Testing

Use the provided test script to verify the functionality:

```
http://your-site.com/test-product-level-trial-control.php?test_key=zlaark2025
```

The test script checks:
- Admin panel integration
- Template logic
- Shortcode behavior
- Database integration
- Backward compatibility
- JavaScript functionality

## Troubleshooting

### Trial buttons still showing when disabled

1. Clear all caches (browser, WordPress, CDN)
2. Verify the product has `_subscription_trial_enabled` set to `'no'`
3. Check if the product class has the `is_trial_enabled()` method
4. Run the test script to diagnose issues

### Shortcodes not respecting the setting

1. Ensure you're using the latest version of the plugin
2. Check if the shortcode is using the correct product ID
3. Verify the product is a subscription product
4. Enable WP_DEBUG to see admin debug messages

### Admin fields not showing/hiding

1. Check browser console for JavaScript errors
2. Ensure jQuery is loaded
3. Verify you're on a subscription product edit page
4. Clear browser cache and hard refresh

## Best Practices

1. **Test thoroughly**: Always test the trial enabled/disabled functionality on staging before production
2. **Clear caches**: Clear all caches after changing trial settings
3. **User communication**: If disabling trials for existing products, communicate this to users
4. **Monitor impact**: Track how trial availability affects conversion rates
5. **Consistent UX**: Ensure the user experience is consistent across trial-enabled and trial-disabled products

## Compatibility

- **WordPress**: 5.0+
- **WooCommerce**: 3.0+
- **PHP**: 7.4+
- **Existing Features**: Fully backward compatible
- **Themes**: Works with all WooCommerce-compatible themes
- **Plugins**: Compatible with caching and optimization plugins

## Support

For issues or questions about the product-level trial control feature:

1. Check the troubleshooting section above
2. Run the test script to diagnose issues
3. Enable WP_DEBUG for detailed error logging
4. Check the plugin logs for migration and operation details
