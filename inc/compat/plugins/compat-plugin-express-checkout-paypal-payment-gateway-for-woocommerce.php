<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WebToffee PayPal Express Checkout Payment Gateway for WooCommerce (By WebToffee)
 */
class FluidCheckout_ExpressCheckoutPaypalPaymentGatewayForWoocommerce extends FluidCheckout {

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
		add_filter( 'fc_is_checkout_page_or_fragment', array( $this, 'maybe_set_request_as_checkout_fragment' ), 10 );
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

}

FluidCheckout_ExpressCheckoutPaypalPaymentGatewayForWoocommerce::instance();
