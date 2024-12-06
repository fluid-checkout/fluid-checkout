<?php
defined( 'ABSPATH' ) || exit;

// Define a constant to prevent parent class initialization
define( 'FLUIDCHECKOUT_SKIP_PARENT_CLASS_INIT', true );

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
	protected $public_class_name = 'CartBounty_Pro_Public';
	protected $admin_class_name  = 'CartBounty_Pro_Admin';


	/**
	 * Script name.
	 */
	protected $script_name = 'cartbounty-pro';

	/**
	 * Script file path.
	 */
	protected $script_file_path = 'js/compat/plugins/woo-save-abandoned-carts-pro/cartbounty-pro-public';

}

FluidCheckout_WooSaveAbandonedCartsPro::instance();
