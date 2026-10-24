<?php
defined( 'ABSPATH' ) || exit;

/**
 * Shared request, page, and admin screen helpers.
 */
class FluidCheckout_Utils extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Intentionally empty. Do not call parent __construct() to avoid duplicate hooks from parent class.
	}



	/**
	 * Returns whether the current page is the checkout page or requesting for checkout fragments.
	 */
	public function is_checkout_page_or_fragment() {
		global $wp_query;
		$ajax_action = $wp_query->get( 'wc-ajax' );

		// Return `true` if any of the following conditions are met:
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page() ) { return true; }
		if ( 'update_order_review' === $ajax_action ) { return true; }
		if ( 'checkout' === $ajax_action ) { return true; }
		if ( array_key_exists( 'wc-ajax', $_GET ) && 'update_order_review' === sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ?? '' ) ) ) { return true; }
		if ( array_key_exists( 'wc-ajax', $_GET ) && 'checkout' === sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ?? '' ) ) ) { return true; }

		// Filter to allow other plugins to add their own conditions
		/**
		 * Filters whether the current request is the checkout page or a checkout fragment.
		 *
		 * @since 2.3.1
		 *
		 * @param bool $value Value to filter. Default false.
		 */
		if ( true === apply_filters( 'fc_is_checkout_page_or_fragment', false ) ) { return true; }

		// Otherwise, return `false`
		return false;
	}

	/**
	 * Returns whether the current page is the cart page or requesting for cart fragments.
	 */
	public function is_cart_page_or_fragment() {
		global $wp_query;
		$ajax_action = wc_clean( wp_unslash( $wp_query->get( 'wc-ajax' ) ) );

		// Return `true` if any of the following conditions are met:
		if ( function_exists( 'is_cart' ) && is_cart() ) { return true; }
		if ( 'fc_pro_update_cart_fragments' === $ajax_action ) { return true; }
		if ( ( array_key_exists( 'wc-ajax', $_GET ) && 'fc_pro_update_cart_fragments' === sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ?? '' ) ) ) ) { return true; } // Needed to check for AJAX calls for the cart fragments early in the request.

		// Filter to allow other plugins to add their own conditions
		/**
		 * Filters whether the current request is the cart page or a cart fragment.
		 *
		 * @since 1.6.0
		 *
		 * @param bool $value Value to filter. Default false.
		 */
		if ( true === apply_filters( 'fc_is_cart_page_or_fragment', false ) ) { return true; }

		// Otherwise, return `false`
		return false;
	}

	/**
	 * Returns whether the current page is the order pay page or requesting for order pay fragments.
	 */
	public function is_order_pay_page_or_fragment() {
		global $wp;

		// Check whether the order pay endpoint query var is present.
		// Steps register on `parse_request`, before `is_checkout_pay_page()` is available, so this catches the order pay page early.
		if ( ! is_admin() && ! empty( $wp->query_vars['order-pay'] ) ) { return true; }

		// Return `true` if any of the following conditions are met:
		if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) { return true; }

		// Filter to allow other plugins to add their own conditions (Lite filter name)
		/**
		 * Filters whether the current request is the order pay page or an order pay fragment.
		 *
		 * @param bool $value Whether order pay page or fragment. Default false.
		 */
		if ( true === apply_filters( 'fc_is_order_pay_page_or_fragment', false ) ) { return true; }

		// Also honor the PRO filter name for backwards compatibility
		/**
		 * Filters whether order pay page or fragment.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $value Whether order pay page or fragment. Default false.
		 */
		if ( true === apply_filters( 'fc_pro_is_order_pay_page_or_fragment', false ) ) { return true; }

		// Otherwise, return `false`
		return false;
	}

	/**
	 * Returns whether the current page uses the WooCommerce checkout block.
	 */
	public function is_checkout_page_block_based() {
		// Prefer WordPress has_block when available
		if ( function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' ) ) {
			return true;
		}

		// Fall back to WooCommerce Blocks utility when available
		if ( class_exists( 'WC_Blocks_Utils' ) && WC_Blocks_Utils::has_block_in_page( get_queried_object_id(), 'woocommerce/checkout' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns whether the current page is the My Account edit-address endpoint.
	 */
	public function is_edit_address_page() {
		// Bail if WooCommerce account helpers are not available
		if ( ! function_exists( 'is_account_page' ) || ! function_exists( 'is_wc_endpoint_url' ) ) { return false; }

		// Bail if not on the account edit-address endpoint
		if ( ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return false; }

		return true;
	}



	/**
	 * Check if HPOS (custom order tables) is being used.
	 */
	public function is_hpos_enabled() {
		return 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' );
	}

	/**
	 * Get the order edit screen ID.
	 */
	public function get_order_edit_screen_id() {
		return $this->is_hpos_enabled() ? 'woocommerce_page_wc-orders' : 'shop_order';
	}

	/**
	 * Returns whether the current page is the admin edit order page.
	 */
	public function is_admin_edit_order_page() {
		// Bail if not on admin screen
		if ( ! is_admin() ) { return false; }

		// Bail if current screen is not available
		if ( ! function_exists( 'get_current_screen' ) || ! is_object( get_current_screen() ) ) { return false; }

		// Bail if not on the order edit screen
		if ( ! in_array( get_current_screen()->id, array( $this->get_order_edit_screen_id() ), true ) ) { return false; }

		return true;
	}

	/**
	 * Returns whether the current page is the admin new order page.
	 */
	public function is_admin_new_order_page() {
		// Bail if not on admin
		if ( ! is_admin() ) { return false; }

		// HPOS new order screen
		if ( isset( $_GET['page'] ) && 'wc-orders' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['action'] ) && 'new' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		// Classic new order screen
		if ( ! isset( $_GET['post_type'] ) || 'shop_order' !== sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		// Get the current screen
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Bail if current screen action is not 'add'
		if ( ! $screen || 'add' !== $screen->action ) { return false; }

		return true;
	}

}

FluidCheckout_Utils::instance();
