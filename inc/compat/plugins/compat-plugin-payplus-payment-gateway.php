<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: PayPlus Payment Gateway (by PayPlus LTD).
 */
class FluidCheckout_PayplusPaymentGateway extends FluidCheckout {

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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( FluidCheckout_Enqueue::instance(), 'maybe_replace_woocommerce_scripts' ), 20 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout events
		wp_register_script( 'fc-compat-payplus-payment-gateway-checkout', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/payplus-payment-gateway/checkout' ), array( 'jquery', 'wc-checkout' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-payplus-payment-gateway-checkout', 'window.addEventListener("load",function(){PayPlusCheckout.init();})' );

		// Define whether to import ApplePay script
		$options = (object)get_option( 'woocommerce_payplus-payment-gateway_settings' );
		$import_apple_pay_script = null;
		$accepted_values = array( 'samePageIframe', 'popupIframe' );
		$is_mobile = wp_is_mobile();
		if (
			$options
			&& 'yes' === $options->import_applepay_script
			&& in_array( $options->display_mode, $accepted_values )
		) {
			$import_apple_pay_script = 'https://payments.payplus.co.il/statics/applePay/script.js?var=' . PAYPLUS_VERSION;
		}

		// Register ApplePay script parameters as a localized variable
		wp_localize_script( 'fc-compat-payplus-payment-gateway-checkout', 'payplus_script_checkout', array( 'payplus_import_applepay_script' => $import_apple_pay_script, 'payplus_mobile' => $is_mobile ) );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-payplus-payment-gateway-checkout' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}

}

FluidCheckout_PayplusPaymentGateway::instance();
