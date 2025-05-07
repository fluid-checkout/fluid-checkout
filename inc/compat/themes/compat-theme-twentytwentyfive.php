<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Twenty Twenty-Five (by The WordPress Team).
 */
class FluidCheckout_ThemeCompat_TwentyTwentyFive extends FluidCheckout {

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
		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_checkout_login_button_classes', array( $this, 'add_button_class' ), 10 );
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Add 'wp-element-button' class to apply theme styles and 'qodef-m-checkout-link' to add hover animation
		if ( is_array( $classes ) ) {
			array_push( $classes, 'wp-element-button' );
		} 
		else {
			$classes .= ' wp-element-button';
		}

		return $classes;
	}

}

FluidCheckout_ThemeCompat_TwentyTwentyFive::instance();
