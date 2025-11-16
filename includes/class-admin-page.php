<?php
/**
 * Admin Page Class
 *
 * Creates a standalone admin page for managing Stripe custom metadata settings.
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Stripe_Custom_Meta_Admin_Page
 *
 * Creates and manages the standalone admin page.
 */
class WC_Stripe_Custom_Meta_Admin_Page {

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

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add admin menu item.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Stripe Custom Metadata', 'wc-stripe-custom-meta' ),
			__( 'Stripe Metadata', 'wc-stripe-custom-meta' ),
			$this->capability,
			'wc-stripe-custom-meta',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || 'woocommerce_page_wc-stripe-custom-meta' !== $screen->id ) {
			return;
		}

		// Enqueue styles
		wp_enqueue_style(
			'wc-stripe-custom-meta-admin',
			WC_STRIPE_CUSTOM_META_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			WC_STRIPE_CUSTOM_META_VERSION
		);

		// Enqueue scripts for dynamic metadata rows
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script(
			'wc-stripe-custom-meta-admin',
			WC_STRIPE_CUSTOM_META_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WC_STRIPE_CUSTOM_META_VERSION,
			true
		);
	}

	/**
	 * Handle form submissions.
	 *
	 * @since 1.0.0
	 */
	public function handle_form_submission() {
		// Check if this is our form submission
		if ( ! isset( $_POST['wc_stripe_custom_meta_nonce'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc_stripe_custom_meta_nonce'] ) ), 'wc_stripe_custom_meta_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wc-stripe-custom-meta' ) );
		}

		// Check capability
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'wc-stripe-custom-meta' ) );
		}

		// Collect and sanitize data
		$settings = array();

		// Multi-product method
		if ( isset( $_POST['multi_product_method'] ) ) {
			$method = sanitize_key( wp_unslash( $_POST['multi_product_method'] ) );
			if ( in_array( $method, array( 'delimited', 'numbered_keys' ), true ) ) {
				$settings['multi_product_method'] = $method;
			}
		}

		// Cart metadata
		if ( isset( $_POST['cart_metadata'] ) && is_array( $_POST['cart_metadata'] ) ) {
			$settings['cart_metadata'] = array_map( 'sanitize_key', wp_unslash( $_POST['cart_metadata'] ) );
		}

		// User metadata
		if ( isset( $_POST['user_metadata'] ) && is_array( $_POST['user_metadata'] ) ) {
			$settings['user_metadata'] = array_map( 'sanitize_key', wp_unslash( $_POST['user_metadata'] ) );
		}

		// Product metadata
		if ( isset( $_POST['product_metadata'] ) && is_array( $_POST['product_metadata'] ) ) {
			$settings['product_metadata'] = array_map( 'sanitize_key', wp_unslash( $_POST['product_metadata'] ) );
		}

		// Subscription metadata
		if ( isset( $_POST['subscription_metadata'] ) && is_array( $_POST['subscription_metadata'] ) ) {
			$settings['subscription_metadata'] = array_map( 'sanitize_key', wp_unslash( $_POST['subscription_metadata'] ) );
		}

		// Static metadata pairs
		if ( isset( $_POST['static_metadata_keys'] ) && is_array( $_POST['static_metadata_keys'] ) ) {
			$settings['static_metadata'] = array();
			$keys = array_map( 'sanitize_text_field', wp_unslash( $_POST['static_metadata_keys'] ) );
			$values = array_map( 'sanitize_text_field', wp_unslash( $_POST['static_metadata_values'] ) );

			foreach ( $keys as $index => $key ) {
				if ( ! empty( $key ) && isset( $values[ $index ] ) && ! empty( $values[ $index ] ) ) {
					$settings['static_metadata'][] = array(
						'key'   => substr( $key, 0, 40 ),
						'value' => substr( $values[ $index ], 0, 500 ),
					);
				}
			}
		}

		// Save settings
		update_option( $this->option_name, $settings );

		// Redirect with success message
		wp_safe_remote_post( admin_url( 'admin.php?page=wc-stripe-custom-meta&saved=1' ) );
		wp_redirect( admin_url( 'admin.php?page=wc-stripe-custom-meta&saved=1' ) );
		exit;
	}

	/**
	 * Render the admin page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		// Check capability
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wc-stripe-custom-meta' ) );
		}

		// Get current settings
		$settings = get_option( $this->option_name, array() );
		$multi_product_method = isset( $settings['multi_product_method'] ) ? $settings['multi_product_method'] : 'delimited';
		$cart_metadata = isset( $settings['cart_metadata'] ) ? $settings['cart_metadata'] : array();
		$user_metadata = isset( $settings['user_metadata'] ) ? $settings['user_metadata'] : array();
		$product_metadata = isset( $settings['product_metadata'] ) ? $settings['product_metadata'] : array();
		$subscription_metadata = isset( $settings['subscription_metadata'] ) ? $settings['subscription_metadata'] : array();
		$static_metadata = isset( $settings['static_metadata'] ) ? $settings['static_metadata'] : array();

		// Get available metadata
		$available_cart = WC_Stripe_Custom_Meta_Collector::get_cart_metadata();
		$available_user = WC_Stripe_Custom_Meta_Collector::get_user_metadata();
		$available_product = WC_Stripe_Custom_Meta_Collector::get_product_metadata();
		$available_subscription = WC_Stripe_Custom_Meta_Collector::get_subscription_metadata();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Stripe Custom Metadata Configuration', 'wc-stripe-custom-meta' ); ?></h1>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'wc-stripe-custom-meta' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'wc_stripe_custom_meta_action', 'wc_stripe_custom_meta_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<!-- Multi-Product Method -->
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Multi-Product Handling Method', 'wc-stripe-custom-meta' ); ?></label>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Multi-Product Handling Method', 'wc-stripe-custom-meta' ); ?>
									</legend>
									<p>
										<label>
											<input type="radio" name="multi_product_method" value="delimited" <?php checked( $multi_product_method, 'delimited' ); ?> />
											<?php esc_html_e( 'Delimited Values (e.g., SKU1,SKU2,SKU3)', 'wc-stripe-custom-meta' ); ?>
										</label>
									</p>
									<p>
										<label>
											<input type="radio" name="multi_product_method" value="numbered_keys" <?php checked( $multi_product_method, 'numbered_keys' ); ?> />
											<?php esc_html_e( 'Numbered Keys (e.g., product_1_sku, product_2_sku)', 'wc-stripe-custom-meta' ); ?>
										</label>
									</p>
									<p class="description">
										<?php esc_html_e( 'Choose how metadata from multiple products should be combined in a single order.', 'wc-stripe-custom-meta' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- Cart Metadata -->
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Cart & Order Metadata', 'wc-stripe-custom-meta' ); ?></label>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Cart & Order Metadata', 'wc-stripe-custom-meta' ); ?>
									</legend>
									<div class="wc-stripe-custom-meta-checkboxes">
										<?php foreach ( $available_cart as $key => $label ) : ?>
											<label for="cart_meta_<?php echo esc_attr( $key ); ?>">
												<input
													type="checkbox"
													name="cart_metadata[]"
													id="cart_meta_<?php echo esc_attr( $key ); ?>"
													value="<?php echo esc_attr( $key ); ?>"
													<?php checked( in_array( $key, $cart_metadata, true ) ); ?>
												/>
												<span><?php echo esc_html( $label ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<p class="description">
										<?php esc_html_e( 'Select order-level fields to include in Stripe metadata.', 'wc-stripe-custom-meta' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- User Metadata -->
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'User Metadata', 'wc-stripe-custom-meta' ); ?></label>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'User Metadata', 'wc-stripe-custom-meta' ); ?>
									</legend>
									<?php if ( ! empty( $available_user ) ) : ?>
										<div class="wc-stripe-custom-meta-checkboxes">
											<?php foreach ( $available_user as $key => $label ) : ?>
												<label for="user_meta_<?php echo esc_attr( $key ); ?>">
													<input
														type="checkbox"
														name="user_metadata[]"
														id="user_meta_<?php echo esc_attr( $key ); ?>"
														value="<?php echo esc_attr( $key ); ?>"
														<?php checked( in_array( $key, $user_metadata, true ) ); ?>
													/>
													<span><?php echo esc_html( $label ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
										<p class="description">
											<?php esc_html_e( 'Select custom user metadata fields to include in Stripe metadata.', 'wc-stripe-custom-meta' ); ?>
										</p>
									<?php else : ?>
										<p class="description">
											<?php esc_html_e( 'No custom user metadata fields found. Use the Static Metadata section below to add custom data.', 'wc-stripe-custom-meta' ); ?>
										</p>
									<?php endif; ?>
								</fieldset>
							</td>
						</tr>

						<!-- Product Metadata -->
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Product Metadata', 'wc-stripe-custom-meta' ); ?></label>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Product Metadata', 'wc-stripe-custom-meta' ); ?>
									</legend>
									<?php if ( ! empty( $available_product ) ) : ?>
										<div class="wc-stripe-custom-meta-checkboxes">
											<?php foreach ( $available_product as $key => $label ) : ?>
												<label for="product_meta_<?php echo esc_attr( $key ); ?>">
													<input
														type="checkbox"
														name="product_metadata[]"
														id="product_meta_<?php echo esc_attr( $key ); ?>"
														value="<?php echo esc_attr( $key ); ?>"
														<?php checked( in_array( $key, $product_metadata, true ) ); ?>
													/>
													<span><?php echo esc_html( $label ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
										<p class="description">
											<?php esc_html_e( 'Select custom product metadata fields to include in Stripe metadata.', 'wc-stripe-custom-meta' ); ?>
										</p>
									<?php else : ?>
										<p class="description">
											<?php esc_html_e( 'No custom product metadata fields found. Use the Static Metadata section below to add custom data.', 'wc-stripe-custom-meta' ); ?>
										</p>
									<?php endif; ?>
								</fieldset>
							</td>
						</tr>

						<!-- Subscription Metadata -->
						<?php if ( ! empty( $available_subscription ) ) : ?>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Subscription Metadata', 'wc-stripe-custom-meta' ); ?></label>
								</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text">
											<?php esc_html_e( 'Subscription Metadata', 'wc-stripe-custom-meta' ); ?>
										</legend>
										<div class="wc-stripe-custom-meta-checkboxes">
											<?php foreach ( $available_subscription as $key => $label ) : ?>
												<label for="subscription_meta_<?php echo esc_attr( $key ); ?>">
													<input
														type="checkbox"
														name="subscription_metadata[]"
														id="subscription_meta_<?php echo esc_attr( $key ); ?>"
														value="<?php echo esc_attr( $key ); ?>"
														<?php checked( in_array( $key, $subscription_metadata, true ) ); ?>
													/>
													<span><?php echo esc_html( $label ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
										<p class="description">
											<?php esc_html_e( 'Select subscription fields to include when processing subscription orders, renewals, and related payments. Only available when WooCommerce Subscriptions is active.', 'wc-stripe-custom-meta' ); ?>
										</p>
									</fieldset>
								</td>
							</tr>
						<?php endif; ?>

						<!-- Static Metadata -->
						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'Static Metadata Pairs', 'wc-stripe-custom-meta' ); ?></label>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<?php esc_html_e( 'Static Metadata Pairs', 'wc-stripe-custom-meta' ); ?>
									</legend>
									<table class="wc-stripe-custom-meta-static-table">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Key (max 40 chars)', 'wc-stripe-custom-meta' ); ?></th>
												<th><?php esc_html_e( 'Value (max 500 chars)', 'wc-stripe-custom-meta' ); ?></th>
											</tr>
										</thead>
										<tbody id="static-metadata-tbody">
											<?php if ( ! empty( $static_metadata ) ) : ?>
												<?php foreach ( $static_metadata as $index => $pair ) : ?>
													<tr class="static-metadata-row">
														<td>
															<input
																type="text"
																name="static_metadata_keys[]"
																value="<?php echo esc_attr( $pair['key'] ); ?>"
																maxlength="40"
																class="regular-text"
															/>
														</td>
														<td>
															<input
																type="text"
																name="static_metadata_values[]"
																value="<?php echo esc_attr( $pair['value'] ); ?>"
																maxlength="500"
																class="large-text"
															/>
														</td>
													</tr>
												<?php endforeach; ?>
											<?php else : ?>
												<tr class="static-metadata-row">
													<td>
														<input type="text" name="static_metadata_keys[]" maxlength="40" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., store_name', 'wc-stripe-custom-meta' ); ?>" />
													</td>
													<td>
														<input type="text" name="static_metadata_values[]" maxlength="500" class="large-text" placeholder="<?php esc_attr_e( 'e.g., My Store', 'wc-stripe-custom-meta' ); ?>" />
													</td>
												</tr>
											<?php endif; ?>
										</tbody>
									</table>
									<button type="button" class="button" id="add-static-metadata-row">
										<?php esc_html_e( 'Add Row', 'wc-stripe-custom-meta' ); ?>
									</button>
									<p class="description">
										<?php esc_html_e( 'Add custom key-value pairs that will always be sent to Stripe.', 'wc-stripe-custom-meta' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
