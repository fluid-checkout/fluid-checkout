<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartBounty - Save and recover abandoned carts for WooCommerce (by Streamline.lv).
 */
class FluidCheckout_WooSaveAbandonedCarts extends FluidCheckout {

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
		// Get plugin object instance
		$class_object = $this->get_object_by_class_name_from_hooks( 'CartBounty_Public' );

		// Bail if class object is not found in hooks
		if ( ! $class_object ) { return; }

		// Remove input data restoring feature conflicting with Fluid Checkout
		remove_filter( 'wp', array( $class_object, 'restore_input_data' ), 10 );
	}

}

FluidCheckout_WooSaveAbandonedCarts::instance();
