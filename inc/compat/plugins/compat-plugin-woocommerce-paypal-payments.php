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
		// Set checkout context
		add_filter( 'woocommerce_paypal_payments_context' , array( $this, 'maybe_change_context_checkout' ), 300 );
		
		// Payment methods
		add_filter( 'fc_checkout_update_on_visibility_change', array( $this, 'disable_update_on_visibility_change' ), 100 );

		// Place order
		add_filter( 'woocommerce_paypal_payments_checkout_button_renderer_hook', array( $this, 'change_paypal_button_hook_name' ), 100 );
		add_filter( 'woocommerce_paypal_payments_checkout_dcc_renderer_hook', array( $this, 'change_paypal_button_hook_name' ), 100 );
	}



	/**
	 * Maybe set the context to the plugin.
	 *
	 * @param   string  $context  The current context.
	 */
	public function maybe_change_context_checkout( $context ) {
		// Bail if not target context
		if ( 'checkout-block' !== $context ) { return $context; }
		
		// Otherwise, change the context
		$context = 'checkout';
		return $context;
	}



	/**
	 * Disable update on visibility change.
	 */
	public function disable_update_on_visibility_change( $update_enabled ) {
		// Get available payment methods
		$available_payment_methods = WC()->payment_gateways->get_available_payment_gateways();

		// Check if PayPal Payments is available.
		if ( isset( $available_payment_methods[ 'ppcp-credit-card-gateway' ] ) ) {
			$update_enabled = 'no';
		}

		return $update_enabled;
	}



	/**
	 * Change the position of the PayPal payment buttons to the payment step.
	 */
	public function change_paypal_button_hook_name( $hook_name ) {
		return 'fc_place_order_custom_buttons';
	}

}

FluidCheckout_WooCommercePayPalPayments::instance();
