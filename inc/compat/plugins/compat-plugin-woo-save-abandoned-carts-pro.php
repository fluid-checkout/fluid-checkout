<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: CartBounty Pro - Save and recover abandoned carts for WooCommerce (by Streamline.lv).
 */
class FluidCheckout_WooSaveAbandonedCartsPro extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->load_compat_plugin_woo_delivery();
		$this->hooks();
	}

	/**
	 * Load compatibility with Lite version of the same plugin.
	 */
	public function load_compat_plugin_woo_delivery() {
		$compat_file = FluidCheckout::$directory_path . 'inc/compat/plugins/compat-plugin-woo-save-abandoned-carts.php';
		if ( file_exists( $compat_file ) ) {
			require_once $compat_file;
		}
	}

}

FluidCheckout_WooSaveAbandonedCartsPro::instance();
