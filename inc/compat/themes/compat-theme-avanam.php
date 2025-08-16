<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Avanam (by QantumThemes).
 */
class FluidCheckout_ThemeCompat_Avanam extends FluidCheckout {

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

		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );

		// CSS variables
		add_action( 'fc_css_variables', array( $this, 'add_css_variables' ), 20 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Get sticky header settings
		$sticky_header = get_theme_mod( 'header_sticky', 'no' );
		$sticky_header_mobile = get_theme_mod( 'mobile_header_sticky', 'no' );

		// Set selectors based on sticky header settings
		$desktop_selector = ( 'no' !== $sticky_header ) ? '.base-sticky-header' : '';
		$mobile_selector = ( 'no' !== $sticky_header_mobile ) ? '.site-mobile-header-wrap .base-sticky-header' : '';

		// Use responsive format - if both are empty, no attribute will be set
		if ( $desktop_selector || $mobile_selector ) {
			$attributes['data-sticky-relative-to'] = '{ "xs": { "breakpointInitial": 0, "breakpointFinal": 1124, "selector": "' . $mobile_selector . '" }, "sm": { "breakpointInitial": 1125, "breakpointFinal": 100000, "selector": "' . $desktop_selector . '" } }';
		}

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
				'--fluidcheckout--field--height' => '42.79px',
				'--fluidcheckout--field--padding-left' => '14px',
				'--fluidcheckout--field--font-size' => '15px',
				'--fluidcheckout--field--border-radius' => '3px',
				'--fluidcheckout--field--border-color' => 'var(--global-gray-400)',
			),
		);

		return FluidCheckout_DesignTemplates::instance()->merge_css_variables( $css_variables, $new_css_variables );
	}

}

FluidCheckout_ThemeCompat_Avanam::instance();
