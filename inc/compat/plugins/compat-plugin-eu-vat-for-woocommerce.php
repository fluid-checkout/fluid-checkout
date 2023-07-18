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
		wp_register_script( 'alg-wc-eu-vat', self::$directory_url . 'js/compat/plugins/eu-vat-for-woocommerce/alg-wc-eu-vat' . self::$asset_version . '.js', array( 'jquery' ), NULL );
	}

}

FluidCheckout_EUVATForWooCommerce::instance();
