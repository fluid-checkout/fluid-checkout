<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: States, Cities, and Places for WooCommerce (by Kingsley Ochu).
 */
class FluidCheckout_StatesCitiesAndPlacesForWooCommerce extends FluidCheckout {

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
		wp_register_script( 'wc-city-select', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/states-cities-and-places-for-woocommerce/place-select' ), array( 'jquery', 'woocommerce' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

}

FluidCheckout_StatesCitiesAndPlacesForWooCommerce::instance();
