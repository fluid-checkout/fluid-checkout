<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Purple (by Automattic).
 */
class FluidCheckout_ThemeCompat_Purple extends FluidCheckout {

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
		// Keep FC container when using the theme header (distraction-free off).
		// Unlike Twenty Twenty-Five, Purple's constrained/alignwide layout does not
		// provide a usable content width for the multi-column checkout.

		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_checkout_login_button_classes', array( $this, 'add_button_class' ), 10 );

		// CSS variables
		add_filter( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
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
				'--fluidcheckout--field--height' => '49.7px',
				'--fluidcheckout--field--font-size' => '14px',
				'--fluidcheckout--field--padding-left' => '1.1rem',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Add 'wp-element-button' class to apply theme styles
		if ( is_array( $classes ) ) {
			array_push( $classes, 'wp-element-button' );
		}
		else {
			$classes .= ' wp-element-button';
		}

		return $classes;
	}

}

FluidCheckout_ThemeCompat_Purple::instance();
