<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: The7 (by Dream-Theme).
 */
class FluidCheckout_ThemeCompat_DTThe7 extends FluidCheckout {

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
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if theme function is not available
		if ( ! function_exists( 'of_get_option' ) ) { return $attributes; }

		// Get the sticky header option
		$is_sticky = of_get_option( 'header-show_floating_navigation' );

		// Bail if sticky header is not enabled
		if ( ! $is_sticky ) { return $attributes; }

		// Get the header style option
		$header_style = of_get_option( 'header-floating_navigation-style' );

		// Define relative element based on the header style
		switch ( $header_style ) {
			case 'slide':
			case 'fade':
				$attributes[ 'data-sticky-relative-to' ] = '{ "xs": { "breakpointInitial": 0, "breakpointFinal": 992, "selector": ".masthead.sticky-mobile-on" }, "sm": { "breakpointInitial": 993, "breakpointFinal": 100000, "selector": "#phantom" } }';
				break;
			case 'sticky':
				$attributes[ 'data-sticky-relative-to' ] = '.masthead';
				break;
		}

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_DTThe7::instance();
