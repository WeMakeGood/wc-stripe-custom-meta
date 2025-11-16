# WooCommerce Subscriptions Integration Guide

This guide explains how to use the WooCommerce Subscriptions metadata features in the WC Stripe Custom Meta plugin.

## Overview

Version 1.1.0 adds full support for WooCommerce Subscriptions, allowing you to send subscription-specific metadata to Stripe for:

- **Parent Orders**: Initial orders that create subscriptions
- **Renewal Orders**: Automatic subscription renewal payments
- **Switch Orders**: When customers modify their subscription
- **Resubscribe Orders**: When customers reactivate a cancelled subscription

## Requirements

- **WooCommerce**: 5.0 or higher
- **WooCommerce Stripe Payment Gateway**: 7.0 or higher
- **WooCommerce Subscriptions**: 6.0 or higher (optional but recommended)
- **WordPress**: 5.9 or higher
- **PHP**: 7.4 or higher

## Available Subscription Metadata Fields

When WooCommerce Subscriptions is active, the following metadata fields become available:

| Field | Description | Example |
|-------|-------------|---------|
| `subscription_id` | The unique subscription ID | 123 |
| `subscription_status` | Current subscription status | `active`, `on-hold`, `cancelled`, `expired`, `pending-cancel` |
| `subscription_billing_period` | Billing frequency | `month`, `year`, `week`, `day` |
| `subscription_billing_interval` | How often to bill | `1`, `2`, `3`, etc. |
| `subscription_total` | Recurring payment amount | 29.99 |
| `subscription_sign_up_fee` | One-time setup fee (if applicable) | 9.99 |
| `subscription_next_payment_date` | Date of next scheduled payment | 2025-01-15 12:00:00 |
| `subscription_trial_end_date` | When trial period ends (if applicable) | 2025-01-08 12:00:00 |
| `subscription_start_date` | When subscription began | 2025-01-01 12:00:00 |
| `subscription_end_date` | When subscription ends (if applicable) | 2025-12-31 23:59:59 |
| `subscription_payment_count` | Number of payments completed | 5 |
| `subscription_parent_order_id` | ID of the initial order (for renewals) | 456 |
| `subscription_order_type` | Type of order | `parent`, `renewal`, `switch`, `resubscribe` |

## Configuration

### 1. Access Settings

1. Go to **WooCommerce** → **Stripe Metadata** (in the admin menu)
2. Look for the **"Subscription Metadata"** section
3. This section only appears if WooCommerce Subscriptions is active

### 2. Select Subscription Fields

Select which subscription metadata fields to send to Stripe:

- Check the boxes for the fields you want included
- Fields work alongside existing product, user, and order metadata
- The order-type field is automatically added to identify the subscription order type

### 3. Multi-Product Strategy

Your existing multi-product handling method applies to subscriptions too:

**Delimited Values** (default):
```
subscription_ids: "123,456"
subscription_statuses: "active,on-hold"
subscription_billing_periods: "month,month"
```

**Numbered Keys**:
```
subscription_1_id: 123
subscription_1_status: active
subscription_2_id: 456
subscription_2_status: on-hold
```

### 4. Save Settings

Click **"Save Changes"** to apply your subscription metadata configuration.

## Use Cases

### Example 1: Track All Subscription Info

**Configuration:**
- Select: `subscription_id`, `subscription_status`, `subscription_billing_period`, `subscription_total`
- Multi-product method: Numbered Keys (if you have multiple subscriptions per order)

**Result in Stripe:**
```
subscription_1_id: "123"
subscription_1_status: "active"
subscription_1_billing_period: "month"
subscription_1_total: "29.99"
```

**Use Case:** Track subscription performance and churn in your Stripe dashboard.

---

### Example 2: Payment Retry Tracking

**Configuration:**
- Select: `subscription_id`, `subscription_order_type`, `subscription_status`

**Result in Stripe (for renewal payment):**
```
subscription_id: "123"
subscription_order_type: "renewal"
subscription_status: "active"
```

**Use Case:** Identify renewal payment failures and track retry attempts.

---

### Example 3: Trial Monitoring

**Configuration:**
- Select: `subscription_id`, `subscription_trial_end_date`, `subscription_start_date`

**Result in Stripe:**
```
subscription_id: "123"
subscription_trial_end_date: "2025-01-15 12:00:00"
subscription_start_date: "2025-01-01 12:00:00"
```

**Use Case:** Monitor trial period endings for follow-up campaigns.

---

### Example 4: Complete Subscription Lifecycle

**Configuration:**
- Select all subscription metadata fields
- Multi-product method: Delimited Values

**Result in Stripe:**
All subscription metadata is sent as delimited values, providing a complete picture of each subscription.

**Use Case:** Full reconciliation and reporting in your financial system.

## How It Works

### For Parent Orders (Initial Purchase)

When a customer purchases a subscription product:
1. An order is created with the subscription product(s)
2. A subscription object is created and linked to the order
3. All selected subscription metadata is collected from the subscription
4. Metadata is sent to Stripe with the payment intent

**Result:** Complete subscription details available in Stripe for reconciliation.

### For Renewal Orders

When a subscription renews automatically:
1. A renewal order is created and linked to the parent subscription
2. All selected subscription metadata is retrieved
3. Metadata is sent to Stripe with the renewal payment
4. `subscription_order_type: "renewal"` helps identify renewal payments

