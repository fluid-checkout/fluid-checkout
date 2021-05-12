<?php

/**
 * Feature for adding coupon codes or gift certificate codes to checkout
 */
class FluidCheckout_CouponCodes extends FluidCheckout {

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
		// Body Class
		add_filter( 'body_class', array( $this, 'add_body_class' ) );

		// Checkout
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	}



	/**
	 * Return Checkout Steps class instance.
	 */
	public function checkout_steps() {
		return FluidCheckout_Steps::instance();
	}



	/**
	 * Add page body class for feature detection.
	 *
	 * @param   array  $classes  Body classes array.
	 */
	public function add_body_class( $classes ) {
		// Bail if not on checkout page.
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() ){ return $classes; }

		return array_merge( $classes, array( 'has-wfc-coupon-code-fields' ) );
	}

}

FluidCheckout_CouponCodes::instance();
