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

		// CHANGE: Enable the plugin's built-in Fluid Checkout compatibility while on checkout.
		add_filter( 'pre_option_alg_wc_eu_vat_compatibility_fluid_checkout', array( $this, 'maybe_enable_fluid_checkout_compatibility' ), 10, 3 );
	}



	/**
	 * Enable the EU VAT plugin's Fluid Checkout compatibility on checkout pages.
	 *
	 * @param   mixed   $pre_option  The value to return instead of the option value.
	 * @param   string  $option      Option name.
	 * @param   mixed   $default     The fallback value to return if the option does not exist.
	 */
	public function maybe_enable_fluid_checkout_compatibility( $pre_option, $option, $default ) {
		// Bail if option value is already set
		if ( false !== $pre_option ) { return $pre_option; }

		// Bail if not on checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return $pre_option; }

		return 'yes';
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Scripts
		wp_register_script( 'wpfactory-wc-eu-vat', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/eu-vat-for-woocommerce/wpfactory-wc-eu-vat' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

}

FluidCheckout_EUVATForWooCommerce::instance();
