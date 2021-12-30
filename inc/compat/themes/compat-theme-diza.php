<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Diza.
 */
class FluidCheckout_ThemeCompat_Diza extends FluidCheckout {

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
		// Remove extraneous payment section from order summary
		remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 20 );
	}

}

FluidCheckout_ThemeCompat_Diza::instance();
