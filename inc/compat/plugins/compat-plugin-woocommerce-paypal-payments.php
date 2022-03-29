<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce PayPal Payments (by WooCommerce).
 */
class FluidCheckout_WooCommercePayPalPayments extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		$smart_button_module = FluidCheckout::instance()->get_object_by_class_name_from_hooks( 'WooCommerce\PayPalCommerce\Button\Assets\SmartButton' );

		if ( $smart_button_module ) {
			// PayPal buttons
			remove_action( 'woocommerce_review_order_after_payment', array( $smart_button_module, 'button_renderer' ), 10 );
			add_action( 'fc_output_step_payment', array( $smart_button_module, 'button_renderer' ), 110 );

			// Widget area after submit button
			if ( class_exists( 'FluidCheckout_CheckoutWidgetAreas' ) ) {
				remove_action( 'woocommerce_review_order_after_submit', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 50 );
				add_action( 'fc_output_step_payment', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 150 );
			}
		}
	}

}

FluidCheckout_WooCommercePayPalPayments::instance();
