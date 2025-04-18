<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: LeadEngine (by Key-Design).
 */
class FluidCheckout_ThemeCompat_LeadEngine extends FluidCheckout {

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
		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 30 );

		// Buttons
		add_filter( 'fc_next_step_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_substep_save_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_coupon_code_apply_button_classes', array( $this, 'add_button_class' ), 10 );
		add_filter( 'fc_place_order_button_classes', array( $this, 'add_button_class' ), 10 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $settings; }

		// Add settings
		$settings[ 'utils' ][ 'scrollOffsetSelector' ] = '.navbar-default';

		return $settings;
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		$attributes[ 'data-sticky-relative-to' ] = '.navbar-default';

		return $attributes;
	}



	/**
	 * Add button class from the theme.
	 * 
	 * @param  array|string  $classes  The button classes.
	 */
	public function add_button_class( $classes ) {
		// Define button class
		$button_class = 'tt_button';

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

FluidCheckout_ThemeCompat_LeadEngine::instance();
