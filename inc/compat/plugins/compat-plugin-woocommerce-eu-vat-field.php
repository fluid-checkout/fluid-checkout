<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce EU Vat & B2B (by Lagudi Domenico).
 */
class FluidCheckout_WooCommerceEUVatField extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Register assets
		add_filter( 'init', array( $this, 'register_assets' ), 10 ); // Use 'init' hook to override the plugin's assets registration within the 'woocommerce_billing_fields' hook
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wcev-field-visibility-managment', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woocommerce-eu-vat-field/frontend-eu-vat-field-visibility' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

}

FluidCheckout_WooCommerceEUVatField::instance();
