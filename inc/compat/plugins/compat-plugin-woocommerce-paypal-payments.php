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
			$checkout_button_renderer_hook = apply_filters( 'woocommerce_paypal_payments_checkout_button_renderer_hook', 'woocommerce_review_order_after_payment' );
			remove_action( $checkout_button_renderer_hook, array( $smart_button_module, 'button_renderer' ), 10 );
			// TODO: Check which version had this changed
			$this->remove_action_for_closure( $checkout_button_renderer_hook, 10 );

			// Place order position
			$place_order_position = get_option( 'fc_checkout_place_order_position', 'below_payment_section' );
			if ( 'below_payment_section' === $place_order_position || 'both_payment_and_order_summary' === $place_order_position ) {
				// PayPal buttons
				// TODO: Add action for closure since version that changed it
				add_action( 'fc_output_step_payment', array( $smart_button_module, 'button_renderer' ), 110 );

				// Widget area after submit button
				if ( class_exists( 'FluidCheckout_CheckoutWidgetAreas' ) ) {
					remove_action( 'woocommerce_review_order_after_submit', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 50 );
					add_action( 'fc_output_step_payment', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 150 );
				}
			}
			else if ( 'below_order_summary' === $place_order_position ) {
				// PayPal buttons
				// TODO: Add action for closure since version that changed it
				add_action( 'woocommerce_review_order_after_submit', array( $smart_button_module, 'button_renderer' ), 40 );
			}
			
		}
	}

}

FluidCheckout_WooCommercePayPalPayments::instance();
