<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Suki (by Suki Team).
 */
class FluidCheckout_ThemeCompat_Suki extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Remove plus and minus buttons to the quantity input from suki theme.
		remove_action( 'woocommerce_after_quantity_input_field', array( Suki_Compatibility_WooCommerce::instance(), 'add_quantity_plus_minus_buttons' ) );
		remove_action( 'wp_enqueue_scripts', array( Suki_Compatibility_WooCommerce::instance(), 'add_quantity_plus_minus_buttons_scripts' ) );
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
				// ! Double check if theme is using variables for these values
				// Form field styles
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'rgba(0,0,0,.1)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Suki::instance();
