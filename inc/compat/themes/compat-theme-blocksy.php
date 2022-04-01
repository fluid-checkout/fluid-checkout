<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Blocksy.
 */
class FluidCheckout_ThemeCompat_Blocksy extends FluidCheckout {

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
		// Bail if current theme is not blocksy
		if ( 'blocksy' !== get_template() ) { return; }

        // Bail if not on checkout or cart page or doing AJAX call
		if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_cart() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) { return; }

		// Very late hooks
		remove_all_actions( 'woocommerce_checkout_before_customer_details' );
		remove_all_actions( 'woocommerce_checkout_after_customer_details' );
		remove_all_actions( 'woocommerce_checkout_before_order_review_heading' );
		remove_all_actions( 'woocommerce_checkout_after_order_review' );
	}

}

FluidCheckout_ThemeCompat_Blocksy::instance();
