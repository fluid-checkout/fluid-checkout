<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WebToffee PayPal Express Checkout Payment Gateway for WooCommerce (By WebToffee)
 */
class FluidCheckout_ExpressCheckoutPaypalPaymentGatewayForWoocommerce extends FluidCheckout {

	/**
	 * Instance of the hooks class of the plugin.
	 */
	public $hooks_class_object = null;



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->hooks();

		// Maybe set the hooks class object
		if ( function_exists( 'eh_paypal_express_run' ) ) {
			// Get object
			$this->class_object = eh_paypal_express_run();
			$this->hooks_class_object = $this->class_object->hook_include;
		}
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Checkout request
		add_filter( 'fc_is_checkout_page_or_fragment', array( $this, 'maybe_set_request_as_checkout_fragment' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Bail if hooks class is not available
		if ( ! isset( $this->hooks_class_object ) ) { return; }

		// Payment buttons
		remove_action( 'woocommerce_review_order_after_payment', array( $this->hooks_class_object, 'eh_express_checkout_hook' ), 10 );
		remove_action( 'woocommerce_before_checkout_form', array( $this->hooks_class_object, 'eh_express_checkout_hook' ), 10 );
		add_action( 'fc_place_order_custom_buttons', array( $this, 'maybe_output_payment_buttons' ), 10, 2 );
	}



	/**
	 * Maybe set the current request as a checkout fragment when processing a PayPal Express Checkout API request.
	 */
	public function maybe_set_request_as_checkout_fragment( $is_checkout_fragment ) {
		global $wp;

		// Bail if the request is not for the PayPal Express Checkout API.
		if ( empty( $wp ) || ! isset( $wp->query_vars['wc-api'] ) || 'Eh_PayPal_Express_Payment' !== $wp->query_vars['wc-api'] ) { return $is_checkout_fragment; }

		return true;
	}



	/**
	 * Maybe output the payment buttons.
	 * 
	 * @param   string   $step_id      The ID of the step currently being output.
	 * @param   boolean  $is_sidebar   Whether outputting the sidebar.
	 */
	public function maybe_output_payment_buttons( $step_id, $is_sidebar ) {
		// Bail if outputting the sidebar
		if ( $is_sidebar ) { return; }

		// Output the payment buttons
		$this->hooks_class_object->eh_express_checkout_hook();
	}

}

FluidCheckout_ExpressCheckoutPaypalPaymentGatewayForWoocommerce::instance();
