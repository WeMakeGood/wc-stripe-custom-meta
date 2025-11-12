# Plugin Testing Guide

## Quick Verification

The plugin is now installed and activated. Here's how to verify it's working:

### 1. Check Admin Interface

1. Log in to WordPress admin
2. Go to **WooCommerce → Stripe Metadata** (new submenu page)
3. You should see a standalone configuration page with:
   - Multi-Product Handling Method selector (radio buttons)
   - Cart & Order Metadata checkboxes section
   - User Metadata checkboxes section
   - Product Metadata checkboxes section
   - Static Metadata Pairs section with "Add Row" button

### 2. Test Metadata Selection

1. Under **Cart & Order Metadata**, select a few fields (e.g., Order ID, Customer Email, Order Total)
2. Click **Save Changes**
3. Go back to the same page and verify your selections were saved

### 3. Test Static Metadata

1. Under **Static Metadata Pairs**, click **"Add Metadata Pair"**
2. Enter a test key (e.g., "test_source") with maxlength 40 chars
3. Enter a test value (e.g., "woocommerce_stripe") with maxlength 500 chars
4. Click **Save Changes**
5. Verify the pair appears when you return to the page

### 4. Test Multi-Product Handling

1. Select **"Delimited Values"** or **"Numbered Keys"** option
2. Click **Save Changes**
3. Verify your selection persists when you return

## Integration Testing

The plugin integrates with WooCommerce Stripe via the `wc_stripe_intent_metadata` filter. To verify integration:

### 1. Verify Filter Registration

Run via WP-CLI:
```bash
wp eval 'echo has_filter("wc_stripe_intent_metadata") ? "✓ Filter registered" : "✗ Filter NOT registered"'
```

### 2. Process a Test Payment

1. Create a test order through the WordPress admin or frontend
2. Note which metadata fields you have selected
3. Proceed through checkout with Stripe as payment method
4. After successful payment, check the Stripe Dashboard:
   - Go to **Payments → Payment Intents**
   - Find the payment intent for your test order
   - Click it to view details
   - Scroll to the **Metadata** section
   - Verify your selected metadata appears

## Stripe Dashboard Verification

To verify metadata is reaching Stripe:

1. Log in to **Stripe Dashboard** (test or live, depending on your configuration)
2. Navigate to **Developers → Events** or **Payments → Payment Intents**
3. Find a recent payment intent from your test order
4. Click to open it
5. Scroll down to **Metadata** section
6. Verify:
   - Your selected cart/user/product metadata appears
   - Your static metadata pairs are present
   - Metadata respects Stripe limits:
     - Max 50 key-value pairs
     - Keys: max 40 characters
     - Values: max 500 characters

## Sample Expected Metadata

If you configured the plugin with:
- **Cart Metadata**: Order ID, Customer Email, Order Total
- **Static Metadata**:
  - `store_location: "main"`
  - `order_source: "woocommerce"`

You should see in Stripe:
```json
{
  "order_id": "12345",
  "customer_email": "customer@example.com",
  "order_total": "99.99",
  "store_location": "main",
  "order_source": "woocommerce"
}
```

## Troubleshooting

### Settings page doesn't show new section

**Possible causes:**
- WooCommerce not active - verify via WooCommerce → Settings page loads
- WooCommerce Stripe Gateway not active - check Plugins page
- Page caching - clear cache and hard refresh browser

**Solution:**
```bash
wp plugin list | grep woo
# Verify both woocommerce and woocommerce-gateway-stripe show "active"
```

### Metadata not appearing in Stripe

**Possible causes:**
- Settings not saved - go back to settings page and verify selections persist
- Metadata fields are empty in order
- Stripe key configuration issue (test vs live keys mismatch)

**Solution:**
1. Enable WordPress debug logging in `wp-config.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```

2. Check log file:
   ```bash
   tail -50 wp-content/debug.log | grep stripe
   ```

### "No metadata fields available" message

**Possible cause:**
- No user or product metadata exists yet in database

**Solution:**
This is normal behavior. Use the **Static Metadata Pairs** section instead to add metadata that doesn't depend on database fields.

## Performance Considerations

The plugin performs database queries to discover metadata fields:

**Cart Metadata**: No database query (uses hardcoded list)
**User Metadata**: Queries `wp_usermeta` table with DISTINCT
**Product Metadata**: Queries `wp_postmeta` table with DISTINCT

For large databases (100,000+ posts/users), these queries may take 1-2 seconds. This only happens during:
- Plugin first load
- Admin settings page load
- Manual refresh (WP-CLI cache clears)

## Known Limitations

1. **Maximum Stripe Metadata**: 50 key-value pairs per payment intent
2. **Key Character Limit**: 40 characters (no square brackets)
3. **Value Character Limit**: 500 characters
4. **Numbered Keys Mode**: Supports up to ~16 products before hitting 50-key limit
5. **Database Queries**: Metadata discovery queries run on every settings page load

## Test Checklist

- [ ] Plugin activates without errors
- [ ] Settings page appears under Stripe settings
- [ ] Can select and save cart metadata
- [ ] Can select and save user metadata
- [ ] Can select and save product metadata
- [ ] Can add/remove static metadata pairs
- [ ] Can select multi-product handling method
- [ ] Settings persist after page reload
- [ ] Test payment processes without errors
- [ ] Metadata appears in Stripe Dashboard
- [ ] Custom capability filter can be applied
- [ ] Multiple static metadata pairs can be added

## Development Testing

To test the plugin during development:

### Run Integration Test
```bash
cd /path/to/wordpress/root
wp eval-file wp-content/plugins/wc-stripe-custom-meta/test-integration.php
```

### Check PHP Syntax
```bash
php -l wp-content/plugins/wc-stripe-custom-meta/wc-stripe-custom-meta.php
php -l wp-content/plugins/wc-stripe-custom-meta/includes/*.php
```

### Review Plugin Code
Main entry point: [wc-stripe-custom-meta.php](wc-stripe-custom-meta.php)
Admin settings: [includes/class-admin-settings.php](includes/class-admin-settings.php)
Metadata handler: [includes/class-stripe-metadata-handler.php](includes/class-stripe-metadata-handler.php)
Field discovery: [includes/class-metadata-collector.php](includes/class-metadata-collector.php)

## Support

If you encounter issues:

1. Check this testing guide
2. Review the [README.md](README.md) for usage documentation
3. Check WordPress debug log at `wp-content/debug.log`
4. Verify WooCommerce and Stripe Gateway are active and up to date
5. Test with a simple metadata selection first before complex scenarios
