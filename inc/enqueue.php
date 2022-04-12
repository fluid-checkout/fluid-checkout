<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue scripts and styles
 */
class FluidCheckout_Enqueue extends FluidCheckout {

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
		// Need to run before WooCommerce registers and enqueues its scripts, priority has to be less than 10
		add_action( 'wp_enqueue_scripts', array( $this, 'replace_woocommerce_scripts' ), 5 );

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_require_bundle' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_custom_fonts' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_styles_edit_address' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_theme_compat_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plugin_compat_styles' ), 10 );
	}



	/**
	 * Replace WooCommerce scripts with modified version.
	 */
	public function replace_woocommerce_scripts() {
		wp_register_script( 'woocommerce', self::$directory_url . 'js/woocommerce'. self::$asset_version . '.js', array( 'jquery', 'jquery-blockui', 'js-cookie' ), NULL, true );
		wp_register_script( 'wc-country-select', self::$directory_url . 'js/country-select'. self::$asset_version . '.js', array( 'jquery' ), NULL, true );
		wp_register_script( 'wc-address-i18n', self::$directory_url . 'js/address-i18n'. self::$asset_version . '.js', array( 'jquery', 'wc-country-select' ), NULL, true );
		wp_register_script( 'wc-checkout', self::$directory_url . 'js/checkout'. self::$asset_version . '.js', array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ), NULL, true );
	}



	/**
	 * Maybe enqueue Require Bundle.
	 */
	public function maybe_enqueue_require_bundle() {
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ( ! is_checkout() && ! is_account_page() ) ) { return; }

		$this->enqueue_require_bundle();
	}

	/**
	 * Enqueue Require Bundle.
	 */
	public function enqueue_require_bundle() {
		// Require Bundle & Polyfills
		if ( ! wp_script_is( 'require-bundle', 'registered' ) ) { wp_enqueue_script( 'require-bundle', self::$directory_url . 'js/lib/require-bundle'. self::$asset_version . '.js', NULL, NULL ); }
		if ( ! wp_script_is( 'require-polyfills', 'registered' ) ) { wp_enqueue_script( 'require-polyfills', self::$directory_url . 'js/lib/require-polyfills'. self::$asset_version . '.js', NULL, NULL ); }
	}



	/**
	 * Maybe enqueue scripts.
	 */
	public function maybe_enqueue_scripts() {
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ( ! is_checkout() && ! is_account_page() ) ) { return; }

		$this->enqueue_scripts();
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'fc-bundles', self::$directory_url . 'js/bundles'. self::$asset_version . '.js', array( 'require-bundle' ), NULL, true );
		wp_localize_script(
			'fc-bundles',
			'fcSettings',
			apply_filters( 'fc_js_settings', array(
				'ver'                            => self::$version,
				'assetsVersion'                  => self::$asset_version,
				'cookiePath'                     => parse_url( get_option( 'siteurl' ), PHP_URL_PATH ),
				'cookieDomain'                   => parse_url( get_option( 'siteurl' ), PHP_URL_HOST ),
				'jsPath'                         => self::$directory_url . 'js/',
				'jsLibPath'                      => self::$directory_url . 'js/lib/',
				'cssPath'                        => self::$directory_url . 'css/',
				'ajaxUrl'                        => admin_url( 'admin-ajax.php' ),
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
			) )
		);
	}



	/**
	 * Maybe enqueue custom fonts.
	 */
	function maybe_enqueue_custom_fonts() {
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ( ! is_checkout() && ! is_account_page() ) ) { return; }

		$this->enqueue_custom_fonts();
	}

	/**
	 * Enqueue custom fonts.
	 */
	function enqueue_custom_fonts() {
		wp_enqueue_style( 'fc-fonts', self::$directory_url . 'css/fonts'. self::$asset_version . '.css', array(), null );
	}



	/**
	 * Maybe enqueue edit address styles.
	 */
	function maybe_enqueue_styles_edit_address() {
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_wc_endpoint_url( 'edit-address' ) ) { return; }

		$this->enqueue_styles_edit_address();
	}

	/**
	 * Enqueue edit address styles.
	 */
	function enqueue_styles_edit_address() {
		wp_enqueue_style( 'fc-account-page-address', self::$directory_url . 'css/account-page-address'. self::$asset_version . '.css', array(), null );
	}



	/**
	 * Enqueue themes compatibility styles.
	 * @since 1.2.0
	 */
	public function enqueue_theme_compat_styles() {
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ( ! is_checkout() && ! is_account_page() ) ) { return; }

		// Get currently active theme and child theme
		$theme_slugs = array( get_template(), get_stylesheet() );

		foreach ( $theme_slugs as $theme_slug ) {
			// Maybe skip compat file
			if ( apply_filters( 'fc_enable_compat_theme_style_' . $theme_slug, true ) === false ) { continue; }

			// Maybe load RTL file
			$rlt_suffix = is_rtl() ? '-rtl' : '';

			// Get current theme's compatibility style file name
			$theme_compat_file_path = 'css/compat/themes/compat-' . $theme_slug . $rlt_suffix . self::$asset_version . '.css';

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
		// Bail if not visiting pages affected by the plugin
		if ( is_admin() || ( ! is_checkout() && ! is_account_page() ) ) { return; }

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
			$rlt_suffix = is_rtl() ? '-rtl' : '';

			// Get current plugin's compatibility style file name
			$plugin_compat_file_path = 'css/compat/plugins/compat-' . $plugin_slug . $rlt_suffix . self::$asset_version . '.css';

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
