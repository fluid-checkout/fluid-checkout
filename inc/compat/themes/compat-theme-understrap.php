<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Understrap (by Howard Development & Consulting).
 */
class FluidCheckout_ThemeCompat_Understrap extends FluidCheckout {

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
		add_filter( 'fc_content_section_class', array( $this, 'add_content_section_class' ), 10 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function add_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_CheckoutPageTemplate::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		// Maybe add the container class
		$class = $class . ' container';

		return $class;
	}

}

FluidCheckout_ThemeCompat_Understrap::instance();
