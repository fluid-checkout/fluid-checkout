<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Smart Coupons (by StoreApps).
 */
class FluidCheckout_WooCommerceSmartCoupons extends FluidCheckout {

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
		// Bail if class is not available
		$class_name = 'WC_SC_Display_Coupons';
		if ( ! class_exists( $class_name ) ) { return; }

		// Bail if class object is not found
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
		if ( ! is_object( $class_object ) ) { return; }

		// Available coupons section
		remove_action( 'woocommerce_checkout_before_customer_details', array( $class_object, 'show_available_coupons_on_classic_checkout' ), 11 );
		add_action( 'woocommerce_before_checkout_form', array( $class_object, 'show_available_coupons_on_classic_checkout' ), 3 ); // Before FC progress bar
	}

}

FluidCheckout_WooCommerceSmartCoupons::instance();
