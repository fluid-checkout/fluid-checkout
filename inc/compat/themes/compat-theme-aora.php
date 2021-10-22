<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Aora (by ThemeIsle).
 */
class FluidCheckout_ThemeCompat_Aora extends FluidCheckout {

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
		remove_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 20 );
	}

}

FluidCheckout_ThemeCompat_Aora::instance();
