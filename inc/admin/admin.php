<?php
defined( 'ABSPATH' ) || exit;

/**
 * Checkout admin options.
 */
class FluidCheckout_Admin extends FluidCheckout {

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
		// Plugin settings link
		add_filter( 'plugin_action_links_' . self::$plugin_basename, array( $this, 'add_plugin_settings_link' ), 10 );
		
		// Setting types
		add_action( 'init', array( $this, 'load_setting_types' ), 10 );

		// WooCommerce Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_pages' ), 50 );

		// WooCommerce Settings Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles'), 10 );
	}



	/**
	 * Enqueue styles for the current admin settings page.
	 *
	 * @param int $hook_suffix Hook suffix for the current admin page.
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		// Bail if not on WooCommerce settings page
		if ( $hook_suffix !== 'woocommerce_page_wc-settings' ) { return; }
		wp_enqueue_style( 'fc-admin-options', self::$directory_url . 'css/admin-options'. self::$asset_version . '.css', NULL, NULL );
	}



	/**
	 * Add new WooCommerce settings pages/tabs.
	 */
	public function load_setting_types() {
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-paragraph.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-layout-selector.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-image-uploader.php';
	}

	/**
	 * Add new WooCommerce settings pages/tabs.
	 */
	public function add_settings_pages( $settings ) {
		// `$settings` need to be an array
		if ( ! is_array( $settings ) ) { $settings = array( $settings ); }

		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-wc-shipping.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-fluid-checkout.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-general.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-integrations.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-tools.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-advanced.php';
		
		return $settings;
	}



	/**
	 * Add settings page link to plugin listing.
	 * @param array $links
	 */
	public function add_plugin_settings_link( $links = array() ) {
		// Add links before existing ones
		$new_links = array(
			sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=fc_checkout' ), esc_html( __( 'Settings', 'fluid-checkout' ) ) ),
			sprintf( '<a href="%s" target="_blank">%s</a>', 'https://support.fluidcheckout.com', esc_html( __( 'Support', 'fluid-checkout' ) ) ),
		);

		$links = array_merge( $new_links, $links );

		// TODO: Show PRO version promotion when released
		// Maybe add PRO version promotion
		// if ( ! FluidCheckout::instance()->is_pro_activated() ) {
		// 	$links[] = sprintf( '<a href="%s" style="color:#007F01;font-weight:bold;">%s</a>', 'https://fluidcheckout.com/product/fluid-checkout-pro/', esc_html( __( 'Get PRO', 'fluid-checkout' ) ) );
		// }

		return $links;
	}

}

FluidCheckout_Admin::instance();
