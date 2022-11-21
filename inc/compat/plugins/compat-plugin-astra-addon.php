<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Astra Pro (by Brainstorm Force).
 */
class FluidCheckout_AstraAddon extends FluidCheckout {

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
		// Disable theme addon features
		add_filter( 'astra_get_option_array', array( $this, 'force_change_theme_options' ), 10, 3 );
	}



	/**
	 * Force changing the theme addon feature options to avoid conflicts with Fluid Checkout.
	 */
	public function force_change_theme_options( $theme_options, $option, $default ) {
		// Disable Astra PRO checkout options
		$theme_options[ 'checkout-content-width' ] = 'default';
		$theme_options[ 'checkout-layout-type' ] = 'default';
		$theme_options[ 'two-step-checkout' ] = false;
		$theme_options[ 'checkout-coupon-display' ] = false;
		$theme_options[ 'checkout-labels-as-placeholders' ] = false;
		$theme_options[ 'checkout-persistence-form-data' ] = false;

		// Set display order notes option to `yes` to prevent it from removing the field from the checkout page.
		// Fluid Checkout option to show/hide the order notes field superseeds the theme option.
		$theme_options[ 'checkout-order-notes-display' ] = 'yes';

		// Remove order summary and payment section colors from the theme
		$theme_options[ 'order-summary-background-color' ] = '';
		$theme_options[ 'payment-option-content-background-color' ] = '';

		return $theme_options;
	}

}

FluidCheckout_AstraAddon::instance();
