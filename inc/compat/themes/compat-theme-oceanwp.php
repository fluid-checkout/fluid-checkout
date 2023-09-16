<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: OceanWP (by OceanWP).
 */
class FluidCheckout_ThemeCompat_OceanWP extends FluidCheckout {

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
		add_filter( 'fc_add_container_class', '__return_false' );
		add_filter( 'fc_content_section_class', array( $this, 'change_fc_content_section_class' ), 10 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function change_fc_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		return $class . ' container';
	}

}

FluidCheckout_ThemeCompat_OceanWP::instance();
