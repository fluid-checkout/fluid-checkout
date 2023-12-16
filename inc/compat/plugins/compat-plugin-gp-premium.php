<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: GP Premium (by GeneratePress / Tom Usborne).
 */
class FluidCheckout_GPPremium extends FluidCheckout {

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
		// Sticky elements
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'maybe_change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'maybe_change_sticky_elements_relative_header' ), 20 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function maybe_change_sticky_elements_relative_header( $attributes ) {
		// Bail if using distraction free header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return $attributes; }

		// Bail if required functions are not available
		if ( ! function_exists( 'generate_menu_plus_get_defaults' ) ) { return $attributes; }

		// Get the header settings
		$menu_settings = wp_parse_args(
			get_option( 'generate_menu_plus_settings', array() ),
			generate_menu_plus_get_defaults()
		);

		// Bail if option is not enabled on the plugin
		if ( ! is_array( $menu_settings ) || 'false' === $menu_settings['sticky_menu'] ) { return $attributes; }

		// Change the relative ID
		$attributes['data-sticky-relative-to'] = 'nav.has-sticky-branding';

		return $attributes;
	}

}

FluidCheckout_GPPremium::instance();
