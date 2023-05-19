<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Mercado Pago payments for WooCommerce (by Mercado Pago).
 */
class FluidCheckout_WooCommerceMercadoPago extends FluidCheckout {

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
		// Payment methods
		add_filter( 'fc_checkout_update_on_visibility_change', array( $this, 'disable_update_on_visibility_change' ), 100 );
	}



	/**
	 * Disable update on visibility change.
	 */
	public function disable_update_on_visibility_change( $update_enabled ) {
		return 'no';
	}

}

FluidCheckout_WooCommerceMercadoPago::instance();
