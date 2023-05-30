<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Payment Plugins for Stripe WooCommerce (by Payment Plugins).
 */
class FluidCheckout_PymntplPayPalWooCommerce extends FluidCheckout {

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
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout events
		wp_register_script( 'fc-compat-pymntpl-paypal-woocommerce-checkout', self::$directory_url . 'js/compat/plugins/pymntpl-paypal-woocommerce/paypal-checkout-events' . self::$asset_version . '.js', array( 'jquery' ), NULL, true );
		wp_add_inline_script( 'fc-compat-pymntpl-paypal-woocommerce-checkout', 'window.addEventListener("load",function(){PaymentPluginsPayPalCheckoutEvents.init();})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-pymntpl-paypal-woocommerce-checkout' );
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

FluidCheckout_PymntplPayPalWooCommerce::instance();
