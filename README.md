# WooCommerce Stripe Custom Meta

A WordPress plugin that extends the WooCommerce Stripe Payment Gateway, providing an interactive admin interface to select which metadata fields get pushed to Stripe payment intents. Includes full support for WooCommerce Subscriptions.

## Features

- **Interactive Admin Interface**: Select metadata fields from cart, user, product, and subscription sources via checkboxes
- **WooCommerce Subscriptions Support**: Track subscription metadata for parent orders, renewals, switches, and resubscribes
- **Static Metadata Pairs**: Add custom key-value pairs that are consistently sent to Stripe
- **Multi-Product & Multi-Subscription Support**: Handle orders with multiple products and subscriptions using either:
  - Delimited values (all product SKUs as "PROD-1,PROD-2,PROD-3")
  - Numbered keys (product_1_sku, product_2_sku, product_3_sku)
- **Stripe Compliance**: Automatic validation of metadata against Stripe limits:
  - Maximum 50 key-value pairs per payment intent
  - Keys limited to 40 characters
  - Values limited to 500 characters
  - No square brackets in keys
- **Custom Permissions**: Flexible capability filtering for access control
- **Dynamic Field Discovery**: Automatically discovers available metadata fields from your database
- **Graceful Fallback**: Works perfectly even if WooCommerce Subscriptions is not installed

## Installation

1. Clone or download this plugin to your WordPress plugins directory:
   ```
   wp-content/plugins/wc-stripe-custom-meta/
   ```

2. Activate the plugin from the WordPress admin:
   - Go to **Plugins** → **Installed Plugins**
   - Find **WooCommerce Stripe Custom Meta**
   - Click **Activate**

## Requirements

- WordPress 5.9+
- PHP 7.4+
- WooCommerce 5.0+
- WooCommerce Stripe Payment Gateway 7.0+

## Usage

### Accessing Settings

1. Navigate to **WooCommerce** → **Settings** → **Payments** → **Stripe**
2. Look for the **"Custom Metadata for Stripe"** section

### Configuring Metadata

#### 1. Multi-Product Handling Method
Choose how to handle metadata from orders with multiple products:

- **Delimited Values**: Collects values from all products and joins them with commas
  - Example: `product_sku: "PROD-1,PROD-2,PROD-3"`
  - Better for: Simpler, fewer Stripe metadata keys

- **Numbered Keys**: Creates separate metadata keys for each product
  - Example: `product_1_sku: "PROD-1"`, `product_2_sku: "PROD-2"`
  - Better for: More granular data separation, complex analysis

#### 2. Cart & Order Metadata
Select which order-level fields to include:

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

#### 3. User Metadata
Select from available user metadata fields. Common examples:

- WordPress user meta fields
- Custom user fields from other plugins
- WooCommerce customer data

#### 4. Product Metadata
Select from available product metadata fields. Common examples:

- Custom product attributes
- Product meta fields
- Plugin-specific product data

#### 5. Subscription Metadata (WooCommerce Subscriptions)
*Only available when WooCommerce Subscriptions is installed and active*

Select subscription-specific fields to track subscription data. Fields include:

- Subscription ID and Status
- Billing period and interval
- Recurring total and sign-up fee
- Payment dates (next payment, trial end, start/end)
- Payment count (number of completed payments)
- Order type (parent, renewal, switch, resubscribe)

Subscription metadata is automatically included for all subscription-related orders, including renewal payments and subscription modifications.

For detailed subscription support, see [SUBSCRIPTION_GUIDE.md](SUBSCRIPTION_GUIDE.md).

#### 6. Static Metadata Pairs
Add custom key-value pairs that are always included:

- **Key**: Up to 40 characters (validated automatically)
- **Value**: Up to 500 characters (validated automatically)
- Click "Add Metadata Pair" to add more rows
- Click "Remove" to delete a pair

### Examples

#### Example 1: Basic Setup
Include essential order information:
1. Cart Fields: Order ID, Order Total, Customer Email
2. Static Pairs:
   - `order_source: "woocommerce"`
   - `environment: "production"`

#### Example 2: Advanced Setup with Product Details
Track detailed product information:
1. Multi-Product Method: Numbered Keys
2. Cart Fields: Order ID, Number of Items
3. Product Fields: Product SKU, Product Name, Product Price, Product Quantity
4. Static Pairs: `business_unit: "online_store"`

