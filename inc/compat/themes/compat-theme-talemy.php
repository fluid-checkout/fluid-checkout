<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Talemy (by ThemeSpirit).
 */
class FluidCheckout_ThemeCompat_Talemy extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );

		// Buttons
		add_filter( 'fc_place_order_button_classes', array( $this, 'remove_place_order_button_alt_class' ), 10 );
	}



	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Fixes contact change button not being displayed.
		$this->remove_action_for_class( 'woocommerce_checkout_before_customer_details', array( 'Talemy_WooCommerce', 'customer_details_start' ), 0 );

		// Fixes the product summary not being displayed.
		$this->remove_action_for_class( 'woocommerce_checkout_after_customer_details', array( 'Talemy_WooCommerce', 'customer_details_end' ), 3000 );
	}



	/**
	 * Remove alt class from place order button.
	 *
	 * Talemy styles `.button.alt` but does not define a hover state for it,
	 * which prevents Fluid Checkout button hover styles from applying.
	 *
	 * @param  string  $classes  Button classes.
	 */
	public function remove_place_order_button_alt_class( $classes ) {
		return str_replace( ' alt', '', $classes );
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
				'--fluidcheckout--field--height'        => '43px',
				'--fluidcheckout--field--padding-left'  => '18px',
				'--fluidcheckout--field--font-size'     => '15px',
				'--fluidcheckout--field--border-radius' => '2px',
				'--fluidcheckout--field--border-color'  => 'var(--theme-color-border)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Talemy::instance();
