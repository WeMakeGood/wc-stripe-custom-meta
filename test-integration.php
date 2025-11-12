<?php
/**
 * Test Integration Script
 *
 * This file tests the plugin integration with WooCommerce Stripe.
 * Run via WP-CLI: wp eval-file wp-content/plugins/wc-stripe-custom-meta/test-integration.php
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not allowed' );
}

echo "\n";
echo "=============================================\n";
echo "WooCommerce Stripe Custom Meta - Integration Test\n";
echo "=============================================\n\n";

// 1. Check plugin is active
echo "1. Plugin Status:\n";
echo "   Active: " . ( is_plugin_active( 'wc-stripe-custom-meta/wc-stripe-custom-meta.php' ) ? "✓ YES" : "✗ NO" ) . "\n";

// 2. Check WooCommerce is active
echo "\n2. WooCommerce Status:\n";
echo "   Active: " . ( class_exists( 'WooCommerce' ) ? "✓ YES" : "✗ NO" ) . "\n";

// 3. Check WooCommerce Stripe Gateway is active
echo "\n3. WooCommerce Stripe Gateway Status:\n";
echo "   Active: " . ( class_exists( 'WC_Gateway_Stripe' ) ? "✓ YES" : "✗ NO" ) . "\n";

// 4. Check classes are loaded
echo "\n4. Plugin Classes:\n";
$classes = array(
	'WC_Stripe_Custom_Meta_Admin_Page',
	'WC_Stripe_Custom_Meta_Collector',
	'WC_Stripe_Metadata_Handler',
);
foreach ( $classes as $class ) {
	echo "   " . $class . ": " . ( class_exists( $class ) ? "✓ YES" : "✗ NO" ) . "\n";
}

// 5. Check filter hooks
echo "\n5. Filter Hooks:\n";
global $wp_filter;

$hooks_to_check = array(
	'wc_stripe_intent_metadata' => 'wc_stripe_intent_metadata',
	'wc_stripe_settings'        => 'wc_stripe_settings',
	'admin_init'                => 'admin_init',
);

foreach ( $hooks_to_check as $label => $hook ) {
	$has_hook = isset( $wp_filter[ $hook ] ) && ! empty( $wp_filter[ $hook ]->callbacks );
	echo "   {$label}: " . ( $has_hook ? "✓ YES" : "✗ NO" ) . "\n";
}

// 6. Test metadata collection
echo "\n6. Metadata Collection:\n";
if ( class_exists( 'WC_Stripe_Custom_Meta_Collector' ) ) {
	try {
		$cart_meta    = WC_Stripe_Custom_Meta_Collector::get_cart_metadata();
		$user_meta    = WC_Stripe_Custom_Meta_Collector::get_user_metadata();
		$product_meta = WC_Stripe_Custom_Meta_Collector::get_product_metadata();

		echo "   Cart metadata fields: " . count( $cart_meta ) . "\n";
		echo "   User metadata fields: " . count( $user_meta ) . "\n";
		echo "   Product metadata fields: " . count( $product_meta ) . "\n";

		if ( count( $cart_meta ) > 0 ) {
			echo "\n   Sample cart fields:\n";
			$sample_count = min( 3, count( $cart_meta ) );
			$count        = 0;
			foreach ( $cart_meta as $key => $value ) {
				echo "      - {$key}: {$value}\n";
				$count++;
				if ( $count >= $sample_count ) {
					break;
				}
			}
		}
	} catch ( Exception $e ) {
		echo "   Error collecting metadata: " . $e->getMessage() . "\n";
	}
}

// 7. Test metadata handler
echo "\n7. Metadata Handler:\n";
if ( class_exists( 'WC_Stripe_Metadata_Handler' ) ) {
	echo "   Handler class loaded: ✓ YES\n";

	// Create a test order object to verify the handler works
	try {
		$test_order = wc_create_order();
		if ( $test_order ) {
			echo "   Can create test order: ✓ YES\n";

			// Test that the metadata handler can process metadata
			$handler  = new WC_Stripe_Metadata_Handler();
			$metadata = $handler->add_custom_metadata( array(), $test_order );
			echo "   Handler processes metadata: ✓ YES\n";
			echo "   Metadata items returned: " . count( $metadata ) . "\n";

			// Clean up test order
			wp_delete_post( $test_order->get_id(), true );
		} else {
			echo "   Test order creation: ✗ FAILED\n";
		}
	} catch ( Exception $e ) {
		echo "   Handler test error: " . $e->getMessage() . "\n";
	}
}

// 8. Test settings storage
echo "\n8. Settings Storage:\n";
$settings = get_option( 'wc_stripe_custom_meta_settings', false );
if ( false === $settings ) {
	echo "   Stored settings: (none - will be created on first save)\n";
} else {
	echo "   Stored settings: ✓ YES\n";
	echo "   Settings keys: " . implode( ', ', array_keys( $settings ) ) . "\n";
}

// 9. Check admin capability
echo "\n9. Admin Capabilities:\n";
$admin = get_user_by( 'ID', 1 );
if ( $admin ) {
	echo "   Admin user: " . $admin->user_login . "\n";
	$capability = apply_filters( 'wc_stripe_custom_meta_capability', 'manage_woocommerce' );
	echo "   Required capability: {$capability}\n";
	echo "   Admin has capability: " . ( user_can( $admin->ID, $capability ) ? "✓ YES" : "✗ NO" ) . "\n";
}

echo "\n";
echo "=============================================\n";
echo "Integration Test Complete\n";
echo "=============================================\n\n";
