<?php
/**
 * Metadata Collector Class
 *
 * Discovers available metadata fields from cart, users, and products.
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Stripe_Custom_Meta_Collector
 *
 * Handles discovery of available metadata fields.
 */
class WC_Stripe_Custom_Meta_Collector {

	/**
	 * Get all available product metadata fields from the database.
	 *
	 * @since 1.0.0
	 * @return array Array of metadata keys with display names.
	 */
	public static function get_product_metadata() {
		global $wpdb;

		// Query for unique product metadata keys.
		$query = $wpdb->prepare(
			"SELECT DISTINCT meta_key FROM {$wpdb->postmeta}
			WHERE post_id IN (
				SELECT ID FROM {$wpdb->posts}
				WHERE post_type IN (%s, %s)
			)
			AND meta_key NOT LIKE %s
			AND meta_key NOT LIKE %s
			ORDER BY meta_key ASC",
			'product',
			'product_variation',
			'\\_%', // Exclude private meta (starts with _).
			'_wc_%' // Exclude WooCommerce private meta.
		);

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$metadata = array();
		foreach ( $results as $row ) {
			if ( ! empty( $row->meta_key ) ) {
				$metadata[ $row->meta_key ] = self::sanitize_field_name( $row->meta_key );
			}
		}

		return $metadata;
	}

	/**
	 * Get all available user metadata fields from the database.
	 *
	 * @since 1.0.0
	 * @return array Array of metadata keys with display names.
	 */
	public static function get_user_metadata() {
		global $wpdb;

		// Query for unique user metadata keys.
		$query = $wpdb->prepare(
			"SELECT DISTINCT meta_key FROM {$wpdb->usermeta}
			WHERE meta_key NOT LIKE %s
			AND meta_key NOT LIKE %s
			ORDER BY meta_key ASC",
			'\\_%', // Exclude private meta (starts with _).
			'%capabilities%' // Exclude capabilities.
		);

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$metadata = array();
		foreach ( $results as $row ) {
			if ( ! empty( $row->meta_key ) ) {
				$metadata[ $row->meta_key ] = self::sanitize_field_name( $row->meta_key );
			}
		}

		return $metadata;
	}

	/**
	 * Get all available cart/order metadata fields.
	 *
	 * Returns common WooCommerce order fields that can be sent to Stripe.
	 *
	 * @since 1.0.0
	 * @return array Array of metadata keys with display names.
	 */
	public static function get_cart_metadata() {
		// Common cart/order metadata fields available in WooCommerce.
		$metadata = array(
			'order_id'              => __( 'Order ID', 'wc-stripe-custom-meta' ),
			'order_total'           => __( 'Order Total', 'wc-stripe-custom-meta' ),
			'order_subtotal'        => __( 'Order Subtotal', 'wc-stripe-custom-meta' ),
			'order_tax'             => __( 'Order Tax', 'wc-stripe-custom-meta' ),
			'order_shipping'        => __( 'Order Shipping', 'wc-stripe-custom-meta' ),
			'order_number_of_items' => __( 'Number of Items', 'wc-stripe-custom-meta' ),
			'customer_email'        => __( 'Customer Email', 'wc-stripe-custom-meta' ),
			'customer_phone'        => __( 'Customer Phone', 'wc-stripe-custom-meta' ),
			'billing_country'       => __( 'Billing Country', 'wc-stripe-custom-meta' ),
			'shipping_country'      => __( 'Shipping Country', 'wc-stripe-custom-meta' ),
			'payment_method'        => __( 'Payment Method', 'wc-stripe-custom-meta' ),
			'shipping_method'       => __( 'Shipping Method', 'wc-stripe-custom-meta' ),
		);

		return $metadata;
	}

	/**
	 * Get product-specific fields for multi-product handling.
	 *
	 * @since 1.0.0
	 * @return array Array of product field keys with display names.
	 */
	public static function get_product_fields() {
		$fields = array(
			'product_sku'      => __( 'Product SKU', 'wc-stripe-custom-meta' ),
			'product_name'     => __( 'Product Name', 'wc-stripe-custom-meta' ),
			'product_price'    => __( 'Product Price', 'wc-stripe-custom-meta' ),
			'product_quantity' => __( 'Product Quantity', 'wc-stripe-custom-meta' ),
			'product_id'       => __( 'Product ID', 'wc-stripe-custom-meta' ),
		);

		return $fields;
	}

	/**
	 * Sanitize field name for display purposes.
	 *
	 * @since 1.0.0
	 * @param string $field_name The field name to sanitize.
	 * @return string The sanitized field name.
	 */
	private static function sanitize_field_name( $field_name ) {
		// Replace underscores with spaces and capitalize words.
		$display_name = str_replace( array( '_', '-' ), ' ', $field_name );
		$display_name = ucwords( $display_name );

		return $display_name;
	}
}
