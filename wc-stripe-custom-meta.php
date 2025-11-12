<?php
/**
 * Plugin Name: WooCommerce Stripe Custom Meta
 * Plugin URI: https://github.com/WeMakeGood/wc-stripe-custom-meta
 * Description: Interactive admin interface for selecting metadata fields to push to Stripe payment intents
 * Version: 1.0.0
 * Author: WeMakeGood
 * Author URI: https://www.wemakegood.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-stripe-custom-meta
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 10.3
 * Requires at least: 5.9
 * Requires PHP: 7.4
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define( 'WC_STRIPE_CUSTOM_META_VERSION', '1.0.0' );
define( 'WC_STRIPE_CUSTOM_META_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_STRIPE_CUSTOM_META_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_STRIPE_CUSTOM_META_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_init() {
	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wc_stripe_custom_meta_missing_woocommerce_notice' );
		return;
	}

	// Check if WooCommerce Stripe Gateway is active.
	if ( ! class_exists( 'WC_Gateway_Stripe' ) ) {
		add_action( 'admin_notices', 'wc_stripe_custom_meta_missing_stripe_notice' );
		return;
	}

	// Load plugin classes.
	require_once WC_STRIPE_CUSTOM_META_PLUGIN_DIR . 'includes/class-stripe-metadata-handler.php';
	require_once WC_STRIPE_CUSTOM_META_PLUGIN_DIR . 'includes/class-admin-settings.php';
	require_once WC_STRIPE_CUSTOM_META_PLUGIN_DIR . 'includes/class-metadata-collector.php';

	// Initialize admin settings.
	new WC_Stripe_Custom_Meta_Admin_Settings();

	// Initialize metadata handler.
	new WC_Stripe_Metadata_Handler();
}

add_action( 'plugins_loaded', 'wc_stripe_custom_meta_init' );

/**
 * Display notice if WooCommerce is not active.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_missing_woocommerce_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				__( '<strong>WooCommerce Stripe Custom Meta</strong> requires WooCommerce to be installed and activated.', 'wc-stripe-custom-meta' )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display notice if WooCommerce Stripe Gateway is not active.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_missing_stripe_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				__( '<strong>WooCommerce Stripe Custom Meta</strong> requires WooCommerce Stripe Payment Gateway to be installed and activated.', 'wc-stripe-custom-meta' )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Register activation hook.
 *
 * @since 1.0.0
 */
register_activation_hook( __FILE__, 'wc_stripe_custom_meta_activate' );

/**
 * Plugin activation callback.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_activate() {
	// Plugin activation tasks.
	flush_rewrite_rules();
}

/**
 * Register deactivation hook.
 *
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, 'wc_stripe_custom_meta_deactivate' );

/**
 * Plugin deactivation callback.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_deactivate() {
	// Plugin deactivation tasks.
	flush_rewrite_rules();
}
