<?php
/**
 * Subscription Detector Class
 *
 * Handles detection and access of WooCommerce Subscriptions data with graceful fallback.
 *
 * @package WC_Stripe_Custom_Meta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Stripe_Custom_Meta_Subscription_Detector
 *
 * Provides helper methods for detecting and accessing subscription data.
 */
class WC_Stripe_Custom_Meta_Subscription_Detector {

	/**
	 * Check if WooCommerce Subscriptions plugin is active.
	 *
	 * @since 1.1.0
	 * @return bool True if subscriptions plugin is active.
	 */
	public static function is_subscription_plugin_active() {
		return function_exists( 'wcs_get_subscription' ) && function_exists( 'wcs_order_contains_subscription' );
	}

	/**
	 * Check if an order contains any subscription relationship.
	 *
	 * @since 1.1.0
	 * @param WC_Order|int $order The order object or order ID.
	 * @return bool True if order is related to a subscription.
	 */
	public static function is_subscription_order( $order ) {
		if ( ! self::is_subscription_plugin_active() ) {
			return false;
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return false;
		}

		return wcs_order_contains_subscription( $order, 'any' );
	}

	/**
	 * Get the subscription order type (parent, renewal, switch, resubscribe, or none).
	 *
	 * @since 1.1.0
	 * @param WC_Order|int $order The order object or order ID.
	 * @return string The subscription type: 'parent', 'renewal', 'switch', 'resubscribe', or 'none'.
	 */
	public static function get_subscription_order_type( $order ) {
		if ( ! self::is_subscription_plugin_active() ) {
			return 'none';
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return 'none';
		}

		// Check in priority order
		if ( wcs_order_contains_parent( $order ) ) {
			return 'parent';
		}
		if ( wcs_order_contains_renewal( $order ) ) {
			return 'renewal';
		}
		if ( wcs_order_contains_switch( $order ) ) {
			return 'switch';
		}
		if ( wcs_order_contains_resubscribe( $order ) ) {
			return 'resubscribe';
		}

		return 'none';
	}

	/**
	 * Get subscriptions related to an order.
	 *
	 * @since 1.1.0
	 * @param WC_Order|int $order The order object or order ID.
	 * @param string       $order_type Type of relationship: 'any', 'parent', 'renewal', 'switch', 'resubscribe'.
	 * @return array Array of WC_Subscription objects, empty array if none found.
	 */
	public static function get_subscriptions_for_order( $order, $order_type = 'any' ) {
		if ( ! self::is_subscription_plugin_active() ) {
			return array();
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return array();
		}

		try {
			$subscriptions = wcs_get_subscriptions_for_order(
				$order,
				array( 'order_type' => $order_type )
			);

			// Ensure we return an array
			if ( ! is_array( $subscriptions ) ) {
				return array();
			}

			return $subscriptions;
		} catch ( Exception $e ) {
			// Log error for debugging
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'WC Subscriptions Error: ' . $e->getMessage() );
			}
			return array();
		}
	}

	/**
	 * Get subscription IDs related to an order.
	 *
	 * @since 1.1.0
	 * @param WC_Order|int $order The order object or order ID.
	 * @param string       $order_type Type of relationship: 'any', 'parent', 'renewal', 'switch', 'resubscribe'.
	 * @return array Array of subscription IDs, empty array if none found.
	 */
	public static function get_subscription_ids_for_order( $order, $order_type = 'any' ) {
		if ( ! self::is_subscription_plugin_active() ) {
			return array();
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return array();
		}

		try {
			$subscription_ids = wcs_get_subscription_ids_for_order(
				$order,
				array( $order_type )
			);

			// Ensure we return an array
			if ( ! is_array( $subscription_ids ) ) {
				return array();
			}

			return $subscription_ids;
		} catch ( Exception $e ) {
			// Log error for debugging
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'WC Subscriptions Error: ' . $e->getMessage() );
			}
			return array();
		}
	}

	/**
	 * Get a subscription object by ID.
	 *
	 * @since 1.1.0
	 * @param int $subscription_id The subscription ID.
	 * @return WC_Subscription|false The subscription object or false if not found.
	 */
	public static function get_subscription( $subscription_id ) {
		if ( ! self::is_subscription_plugin_active() ) {
			return false;
		}

		try {
			$subscription = wcs_get_subscription( $subscription_id );
			return $subscription ? $subscription : false;
		} catch ( Exception $e ) {
			// Log error for debugging
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'WC Subscriptions Error: ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get subscription metadata value.
	 *
	 * @since 1.1.0
	 * @param WC_Subscription $subscription The subscription object.
	 * @param string          $field The subscription field to retrieve.
	 * @return mixed The field value or null if not available.
	 */
	public static function get_subscription_value( $subscription, $field ) {
		if ( ! $subscription ) {
			return null;
		}

		try {
			switch ( $field ) {
				case 'subscription_id':
					return $subscription->get_id();
				case 'subscription_status':
					return $subscription->get_status();
				case 'subscription_billing_period':
					return $subscription->get_billing_period();
				case 'subscription_billing_interval':
					return $subscription->get_billing_interval();
				case 'subscription_total':
					return $subscription->get_total();
				case 'subscription_sign_up_fee':
					return $subscription->get_sign_up_fee();
				case 'subscription_next_payment_date':
					$date = $subscription->get_date( 'next_payment' );
					if ( ! $date ) {
						return null;
					}
					// Handle both DateTime objects and strings
					if ( is_object( $date ) && method_exists( $date, 'format' ) ) {
						return $date->format( 'Y-m-d H:i:s' );
					}
					return (string) $date;
				case 'subscription_trial_end_date':
					$date = $subscription->get_date( 'trial_end' );
					if ( ! $date ) {
						return null;
					}
					if ( is_object( $date ) && method_exists( $date, 'format' ) ) {
						return $date->format( 'Y-m-d H:i:s' );
					}
					return (string) $date;
				case 'subscription_start_date':
					$date = $subscription->get_date( 'start' );
					if ( ! $date ) {
						return null;
					}
					if ( is_object( $date ) && method_exists( $date, 'format' ) ) {
						return $date->format( 'Y-m-d H:i:s' );
					}
					return (string) $date;
				case 'subscription_end_date':
					$date = $subscription->get_date( 'end' );
					if ( ! $date ) {
						return null;
					}
					if ( is_object( $date ) && method_exists( $date, 'format' ) ) {
						return $date->format( 'Y-m-d H:i:s' );
					}
					return (string) $date;
				case 'subscription_payment_count':
					// Get number of completed payments
					$completed_payments = $subscription->get_completed_payment_count();
					return is_numeric( $completed_payments ) ? $completed_payments : 0;
				case 'subscription_parent_order_id':
					return $subscription->get_parent_id();
				default:
					return null;
			}
		} catch ( Exception $e ) {
			// Log error for debugging
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'WC Subscriptions Field Error: ' . $e->getMessage() );
			}
			return null;
		}
	}
}
