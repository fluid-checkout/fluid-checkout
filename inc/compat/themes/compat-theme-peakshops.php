<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: PeakShops (by fuelthemes).
 */
class FluidCheckout_ThemeCompat_PeakShops extends FluidCheckout {

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
		// Checkout Page Layout
		remove_action( 'woocommerce_checkout_before_customer_details', 'thb_checkout_before_customer_details', 5 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'thb_checkout_after_customer_details', 30 );
		remove_action( 'woocommerce_checkout_after_order_review', 'thb_checkout_after_order_review', 30 );
	}

}

FluidCheckout_ThemeCompat_PeakShops::instance();
