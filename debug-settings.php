<?php
/**
 * Debug script to test settings integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Load WordPress
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
}

echo "\n=== WC Stripe Custom Meta - Settings Integration Test ===\n\n";

// Simulate admin context
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

// Make sure all plugins are loaded
do_action( 'plugins_loaded' );

// Test 1: Check filter registration
echo "1. Filter Registration:\n";
if ( has_filter( 'wc_stripe_settings' ) ) {
	echo "   ✓ wc_stripe_settings filter is registered\n";
} else {
	echo "   ✗ wc_stripe_settings filter is NOT registered\n";
}

// Test 2: Check class availability
echo "\n2. Class Availability:\n";
echo "   WC_Gateway_Stripe: " . ( class_exists( 'WC_Gateway_Stripe' ) ? "✓" : "✗" ) . "\n";
echo "   WC_Stripe_Custom_Meta_Admin_Settings: " . ( class_exists( 'WC_Stripe_Custom_Meta_Admin_Settings' ) ? "✓" : "✗" ) . "\n";
echo "   WC_Stripe_Metadata_Handler: " . ( class_exists( 'WC_Stripe_Metadata_Handler' ) ? "✓" : "✗" ) . "\n";

// Test 3: Try to get the Stripe gateway and check form fields
echo "\n3. Stripe Gateway Configuration:\n";
if ( class_exists( 'WC_Gateway_Stripe' ) ) {
	try {
		$gateway = new WC_Gateway_Stripe();
		echo "   ✓ Gateway instantiated successfully\n";

		if ( ! empty( $gateway->form_fields ) ) {
			echo "   ✓ Form fields exist: " . count( $gateway->form_fields ) . " fields\n";

			// Check for our custom section
			if ( isset( $gateway->form_fields['wc_stripe_custom_meta_title'] ) ) {
				echo "   ✓ Custom metadata section found!\n";

				// Count our custom fields
				$custom_count = 0;
				foreach ( $gateway->form_fields as $key => $field ) {
					if ( strpos( $key, 'wc_stripe_custom_meta' ) === 0 ) {
						$custom_count++;
					}
				}
				echo "   ✓ Custom metadata fields: " . $custom_count . "\n";
			} else {
				echo "   ✗ Custom metadata section NOT found\n";
				echo "   First 5 field keys:\n";
				$count = 0;
				foreach ( $gateway->form_fields as $key => $field ) {
					echo "      - " . $key . "\n";
					$count++;
					if ( $count >= 5 ) break;
				}
			}
		} else {
			echo "   ✗ No form fields found\n";
		}
	} catch ( Exception $e ) {
		echo "   ✗ Error instantiating gateway: " . $e->getMessage() . "\n";
	}
} else {
	echo "   ✗ WC_Gateway_Stripe class not available\n";
}

// Test 4: Check if WooCommerce is active
echo "\n4. WooCommerce Status:\n";
echo "   WooCommerce class: " . ( class_exists( 'WooCommerce' ) ? "✓ Active" : "✗ Not active" ) . "\n";

echo "\n=== End of Test ===\n\n";
