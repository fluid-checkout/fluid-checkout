<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Mollie Payments for WooCommerce (by Mollie).
 */
class FluidCheckout_MolliePaymentsForWooCommerce extends FluidCheckout {

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

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_billie_payment' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'fc-mollie-billie-company-mirror', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/mollie-payments-for-woocommerce/mollie-billie-company-mirror' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-mollie-billie-company-mirror', 'window.addEventListener("load",function(){MollieBillieCompanyMirror.init();})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-mollie-billie-company-mirror' );
	}

	/**
	 * Maybe enqueue scripts.
	 */
	public function maybe_enqueue_assets_billie_payment() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Get available payment methods
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		// Bail if target payment method is not available
		if ( ! array_key_exists( 'mollie_wc_gateway_billie', $available_gateways ) ) { return; }

		// Enqueue scripts
		$this->enqueue_assets();
	}

}

FluidCheckout_MolliePaymentsForWooCommerce::instance();
