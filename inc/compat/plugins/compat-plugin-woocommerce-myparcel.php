<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: MyParcel (by MyParcel).
 */
class FluidCheckout_WooCommerceMyParcel extends FluidCheckout {

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
		// Shipping methods
		add_filter( 'wc_wcmp_delivery_options_location', array( $this, 'change_hook_delivery_options_location' ), 10 );
	}



	/**
	 * Change the hook location for the delivery options.
	 */
	public function change_hook_delivery_options_location( $hook_name ) {
		return 'fc_shipping_methods_after_packages';
	}

}

FluidCheckout_WooCommerceMyParcel::instance();