#### Example 3: Subscription Tracking (WooCommerce Subscriptions)
Track recurring revenue and subscription lifecycle:
1. Multi-Product Method: Numbered Keys
2. Cart Fields: Order ID, Order Total
3. Product Fields: Product SKU, Product Name
4. Subscription Fields: Subscription ID, Status, Billing Period, Total, Next Payment Date, Order Type
5. Static Pairs: `revenue_type: "recurring"`

**Result:** Complete subscription tracking from initial purchase through renewals:
- Parent order includes subscription details
- Renewal payments tagged with `order_type: "renewal"`
- Subscription modifications tracked with order type

For more details, see the [SUBSCRIPTION_GUIDE.md](SUBSCRIPTION_GUIDE.md).

## Metadata Collection Process

When a Stripe payment intent is created:

1. Plugin retrieves saved settings
2. For each selected metadata type, values are collected from the order
3. Multi-product handling is applied (delimited or numbered)
4. All metadata is validated against Stripe limits
5. Metadata is sent to Stripe with the payment intent

## Permissions

By default, the **"Manage WooCommerce"** capability is required to access settings. This typically includes:

- Administrators
- Shop Managers

To customize permissions, use the filter:

```php
add_filter( 'wc_stripe_custom_meta_capability', function() {
    return 'manage_options'; // Only administrators
} );
```

## Hooks and Filters

### `wc_stripe_custom_meta_capability`
Filter the required capability to access settings.

```php
add_filter( 'wc_stripe_custom_meta_capability', function( $capability ) {
    return 'custom_capability';
} );
```

## Development

### File Structure
```
wc-stripe-custom-meta/
├── wc-stripe-custom-meta.php          # Main plugin file
├── includes/
│   ├── class-admin-settings.php       # Admin interface and settings
│   ├── class-stripe-metadata-handler.php # Stripe integration and filtering
│   └── class-metadata-collector.php   # Metadata discovery
├── assets/
│   ├── css/
│   │   └── admin.css                  # Admin styles
│   └── js/
│       └── admin.js                   # (Future) Admin scripts
└── README.md
```

### Key Classes

#### WC_Stripe_Custom_Meta_Admin_Settings
Manages the admin interface and settings page integration with WooCommerce Stripe.

#### WC_Stripe_Metadata_Handler
Implements the `wc_stripe_intent_metadata` filter to add collected metadata to payment intents.

#### WC_Stripe_Custom_Meta_Collector
Discovers available metadata fields from the database for cart, user, and product sources.

## Testing

### Local Testing
1. Activate the plugin in your LocalWP WordPress instance
2. Verify WooCommerce and Stripe Gateway are active
3. Navigate to WooCommerce → Settings → Payments → Stripe
4. Confirm the "Custom Metadata for Stripe" section appears
5. Select test metadata fields and save
6. Process a test payment and verify metadata appears in Stripe Dashboard

### Stripe Dashboard Verification
1. Log in to your Stripe Dashboard
2. Navigate to Payments → Payment Intents
3. Click on a test payment intent
4. Verify your custom metadata appears in the metadata section

## Troubleshooting

### Settings page doesn't appear
- Ensure WooCommerce is installed and activated
- Ensure WooCommerce Stripe Payment Gateway is installed and activated
- Clear any WordPress caches

### Metadata not appearing in Stripe
- Verify metadata fields are selected in plugin settings
- Check Stripe API limit - maximum 50 key-value pairs
- Check key names are under 40 characters
- Check values are under 500 characters
- Review WordPress debug logs for errors

### "No metadata fields available" message
- This is normal for user and product metadata if no custom fields exist
- Add static metadata pairs as an alternative
- Create some test metadata before running plugin

## Contributing

Contributions are welcome! Please feel free to submit pull requests or issues to the GitHub repository.

## License

This plugin is licensed under the GNU General Public License v2 or later. See LICENSE file for details.

## Support

For issues, questions, or feature requests, please visit the GitHub repository:
https://github.com/WeMakeGood/wc-stripe-custom-meta

## Changelog

### 1.0.0 (2025)
- Initial release
- Interactive metadata field selection
- Multi-product handling (delimited and numbered keys)
- Stripe compliance validation
- Custom permissions support
- Dynamic field discovery
