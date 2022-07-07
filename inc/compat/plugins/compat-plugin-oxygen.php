<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Oxygen (by Soflyy).
 */
class FluidCheckout_Oxygen extends FluidCheckout {

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
		// Maybe remove checkout page template
		// - when editing the checkout page
		// - when using the plugin's header and footer on the front-end
		if ( isset( $_GET[ 'ct_builder' ] ) || isset( $_GET[ 'action' ] ) || ! FluidCheckout_Steps::instance()->get_hide_site_header_footer_at_checkout() ) {
			remove_filter( 'template_include', array( FluidCheckout_Steps::instance(), 'checkout_page_template' ), 100 );
		}
	}

}

FluidCheckout_Oxygen::instance();
