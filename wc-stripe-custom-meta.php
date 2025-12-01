<?php
/**
 * Plugin Name: WooCommerce Stripe Custom Meta
 * Plugin URI: https://github.com/WeMakeGood/wc-stripe-custom-meta
 * Description: Interactive admin interface for selecting metadata fields to push to Stripe payment intents. Compatible with WooCommerce Stripe Gateway and Payment Plugins for Stripe. Includes WooCommerce Subscriptions support.
 * Version: 1.2.0
 * Author: WeMakeGood
 * Author URI: https://www.wemakegood.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-stripe-custom-meta
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 10.4
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define( 'WC_STRIPE_CUSTOM_META_VERSION', '1.2.0' );
define( 'WC_STRIPE_CUSTOM_META_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_STRIPE_CUSTOM_META_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_STRIPE_CUSTOM_META_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce Stripe Gateway (official) is active.
 *
 * @since 1.2.0
 * @return bool True if official gateway is active.
 */
function wc_stripe_custom_meta_is_official_gateway_active() {
	return class_exists( 'WC_Stripe' ) || class_exists( 'WC_Gateway_Stripe' );
}

/**
 * Check if Payment Plugins for Stripe WooCommerce is active.
 *
 * @since 1.2.0
 * @return bool True if Payment Plugins gateway is active.
 */
function wc_stripe_custom_meta_is_payment_plugins_active() {
	return function_exists( 'stripe_wc' ) || class_exists( 'WC_Stripe_Gateway' );
}

/**
 * Check if any supported Stripe gateway is active.
 *
 * @since 1.2.0
 * @return bool True if any supported gateway is active.
 */
function wc_stripe_custom_meta_has_stripe_gateway() {
	return wc_stripe_custom_meta_is_official_gateway_active() || wc_stripe_custom_meta_is_payment_plugins_active();
}

/**
 * Begins execution of the plugin - EARLY initialization to load admin page.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_early_init() {
	// Load plugin classes.
	require_once WC_STRIPE_CUSTOM_META_PLUGIN_DIR . 'includes/class-admin-page.php';
	require_once WC_STRIPE_CUSTOM_META_PLUGIN_DIR . 'includes/class-metadata-collector.php';

	// Initialize admin page - creates standalone configuration interface.
	new WC_Stripe_Custom_Meta_Admin_Page();
}

/**
 * Main plugin initialization - handles metadata handler and dependency checks.
 *
 * @since 1.0.0
 */
function wc_stripe_custom_meta_init() {
	// Check if WooCommerce is active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wc_stripe_custom_meta_missing_woocommerce_notice' );
		return;
	}

	// Load metadata handler.
	require_once WC_STRIPE_CUSTOM_META_PLUGIN_DIR . 'includes/class-stripe-metadata-handler.php';

	// Initialize metadata handler - hooks into Stripe payment processing.
	new WC_Stripe_Metadata_Handler();
}

// Hook early to ensure settings filter is registered before Stripe loads
add_action( 'plugins_loaded', 'wc_stripe_custom_meta_early_init', 1 );

// Hook for metadata handler after other plugins are loaded
add_action( 'plugins_loaded', 'wc_stripe_custom_meta_init', 20 );

/**
 * Declare HPOS (High Performance Order Storage) compatibility.
 *
 * @since 1.0.0
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

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
 * Display notice if no supported Stripe gateway is active.
 *
 * @since 1.0.0
 * @since 1.2.0 Updated to support multiple gateways.
 */
function wc_stripe_custom_meta_missing_stripe_notice() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				__( '<strong>WooCommerce Stripe Custom Meta</strong> requires a Stripe payment gateway to be installed and activated.<br>Supported gateways: <strong>WooCommerce Stripe Gateway</strong> (official) or <strong>Payment Plugins for Stripe WooCommerce</strong>.', 'wc-stripe-custom-meta' )
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
