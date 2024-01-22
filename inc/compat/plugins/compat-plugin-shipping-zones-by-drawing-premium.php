<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Shipping Zones by Drawing Premium for WooCommerce (by Arosoft.se).
 */
class FluidCheckout_ShippingZonesByDrawingPremium extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->load_compat_lite_version();
	}



	/**
	 * Load compatibility with Lite version of the same plugin.
	 */
	public function load_compat_lite_version() {
		$compat_file = FluidCheckout::$directory_path . 'inc/compat/plugins/compat-plugin-shipping-zones-by-drawing-for-woocommerce.php';
		if ( file_exists( $compat_file ) ) {
			require_once $compat_file;
		}
	}

}

FluidCheckout_ShippingZonesByDrawingPremium::instance();
