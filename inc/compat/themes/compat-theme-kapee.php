<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Kapee (by PressLayouts).
 */
class FluidCheckout_ThemeCompat_Kapee extends FluidCheckout {

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
		// Container class
		add_filter( 'fc_add_container_class', '__return_false', 10 );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $class; }

		return $class . ' col-md-12';
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if plugin function isn't available
		if ( ! function_exists( 'kapee_get_option' ) ) { return $attributes; }

		// Desktop settings
		$desktop_settings = '';
		if ( true == kapee_get_option( 'sticky_header', 0 ) ) {
			$desktop_settings = '"md": { "breakpointInitial": 993, "breakpointFinal": 10000, "selector": ".header-sticky" }';
		}

		// Tablet settings
		$tablet_settings = '';
		if ( true == kapee_get_option( 'sticky-header-tablet', 0 ) ) {
			$tablet_settings = '"sm": { "breakpointInitial": 481, "breakpointFinal": 992, "selector": ".site-header .header-sticky" }';
		}

		// Mobile settings
		$mobile_settings = '';
		if ( true == kapee_get_option( 'sticky-header-mobile', 0 ) ) {
			$mobile_settings = '"xs": { "breakpointInitial": 0, "breakpointFinal": 480, "selector": ".site-header .header-sticky" }';
		}

		// Only keep non-empty values
		$settings = '';
		$settings = array_filter( array( $mobile_settings, $tablet_settings, $desktop_settings ), function( $value ) {
			return ! empty( $value );
		} );

		// Concatenate values with a comma
		$settings = implode( ', ', $settings );

		// Add the settings to the data attribute
		$attributes['data-sticky-relative-to'] = "{ {$settings} }";

		return $attributes;
	}



	/**
	 * Add CSS variables.
	 * 
	 * @param  array  $css_variables  The CSS variables key/value pairs.
	 */
	public function add_css_variables( $css_variables ) {
		// Bail if plugin function isn't available
		if ( ! function_exists( 'kapee_get_option' ) ) { return $attributes; }

		// Get border width value from theme options
		$border = kapee_get_option( 'site-border' );
		$border_width = '1px';
		if ( ! empty( $border['border-width'] ) ) {
			$border_width = $border['border-width'];
		}

		// Add CSS variables
		$new_css_variables = array(
			':root' => array(
				// Form field styles
				'--fluidcheckout--field--height' => '42px',
				'--fluidcheckout--field--padding-left' => '12px',
				'--fluidcheckout--field--border-color' => 'var(--site-border-color)',
				'--fluidcheckout--field--border-width' => $border_width,
				'--fluidcheckout--field--border-radius' => 'var(--site-border-radius)',
				'--fluidcheckout--field--font-size' => 'var(--site-font-size)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Kapee::instance();
