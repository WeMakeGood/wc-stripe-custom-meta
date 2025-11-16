# Installation & Setup Guide

## Status

✅ **Plugin is installed and activated**

Location: `wp-content/plugins/wc-stripe-custom-meta/`
Status: Active
Version: 1.1.0

## Requirements Met

- ✅ WordPress 5.9+ (You have: Current version)
- ✅ PHP 7.4+ (Required for plugin functionality)
- ✅ WooCommerce 5.0+ (You have: 10.3.4)
- ✅ WooCommerce Stripe Payment Gateway 7.0+ (You have: 10.0.1)
- ✅ WooCommerce Subscriptions 6.0+ (Optional - automatic subscription metadata support when active)

## Quick Start

### 1. Access Plugin Settings

Navigate to:
```
WordPress Admin → WooCommerce → Stripe Metadata
```

This opens the standalone configuration page for the plugin.

### 2. Configure Metadata

The section contains four subsections:

#### A. Multi-Product Handling Method
Choose how to handle products in multi-item orders:
- **Delimited Values**: Combines all product data with commas
  - Better for: Simple reporting, fewer metadata keys
  - Example: `product_sku: "SKU1,SKU2,SKU3"`

- **Numbered Keys**: Creates separate keys for each product
  - Better for: Detailed analysis, product-level tracking
  - Example: `product_1_sku: "SKU1"`, `product_2_sku: "SKU2"`

#### B. Cart & Order Metadata
Select order-level fields to include:
- Order ID
- Order Total
- Order Subtotal
- Order Tax
- Order Shipping
- Number of Items
- Customer Email
- Customer Phone
- Billing Country
- Shipping Country
- Payment Method
- Shipping Method

#### C. User Metadata
Select customer profile fields (if any exist):
- Any custom user metadata fields from plugins
- WordPress user meta fields
- Custom profile fields

#### D. Product Metadata
Select product-level fields (if any exist):
- Custom product attributes
- Plugin-specific product data
- Any additional product metadata

#### E. Subscription Metadata (WooCommerce Subscriptions)
*This section only appears if WooCommerce Subscriptions is installed and active.*

If you have WooCommerce Subscriptions enabled, you can select subscription fields to track:
- Subscription ID, Status, and Order Type
- Billing details (period, interval, total)
- Payment dates (next payment, trial end, start/end dates)
- Payment count and parent order references

Subscription metadata is automatically included for:
- Parent orders (initial subscription purchase)
- Renewal payments (automatic recurring charges)
- Switch orders (subscription modifications)
- Resubscribe orders (reactivated subscriptions)

For complete subscription setup, see [SUBSCRIPTION_GUIDE.md](SUBSCRIPTION_GUIDE.md).

#### F. Static Metadata Pairs
Add static key-value pairs that always send to Stripe:
- Click "Add Metadata Pair"
- Enter key (max 40 characters)
- Enter value (max 500 characters)
- Repeat as needed
- Click "Remove" to delete entries

### 3. Save Settings

Click **"Save Changes"** to store your configuration.

Settings are saved immediately and persisted in the WordPress database.

### 4. Verify in Stripe

When customers complete checkout:

1. Log into **Stripe Dashboard**
2. Go to **Payments** → **Payment Intents** (or **Events**)
3. Find your test payment
4. Click to view details
5. Scroll to **Metadata** section
6. Verify your configured fields appear

## Troubleshooting

### Issue: Settings page doesn't show new section

**Solution:**
1. Verify WooCommerce is active: Settings page should load
2. Verify Stripe Gateway is active: Check Plugins page
3. Clear browser cache and do hard refresh (Cmd+Shift+R or Ctrl+Shift+R)
4. Check user has `manage_woocommerce` capability (admins and shop managers should)

### Issue: "WooCommerce has detected incompatible plugins"

**Status:** ✅ FIXED in v1.0.0+

This message appeared in earlier versions. It's now resolved with proper "Requires Plugins" declaration.

**If still seeing after update:**
- Deactivate plugin: Plugins → Deactivate
- Reactivate plugin: Plugins → Activate
- Refresh page

### Issue: No metadata fields showing for User or Product sections

**This is normal!** It means:
- No custom user metadata exists yet in your database
- No custom product metadata exists yet in your database

**Solution:** Use the **Static Metadata Pairs** section instead to add custom metadata.

### Issue: Metadata not appearing in Stripe

**Check:**
1. Are metadata fields selected in settings? (Go back and verify checkboxes are checked)
2. Do the selected fields have values in the order?
3. Are you looking at the right Stripe account? (Test vs Live)
4. Is there a Stripe API error? (Check WordPress debug log)

