<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Rvvup for WooCommerce (by Rvvup).
 */
class FluidCheckout_RvvupForWoocommerce extends FluidCheckout {

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
		wp_register_script( 'fc-compat-rvvup-for-woocommerce-checkout', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/rvvup-for-woocommerce/rvvup-checkout' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-compat-rvvup-for-woocommerce-checkout', 'window.addEventListener("load",function(){RvvupCheckout.init();});' );

		// Rvvup scripts.js
		wp_register_script( 'rvvup_payment_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/rvvup-for-woocommerce/scripts' ), array( 'jquery', 'rvvup_payment_parameters_js', 'rvvup_payment_paypal_js' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-compat-rvvup-for-woocommerce-checkout' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}

}

FluidCheckout_RvvupForWoocommerce::instance();
