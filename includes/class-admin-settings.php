<?php
/**
 * Admin Settings Class
 *
 * Handles the admin interface and settings page for the plugin.
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Stripe_Custom_Meta_Admin_Settings
 *
 * Creates and manages the admin settings interface.
 */
class WC_Stripe_Custom_Meta_Admin_Settings {

	/**
	 * Admin capability required to access settings.
	 *
	 * @var string
	 */
	private $capability = 'manage_woocommerce';

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private $option_name = 'wc_stripe_custom_meta_settings';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->capability = apply_filters( 'wc_stripe_custom_meta_capability', 'manage_woocommerce' );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'woocommerce_admin_field_metadata_checkboxes', array( $this, 'render_metadata_checkboxes' ) );
		add_action( 'woocommerce_admin_field_static_metadata', array( $this, 'render_static_metadata' ) );
		add_action( 'woocommerce_admin_field_multi_product_method', array( $this, 'render_multi_product_method' ) );
		add_filter( 'wc_stripe_settings', array( $this, 'add_custom_settings_fields' ) );
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'wc_stripe_custom_meta',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Add custom settings fields to WooCommerce Stripe settings.
	 *
	 * @since 1.0.0
	 * @param array $settings The existing settings array.
	 * @return array The modified settings array.
	 */
	public function add_custom_settings_fields( $settings ) {
		// Add a section divider for our custom settings.
		$custom_settings = array(
			array(
				'title' => __( 'Custom Metadata for Stripe', 'wc-stripe-custom-meta' ),
				'type'  => 'title',
				'desc'  => __( 'Configure which metadata fields should be sent to Stripe payment intents.', 'wc-stripe-custom-meta' ),
				'id'    => 'wc_stripe_custom_meta_title',
			),
			array(
				'title' => __( 'Multi-Product Handling Method', 'wc-stripe-custom-meta' ),
				'type'  => 'multi_product_method',
				'id'    => 'wc_stripe_custom_meta_multi_product_method',
			),
			array(
				'title' => __( 'Cart & Order Metadata', 'wc-stripe-custom-meta' ),
				'desc'  => __( 'Select cart and order fields to include in Stripe metadata.', 'wc-stripe-custom-meta' ),
				'type'  => 'metadata_checkboxes',
				'id'    => 'wc_stripe_custom_meta_cart',
				'meta_type' => 'cart',
			),
			array(
				'title' => __( 'User Metadata', 'wc-stripe-custom-meta' ),
				'desc'  => __( 'Select user metadata fields to include in Stripe metadata.', 'wc-stripe-custom-meta' ),
				'type'  => 'metadata_checkboxes',
				'id'    => 'wc_stripe_custom_meta_user',
				'meta_type' => 'user',
			),
			array(
				'title' => __( 'Product Metadata', 'wc-stripe-custom-meta' ),
				'desc'  => __( 'Select product metadata fields to include in Stripe metadata.', 'wc-stripe-custom-meta' ),
				'type'  => 'metadata_checkboxes',
				'id'    => 'wc_stripe_custom_meta_product',
				'meta_type' => 'product',
			),
			array(
				'title' => __( 'Static Metadata Pairs', 'wc-stripe-custom-meta' ),
				'desc'  => __( 'Add custom static key-value pairs that will always be sent to Stripe.', 'wc-stripe-custom-meta' ),
				'type'  => 'static_metadata',
				'id'    => 'wc_stripe_custom_meta_static',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wc_stripe_custom_meta_end',
			),
		);

		// Merge custom settings into existing settings.
		return array_merge( $settings, $custom_settings );
	}

	/**
	 * Render metadata checkboxes field.
	 *
	 * @since 1.0.0
	 * @param array $field The field configuration.
	 */
	public function render_metadata_checkboxes( $field ) {
		$option_name = $this->option_name;
		$settings    = get_option( $option_name, array() );
		$meta_type   = isset( $field['meta_type'] ) ? $field['meta_type'] : 'cart';

		// Get available metadata for this type.
		switch ( $meta_type ) {
			case 'cart':
				$available_metadata = WC_Stripe_Custom_Meta_Collector::get_cart_metadata();
				$settings_key = 'cart_metadata';
				break;
			case 'user':
				$available_metadata = WC_Stripe_Custom_Meta_Collector::get_user_metadata();
				$settings_key = 'user_metadata';
				break;
			case 'product':
				$available_metadata = WC_Stripe_Custom_Meta_Collector::get_product_metadata();
				$settings_key = 'product_metadata';
				break;
			default:
				return;
		}

		$selected_fields = isset( $settings[ $settings_key ] ) ? $settings[ $settings_key ] : array();

		echo '<fieldset>';
		echo '<legend class="screen-reader-text"><span>' . esc_html( $field['title'] ) . '</span></legend>';

		if ( isset( $field['desc'] ) && $field['desc'] ) {
			echo '<p class="description">' . wp_kses_post( $field['desc'] ) . '</p>';
		}

		echo '<div class="wc-stripe-custom-meta-checkboxes">';

		if ( ! empty( $available_metadata ) ) {
			foreach ( $available_metadata as $key => $label ) {
				$field_id    = sanitize_html_class( $field['id'] . '_' . $key );
				$field_name  = sprintf( '%s[%s][]', $option_name, $settings_key );
				$is_checked  = in_array( $key, $selected_fields, true );

				?>
				<label for="<?php echo esc_attr( $field_id ); ?>">
					<input
						type="checkbox"
						name="<?php echo esc_attr( $field_name ); ?>"
						id="<?php echo esc_attr( $field_id ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						<?php checked( $is_checked ); ?>
					/>
					<span><?php echo esc_html( $label ); ?></span>
				</label>
				<?php
			}
		} else {
			echo '<p>' . esc_html( __( 'No metadata fields available for this category.', 'wc-stripe-custom-meta' ) ) . '</p>';
		}

		echo '</div>';
		echo '</fieldset>';
	}

	/**
	 * Render static metadata field.
	 *
	 * @since 1.0.0
	 * @param array $field The field configuration.
	 */
	public function render_static_metadata( $field ) {
		$option_name = $this->option_name;
		$settings    = get_option( $option_name, array() );
		$static_meta = isset( $settings['static_metadata'] ) ? $settings['static_metadata'] : array();

		echo '<fieldset>';
		echo '<legend class="screen-reader-text"><span>' . esc_html( $field['title'] ) . '</span></legend>';

		if ( isset( $field['desc'] ) && $field['desc'] ) {
			echo '<p class="description">' . wp_kses_post( $field['desc'] ) . '</p>';
		}

		echo '<div id="wc-stripe-custom-meta-static-wrapper" class="wc-stripe-custom-meta-static-wrapper">';

		if ( ! empty( $static_meta ) ) {
			foreach ( $static_meta as $index => $pair ) {
				$this->render_static_metadata_row( $option_name, $index, $pair );
			}
		}

		echo '</div>';

		echo '<button type="button" class="button" id="wc-stripe-custom-meta-add-static">' . esc_html( __( 'Add Metadata Pair', 'wc-stripe-custom-meta' ) ) . '</button>';

		echo '</fieldset>';

		// Output JavaScript for dynamic rows.
		$this->output_static_metadata_script();
	}

	/**
	 * Render a single static metadata row.
	 *
	 * @since 1.0.0
	 * @param string $option_name The option name for the field.
	 * @param int    $index The row index.
	 * @param array  $pair The key-value pair.
	 */
	private function render_static_metadata_row( $option_name, $index, $pair ) {
		$key   = isset( $pair['key'] ) ? $pair['key'] : '';
		$value = isset( $pair['value'] ) ? $pair['value'] : '';

		?>
		<div class="wc-stripe-custom-meta-static-row" data-index="<?php echo esc_attr( $index ); ?>">
			<input
				type="text"
				name="<?php echo esc_attr( $option_name ); ?>[static_metadata][<?php echo esc_attr( $index ); ?>][key]"
				placeholder="<?php esc_attr_e( 'Metadata Key (max 40 chars)', 'wc-stripe-custom-meta' ); ?>"
				maxlength="40"
				value="<?php echo esc_attr( $key ); ?>"
				class="regular-text"
			/>
			<input
				type="text"
				name="<?php echo esc_attr( $option_name ); ?>[static_metadata][<?php echo esc_attr( $index ); ?>][value]"
				placeholder="<?php esc_attr_e( 'Metadata Value (max 500 chars)', 'wc-stripe-custom-meta' ); ?>"
				maxlength="500"
				value="<?php echo esc_attr( $value ); ?>"
				class="large-text"
			/>
			<button type="button" class="button wc-stripe-custom-meta-remove-row" title="<?php esc_attr_e( 'Remove this row', 'wc-stripe-custom-meta' ); ?>">
				<?php esc_html_e( 'Remove', 'wc-stripe-custom-meta' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render multi-product method field.
	 *
	 * @since 1.0.0
	 * @param array $field The field configuration.
	 */
	public function render_multi_product_method( $field ) {
		$option_name = $this->option_name;
		$settings    = get_option( $option_name, array() );
		$method      = isset( $settings['multi_product_method'] ) ? $settings['multi_product_method'] : 'delimited';

		echo '<fieldset>';
		echo '<legend class="screen-reader-text"><span>' . esc_html( $field['title'] ) . '</span></legend>';

		?>
		<p>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( $option_name ); ?>[multi_product_method]"
					value="delimited"
					<?php checked( $method, 'delimited' ); ?>
				/>
				<span><?php esc_html_e( 'Delimited Values (e.g., SKU1,SKU2,SKU3)', 'wc-stripe-custom-meta' ); ?></span>
			</label>
		</p>

		<p>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( $option_name ); ?>[multi_product_method]"
					value="numbered_keys"
					<?php checked( $method, 'numbered_keys' ); ?>
				/>
				<span><?php esc_html_e( 'Numbered Keys (e.g., product_1_sku, product_2_sku)', 'wc-stripe-custom-meta' ); ?></span>
			</label>
		</p>

		<?php
		echo '</fieldset>';
	}

	/**
	 * Output JavaScript for managing static metadata rows dynamically.
	 *
	 * @since 1.0.0
	 */
	private function output_static_metadata_script() {
		?>
		<script>
		(function( $ ) {
			'use strict';

			$( document ).ready(function() {
				var nextIndex = $( '.wc-stripe-custom-meta-static-row' ).length;

				// Add new row.
				$( '#wc-stripe-custom-meta-add-static' ).on( 'click', function( e ) {
					e.preventDefault();

					var wrapper = $( '#wc-stripe-custom-meta-static-wrapper' );
					var optionName = wrapper.closest( 'tr' ).find( 'input' ).attr( 'name' ).split( '[' )[0];

					var html = '<div class="wc-stripe-custom-meta-static-row" data-index="' + nextIndex + '">' +
						'<input type="text" name="' + optionName + '[static_metadata][' + nextIndex + '][key]" ' +
						'placeholder="Metadata Key (max 40 chars)" maxlength="40" class="regular-text" />' +
						'<input type="text" name="' + optionName + '[static_metadata][' + nextIndex + '][value]" ' +
						'placeholder="Metadata Value (max 500 chars)" maxlength="500" class="large-text" />' +
						'<button type="button" class="button wc-stripe-custom-meta-remove-row"><?php esc_html_e( 'Remove', 'wc-stripe-custom-meta' ); ?></button>' +
						'</div>';

					wrapper.append( html );
					nextIndex++;
				});

				// Remove row.
				$( document ).on( 'click', '.wc-stripe-custom-meta-remove-row', function( e ) {
					e.preventDefault();
					$( this ).closest( '.wc-stripe-custom-meta-static-row' ).remove();
				});
			});
		})( jQuery );
		</script>
		<?php
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @since 1.0.0
	 * @param array $input The input settings to sanitize.
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		// Sanitize cart metadata array.
		if ( isset( $input['cart_metadata'] ) && is_array( $input['cart_metadata'] ) ) {
			$sanitized['cart_metadata'] = array_map( 'sanitize_key', $input['cart_metadata'] );
		}

		// Sanitize user metadata array.
		if ( isset( $input['user_metadata'] ) && is_array( $input['user_metadata'] ) ) {
			$sanitized['user_metadata'] = array_map( 'sanitize_key', $input['user_metadata'] );
		}

		// Sanitize product metadata array.
		if ( isset( $input['product_metadata'] ) && is_array( $input['product_metadata'] ) ) {
			$sanitized['product_metadata'] = array_map( 'sanitize_key', $input['product_metadata'] );
		}

		// Sanitize static metadata pairs.
		if ( isset( $input['static_metadata'] ) && is_array( $input['static_metadata'] ) ) {
			$sanitized['static_metadata'] = array();
			foreach ( $input['static_metadata'] as $pair ) {
				if ( is_array( $pair ) && ! empty( $pair['key'] ) && ! empty( $pair['value'] ) ) {
					$sanitized['static_metadata'][] = array(
						'key'   => substr( sanitize_key( $pair['key'] ), 0, 40 ),
						'value' => substr( sanitize_text_field( $pair['value'] ), 0, 500 ),
					);
				}
			}
		}

		// Sanitize multi-product method.
		if ( isset( $input['multi_product_method'] ) ) {
			$method = sanitize_key( $input['multi_product_method'] );
			if ( in_array( $method, array( 'delimited', 'numbered_keys' ), true ) ) {
				$sanitized['multi_product_method'] = $method;
			}
		}

		return $sanitized;
	}
}
