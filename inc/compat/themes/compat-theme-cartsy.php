<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with theme: Cartsy (by Redq).
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
		if ( ! FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) {
			// Prevent theme's page template from being replaced by FC checkout template
			add_filter( 'fc_enable_checkout_page_template', '__return_false', 10 );
		}
	}

}

FluidCheckout_ThemeCompat_Cartsy::instance();
