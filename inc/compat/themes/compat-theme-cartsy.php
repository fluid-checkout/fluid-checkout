<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Flatsome (by UX-Themes).
 */
class FluidCheckout_ThemeCompat_Cartsy extends FluidCheckout {

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
		// Prevent theme's page template from being replaced by FC Pro checkout template
		add_filter( 'fc_enable_checkout_page_template', '__return_false', 10 );
	}

}

FluidCheckout_ThemeCompat_Cartsy::instance();