**Result:** Renewal payments are tagged in Stripe for analysis.

### For Switch/Resubscribe Orders

When a customer modifies or reactivates:
1. A switch or resubscribe order is created
2. Both the old and new subscription data is available
3. Metadata reflects the current subscription state
4. `subscription_order_type` indicates the action type

**Result:** Subscription modifications are tracked in Stripe.

## Important Notes

### Stripe Limits

The plugin respects Stripe's metadata limits:
- **Maximum 50 key-value pairs per payment intent**
- **Key names: 40 characters maximum**
- **Values: 500 characters maximum**

**Priority order:** Static → Cart/Order → User → Product → Subscription

If you hit the 50-key limit, subscription metadata will be trimmed to ensure other critical metadata is preserved.

### Multiple Subscriptions

If an order contains multiple subscription products:
- **Delimited mode:** All subscription values are comma-separated in a single key
- **Numbered mode:** Each subscription gets its own numbered key set (subscription_1_*, subscription_2_*, etc.)

Choose numbered mode for better detail, delimited mode for simplicity.

### Empty Fields

If a subscription doesn't have a value for a field (e.g., no trial, no end date), that field won't appear in the metadata. This helps conserve the 50-key limit.

### Graceful Degradation

If WooCommerce Subscriptions is not active:
- Subscription metadata section won't appear
- Plugin continues to work normally with other metadata sources
- If you later activate subscriptions, the section will appear on next page load

## Filtering & Customization

You can use WordPress hooks to customize subscription metadata collection:

### Modify subscription order type detection

```php
add_filter( 'wc_stripe_custom_meta_subscription_order_type', function( $type, $order ) {
    // Custom logic here
    return $type;
}, 10, 2 );
```

### Add custom subscription fields

```php
add_filter( 'wc_stripe_custom_meta_subscription_metadata', function( $fields ) {
    $fields['custom_subscription_field'] = __( 'Custom Field', 'your-domain' );
    return $fields;
} );
```

## Troubleshooting

### Subscription Metadata Not Appearing in Stripe

**Possible causes:**
1. **Subscription metadata not selected** - Check admin settings
2. **Order not recognized as subscription order** - Verify order is linked to a subscription
3. **Hit the 50-key limit** - Reduce other metadata selections
4. **WC Subscriptions not active** - Ensure plugin is installed and activated

**Solution:**
1. Go to **WooCommerce** → **Stripe Metadata** settings
2. Check that subscription fields are selected
3. Reduce other metadata if needed
4. Save and test with a new subscription payment

### Only Seeing Some Subscription Fields

**Possible cause:** Subscription data doesn't exist for that field
- Not all subscriptions have trial dates
- Subscriptions might have no end date
- Sign-up fee only appears for specific products

**Solution:** This is normal. Empty fields don't send to conserve key limits.

### Settings Not Saving

**Check:**
1. Do you have "Manage WooCommerce" capability?
2. Is a nonce validation error shown?
3. Check WordPress debug log for errors

**Fix:** Clear browser cache and try again. If persistent, contact your administrator.

### Multiple Subscriptions Not Showing Up

**Check:**
1. Verify multi-product method setting (numbered vs. delimited)
2. Ensure order actually contains multiple subscription items
3. Check Stripe's 50-key limit hasn't been reached

**Debug:**  Enable WP_DEBUG_LOG to see what's being sent to Stripe.

## Performance Impact

- **Settings page load:** 200-500ms (subscription detection on first load)
- **Payment processing:** <5ms overhead per payment
- **Database queries:** 0 during payment (subscriptions pre-loaded from cache)

Subscription metadata collection is optimized to have minimal impact on payment processing speed.

## Security

- All subscription data is sanitized before sending to Stripe
- Only metadata values explicitly selected in settings are included
- Sensitive fields (like customer addresses) are never included
- Stripe communication is encrypted via HTTPS
- Private subscription data remains private; only your selected fields are sent

## Advanced: Accessing Subscriptions in Code

If you need programmatic access to subscription detection in your own code:

```php
// Load the subscription detector
require_once 'wp-content/plugins/wc-stripe-custom-meta/includes/class-subscription-detector.php';

// Check if an order has subscriptions
if ( WC_Stripe_Custom_Meta_Subscription_Detector::is_subscription_order( $order_id ) ) {
    // Get all subscriptions
    $subscriptions = WC_Stripe_Custom_Meta_Subscription_Detector::get_subscriptions_for_order( $order_id );

    // Get specific subscription field value
    $sub_id = WC_Stripe_Custom_Meta_Subscription_Detector::get_subscription_value( $subscription, 'subscription_id' );
}
```

## Support & Feedback

For issues, feature requests, or feedback on the subscriptions integration:

1. Check this guide first
2. Review the [main README.md](README.md)
3. See [TROUBLESHOOTING-RESOLVED.md](TROUBLESHOOTING-RESOLVED.md)
4. Visit the GitHub repository: https://github.com/WeMakeGood/wc-stripe-custom-meta

## Changelog

### Version 1.1.0 (2025)
- Added WooCommerce Subscriptions support
- New subscription metadata fields
- Numbered and delimited multi-subscription strategies
- Order type tracking (parent, renewal, switch, resubscribe)
- Graceful degradation when subscriptions not active
- Subscription metadata collection in Stripe payments
