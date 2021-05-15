<?php

/**
 * Compatibility with Payment Method Stripe.
 */
class FluidCheckout_ThemeCompat_Storefront extends FluidCheckout {

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
		add_filter('wfc_coupon_code_apply_button_classes', array( $this, 'change_coupon_code_apply_button_class' ), 10 );
	}



	/**
	 * Change coupon code apply button class.
	 *
	 * @param   string  $class  Coupon code apply button class.
	 */
	public function change_coupon_code_apply_button_class( $class ) {
		return $class . ' button alt';
	}


}

FluidCheckout_ThemeCompat_Storefront::instance();
