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
		// Maybe set the hooks class object
		if ( function_exists( 'eh_paypal_express_run' ) ) {
			// Get object
			$this->class_object = eh_paypal_express_run();
			$this->hooks_class_object = $this->class_object->hook_include;
		}

		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Checkout request
		add_filter( 'fc_is_checkout_page_or_fragment', array( $this, 'maybe_set_request_as_checkout_fragment' ), 10 );

		// Payment buttons
		$this->smart_buttons_hooks();
	}

	/**
	 * Add or remove late hooks.
	 */
	public function smart_buttons_hooks() {
		// Bail if hooks class is not available
		if ( ! isset( $this->hooks_class_object ) ) { return; }

		// Get plugin settings
		$plugin_settings = get_option( 'woocommerce_eh_paypal_express_settings' );

		// Payment buttons
		if ( array_key_exists( 'smart_button_enabled', $plugin_settings ) && 'yes' === $plugin_settings[ 'smart_button_enabled' ] ) {
			// Smart buttons
			remove_action( 'woocommerce_review_order_after_payment', array( $this->hooks_class_object, 'eh_express_checkout_hook' ), 10 );
			add_action( 'fc_place_order_custom_buttons', array( $this, 'maybe_output_payment_buttons' ), 10, 2 );
		}
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
	public function maybe_output_payment_buttons( $step_id = 'payment', $is_sidebar = false ) {
		// Bail if outputting the sidebar
		if ( $is_sidebar && 'below_order_summary' !== FluidCheckout_Steps::instance()->get_place_order_position() ) { return; }

		// Get plugin settings
		$plugin_settings = get_option( 'woocommerce_eh_paypal_express_settings' );

		// Define extra classes\
		$extra_classes = ! array_key_exists( 'smart_button_enabled', $plugin_settings ) || 'yes' !== $plugin_settings[ 'smart_button_enabled' ] ? 'hide-description' : '';

		// Output the payment buttons
		echo '<div class="fc-payment-buttons--webtoffee-paypal ' . $extra_classes . '">';
		$this->hooks_class_object->eh_express_checkout_hook();
		echo '</div>';
	}

}

FluidCheckout_ExpressCheckoutPaypalPaymentGatewayForWoocommerce::instance();
