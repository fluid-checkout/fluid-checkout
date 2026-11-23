<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Distance Rate Shipping (by WooCommerce).
 */
class FluidCheckout_WooCommerceDistanceRateShipping extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Shipping packages
		// After Fluid Checkout PRO Local Pickup may rebuild package destinations (priorities 5–20).
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'maybe_set_package_destination_address_alias' ), 100 );
	}



	/**
	 * Maybe copy destination `address_1` into `address` for Distance Rate Shipping.
	 *
	 * Distance Rate skips rate calculation during `update_order_review` when
	 * `destination['address']` is empty. WooCommerce packages only set `address_1`.
	 *
	 * @param   array  $packages  The shipping packages.
	 */
	public function maybe_set_package_destination_address_alias( $packages ) {
		// Bail if packages is not an array
		if ( ! is_array( $packages ) ) { return $packages; }

		// Iterate packages
		foreach ( $packages as $package_index => $package ) {
			// Skip packages without a destination array
			if ( ! isset( $package[ 'destination' ] ) || ! is_array( $package[ 'destination' ] ) ) { continue; }

			// Skip when address is already set
			if ( ! empty( $package[ 'destination' ][ 'address' ] ) ) { continue; }

			// Skip when address_1 is empty
			if ( empty( $package[ 'destination' ][ 'address_1' ] ) ) { continue; }

			// Copy address_1 into the address key Distance Rate expects
			$packages[ $package_index ][ 'destination' ][ 'address' ] = $package[ 'destination' ][ 'address_1' ];
		}

		return $packages;
	}

}

FluidCheckout_WooCommerceDistanceRateShipping::instance();
