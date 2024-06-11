<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Blocksy (by CreativeThemes).
 */
class FluidCheckout_ThemeCompat_Blocksy extends FluidCheckout {

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

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

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

		// Prevent theme from adding custom quantity fields
		add_filter( 'theme_mod_has_custom_quantity', '__return_false', 10 );
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if Blocksy instance is not available
		if ( ! class_exists( 'Blocksy\Plugin' ) || ! method_exists( 'Blocksy\Plugin', 'instance' ) ) { return $attributes; }

		$blocksy = Blocksy\Plugin::instance();

		// Bail if header property is not available
		if ( ! isset( $blocksy->header ) || ! is_object( $blocksy->header ) ) { return $attributes; }

		$blocksy_header = $blocksy->header;

		// Bail if method is not available
		if ( ! method_exists( $blocksy_header, 'current_screen_has_sticky' ) ) { return $attributes; }

		// Get sticky header settings from Blocksy
		$sticky_header_settings = $blocksy_header->current_screen_has_sticky();

		// Bail if theme's conditions for sticky header are not met
		if ( ! $sticky_header_settings || ! is_array( $sticky_header_settings ) ) { return $attributes; }

		// Bail if no devices are selected for sticky header
		if ( ! isset( $sticky_header_settings['devices'] ) || empty( $sticky_header_settings['devices'] ) ) { return $attributes; }

		// Default values
		$mobile_settings = '';
		$desktop_settings = '';

		// Set the sticky header settings based on the selected devices
		if ( in_array( 'desktop', $sticky_header_settings['devices'] ) ) {
			$desktop_settings = '"sm": { "breakpointInitial": 1000, "breakpointFinal": 10000, "selector": "header [data-sticky*=yes]" }';
		}
		if ( in_array( 'mobile', $sticky_header_settings['devices'] ) ) {
			$mobile_settings = '"xs": { "breakpointInitial": 0, "breakpointFinal": 999, "selector": "header [data-sticky*=yes]" }';
		}

		// Only keep non-empty values
		$settings = array_filter( array( $mobile_settings, $desktop_settings ), function( $value ) {
			return !empty( $value );
		} );

		// Concatenate values with a comma
		$settings = implode( ', ', $settings );

		$attributes['data-sticky-relative-to'] = "{ {$settings} }";

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
				'--fluidcheckout--field--height' => '40px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--background-color--accent' => 'var(--theme-palette-color-1)',

				// Checkout validation styles
				'--fluidcheckout--validation-check--horizontal-spacing--select' => '20px',
				'--fluidcheckout--validation-check--horizontal-spacing--select-alt' => '32px',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Blocksy::instance();
