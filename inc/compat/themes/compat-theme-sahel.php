<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: PeakShops (by Elated Themes).
 */
class FluidCheckout_ThemeCompat_Sahel extends FluidCheckout {

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
		add_filter( 'fc_checkout_progress_bar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
		add_filter( 'fc_checkout_sidebar_attributes', array( $this, 'change_sticky_elements_relative_header' ), 20 );
	}



	/**
	 * Change the sticky element relative ID.
	 *
	 * @param   array   $attributes    HTML element attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.eltdf-fixed-wrapper';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_Sahel::instance();