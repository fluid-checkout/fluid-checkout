<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Listable (by Pixelgrade).
 */
class FluidCheckout_ThemeCompat_Listable extends FluidCheckout {

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
		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Remove Listable's custom place order button from shipping section and after customer details
		remove_action( 'woocommerce_checkout_shipping', 'listable_checkout_place_order_button', 20 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 20 );

		// Remove Listable's login and coupon forms from checkout
		remove_action( 'woocommerce_checkout_before_customer_details', 'woocommerce_checkout_login_form', 10 );
		remove_action( 'woocommerce_checkout_before_customer_details', 'woocommerce_checkout_coupon_form', 10 );
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '46px',
				'--fluidcheckout--field--padding-left' => '15px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--color' => 'inherit',
				'--fluidcheckout--field--font-size' => '15px',
				'--fluidcheckout--field--border-color' => 'rgba(0, 0, 0, 0.075)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Listable::instance();
