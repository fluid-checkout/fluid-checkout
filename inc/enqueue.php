<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue scripts and styles
 */
class FluidCheckout_Enqueue extends FluidCheckout {


	/**
	 * @var  string  $has_enqueued_settings  Whether the JS settings have been enqueued.
	 */
	public static $has_enqueued_settings = false;


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
		// Replace WooCommerce scripts, need to run before WooCommerce registers and enqueues its scripts, priority has to be less than 10
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_woocommerce_scripts' ), 5 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_custom_fonts' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_edit_address' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_add_payment_method' ), 10 );
	
		// Theme and Plugin Compatibility
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_theme_compat_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugin_compat_styles' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Replace WooCommerce scripts, need to run before WooCommerce registers and enqueues its scripts, priority has to be less than 10
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_woocommerce_scripts' ), 5 );

		// Register assets
		remove_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_custom_fonts' ), 1 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_edit_address' ), 10 );
		remove_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets_add_payment_method' ), 10 );
	
		// Theme and Plugin Compatibility
		// Should not remove theme and plugin compatibility hooks. Keep this comment here for future reference.
	}



	/**
	 * Pre-register WooCommerce scripts with modified version in order to replace them.
	 * This function is intended to be used with hook `wp_enqueue_scripts` at priority lower than `10`,
	 * which is the priority used by WooCommerce to register its scripts.
	 */
	public function pre_register_woocommerce_scripts() {
		wp_register_script( 'woocommerce', self::$directory_url . 'js/woocommerce'. self::$asset_version . '.js', array( 'jquery', 'jquery-blockui', 'js-cookie' ), NULL, true );
		wp_register_script( 'wc-country-select', self::$directory_url . 'js/country-select'. self::$asset_version . '.js', array( 'jquery' ), NULL, true );
		wp_register_script( 'wc-address-i18n', self::$directory_url . 'js/address-i18n'. self::$asset_version . '.js', array( 'jquery', 'wc-country-select' ), NULL, true );
		wp_register_script( 'wc-checkout', self::$directory_url . 'js/checkout'. self::$asset_version . '.js', array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n', 'fc-utils' ), NULL, true );
	}

	/**
	 * Replace WooCommerce scripts with modified version.
	 */
	public function maybe_replace_woocommerce_scripts() {
		// Bail if not on checkout page or address edit page
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_wc_endpoint_url( 'edit-address' ) ) ) { return; }

		$this->pre_register_woocommerce_scripts();
	}



	/**
	 * Returns the JS settings for Fluid Checkout.
	 *
	 * @return  array  JS settings for Fluid Checkout. 
	 */
	public function get_fc_settings() {
		return apply_filters( 'fc_js_settings', array(
			'ver'                            => self::$version,
			'assetsVersion'                  => self::$asset_version,
			'cookiePath'                     => parse_url( get_option( 'siteurl' ), PHP_URL_PATH ),
			'cookieDomain'                   => parse_url( get_option( 'siteurl' ), PHP_URL_HOST ),
			'jsPath'                         => self::$directory_url . 'js/',
			'jsLibPath'                      => self::$directory_url . 'js/lib/',
			'cssPath'                        => self::$directory_url . 'css/',
			'ajaxUrl'                        => admin_url( 'admin-ajax.php' ),
			'wcAjaxUrl'                      => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'flyoutBlock'                    => array(
				'openAnimationClass'         => 'fade-in-up',
				'closeAnimationClass'        => 'fade-out-down',
			),
			'collapsibleBlock'               => array(),
			'stickyStates'                   => array(),
			'checkoutUpdateBeforeUnload'     => apply_filters( 'fc_checkout_update_before_unload', 'yes' ),
			'checkoutUpdateFieldsSelector'   => join( ',', apply_filters( 'fc_checkout_update_fields_selectors', array(
				'.address-field input.input-text',
				'.update_totals_on_change input.input-text',
			) ) ),
		) );
	}

	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Maybe load RTL file
		$rtl_suffix = is_rtl() ? '-rtl' : '';

		// Register library scripts
		wp_register_script( 'fc-polyfill-inert', self::$directory_url . 'js/lib/inert'. self::$asset_version . '.js', array( 'woocommerce' ), NULL );
		wp_register_script( 'fc-animate-helper', self::$directory_url . 'js/lib/animate-helper'. self::$asset_version . '.js', array( 'woocommerce' ), NULL );
		wp_register_script( 'fc-collapsible-block', self::$directory_url . 'js/lib/collapsible-block'. self::$asset_version . '.js', array( 'woocommerce' ), NULL );
		wp_add_inline_script( 'fc-collapsible-block', 'window.addEventListener("load",function(){CollapsibleBlock.init(fcSettings.collapsibleBlock);})' );
		wp_register_script( 'fc-flyout-block', self::$directory_url . 'js/lib/flyout-block'. self::$asset_version . '.js', array( 'woocommerce', 'fc-polyfill-inert', 'fc-animate-helper' ), NULL );
		wp_add_inline_script( 'fc-flyout-block', 'window.addEventListener("load",function(){FlyoutBlock.init(fcSettings.flyoutBlock);})' );
		wp_register_script( 'fc-sticky-states', self::$directory_url . 'js/lib/sticky-states'. self::$asset_version . '.js', array( 'woocommerce' ), NULL );
		wp_add_inline_script( 'fc-sticky-states', 'window.addEventListener("load",function(){StickyStates.init(fcSettings.stickyStates);})' );

		// Register script utilities
		wp_register_script( 'fc-utils', self::$directory_url . 'js/fc-utils'. self::$asset_version . '.js', array(), NULL );

		// Register custom fonts
		wp_register_style( 'fc-fonts', self::$directory_url . 'css/fonts' . self::$asset_version . '.css', array(), null );

		// Register styles
		wp_register_style( 'fc-edit-address-page', self::$directory_url . 'css/edit-address-page' . $rtl_suffix . self::$asset_version . '.css', array(), null );
		wp_register_style( 'fc-add-payment-method-page', self::$directory_url . 'css/add-payment-method-page' . $rtl_suffix . self::$asset_version . '.css', array(), null );
		wp_register_style( 'fc-flyout-block', self::$directory_url . 'css/flyout-block' . $rtl_suffix . self::$asset_version . '.css', array(), null );
		wp_register_style( 'fc-sticky-states', self::$directory_url . 'css/sticky-states' . $rtl_suffix . self::$asset_version . '.css', array(), null );
	}



	/**
	 * Enqueue Require Bundle.
	 * @deprecated  Marked to be removed in version 3.0.0.
	 */
	public function enqueue_require_bundle() {
		// Add deprecation notice
		wc_doing_it_wrong( __FUNCTION__, 'Dependency on RequireBundle has been removed. This function will be removed in version 3.0.0.', '2.4.0' );
	}



	/**
	 * Enqueue JS settings object.
	 */
	public function enqueue_settings_inline_script( $handler = 'woocommerce' ) {
		// Bail if already enqueued
		if ( self::$has_enqueued_settings ) { return; }

		// Enqueue settings
		wp_localize_script( $handler, 'fcSettings', $this->get_fc_settings() );

		// Set flag for settings enqueued
		self::$has_enqueued_settings = true;
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		// Enqueue settings
		$this->enqueue_settings_inline_script();

		// Scripts
		wp_enqueue_script( 'fc-utils' );
		wp_enqueue_script( 'fc-polyfill-inert' );
		wp_enqueue_script( 'fc-animate-helper' );
		wp_enqueue_script( 'fc-collapsible-block' );
		wp_enqueue_script( 'fc-flyout-block' );
		wp_enqueue_script( 'fc-sticky-states' );

		// Styles
		wp_enqueue_style( 'fc-flyout-block' );
		wp_enqueue_style( 'fc-sticky-states' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not on checkout page
		if ( is_admin() || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Enqueue assets
		$this->enqueue_assets();
	}



	/**
	 * Maybe enqueue custom fonts.
	 */
	function maybe_enqueue_custom_fonts() {
		// Bail if not on checkout page
		if ( is_admin() || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_custom_fonts();
	}

	/**
	 * Enqueue custom fonts.
	 */
	function enqueue_custom_fonts() {
		wp_enqueue_style( 'fc-fonts' );
	}



	/**
	 * Enqueue assets for the edit address page.
	 */
	function enqueue_assets_edit_address() {
		wp_enqueue_style( 'fc-edit-address-page' );
	}

	/**
	 * Maybe enqueue assets for the edit address page.
	 */
	function maybe_enqueue_assets_edit_address() {
		// Bail if not on checkout page or address edit page
		if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return; }

		// Enqueue assets
		$this->enqueue_custom_fonts();
		$this->enqueue_assets();
		
		// Enqueue assets for the edit address page
		$this->enqueue_assets_edit_address();
	}



	/**
	 * Enqueue assets for the add payment method page.
	 */
	function enqueue_assets_add_payment_method() {
		wp_enqueue_style( 'fc-add-payment-method-page' );
	}

	/**
	 * Maybe enqueue assets for the add payment method page.
	 */
	function maybe_enqueue_assets_add_payment_method() {
		// Bail if not on checkout page or address edit page
		if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'add-payment-method' ) ) { return; }

		// Enqueue assets
		$this->enqueue_custom_fonts();
		$this->enqueue_assets();
		
		// Enqueue assets for the add payment method page
		$this->enqueue_assets_add_payment_method();
	}



	/**
	 * Enqueue themes compatibility styles.
	 * @since 1.2.0
	 */
	public function enqueue_theme_compat_styles() {
		// Bail if not on checkout, address edit or add payment method pages
		if ( is_admin() || ! ( is_checkout() || ( is_account_page() && ( is_wc_endpoint_url( 'edit-address' ) || is_wc_endpoint_url( 'add-payment-method' ) )  ) ) || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Get currently active theme and child theme
		$theme_slugs = array( get_template(), get_stylesheet() );

		foreach ( $theme_slugs as $theme_slug ) {
			// Maybe skip compat file
			if ( apply_filters( 'fc_enable_compat_theme_style_' . $theme_slug, true ) === false ) { continue; }

			// Maybe load RTL file
			$rtl_suffix = is_rtl() ? '-rtl' : '';

			// Get current theme's compatibility style file name
			$theme_compat_file_path = 'css/compat/themes/compat-' . $theme_slug . $rtl_suffix . self::$asset_version . '.css';

			// Revert to default compat style file if RTL file does not exist
			if ( is_rtl() && ! file_exists( self::$directory_path . $theme_compat_file_path ) ) {
				$theme_compat_file_path = 'css/compat/themes/compat-' . $theme_slug . self::$asset_version . '.css';
			}

			// Maybe load theme's compatibility file
			if ( file_exists( self::$directory_path . $theme_compat_file_path ) ) {
				wp_enqueue_style( 'fc-theme-compat-'.$theme_slug, self::$directory_url . $theme_compat_file_path, array(), null );
			}
		}
	}



	/**
	 * Enqueue plugins compatibility styles.
	 * @since 1.2.4
	 */
	public function enqueue_plugin_compat_styles() {
		// Bail if not on checkout, address edit or add payment method pages
		if ( is_admin() || ! ( is_checkout() || ( is_account_page() && ( is_wc_endpoint_url( 'edit-address' ) || is_wc_endpoint_url( 'add-payment-method' ) )  ) ) || is_order_received_page() || is_checkout_pay_page() ) { return; }

		// Get all plugins installed
		$plugins_installed = get_plugins();
		
		foreach ( $plugins_installed as $plugin_file => $plugin_meta ) {
			// Skip plugins not activated
			if ( ! is_plugin_active( $plugin_file ) ) { continue; }

			// Get plugin slug
			$plugin_slug = strpos( $plugin_file, '/' ) !== false ? explode( '/', $plugin_file )[0] : explode( '.', $plugin_file )[0];

			// Maybe skip compat file
			if ( apply_filters( 'fc_enable_compat_plugin_style_' . $plugin_slug, true ) === false ) { continue; }

			// Maybe load RTL file
			$rtl_suffix = is_rtl() ? '-rtl' : '';

			// Get current plugin's compatibility style file name
			$plugin_compat_file_path = 'css/compat/plugins/compat-' . $plugin_slug . $rtl_suffix . self::$asset_version . '.css';

			// Revert to default compat style file if RTL file does not exist
			if ( is_rtl() && ! file_exists( self::$directory_path . $plugin_compat_file_path ) ) {
				$plugin_compat_file_path = 'css/compat/plugins/compat-' . $plugin_slug . self::$asset_version . '.css';
			}

			// Maybe load plugin's compatibility file
			if ( file_exists( self::$directory_path . $plugin_compat_file_path ) ) {
				wp_enqueue_style( 'fc-plugin-compat-'.$plugin_slug, self::$directory_url . $plugin_compat_file_path, array(), null );
			}
		}
	}

}

FluidCheckout_Enqueue::instance();
