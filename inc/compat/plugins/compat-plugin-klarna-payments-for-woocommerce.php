<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Klarna Payments for WooCommerce (by Krokedil).
 */
class FluidCheckout_KlarnaPaymentsForWooCommerce extends FluidCheckout {

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
		// Replace Klarna Payments scripts, need to run before Klarna Payments registers and enqueues its scripts, priority has to be less than 10
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_woocommerce_scripts' ), 5 );
	}



    /**
	 * Pre-register WooCommerce scripts with modified version in order to replace them.
	 * This function is intended to be used with hook `wp_enqueue_scripts` at priority lower than `10`,
	 * which is the priority used by WooCommerce to register its scripts.
	 */
	public function pre_register_scripts() {
		wp_register_script( 'klarna_payments', self::$directory_url . 'js/compat/plugins/klarna-payments-for-woocommerce/klarna-payments'. self::$asset_version . '.js', array( 'jquery', 'wc-checkout', 'jquery-blockui' ), NULL, true );
	}

	/**
	 * Replace WooCommerce scripts with modified version.
	 */
	public function maybe_replace_woocommerce_scripts() {
		// Bail if not on checkout page
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }

		$this->pre_register_scripts();
	}

}

FluidCheckout_KlarnaPaymentsForWooCommerce::instance();
