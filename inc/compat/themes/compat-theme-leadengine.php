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
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $settings; }

		// Add settings
		$settings[ 'checkoutSteps' ][ 'scrollOffsetSelector' ] = '.navbar-default';

		return $settings;
	}



	/**
	 * Change the element used to position the progress bar and order summary when sticky.
	 * 
	 * @param  array  $attributes  The elements attributes.
	 */
	public function change_sticky_elements_relative_header( $attributes ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $attributes; }

		$attributes['data-sticky-relative-to'] = '.navbar-default';

		return $attributes;
	}

}

FluidCheckout_ThemeCompat_LeadEngine::instance();
