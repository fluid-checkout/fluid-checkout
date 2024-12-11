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
		
		// Load dashboard
		add_action( 'init', array( $this, 'load_dashboard' ), 10 );

		// Setting types
		add_action( 'init', array( $this, 'load_setting_types' ), 10 );

		// WooCommerce Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_pages' ), 50 );

		// WooCommerce Settings Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_dashboard_styles' ), 10 );

		// Clear cache after saving settings
		add_action( 'woocommerce_settings_saved', array( $this, 'flush_cache' ), 10 );
	}



	/**
	 * Enqueue styles for the current admin settings page.
	 *
	 * @param int $hook_suffix Hook suffix for the current admin page.
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		// Bail if not on WooCommerce settings page
		if ( $hook_suffix !== 'woocommerce_page_wc-settings' ) { return; }

		wp_enqueue_style( 'fc-admin-options', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-options' ), NULL, NULL );
	}

	/**
	 * Enqueue styles for the current admin settings page.
	 *
	 * @param int $hook_suffix Hook suffix for the current admin page.
	 */
	public function enqueue_admin_dashboard_styles( $hook_suffix ) {
		// Get current screen
		$current_screen = get_current_screen();

		// Bail if not on WooCommerce settings page
		if ( $current_screen->id !== 'woocommerce_page_wc-settings' ) { return; }
		
		// Get current tab and section
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

		// Bail if not on dashboard settings page
		if ( 'fc_checkout' !== $current_tab || ! empty( $current_section ) ) { return; }

		wp_enqueue_style( 'fc-admin-dashboard', FluidCheckout_Enqueue::instance()->get_style_url( 'css/admin-dashboard' ), NULL, NULL );
	}



	/**
	 * Load dashboard section types.
	 */
	public function load_dashboard() {
		include_once self::$directory_path . 'inc/admin/admin-dashboard-actions.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-setup.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-addons.php';
	}

	/**
	 * Load custom setting field types.
	 */
	public function load_setting_types() {
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-paragraph.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-input.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-select.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-textarea.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-layout-selector.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-template-selector.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-image-uploader.php';
		include_once self::$directory_path . 'inc/admin/admin-setting-type-fc-license-key.php';
	}

	/**
	 * Add new WooCommerce settings pages/tabs.
	 */
	public function add_settings_pages( $settings ) {
		// `$settings` need to be an array
		if ( ! is_array( $settings ) ) { $settings = array( $settings ); }

		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-wc-shipping.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-tab-fluid-checkout.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-dashboard.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-checkout.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-cart.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-order-received.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-order-pay.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-integrations.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-tools.php';
		$settings[] = include self::$directory_path . 'inc/admin/admin-settings-license-keys.php';
		
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
			sprintf( '<a href="%s" target="_blank">%s</a>', 'https://fluidcheckout.com/support/', esc_html( __( 'Support', 'fluid-checkout' ) ) ),
		);

		$links = array_merge( $new_links, $links );

		// Maybe add PRO version promotion
		if ( ! FluidCheckout::instance()->is_pro_activated() ) {
			$links[] = sprintf( '<a href="%s" target="_blank" style="color:#007F01;font-weight:bold;">%s</a>', 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=plugins-list&mtm_source=lite-plugin', esc_html( __( 'Upgrade to PRO', 'fluid-checkout' ) ) );
		}

		return $links;
	}



	/**
	 * Get HTML for "upgrade to PRO" to be used on settings descriptions.
	 * 
	 * @param  bool  $new_line  Whether to add a new line before.
	 */
	public function get_upgrade_pro_html( $new_line = true ) {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return ''; }

		// Get HTML for the upgrade link
		$html = wp_kses_post( sprintf( __( '<a target="_blank" href="%s">Upgrade to PRO</a> to unlock more options.', 'fluid-checkout' ), 'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=plugin-settings&mtm_source=lite-plugin' ) );
		
		// Maybe add line break
		if ( $new_line ) {
			$html = ' <br>' . $html;
		}
	
		return $html;
	}

	/**
	 * Get HTML for PRO features label.
	 * 
	 * @param  bool  $add_space_after  Whether to add a space after the text. Defaults to `false`, which adds the space before.
	 */
	public function get_pro_feature_option_html( $add_space_after = false ) {
		// Bail if PRO is already activated
		if ( FluidCheckout::instance()->is_pro_activated() ) { return ''; }

		// Get the HTML for the PRO feature labels
		$html = __( '(PRO)', 'fluid-checkout' );

		// Maybe add space after the text
		if ( $add_space_after ) {
			$html = $html . ' ';
		}
		// Otherwise add the space before
		else {
			$html = ' ' . $html;
		}

		return $html;
	}

	/**
	 * Get HTML for Experimental features label.
	 * 
	 * @param  bool  $new_line  Whether to add a new line before.
	 */
	public function get_experimental_feature_html( $new_line = false ) {
		return ' ' . ( $newline ? '<br>' : '' ) . __( '(experimental)', 'fluid-checkout' );
	}

	/**
	 * Get HTML experimental features explanation.
	 * 
	 * @param  bool  $newline  Whether to add a new line before.
	 */
	public function get_experimental_feature_explanation_html( $newline = false ) {		
		return ' ' . ( $newline ? '<br>' : '' ) . __( 'This is an experimental feature and may not work as expected with all themes and plugins. Use it with caution.', 'fluid-checkout' );
	}

	/**
	 * Get HTML for documentation link to be used on settings descriptions.
	 */
	public function get_documentation_link_html( $url = 'https://fluidcheckout.com/docs/' ) {
		return sprintf( '<a target="_blank" href="%s">%s</a>', esc_url( $url ), __( 'Read the documentation.', 'fluid-checkout' ) );
	}



	/**
	 * Encloses the function `wp_cache_flush()` to ensure no parameter is passed into it.
	 */
	public function flush_cache() {
		wp_cache_flush();
	}

}

FluidCheckout_Admin::instance();
