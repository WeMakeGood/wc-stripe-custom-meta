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
	 * Includes core WooCommerce product fields, product attributes, and ACF fields.
	 * Intelligently extracts attributes from WooCommerce taxonomy and top-level ACF
	 * fields from product field groups.
	 *
	 * @since 1.0.0
	 * @return array Array of metadata keys with display names.
	 */
	public static function get_product_metadata() {
		// Start with core WooCommerce product fields
		$metadata = array(
			'product_sku'         => __( 'Product SKU', 'wc-stripe-custom-meta' ),
			'product_name'        => __( 'Product Name', 'wc-stripe-custom-meta' ),
			'product_price'       => __( 'Product Price', 'wc-stripe-custom-meta' ),
			'product_id'          => __( 'Product ID', 'wc-stripe-custom-meta' ),
			'product_quantity'    => __( 'Product Quantity', 'wc-stripe-custom-meta' ),
			'product_category'    => __( 'Product Category', 'wc-stripe-custom-meta' ),
			'product_description' => __( 'Product Description', 'wc-stripe-custom-meta' ),
		);

		// Get WooCommerce product attributes (taxonomies)
		$attributes = self::get_product_attributes();
		$metadata   = array_merge( $metadata, $attributes );

		// If ACF is active, get ACF field groups for products
		if ( function_exists( 'acf_get_field_groups' ) ) {
			$acf_fields = self::get_acf_product_fields();
			$metadata   = array_merge( $metadata, $acf_fields );
		}

		return $metadata;
	}

	/**
	 * Get WooCommerce product attributes (taxonomies).
	 *
	 * Discovers all product attributes available for products.
	 *
	 * @since 1.0.0
	 * @return array Array of attribute keys with display names.
	 */
	private static function get_product_attributes() {
		$attributes = array();

		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $attributes;
		}

		try {
			$taxonomies = wc_get_attribute_taxonomies();

			if ( $taxonomies ) {
				foreach ( $taxonomies as $tax ) {
					if ( isset( $tax->attribute_name ) ) {
						$key = 'product_attribute_' . $tax->attribute_name;
						$label = isset( $tax->attribute_label ) ? $tax->attribute_label : self::sanitize_field_name( $tax->attribute_name );
						$attributes[ $key ] = __( 'Product Attribute:', 'wc-stripe-custom-meta' ) . ' ' . $label;
					}
				}
			}
		} catch ( Exception $e ) {
			// Silently fail if attribute query has issues
			return $attributes;
		}

		return $attributes;
	}

	/**
	 * Get ACF field groups and fields that apply to products.
	 *
	 * Intelligently extracts top-level ACF fields from field groups assigned to products.
	 * Skips repeater internals and nested field meta keys.
	 *
	 * @since 1.0.0
	 * @return array Array of ACF field keys with display names.
	 */
	private static function get_acf_product_fields() {
		$fields = array();

		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return $fields;
		}

		try {
			// Get all ACF field groups
			$field_groups = acf_get_field_groups();

			if ( ! $field_groups ) {
				return $fields;
			}

			foreach ( $field_groups as $group ) {
				// Check if field group applies to products
				$apply_to_products = false;

				if ( isset( $group['location'] ) && is_array( $group['location'] ) ) {
					foreach ( $group['location'] as $location_group ) {
						if ( ! is_array( $location_group ) ) {
							continue;
						}
						foreach ( $location_group as $location_rule ) {
							if ( ! is_array( $location_rule ) ) {
								continue;
							}
							if ( isset( $location_rule['param'] ) && 'post_type' === $location_rule['param'] ) {
								if ( isset( $location_rule['value'] ) && 'product' === $location_rule['value'] ) {
									$apply_to_products = true;
									break;
								}
							}
						}
						if ( $apply_to_products ) {
							break;
						}
					}
				}

				// If this group applies to products, get its fields
				if ( $apply_to_products ) {
					$group_fields = acf_get_fields( $group['ID'] );

					if ( is_array( $group_fields ) ) {
						foreach ( $group_fields as $field ) {
							// Only include top-level fields (not sub-fields of repeaters)
							// Top-level fields have parent == group ID, or parent is empty/0
							$is_top_level = ! isset( $field['parent'] ) ||
										   empty( $field['parent'] ) ||
										   $field['parent'] === $group['ID'] ||
										   $field['parent'] === (string) $group['ID'];

							if ( isset( $field['name'] ) && $is_top_level ) {
								$field_label = isset( $field['label'] ) ? $field['label'] : self::sanitize_field_name( $field['name'] );
								$fields[ $field['name'] ] = $field_label;
							}
						}
					}
				}
			}
		} catch ( Exception $e ) {
			// Log errors for debugging
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'ACF Product Fields Error: ' . $e->getMessage() );
			}
			return $fields;
		}

		return $fields;
	}

	/**
	 * Get available user metadata fields.
	 *
	 * Returns curated, safe user metadata fields that are commonly used in
	 * e-commerce contexts. Filters out system metadata, plugin internals,
	 * and security-sensitive data. If ACF is active, includes ACF user fields.
	 *
	 * @since 1.0.0
	 * @return array Array of metadata keys with display names.
	 */
	public static function get_user_metadata() {
		// Whitelist of safe, common user metadata fields - use ONLY these by default
		$metadata = array(
			'first_name'      => __( 'First Name', 'wc-stripe-custom-meta' ),
			'last_name'       => __( 'Last Name', 'wc-stripe-custom-meta' ),
			'user_email'      => __( 'User Email', 'wc-stripe-custom-meta' ),
			'billing_phone'   => __( 'Billing Phone', 'wc-stripe-custom-meta' ),
			'billing_company' => __( 'Billing Company', 'wc-stripe-custom-meta' ),
			'customer_type'   => __( 'Customer Type', 'wc-stripe-custom-meta' ),
		);

		// If ACF is active, get ACF field groups for users
		if ( function_exists( 'acf_get_field_groups' ) ) {
			$acf_fields = self::get_acf_user_fields();
			$metadata   = array_merge( $metadata, $acf_fields );
		}

		// Don't scan database for user meta - whitelist is strict and intentional
		// Reduces noise and prevents exposing sensitive/system fields

		return $metadata;
	}

	/**
	 * Get ACF field groups and fields that apply to users.
	 *
	 * Intelligently extracts top-level ACF fields from field groups assigned to users.
	 * Skips repeater internals and nested field meta keys.
	 *
	 * @since 1.0.0
	 * @return array Array of ACF field keys with display names.
	 */
	private static function get_acf_user_fields() {
		$fields = array();

		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return $fields;
		}

		try {
			// Get all ACF field groups
			$field_groups = acf_get_field_groups();

			if ( ! $field_groups ) {
				return $fields;
			}

			foreach ( $field_groups as $group ) {
				// Check if field group applies to users
				$apply_to_users = false;

				if ( isset( $group['location'] ) && is_array( $group['location'] ) ) {
					foreach ( $group['location'] as $location_group ) {
						if ( ! is_array( $location_group ) ) {
							continue;
						}
						foreach ( $location_group as $location_rule ) {
							if ( ! is_array( $location_rule ) ) {
								continue;
							}
							if ( isset( $location_rule['param'] ) && 'user_form' === $location_rule['param'] ) {
								$apply_to_users = true;
								break;
							}
						}
						if ( $apply_to_users ) {
							break;
						}
					}
				}

				// If this group applies to users, get its fields
				if ( $apply_to_users ) {
					$group_fields = acf_get_fields( $group['ID'] );

					if ( is_array( $group_fields ) ) {
						foreach ( $group_fields as $field ) {
							// Only include top-level fields (not sub-fields of repeaters)
							// Top-level fields have parent == group ID, or parent is empty/0
							$is_top_level = ! isset( $field['parent'] ) ||
										   empty( $field['parent'] ) ||
										   $field['parent'] === $group['ID'] ||
										   $field['parent'] === (string) $group['ID'];

							if ( isset( $field['name'] ) && $is_top_level ) {
								$field_label = isset( $field['label'] ) ? $field['label'] : self::sanitize_field_name( $field['name'] );
								$fields[ $field['name'] ] = $field_label;
							}
						}
					}
				}
			}
		} catch ( Exception $e ) {
			// Log errors for debugging
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'ACF User Fields Error: ' . $e->getMessage() );
			}
			return $fields;
		}

		return $fields;
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
