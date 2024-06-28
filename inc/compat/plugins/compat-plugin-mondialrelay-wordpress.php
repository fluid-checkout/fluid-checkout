<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Mondial Relay - WordPress (by Kasutan).
 */
class FluidCheckout_WooShippingDPDBaltic extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'class_MRWP_public';



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
		// Shipping methods hooks
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_methods_hooks' ), 100 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_methods_hooks() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Move shipping method hooks
		remove_action( 'woocommerce_review_order_after_shipping', array( $class_object, 'modaal_link' ), 10 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $class_object, 'modaal_link' ), 10 );

		// Mondial Relay logo from order overview
		remove_filter( 'woocommerce_cart_shipping_method_full_label', array( 'MRWP_Shipping_Method', 'embellish_label' ), 10, 2 );
	}

}

FluidCheckout_WooShippingDPDBaltic::instance();
