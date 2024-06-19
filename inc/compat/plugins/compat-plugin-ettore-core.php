<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Ettore Core (by Qode Themes).
 */
class FluidCheckout_EttoreCore extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
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

		// Bail if plugin function isn't available
		if ( ! function_exists( 'ettore_core_get_post_value_through_levels' ) ) { return $attributes; }

		// Get theme settings for sticky header
		$header_scroll_appearance = ettore_core_get_post_value_through_levels( 'qodef_header_scroll_appearance' );
		$header_scroll_appearance_mobile = ettore_core_get_post_value_through_levels( 'qodef_mobile_header_scroll_appearance' );

		// Default values
		$mobile_settings = '';
		$desktop_settings = '';

		// Set the desktop sticky header selector if theme option is set to sticky or fixed
		if ( in_array( $header_scroll_appearance, array( 'sticky', 'fixed' ) ) ) {
			switch ( $header_scroll_appearance ) {
				case 'sticky':
					$selector = '.qodef-header-sticky';
					break;
				case 'fixed':
					$selector = '.qodef-header--fixed-display #qodef-page-header';
					break;
			}

			$desktop_settings = '"sm": { "breakpointInitial": 1201, "breakpointFinal": 10000, "selector": "'. $selector .'" }';
		}

		// Set the mobile sticky header selector if theme option is enabled
		if ( 'yes' === $header_scroll_appearance_mobile ) {
			$mobile_settings = '"xs": { "breakpointInitial": 0, "breakpointFinal": 1200, "selector": "#qodef-page-mobile-header" }';
		}

		// Only keep non-empty values
		$settings = array_filter( array( $mobile_settings, $desktop_settings ), function( $value ) {
			return ! empty( $value );
		} );

		// Concatenate values with a comma
		$settings = implode( ', ', $settings );

		// Add the settings to the data attribute
		$attributes['data-sticky-relative-to'] = "{ {$settings} }";

		return $attributes;
	}

}

FluidCheckout_EttoreCore::instance();
