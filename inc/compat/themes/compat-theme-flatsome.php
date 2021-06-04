<?php

/**
 * Compatibility with theme: Flatsome.
 */
class FluidCheckout_ThemeCompat_Flatsome extends FluidCheckout {

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
		// Page container class
		remove_filter( 'fc_content_section_class', array( FluidCheckout_Steps::instance(), 'fc_content_section_class' ), 10 );
		add_filter( 'fc_content_section_class', array( $this, 'fc_content_section_class' ), 10 );
	}



	/**
	 * Add container class to the main content element.
	 *
	 * @param string $class Main content element classes.
	 */
	public function fc_content_section_class( $class ) {
		// Bail if using the plugin's header and footer
		if ( FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) { return $class; }

		return $class . ' container';
	}

}

FluidCheckout_ThemeCompat_Flatsome::instance();
