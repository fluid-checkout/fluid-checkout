<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: FFL Checkout by TTG for WooCommerce (by Texas Technology Group).
 */
class FluidCheckout_FFLCheckoutByTTGForWooCommerce extends FluidCheckout {

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
		// Checkout hooks
		$this->checkout_hooks();
	}

	/**
	 * Add or remove checkout hooks.
	 */
	public function checkout_hooks() {
		// Bail if class is not available
		$class_name = 'FFLCheckoutByTTG_Public';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );

		// Bail if object is not available
		if ( ! $class_object ) { return; }

		// Checkout hooks
		remove_action( 'woocommerce_before_checkout_form', array( $class_object, 'hookCheckoutPage' ), 10 );
		remove_filter( 'render_block_woocommerce/checkout-contact-information-block', array( $class_object, 'hookCheckoutPageBlock' ), 10 );
		add_action( 'wp', array( $class_object, 'hookCheckoutPage' ), 10 );
	}

}

FluidCheckout_FFLCheckoutByTTGForWooCommerce::instance();
