<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Revolut Gateway for WooCommerce (by Revolut).
 */
class FluidCheckout_RevolutGatewayForWoocommerce extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Checkout page hooks
		$this->checkout_hooks();

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
	}

	/**
	 * Add or remove checkout page hooks.
	 */
	public function checkout_hooks() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Payment methods
		add_filter( 'woocommerce_gateway_title', array( $this, 'maybe_change_payment_gateway_title' ), 10, 2 );
		add_filter( 'woocommerce_gateway_icon', array( $this, 'maybe_change_payment_gateway_icon_html' ), 10, 2 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout events
		wp_register_script( 'fc-compat-revolut-gateway-for-woocommerce-checkout', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/revolut-gateway-for-woocommerce/revolut-checkout-events' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-revolut-gateway-for-woocommerce-checkout', 'window.addEventListener("load",function(){PaymentPluginsRevolutCheckoutEvents.init();});' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-revolut-gateway-for-woocommerce-checkout' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Maybe change the payment method title.
	 * 
	 * @param  string  $title  Payment method title.
	 * @param  string  $id     Payment method ID.
	 */
	public function maybe_change_payment_gateway_title( $title, $id = null ) {
		// Bail if processing the checkout form
		if ( did_action( 'woocommerce_before_checkout_process' ) ) { return $title; }

		// Set payment methods that required the change
		$payment_method_ids = array( 'revolut_pay', 'revolut_cc' );

		// Bail if not Revolut payment method
		if ( ! in_array( $id, $payment_method_ids ) ) { return $title; }

		// Add container used by the plugin to add "Learn more" link via JS
		if ( 'revolut_pay' === $id ) {
			$title .= '<span class="revolut-label-informational-icon" id="revolut-pay-label-informational-icon"></span>';
		}
		else {
			$title .= '<span class="revolut-label-informational-icon"></span>';
		}

		return $title;
	}



	/**
	 * Maybe change the payment method icons.
	 * 
	 * @param  string  $icon_html  Payment method icon HTML.
	 * @param  string  $id         Payment method ID.
	 */
	public function maybe_change_payment_gateway_icon_html( $icon_html, $id = null ) {
		// Set payment methods that required the change
		$payment_method_ids = array( 'revolut_pay', 'revolut_cc' );

		// Bail if not Revolut payment method
		if ( ! in_array( $id, $payment_method_ids ) ) { return $icon_html; }

		// Remove "Learn more" link container
		if ( 'revolut_pay' === $id ) {
			$icon_html = str_replace( '<span class="revolut-label-informational-icon" id="revolut-pay-label-informational-icon"></span>', '', $icon_html );
		} else {
			$icon_html = str_replace( '<span class="revolut-label-informational-icon"></span>', '', $icon_html );
		}
		
		return $icon_html;
	}

}

FluidCheckout_RevolutGatewayForWoocommerce::instance();
