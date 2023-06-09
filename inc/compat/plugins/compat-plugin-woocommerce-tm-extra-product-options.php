<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Extra Product Options & Add-Ons for WooCommerce (by ThemeComplete).
 */
class FluidCheckout_WooCommerceTMExtraProductOption extends FluidCheckout {

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
		// Maybe set flags
		add_action( 'woocommerce_init', array( $this, 'maybe_set_plugin_flags' ), 1 );
	}



	/**
	 * Maybe set plugin flags.
	 */
	public function maybe_set_plugin_flags() {
		// Bail if class or object doesn't exist
		if ( ! class_exists( 'THEMECOMPLETE_Extra_Product_Options' ) || ! THEMECOMPLETE_Extra_Product_Options::instance() ) { return; }
		
		// Set flags
		THEMECOMPLETE_Extra_Product_Options::instance()->wc_vars[ 'is_checkout' ] = FluidCheckout_Steps::instance()->is_checkout_page_or_fragment();
		THEMECOMPLETE_Extra_Product_Options::instance()->wc_vars[ 'is_cart' ] = FluidCheckout_Steps::instance()->is_cart_page_or_fragment();
	}

}

FluidCheckout_WooCommerceTMExtraProductOption::instance();
