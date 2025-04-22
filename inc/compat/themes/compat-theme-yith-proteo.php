<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: YITH Proteo (by YITH).
 */
class FluidCheckout_ThemeCompat_YithProteo extends FluidCheckout {

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

		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );

		// Theme options
		add_filter( 'theme_mod_yith_proteo_use_enhanced_checkbox_and_radio', array( $this, 'force_disable_echnanced_checkbox_and_radio' ), 100 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();
	}

	/*
	* Add or remove checkout page hooks.
	*/
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// Quantity controls
		remove_action( 'woocommerce_after_quantity_input_field', 'yith_proteo_customize_quantity_inputs', 10 );
	}



	/**
	 * Force disable enhanced checkbox and radio setting from the theme since it's not compatible with Fluid Checkout.
	 */
	public function force_disable_echnanced_checkbox_and_radio() {
		return 'no';
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Get sticky header setting from the theme
		$is_sticky = apply_filters( 'yith_proteo_enable_sticky_header', get_theme_mod( 'yith_proteo_header_sticky', 'no' ) );

		// Bail if sticky header is not enabled
		if ( 'yes' !== $is_sticky ) { return $attributes; }

		$attributes[ 'data-sticky-relative-to' ] = '{ "sm": { "breakpointInitial": 992, "breakpointFinal": 100000, "selector": ".site-header" } }';

		return $attributes;
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
				'--fluidcheckout--field--height' => '40.4px',
				'--fluidcheckout--field--padding-left' => '10px',
				'--fluidcheckout--field--font-size' => 'var(--proteo-forms_input_font_size)',
				'--fluidcheckout--field--border-color' => 'var(--proteo-forms_input_border_color, #cccccc)',
				'--fluidcheckout--field--border-width' => 'var(--proteo-forms_input_border_width)',
				'--fluidcheckout--field--border-radius' => 'var(--proteo-forms_input_borde_radius, 0)',
				'--fluidcheckout--field--background-color--accent' => 'transparent',
				'--fluidcheckout--field--text-color--accent' => 'var(--proteo-main_color_shade, #448a85)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_YithProteo::instance();