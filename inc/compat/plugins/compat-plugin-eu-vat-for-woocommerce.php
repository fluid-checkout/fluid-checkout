<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: EU/UK VAT for WooCommerce (by WPFactory)
 */
class FluidCheckout_EUVATForWooCommerce extends FluidCheckout {

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
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'alg-wc-eu-vat', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/eu-vat-for-woocommerce/alg-wc-eu-vat' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

}

FluidCheckout_EUVATForWooCommerce::instance();
