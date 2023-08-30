<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Storefront Powerpack (by WooCommerce).
 */
class FluidCheckout_StorefrontPowerpack extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Checkout features
		add_filter( 'storefront_powerpack_checkout_enabled', '__return_false', 10 );
	}

}

FluidCheckout_StorefrontPowerpack::instance(); 
