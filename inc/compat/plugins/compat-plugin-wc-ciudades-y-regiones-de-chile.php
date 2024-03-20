<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: States, Cities, and Places for WooCommerce (by Kingsley Ochu).
 */
class FluidCheckout_WCCiudadesYRegionesDeChile extends FluidCheckout {

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
		// Load the modified file from the compatibility with the plugin States, Cities, and Places for WooCommerce (by Kingsley Ochu),
		// as this plugin is a copy of that one, with some modifications.
		wp_register_script( 'wc-city-select', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/states-cities-and-places-for-woocommerce/place-select' ), array( 'jquery', 'woocommerce' ), NULL );
	}

}

FluidCheckout_WCCiudadesYRegionesDeChile::instance();
