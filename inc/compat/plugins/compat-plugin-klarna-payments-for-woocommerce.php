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
	 * Get Klarna Payments script dependencies.
	 */
	public function get_klarna_payments_script_dependencies() {
		// Define base dependencies
		$klarna_payments_deps = array( 'jquery', 'wc-checkout' );

		// Get WooCommerce and Klarna Payments plugin versions
		$woocommerce_version = defined( 'WC_VERSION' ) ? WC_VERSION : '0.0.0';
		$plugin_version = defined( 'WC_KLARNA_PAYMENTS_VERSION' ) ? WC_KLARNA_PAYMENTS_VERSION : '0.0.0';

		// Add BlockUI script dependency based on WooCommerce version
		if ( version_compare( $woocommerce_version, '10.3.0', '>=' ) ) {
			$klarna_payments_deps[] = 'wc-jquery-blockui';
		} 
		else {
			$klarna_payments_deps[] = 'jquery-blockui';
		}

		// Add script dependency for plugin versions 4.4.0 and above
		if ( version_compare( $plugin_version, '4.4.0', '>=' ) ) {
			$klarna_payments_deps[] = 'klarnapayments';
		}

		return $klarna_payments_deps;
	}

	/**
	 * Pre-register WooCommerce scripts with modified version in order to replace them.
	 * This function is intended to be used with hook `wp_enqueue_scripts` at priority lower than `10`,
	 * which is the priority used by WooCommerce to register its scripts.
	 */
	public function pre_register_scripts() {
		$klarna_payments_deps = $this->get_klarna_payments_script_dependencies();
		wp_register_script( 'klarna_payments', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/klarna-payments-for-woocommerce/klarna-payments' ), $klarna_payments_deps, NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
