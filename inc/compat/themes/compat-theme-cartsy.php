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
		// Checkout templates
		$this->checkout_layout_hooks();
	}



	/*
	* Checkout templates hooks.
	*/
	public function checkout_layout_hooks() {
		// Bail if using the distraction free template
		if ( FluidCheckout_CheckoutPageTemplate::instance()->is_distraction_free_header_footer_checkout() ) { return; }

		// Prevent theme's page template from being replaced by FC checkout template
		add_filter( 'fc_enable_checkout_page_template', '__return_false', 10 );
	}

}

FluidCheckout_ThemeCompat_Cartsy::instance();
