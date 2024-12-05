<?php
defined( 'ABSPATH' ) || exit;

// Load Lite plugin's compatibility file
$lite_plugin_compat_file_path = self::$directory_path . 'inc/compat/plugins/compat-plugin-woo-save-abandoned-carts.php';
if ( file_exists( $lite_plugin_compat_file_path ) ) {
	require_once $lite_plugin_compat_file_path;
}

/**
 * Compatibility with plugin: CartBounty Pro - Save and recover abandoned carts for WooCommerce (by Streamline.lv).
 */
class FluidCheckout_WooSaveAbandonedCartsPro extends FluidCheckout_WooSaveAbandonedCarts {

	/**
	 * Plugin class names.
	 */
	public const PUBLIC_CLASS_NAME = 'CartBounty_Pro_Public';
	public const ADMIN_CLASS_NAME  = 'CartBounty_Pro_Admin';



	/**
	 * __construct function.
	 */
	public function __construct() {
		parent::__construct();
	}

}

FluidCheckout_WooSaveAbandonedCartsPro::instance();
