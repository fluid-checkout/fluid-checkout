<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Uncode (by Undsgn).
 */
class FluidCheckout_ThemeCompat_Uncode extends FluidCheckout {

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
		// Order summary section
		remove_action( 'woocommerce_review_order_before_cart_contents', 'uncode_woocommerce_activate_thumbs_on_order_review_table' ); 
	}

}

FluidCheckout_ThemeCompat_Uncode::instance();
