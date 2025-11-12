<?php
/**
 * Stripe Metadata Handler Class
 *
 * Handles the collection and application of metadata to Stripe payment intents.
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Stripe_Metadata_Handler
 *
 * Hooks into WooCommerce Stripe to add custom metadata.
 */
class WC_Stripe_Metadata_Handler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'wc_stripe_intent_metadata', array( $this, 'add_custom_metadata' ), 10, 3 );
	}

	/**
	 * Add custom metadata to Stripe payment intents.
	 *
	 * @since 1.0.0
	 * @param array      $metadata The metadata array to be sent to Stripe.
	 * @param WC_Order   $order The WooCommerce order object.
	 * @param mixed      $prepared_source The prepared payment source (optional).
	 * @return array The modified metadata array.
	 */
	public function add_custom_metadata( $metadata, $order, $prepared_source = null ) {
		// Get saved settings.
		$settings = $this->get_settings();

		// Return early if no settings or metadata configuration.
		if ( empty( $settings ) ) {
			return $metadata;
		}

		// Initialize metadata with existing data.
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}

		// Check if we're at Stripe's metadata limit before adding more.
		if ( count( $metadata ) >= 50 ) {
			return $metadata;
		}

		// Add cart/order metadata.
		$metadata = $this->add_cart_metadata( $metadata, $order, $settings );

		// Add user metadata.
		$metadata = $this->add_user_metadata( $metadata, $order, $settings );

		// Add product metadata.
		$metadata = $this->add_product_metadata( $metadata, $order, $settings );

		// Add static metadata.
		$metadata = $this->add_static_metadata( $metadata, $settings );

		// Ensure we don't exceed Stripe's limits.
		$metadata = $this->validate_stripe_metadata( $metadata );

		return $metadata;
	}

	/**
	 * Add cart/order metadata to the metadata array.
	 *
	 * @since 1.0.0
	 * @param array    $metadata The metadata array.
	 * @param WC_Order $order The WooCommerce order object.
	 * @param array    $settings The plugin settings.
	 * @return array The updated metadata array.
	 */
	private function add_cart_metadata( $metadata, $order, $settings ) {
		$cart_fields = isset( $settings['cart_metadata'] ) ? $settings['cart_metadata'] : array();

		if ( empty( $cart_fields ) ) {
			return $metadata;
		}

		foreach ( $cart_fields as $field ) {
			if ( count( $metadata ) >= 50 ) {
				break;
			}

			$value = $this->get_cart_field_value( $field, $order );

			if ( $value !== null ) {
				$metadata[ $field ] = (string) $value;
			}
		}

		return $metadata;
	}

	/**
	 * Get the value for a specific cart field.
	 *
	 * @since 1.0.0
	 * @param string   $field The field name.
	 * @param WC_Order $order The WooCommerce order object.
	 * @return mixed The field value or null if not found.
	 */
	private function get_cart_field_value( $field, $order ) {
		switch ( $field ) {
			case 'order_id':
				return $order->get_id();
			case 'order_total':
				return $order->get_total();
			case 'order_subtotal':
				return $order->get_subtotal();
			case 'order_tax':
				return $order->get_total_tax();
			case 'order_shipping':
				return $order->get_shipping_total();
			case 'order_number_of_items':
				return $order->get_item_count();
			case 'customer_email':
				return $order->get_billing_email();
			case 'customer_phone':
				return $order->get_billing_phone();
			case 'billing_country':
				return $order->get_billing_country();
			case 'shipping_country':
				return $order->get_shipping_country();
			case 'payment_method':
				return $order->get_payment_method_title();
			case 'shipping_method':
				$shipping_methods = $order->get_shipping_methods();
				if ( ! empty( $shipping_methods ) ) {
					$first_method = reset( $shipping_methods );
					return $first_method->get_method_title();
				}
				return null;
			default:
				return null;
		}
	}

	/**
	 * Add user metadata to the metadata array.
	 *
	 * @since 1.0.0
	 * @param array    $metadata The metadata array.
	 * @param WC_Order $order The WooCommerce order object.
	 * @param array    $settings The plugin settings.
	 * @return array The updated metadata array.
	 */
	private function add_user_metadata( $metadata, $order, $settings ) {
		$user_fields = isset( $settings['user_metadata'] ) ? $settings['user_metadata'] : array();

		if ( empty( $user_fields ) ) {
			return $metadata;
		}

		$user_id = $order->get_user_id();

		if ( empty( $user_id ) ) {
			return $metadata;
		}

		foreach ( $user_fields as $field ) {
			if ( count( $metadata ) >= 50 ) {
				break;
			}

			$value = get_user_meta( $user_id, $field, true );

			if ( ! empty( $value ) ) {
				$metadata[ $field ] = (string) $value;
			}
		}

		return $metadata;
	}

	/**
	 * Add product metadata to the metadata array.
	 *
	 * @since 1.0.0
	 * @param array    $metadata The metadata array.
	 * @param WC_Order $order The WooCommerce order object.
	 * @param array    $settings The plugin settings.
	 * @return array The updated metadata array.
	 */
	private function add_product_metadata( $metadata, $order, $settings ) {
		$product_fields       = isset( $settings['product_metadata'] ) ? $settings['product_metadata'] : array();
		$product_custom_fields = isset( $settings['product_custom_fields'] ) ? $settings['product_custom_fields'] : array();
		$multi_product_method = isset( $settings['multi_product_method'] ) ? $settings['multi_product_method'] : 'delimited';

		if ( empty( $product_fields ) && empty( $product_custom_fields ) ) {
			return $metadata;
		}

		$order_items = $order->get_items();

		if ( 'numbered_keys' === $multi_product_method ) {
			$metadata = $this->add_product_metadata_numbered( $metadata, $order_items, $product_fields, $product_custom_fields );
		} else {
			$metadata = $this->add_product_metadata_delimited( $metadata, $order_items, $product_fields, $product_custom_fields );
		}

		return $metadata;
	}

	/**
	 * Add product metadata using numbered keys approach.
	 *
	 * @since 1.0.0
	 * @param array $metadata The metadata array.
	 * @param array $order_items The order items.
	 * @param array $product_fields Standard product fields to include.
	 * @param array $product_custom_fields Custom product metadata fields to include.
	 * @return array The updated metadata array.
	 */
	private function add_product_metadata_numbered( $metadata, $order_items, $product_fields, $product_custom_fields ) {
		$product_counter = 1;

		foreach ( $order_items as $item ) {
			if ( count( $metadata ) >= 50 ) {
				break;
			}

			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			// Add standard product fields with numbered keys.
			foreach ( $product_fields as $field ) {
				if ( count( $metadata ) >= 50 ) {
					break;
				}

				$value = $this->get_product_field_value( $field, $product, $item );
				if ( $value !== null ) {
					// Remove 'product_' prefix if field already has it to avoid duplication
					$field_name = ( strpos( $field, 'product_' ) === 0 ) ? substr( $field, 8 ) : $field;
					$key = sprintf( 'product_%d_%s', $product_counter, $field_name );
					$metadata[ $key ] = (string) $value;
				}
			}

			// Add custom product metadata fields with numbered keys.
			foreach ( $product_custom_fields as $field ) {
				if ( count( $metadata ) >= 50 ) {
					break;
				}

				$value = get_post_meta( $product->get_id(), $field, true );
				if ( ! empty( $value ) ) {
					$key = sprintf( 'product_%d_meta_%s', $product_counter, $field );
					// Limit to first 100 chars to fit within 500 char value limit.
					$metadata[ $key ] = substr( (string) $value, 0, 100 );
				}
			}

			$product_counter++;
		}

		return $metadata;
	}

	/**
	 * Add product metadata using delimited values approach.
	 *
	 * @since 1.0.0
	 * @param array $metadata The metadata array.
	 * @param array $order_items The order items.
	 * @param array $product_fields Standard product fields to include.
	 * @param array $product_custom_fields Custom product metadata fields to include.
	 * @return array The updated metadata array.
	 */
	private function add_product_metadata_delimited( $metadata, $order_items, $product_fields, $product_custom_fields ) {
		$delimiter = ',';
		$field_values = array();

		foreach ( $product_fields as $field ) {
			$field_values[ $field ] = array();
		}

		foreach ( $product_custom_fields as $field ) {
			$field_values[ 'meta_' . $field ] = array();
		}

		foreach ( $order_items as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			// Collect standard product field values.
			foreach ( $product_fields as $field ) {
				$value = $this->get_product_field_value( $field, $product, $item );
				if ( $value !== null ) {
					$field_values[ $field ][] = (string) $value;
				}
			}

			// Collect custom product metadata field values.
			foreach ( $product_custom_fields as $field ) {
				$value = get_post_meta( $product->get_id(), $field, true );
				if ( ! empty( $value ) ) {
					$field_values[ 'meta_' . $field ][] = substr( (string) $value, 0, 50 );
				}
			}
		}

		// Add the delimited values to metadata.
		foreach ( $field_values as $field => $values ) {
			if ( count( $metadata ) >= 50 ) {
				break;
			}

			if ( ! empty( $values ) ) {
				// Don't double-prefix fields that already start with 'product_' or 'meta_'
				$key = $field;
				if ( strpos( $field, 'product_' ) !== 0 && strpos( $field, 'meta_' ) !== 0 ) {
					$key = 'product_' . $field;
				}
				$metadata[ $key ] = implode( $delimiter, $values );
			}
		}

		return $metadata;
	}

	/**
	 * Get the value for a specific product field.
	 *
	 * @since 1.0.0
	 * @param string    $field The field name.
	 * @param WC_Product $product The product object.
	 * @param WC_Order_Item_Product $item The order item.
	 * @return mixed The field value or null if not found.
	 */
	private function get_product_field_value( $field, $product, $item ) {
		switch ( $field ) {
			case 'product_sku':
				return $product->get_sku();
			case 'product_name':
				return $product->get_name();
			case 'product_price':
				return $product->get_price();
			case 'product_quantity':
				return $item->get_quantity();
			case 'product_id':
				return $product->get_id();
			default:
				// Check if this is a product attribute field (e.g., product_attribute_frequency)
				if ( strpos( $field, 'product_attribute_' ) === 0 ) {
					$attribute_name = str_replace( 'product_attribute_', '', $field );
					$attribute_key = 'pa_' . $attribute_name;
					$attrs = $product->get_attributes();

					if ( isset( $attrs[ $attribute_key ] ) ) {
						$attr = $attrs[ $attribute_key ];
						// Get the attribute value(s)
						if ( is_object( $attr ) && method_exists( $attr, 'get_options' ) ) {
							// Taxonomy attribute
							$options = $attr->get_options();
							if ( ! empty( $options ) ) {
								return implode( ', ', $options );
							}
						} elseif ( is_array( $attr ) ) {
							// Custom product attribute stored as array
							return implode( ', ', $attr );
						} else {
							// String value
							return (string) $attr;
						}
					}
				}
				return null;
		}
	}

	/**
	 * Add static metadata to the metadata array.
	 *
	 * @since 1.0.0
	 * @param array $metadata The metadata array.
	 * @param array $settings The plugin settings.
	 * @return array The updated metadata array.
	 */
	private function add_static_metadata( $metadata, $settings ) {
		$static_metadata = isset( $settings['static_metadata'] ) ? $settings['static_metadata'] : array();

		if ( empty( $static_metadata ) ) {
			return $metadata;
		}

		foreach ( $static_metadata as $item ) {
			if ( count( $metadata ) >= 50 ) {
				break;
			}

			if ( ! empty( $item['key'] ) && ! empty( $item['value'] ) ) {
				$metadata[ sanitize_key( $item['key'] ) ] = (string) $item['value'];
			}
		}

		return $metadata;
	}

	/**
	 * Validate and ensure metadata conforms to Stripe limits.
	 *
	 * Stripe limits: 50 key-value pairs max, keys up to 40 chars, values up to 500 chars.
	 *
	 * @since 1.0.0
	 * @param array $metadata The metadata array.
	 * @return array The validated metadata array.
	 */
	private function validate_stripe_metadata( $metadata ) {
		$validated = array();

		foreach ( $metadata as $key => $value ) {
			// Enforce key length limit (40 chars).
			$key = substr( $key, 0, 40 );

			// Enforce value length limit (500 chars).
			$value = substr( (string) $value, 0, 500 );

			// Remove square brackets which Stripe doesn't allow.
			$key = str_replace( array( '[', ']' ), '', $key );

			// Skip empty keys or values.
			if ( empty( $key ) || empty( $value ) ) {
				continue;
			}

			$validated[ $key ] = $value;

			// Stop if we've reached Stripe's 50 key-value pair limit.
			if ( count( $validated ) >= 50 ) {
				break;
			}
		}

		return $validated;
	}

	/**
	 * Get the plugin settings from WordPress options.
	 *
	 * @since 1.0.0
	 * @return array The plugin settings.
	 */
	private function get_settings() {
		return get_option( 'wc_stripe_custom_meta_settings', array() );
	}
}
