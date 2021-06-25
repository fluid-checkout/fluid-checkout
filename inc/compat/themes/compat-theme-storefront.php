<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Storefront (by WooCommerce).
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
		// Page container class
		remove_filter( 'fc_content_section_class', array( FluidCheckout_Steps::instance(), 'fc_content_section_class' ), 10 );

		// Coupon code button style
		add_filter('fc_coupon_code_apply_button_classes', array( $this, 'change_coupon_code_apply_button_class' ), 10 );
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