**Debug:**
```bash
# Enable WordPress debug logging
# Edit wp-config.php and add:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

# Then check:
# tail -50 wp-content/debug.log | grep stripe
```

## Plugin Features

### Automatic Field Discovery
The plugin automatically discovers available metadata:
- **Cart Metadata**: 12 predefined fields
- **User Metadata**: Scans database for existing user meta
- **Product Metadata**: Scans database for existing product meta

This happens only during settings page load, not during checkout.

### Stripe Compliance
The plugin automatically ensures metadata meets Stripe requirements:

| Limit | Constraint |
|-------|-----------|
| Key-Value Pairs | Max 50 |
| Key Length | Max 40 characters |
| Value Length | Max 500 characters |
| Special Characters | No square brackets |

### Security
- All input is sanitized and validated
- Settings are stored securely in WordPress options
- Sensitive data is never logged or exposed
- User capabilities are checked (default: manage_woocommerce)

## Advanced Configuration

### Custom Permissions

By default, the plugin requires `manage_woocommerce` capability. To customize:

Add to `wp-config.php` or a custom plugin:

```php
add_filter( 'wc_stripe_custom_meta_capability', function() {
    return 'manage_options'; // Only administrators
} );
```

Other options:
- `manage_options` - Administrators only
- `manage_woocommerce` - Admins + Shop Managers (default)
- `edit_products` - Admins + Product Editors
- Custom capability from another plugin

### Extending the Plugin

The plugin uses standard WordPress filters. To add additional metadata:

```php
// Add custom metadata alongside plugin settings
add_filter( 'wc_stripe_intent_metadata', function( $metadata, $order, $source ) {
    $metadata['custom_field'] = 'custom_value';
    return $metadata;
}, 20, 3 ); // Priority 20 runs after plugin (priority 10)
```

## Example Configurations

### Example 1: Basic Setup
Useful for tracking core order information:

**Settings:**
- Multi-Product: Delimited Values
- Cart Metadata: Order ID, Customer Email, Order Total
- Static Pairs:
  - `store_name: "My Store"`
  - `environment: "production"`

**Result in Stripe:**
```json
{
  "order_id": "12345",
  "customer_email": "customer@example.com",
  "order_total": "99.99",
  "store_name": "My Store",
  "environment": "production"
}
```

### Example 2: Advanced Product Tracking
Useful for detailed order analysis:

**Settings:**
- Multi-Product: Numbered Keys
- Cart Metadata: Order ID, Order Total, Number of Items
- Product Metadata: product_category, product_line
- Static Pairs:
  - `business_unit: "ecommerce"`
  - `region: "US"`

**Result in Stripe (multi-product order):**
```json
{
  "order_id": "12345",
  "order_total": "199.99",
  "order_number_of_items": "2",
  "product_1_sku": "PROD-001",
  "product_1_meta_category": "Electronics",
  "product_2_sku": "PROD-002",
  "product_2_meta_category": "Accessories",
  "business_unit": "ecommerce",
  "region": "US"
}
```

## Support Resources

- **User Documentation**: [README.md](README.md)
- **Testing Guide**: [TESTING.md](TESTING.md)
- **Technical Docs**: [IMPLEMENTATION.md](IMPLEMENTATION.md)
- **GitHub Repository**: https://github.com/WeMakeGood/wc-stripe-custom-meta

## Next Steps

1. ✅ Plugin is installed and activated
2. 📍 Configure metadata fields in WooCommerce Stripe settings
3. 🧪 Process a test order
4. ✔️ Verify metadata in Stripe Dashboard
5. 🚀 Go live with your configuration

## FAQ

**Q: Can I change my metadata configuration after going live?**
A: Yes! Changes take effect immediately on the next order.

**Q: Will existing orders' metadata change?**
A: No, only new orders will use the updated configuration.

**Q: Can I add unlimited static metadata pairs?**
A: Yes, but Stripe has a 50 key-value pair limit per payment intent. Plan accordingly.

**Q: What if an order has 20 products?**
A: Stripe limit is 50 pairs total. Delimited strategy uses 1 pair per field. Numbered strategy uses ~5 pairs per product (SKU, name, price, qty, ID).

**Q: Can I export/import my configuration?**
A: Settings are stored as a WordPress option and can be backed up via WordPress export tools.

**Q: Do I need to restart anything after activation?**
A: No, the plugin works immediately after activation.

---

**Plugin Version:** 1.0.0
**Last Updated:** November 2025
**Status:** ✅ Production Ready
