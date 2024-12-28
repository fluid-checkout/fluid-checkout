<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Loobek (by Theme Sky Team).
 */
class FluidCheckout_ThemeCompat_Loobek extends FluidCheckout {

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
		// Coupon bar
		remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 20);
	}

}

FluidCheckout_ThemeCompat_Loobek::instance();