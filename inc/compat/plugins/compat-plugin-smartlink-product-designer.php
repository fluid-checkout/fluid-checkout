<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Smartlink Product Designer (by Jigowatt).
 */
class FluidCheckout_SmartlinkProductDesigner extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Cart product image
		remove_filter( 'woocommerce_cart_item_thumbnail', 'g3d_cart_item_uses_large_image_link', 10, 3 );
	}

}

FluidCheckout_SmartlinkProductDesigner::instance();
