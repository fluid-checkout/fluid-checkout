<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Acowebs Woocommerce Dynamic Pricing PRO (by Acowebs).
 */
class FluidCheckout_AcoWooDynamicPricingPRO extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->load_compat_plugin_lite();
	}

	/**
	 * Load compatibility with Lite version of the same plugin.
	 */
	public function load_compat_plugin_lite() {
		$compat_file = FluidCheckout::$directory_path . 'inc/compat/plugins/compat-plugin-aco-woo-dynamic-pricing.php';
		if ( file_exists( $compat_file ) ) {
			require_once $compat_file;
		}
	}

}

FluidCheckout_AcoWooDynamicPricingPRO::instance();
