<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce PayPal Payments (by WooCommerce).
 */
class FluidCheckout_WooCommercePayPalPayments extends FluidCheckout {

	public $smart_button_module;

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
		$this->smart_button_module = FluidCheckout::instance()->get_object_by_class_name_from_hooks( 'WooCommerce\PayPalCommerce\Button\Assets\SmartButton' );

		if ( $this->smart_button_module ) {
			$plugin_version = $this->get_plugin_version( 'woocommerce-paypal-payments/woocommerce-paypal-payments.php' );

			// Place order position
			$place_order_position = get_option( 'fc_checkout_place_order_position', 'below_payment_section' );

			// PayPal hooks
			$checkout_button_renderer_hook = apply_filters( 'woocommerce_paypal_payments_checkout_button_renderer_hook', 'woocommerce_review_order_after_payment' );
			$checkout_dcc_renderer_hook = apply_filters( 'woocommerce_paypal_payments_checkout_dcc_renderer_hook', 'woocommerce_review_order_after_submit' );

			// Versions 1.9.2+
			if ( version_compare( $plugin_version, '1.9.2', '>=' ) ) {
				$this->remove_action_for_closure( $checkout_button_renderer_hook, 10 );
				remove_action( $checkout_dcc_renderer_hook, array( $this->smart_button_module, 'message_renderer' ), 11 );

				if ( 'below_payment_section' === $place_order_position || 'both_payment_and_order_summary' === $place_order_position ) {
					// PayPal buttons
					add_action( 'fc_output_step_payment', array( $this, 'output_button_wrappers' ), 110 );
					add_action( 'fc_output_step_payment', array( $this->smart_button_module, 'message_renderer' ), 111 );
				}
				else if ( 'below_order_summary' === $place_order_position ) {
					// PayPal buttons
					add_action( 'woocommerce_review_order_after_submit', array( $this, 'output_button_wrappers' ), 40 );
					add_action( 'woocommerce_review_order_after_submit', array( $this->smart_button_module, 'message_renderer' ), 41 );
				}
			}
			// Versions up to 1.9.1
			else {
				remove_action( $checkout_button_renderer_hook, array( $this->smart_button_module, 'button_renderer' ), 10 );
				remove_action( $checkout_dcc_renderer_hook, array( $this->smart_button_module, 'message_renderer' ), 11 );

				if ( 'below_payment_section' === $place_order_position || 'both_payment_and_order_summary' === $place_order_position ) {
					// PayPal buttons
					add_action( 'fc_output_step_payment', array( $this->smart_button_module, 'button_renderer' ), 110 );
					add_action( 'fc_output_step_payment', array( $this->smart_button_module, 'message_renderer' ), 111 );
				}
				else if ( 'below_order_summary' === $place_order_position ) {
					// PayPal buttons
					add_action( 'woocommerce_review_order_after_submit', array( $this->smart_button_module, 'button_renderer' ), 40 );
					add_action( 'woocommerce_review_order_after_submit', array( $this->smart_button_module, 'message_renderer' ), 41 );
				}
			}

			// Widget area after submit button
			if ( class_exists( 'FluidCheckout_CheckoutWidgetAreas' ) && ( 'below_payment_section' === $place_order_position || 'both_payment_and_order_summary' === $place_order_position ) ) {
				remove_action( 'woocommerce_review_order_after_submit', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 50 );
				add_action( 'fc_output_step_payment', array( FluidCheckout_CheckoutWidgetAreas::instance(), 'output_widget_area_checkout_place_order_below' ), 150 );
			}
		}
	}



	/**
	 * Output the button wrappers.
	 * @see anonymous function added to checkout button hook in `SmartButton::render_button_wrapper_registrar`
	 */
	public function output_button_wrappers() {
		// Bail if PayPal Smart Button module or gateway classes are not available
		if ( ! $this->smart_button_module || ! class_exists( 'WooCommerce\PayPalCommerce\WcGateway\Gateway\PayPalGateway' ) || ! class_exists( 'WooCommerce\PayPalCommerce\WcGateway\Gateway\CardButtonGateway' ) ) { return; }

		$this->smart_button_module->button_renderer( WooCommerce\PayPalCommerce\WcGateway\Gateway\PayPalGateway::ID );
		$this->smart_button_module->button_renderer( WooCommerce\PayPalCommerce\WcGateway\Gateway\CardButtonGateway::ID );
	}

}

FluidCheckout_WooCommercePayPalPayments::instance();
