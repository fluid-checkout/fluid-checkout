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
		add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', array( $this, 'change_paypal_payments_checkout_button_renderer_hook' ), 10 );
	}



	/**
	 * Change the hook used to display the PayPal checkout buttons.
	 *
	 * @param  array  $hook  The hook where to display the PayPal checkout buttons.
	 */
	public function change_paypal_payments_checkout_button_renderer_hook( $hook ) {
		return 'woocommerce_review_order_before_submit';
	}

}

FluidCheckout_WooCommercePayPalPayments::instance();
