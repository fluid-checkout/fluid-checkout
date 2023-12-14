<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Fast Cart (by Barn2 Plugins).
 */
class FluidCheckout_WooCommerceFastCart extends FluidCheckout {

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
		// Checkout page template
		add_filter( 'fc_enable_checkout_page_template', array( $this, 'maybe_disable_checkout_page_template' ), 10 );
	}



	/**
	 * Disable custom template for the checkout page content part.
	 */
	public function maybe_disable_checkout_page_template( $enabled ) {
		// Bail if not on fast cart iframe.
		if ( ! array_key_exists( 'wfc-checkout', $_GET ) || 'true' !== $_GET[ 'wfc-checkout' ] ) { return $enabled; }
		
		// Disable custom checkout templates.
		return false;
	}

}

FluidCheckout_WooCommerceFastCart::instance();
