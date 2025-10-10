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

		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_place_order_button_classes', array( $this, 'add_button_class' ), 10 );
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


	/**
	 * Add button class from the theme.
	 * 
	 * @param  array|string  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Define button class
		$button_class = ' alt';

		// Add button class to the classes array
		if ( is_array( $classes ) ) {
			array_push( $classes, $button_class );
		}
		// Otherwise append button class as a string
		else {
			$classes .= ' ' . $button_class;
		}

		return $classes;
	}
}

FluidCheckout_ThemeCompat_Listable::instance();
