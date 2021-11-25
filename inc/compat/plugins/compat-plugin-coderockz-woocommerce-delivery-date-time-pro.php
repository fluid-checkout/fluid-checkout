<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Delivery & Pickup Date Time Pro (by CodeRockz).
 */
class FluidCheckout_CodeRockz_WooCommerceDelivery extends FluidCheckout {

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->load_compat_plugin_woo_delivery();
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugin_compat_styles' ), 10 );
	}



	public function load_compat_plugin_woo_delivery() {
		$compat_file = FluidCheckout::$directory_path . 'inc/compat/plugins/compat-plugin-woo-delivery.php';
		if ( file_exists( $compat_file ) ) {
			require_once $compat_file;
		}
	}



	/**
	 * Enqueue plugins compatibility styles.
	 */
	public function enqueue_plugin_compat_styles() {
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ( ! is_checkout() && ! is_account_page() ) ) { return; }
		
		// Get plugin slug
		$plugin_slug = 'woo-delivery';

		// Maybe skip compat file
		if ( apply_filters( 'fc_enable_compat_plugin_style_' . $plugin_slug, true ) === false ) { return; }

		// Get current plugin's compatibility style file name
		$plugin_compat_file_path = 'css/compat/plugins/compat-' . $plugin_slug . self::$asset_version . '.css';

		// Maybe load plugin's compatibility file
		if ( file_exists( self::$directory_path . $plugin_compat_file_path ) ) {
			wp_enqueue_style( 'fc-plugin-compat-'.$plugin_slug, self::$directory_url . $plugin_compat_file_path, array(), null );
		}
	}

}

FluidCheckout_CodeRockz_WooCommerceDelivery::instance();
