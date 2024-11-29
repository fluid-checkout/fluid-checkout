<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartBounty Pro - Save and recover abandoned carts for WooCommerce (by Streamline.lv).
 */
class FluidCheckout_WooSaveAbandonedCartsPro extends FluidCheckout {

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
		$class_object = $this->get_object_by_class_name_from_hooks( 'CartBounty_Pro_Public' );

		// Bail if class object is not found in hooks
		if ( ! $class_object ) { return; }

		// Remove input data recovery from CartBounty conflicting with a similar feature from Fluid Checkout
		remove_filter( 'wp', array( $class_object, 'restore_input_data' ), 10 );
	}

}

FluidCheckout_WooSaveAbandonedCartsPro::instance();
