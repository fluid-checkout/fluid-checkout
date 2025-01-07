<?php
/*
Plugin Name: Fluid Checkout for WooCommerce - Lite
Plugin URI: https://fluidcheckout.com/
Description: Provides a distraction free checkout experience for any WooCommerce store. Ask for shipping information before billing in a truly linear multi-step or one-step checkout and display a coupon code field at the checkout page that does not distract your customers.
Text Domain: fluid-checkout
Domain Path: /languages
Version: 4.0.1
Author: Fluid Checkout
Author URI: https://fluidcheckout.com/
WC requires at least: 5.0
WC tested up to: 9.5.1
License URI: http://www.gnu.org/licenses/gpl-3.0.html
License: GPLv3

Copyright (C) 2021-2024 Fluid Checkout OÜ

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) || exit;



// Plugin activation and deactivation
require_once plugin_dir_path( __FILE__ ) . 'inc/plugin-activation.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/plugin-deactivation.php';
register_activation_hook( __FILE__, array( 'FluidCheckout_Activation', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'FluidCheckout_Deactivation', 'on_deactivation' ) );



/**
 * Plugin Main Class.
 */
class FluidCheckout {

	// A single instance of this class.
	public static $instances = array();
	public static $directory_path;
	public static $directory_url;
	public static $plugin = 'Fluid Checkout for WooCommerce - Lite';
	public static $plugin_slug = 'fluid-checkout';
	public static $plugin_basename = ''; // Values set at function `set_plugin_vars`
	public static $version = ''; // Values set at function `set_plugin_vars`
	public static $asset_version = ''; // Values set at function `set_plugin_vars`

	/**
	 * Hold list of the plugin features to load when initializing.
	 *
	 * @var array
	 */
	private static $features = array();

	/**
	 * User session keys prefix.
	 *
	 * @var string
	 */
	const SESSION_PREFIX = 'fc_';


	/**
	 * Singleton instance function.
	 *
	 * @access public
	 * @static
	 * @return object
	 * @since  1.0.0
	 */
	public static function instance() {
		$calledClass = get_called_class();

		if ( ! array_key_exists( $calledClass, self::$instances ) || self::$instances[ $calledClass ] === null ) {
			self::$instances[ $calledClass ] = new $calledClass();
		}

		return self::$instances[ $calledClass ];
	}



	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->set_plugin_vars();
		$this->load_db_migrations();
		$this->load_admin_notices();
		$this->register_features();

		// Declare WooCommerce features compatibility
		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_features_compatibility' ), 10 );

		// Run hooks initialization after all plugins have been loaded
		add_action( 'plugins_loaded', array( $this, 'load_settings' ), 10 );
		add_action( 'plugins_loaded', array( $this, 'hooks' ), 10 );
	}



	/**
	 * Define plugin variables.
	 */
	public function set_plugin_vars() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		self::$directory_path = plugin_dir_path( __FILE__ );
		self::$directory_url  = plugin_dir_url( __FILE__ );
		self::$plugin_basename = plugin_basename( __FILE__ );
		self::$version = get_file_data( __FILE__ , ['Version' => 'Version'], 'plugin')['Version'];
		self::$asset_version = $this->get_assets_version_number();
	}



	/**
	 * Load settings manager.
	 */
	public function load_settings() {
		require_once self::$directory_path . 'inc/settings.php';
	}



	/**
	 * Get the locales to be used for each language variant.
	 */
	public function get_locale_language_variants() {
		return apply_filters( 'fc_locale_language_variant', array(
			'de_DE'          => 'de_DE_formal',
			'de_AT'          => 'de_DE_formal',
			'de_CH'          => 'de_DE_formal',
			'de_CH_informal' => 'de_DE_formal',
			'es_AR'          => 'es_ES',
			'es_CL'          => 'es_ES',
			'es_CO'          => 'es_ES',
			'es_CR'          => 'es_ES',
			'es_DO'          => 'es_ES',
			'es_GT'          => 'es_ES',
			'es_MX'          => 'es_ES',
			'es_PE'          => 'es_ES',
			'es_PR'          => 'es_ES',
			'es_UY'          => 'es_ES',
			'es_VE'          => 'es_ES',
			'fr_CA'          => 'fr_FR',
			'fr_BE'          => 'fr_FR',
			'nl_BE'          => 'nl_NL',
			'nl_NL_formal'   => 'nl_NL',			
			'pt_PT'          => 'pt_BR',
			'pt_AO'          => 'pt_BR',
			'pt_PT_ao90'     => 'pt_BR',
		) );
	}

	/**
	 * Get main locale for a language variant, or the current locale if no language variant is found.
	 *
	 * @param   string  $locale_variant  The locale variant to get the main locale for.
	 */
	public function get_main_locale_for_language_variant( $locale_variant ) {
		// Initialize locale with the current locale
		$locale = $locale_variant;

		// Define language to load for the language variants.
		$locale_language_variants = $this->get_locale_language_variants();

		// Maybe set locale for the language variant.
		if ( array_key_exists( $locale_variant, $locale_language_variants ) ) {
			$locale = $locale_language_variants[ $locale_variant ];
		}

		return $locale;
	}

	/**
	 * Get the translation file path for the locale, or the main locale for language variants.
	 *
	 * @param  string  $file    Path to the translation file to load.
	 * @param  string  $domain  The text domain.
	 * @param  string  $locale  The locale.
	 */
	public function get_translation_file_path( $file, $locale, $plugin_slug = null, $plugin_directory = null ) {
		// Maybe set plugin slug if not provided
		if ( ! $plugin_slug ) {
			$plugin_slug = self::$plugin_slug;
		}

		// Maybe set plugin directory if not provided
		if ( ! $plugin_directory ) {
			$plugin_directory = self::$directory_path;
		}

		// Initialize variables
		$file_extension = explode( '.', basename( $file ), 2 )[1];
		$main_locale = $this->get_main_locale_for_language_variant( $locale );

		// Define possible translation file paths, in order of priority.
		$translation_file_paths = array(
			// Locale (e.g. de_CH)
			trailingslashit( WP_LANG_DIR ) . $plugin_slug . '/' . $plugin_slug . '-' . $locale . '.' . $file_extension, // safe location
			$plugin_directory . 'languages/' . $plugin_slug . '-' . $locale . '.' . $file_extension, // author location
			trailingslashit( WP_LANG_DIR ) . 'plugins/' . $plugin_slug . '-' . $locale . '.' . $file_extension, // system location

			// Main locale for language variants (e.g. de_DE_formal, when locale is de_CH)
			trailingslashit( WP_LANG_DIR ) . $plugin_slug . '/' . $plugin_slug . '-' . $main_locale . '.' . $file_extension, // safe location
			$plugin_directory . 'languages/' . $plugin_slug . '-' . $main_locale . '.' . $file_extension, // author location
			trailingslashit( WP_LANG_DIR ) . 'plugins/' . $plugin_slug . '-' . $main_locale . '.' . $file_extension, // system location
		);

		// Iterate through the translation file paths
		foreach ( $translation_file_paths as $translation_file_path ) {
			// Skip to next translation file, if file does not exist
			if ( ! file_exists( $translation_file_path ) ) { continue; }

			// Set the translation file path
			$file = $translation_file_path;
			break;
		}

		// Otherwise, return unchanged file path
		return $file;
	}

	/**
	 * Maybe change the translation file path to load the main locale for language variants.
	 *
	 * @param  string  $file    Path to the translation file to load.
	 * @param  string  $domain  The text domain.
	 * @param  string  $locale  The locale. Defaults to `null`.
	 */
	public function maybe_change_translation_file_path( $file, $domain, $locale = null ) {
		// Bail if not loading the plugin text domain.
		if ( self::$plugin_slug !== $domain ) { return $file; }

		// Try get locale from file name, for WordPress versions prior to 6.6.0
		if ( ! $locale ) {
			$domain_locale = explode( '.', basename( $file ), 2 )[0];
			$locale_parts = explode( '-', $domain_locale );
			$locale = end( $locale_parts );
		}

		// Bail if locale is not provided
		if ( ! $locale ) { return $file; }

		// Get whether current file is saved to the system directory
		$is_system_file = -1 !== strpos( $file, trailingslashit( WP_LANG_DIR ) . 'plugins/' );

		// Bail if custom translation file location, and file exists
		if ( ! $is_system_file && file_exists( $file ) ) { return $file; }

		// Return the correct translation file path
		return $this->get_translation_file_path( $file, $locale, self::$plugin_slug, self::$directory_path );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( self::$plugin_slug, false, self::$plugin_slug . '/languages' );
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Check if Woocommerce is activated
		if( ! $this->is_woocommerce_activated() ) {
			add_action( 'fc_admin_notices', array( $this, 'add_woocommerce_required_notice' ), 10 );
			return;
		}

		// Language locale
		add_action( 'load_translation_file', array( $this, 'maybe_change_translation_file_path' ), 100, 3 ); // High priority to override locale files defined from other plugins (ie. Loco Translate at priority `11`)
		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ), 10 );

		// Load features
		add_action( 'after_setup_theme', array( $this, 'load_features' ), 10 );
		add_action( 'after_setup_theme', array( $this, 'load_plugin_compat_features' ), 10 );
		add_action( 'after_setup_theme', array( $this, 'load_theme_compat_features' ), 10 );

		// Clear cache after upgrading the plugin
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_on_updates' ), 10, 2 );
	}



	/**
	 * Flush caches when the plugin is successfully updated.
	 */
	public static function clear_cache_on_updates( $upgrader_object, $options ) {
		// Bail if necessary options data are not available
		if ( ! is_array( $options ) || ! array_key_exists( 'action', $options ) || ! array_key_exists( 'type', $options ) || ! array_key_exists( 'plugins', $options ) ) { return; }

		// Bail if not updating plugins
		if ( 'update' !== $options[ 'action' ] || 'plugin' !== $options[ 'type' ] || ! is_array( $options[ 'plugins' ] ) ) { return; }

		// Get current plugin path name
		$current_plugin_path_name = plugin_basename( __FILE__ );

		foreach( $options[ 'plugins' ] as $plugin_path_name ) {
			if ( $plugin_path_name === $current_plugin_path_name ) {
				wp_cache_flush();
			}
		}
	}



	/**
	 * Load the database migrations.
	 */
	public function load_db_migrations() {
		// Bail if migrations class already loaded
		if ( class_exists( 'FluidCheckout_AdminDBMigrations' ) ) { return; }

		// Load class
		require_once self::$directory_path . 'inc/admin/admin-db-migrations.php';
	}

	/**
	 * Load admin notices.
	 * @since 1.2.5
	 */
	private function load_admin_notices() {
		require_once self::$directory_path . 'inc/admin/admin-notices.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-db-migrations.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-db-migrations-applied.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-review-request.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-divi-checkout-layout-being-used.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-germanized-pro-multistep-enabled.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-woocommerce-checkout-manager-enabled.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-breaking-changes-version-400.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-coderockz-delivery-plugins-detected.php';
	}



	/**
	 * Register plugin features.
	 * @since 1.2.0
	 */
	private function register_features() {
		self::$features = array(
			// Utility features
			'FluidCheckout_Enqueue'                        => array( 'file' => self::$directory_path . 'inc/enqueue.php' ),
			'FluidCheckout_FragmentsRefresh'               => array( 'file' => self::$directory_path . 'inc/fragments-refresh.php' ),
			'FluidCheckout_Validation'                     => array( 'file' => self::$directory_path . 'inc/checkout-validation.php' ),

			// Design templates
			'FluidCheckout_DesignTemplates'                => array( 'file' => self::$directory_path . 'inc/design-templates.php' ),

			// Checkout features
			'FluidCheckout_CheckoutPageTemplate'           => array( 'file' => self::$directory_path . 'inc/checkout-page-template.php' ),
			'FluidCheckout_CheckoutBlock'                  => array( 'file' => self::$directory_path . 'inc/checkout-block.php' ),
			'FluidCheckout_Steps'                          => array( 'file' => self::$directory_path . 'inc/checkout-steps.php' ),
			'FluidCheckout_CouponCodes'                    => array( 'file' => self::$directory_path . 'inc/checkout-coupon-codes.php' ),
			'FluidCheckout_CheckoutFields'                 => array( 'file' => self::$directory_path . 'inc/checkout-fields.php' ),
			'FluidCheckout_CheckoutHideOptionalFields'     => array( 'file' => self::$directory_path . 'inc/checkout-hide-optional-fields.php' ),
			'FluidCheckout_CheckoutShippingPhoneField'     => array( 'file' => self::$directory_path . 'inc/checkout-shipping-phone-field.php' ),
			'FluidCheckout_CheckoutWidgetAreas'            => array( 'file' => self::$directory_path . 'inc/checkout-widget-areas.php' ),

			// Cart features
			'FluidCheckout_CartShippingCalculator'         => array( 'file' => self::$directory_path . 'inc/cart-shipping-calculator.php' ),

			// Edit address features
			'FluidCheckout_AccountEditAddress'             => array( 'file' => self::$directory_path . 'inc/account-edit-address.php' ),
		);
	}

	/**
	 * Get the plugin features list.
	 */
	public function get_features_list() {
		return self::$features;
	}



	/**
	 * Get the assets version number.
	 */
	public function get_assets_version_number() {
		$asset_version = '-' . preg_replace( '/\./', '', self::$version );
		// Needs to use `get_option` directly as `FluidCheckout_Settings::get_option()` wrapper function is not available yet
		$min = 'yes' === get_option( 'fc_load_unminified_assets', 'no' )  ? '' : '.min';
		return $asset_version . $min;
	}



	/**
	 * Load the plugin features
	 * @since 1.2.0
	 */
	public function load_features() {
		// Bail if features list is not valid
		if ( ! is_array( self::$features )  ) { return; }

		// Load each features
		foreach ( self::$features as $feature_key => $feature ) {
			// Load feature file if enabled, file exists, and file is inside our plugin folder
			$file = array_key_exists( 'file', $feature ) ? $feature[ 'file' ] : null;
			if ( file_exists( $file ) && strpos( $file, plugin_dir_path( __FILE__ ) ) === 0 ) {
				require_once $file;
			}
		}

		// Load admin features
		if( is_admin() ) {
			require_once self::$directory_path . 'inc/admin/admin.php';
		}
	}



	/**
	 * Load plugins compatibility features.
	 * @since 1.2.0
	 */
	public function load_plugin_compat_features() {
		// Get active plugins
		$plugins_installed = array_keys( get_plugins() );

		foreach ( $plugins_installed as $plugin_file ) {
			// Skip plugins not activated
			if ( ! is_plugin_active( $plugin_file ) ) { continue; }

			// Get plugin slug
			$plugin_slug = strpos( $plugin_file, '/' ) !== false ? explode( '/', $plugin_file )[0] : explode( '.', $plugin_file )[0];

			// Maybe skip compat file
			if ( true !== apply_filters( 'fc_enable_compat_plugin_' . $plugin_slug, true ) ) { continue; }

			// Get plugin file path
			$plugin_compat_file_path = self::$directory_path . 'inc/compat/plugins/compat-plugin-' . $plugin_slug . '.php';

			// Maybe load plugin's compatibility file, and file is inside our plugin folder
			if ( file_exists( $plugin_compat_file_path ) && strpos( $plugin_compat_file_path, plugin_dir_path( __FILE__ ) ) === 0 ) {
				require_once $plugin_compat_file_path;
			}
		}
	}



	/**
	 * Load themes compatibility features.
	 * @since 1.2.0
	 */
	public function load_theme_compat_features() {
		// Get currently active theme and child theme
		$theme_slugs = array( get_template(), get_stylesheet() );

		foreach ( $theme_slugs as $theme_slug ) {
			// Maybe skip compat file
			if ( true !== apply_filters( 'fc_enable_compat_theme_' . $theme_slug, true ) ) { continue; }

			// Get current theme's compatibility file name
			$theme_compat_file_path = self::$directory_path . 'inc/compat/themes/compat-theme-' . $theme_slug . '.php';

			// Maybe load theme's compatibility file, and file is inside our plugin folder
			if ( file_exists( $theme_compat_file_path ) && strpos( $theme_compat_file_path, plugin_dir_path( __FILE__ ) ) === 0 ) {
				require_once $theme_compat_file_path;
			}
		}
	}



	/**
	 * Locate template files from this plugin.
	 * @deprecated Use FluidCheckout_Steps::instance()->locate_template() instead. This will be removed in version 3.0.0
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		// Add deprecation notice
		wc_doing_it_wrong( __FUNCTION__, 'Use FluidCheckout_Steps::instance()->locate_template() instead.', '2.3.0' );
		
		// Bail if class `FluidCheckout_Steps` is not yet loaded
		if ( ! class_exists( 'FluidCheckout_Steps' ) ) { return $template; }

		return FluidCheckout_Steps::instance()->locate_template( $template, $template_name, $template_path );
	}



	/**
	 * Check if Woocommerce is active on a single install or network wide.
	 *
	 * @since 1.0.0
	 */
	public function is_woocommerce_activated() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( 'woocommerce/woocommerce.php' ) && function_exists( 'WC' );
	}


	
	/**
	 * Declare compatibility with the WooCommerce features.
	 */
	public function declare_woocommerce_features_compatibility() {
		// Bail if class not available
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) { return; }

		// Declare compatibility with WooCommerce HPOS (High Performance Order Storage)
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}



	/**
	 * Check if a plugin is installed on a single install or network wide.
	 * 
	 * @param  string  $plugin_file   The plugin file name.
	 * @since 3.0.0
	 */
	public function is_plugin_installed( $plugin_file ) {
		$is_installed = file_exists( trailingslashit( WP_PLUGIN_DIR ) . $plugin_file ) || $this->is_plugin_activated( $plugin_file );
		return $is_installed;
	}

	/**
	 * Check if Fluid Checkout PRO is active on a single install or network wide.
	 * 
	 * @param  string  $plugin_file   The plugin file name.
	 * @since 3.0.0
	 */
	public function is_plugin_activated( $plugin_file ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_file );
	}



	/**
	 * Check if Fluid Checkout PRO is installed on a single install or network wide.
	 *
	 * @since 3.0.0
	 */
	public function is_pro_installed() {
		return $this->is_plugin_installed( 'fluid-checkout-pro/fluid-checkout-pro.php' );
	}

	/**
	 * Check if Fluid Checkout PRO is active on a single install or network wide.
	 *
	 * @since 1.5.0
	 */
	public function is_pro_activated() {
		return $this->is_plugin_activated( 'fluid-checkout-pro/fluid-checkout-pro.php' );
	}



	/**
	 * Display a admin notice regarding the need for WooCommerce to be active.
	 * @since  1.2.0
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_woocommerce_required_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		$required_plugin_name = __( 'WooCommerce', 'fluid-checkout' );
		$required_plugin_path_name = 'woocommerce/woocommerce.php';
		$required_plugin_search_term = 'woocommerce';
		$action_label = wp_kses_post( __( 'Go to Plugin Search', 'fluid-checkout' ) );
		$action_url = admin_url( 'plugin-install.php?s=' . $required_plugin_search_term . '&tab=search&type=term' );

		if ( ! is_wp_error( validate_plugin( $required_plugin_path_name ) ) ) {
			$action_label = wp_kses_post( sprintf( __( 'Activate %s', 'fluid-checkout' ), $required_plugin_name ) );
			$action_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $required_plugin_path_name ), 'activate-plugin_' . $required_plugin_path_name );
		}
		
		$description = wp_kses_post( sprintf( __( '<strong>%1$s</strong> requires <strong>%2$s</strong> to be installed and activated. <a href="%4$s">%3$s</a>', 'fluid-checkout' ),
			self::$plugin,
			$required_plugin_name,
			$action_label,
			$action_url
		) );

		$notices[] = array(
			'name'        => 'woocommerce_required',
			'error'       => true,
			'description' => $description,
			'dismissable' => false,
		);

		return $notices;
	}



	/**
	 * Return the user id passed in or the current user id
	 */
	public function get_user_id( $user_id = null ) {
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->ID;
		}

		return $user_id;
	}



	/**
	 * Get user location on ip geolocation
	 */
	public function get_user_geo_location() {
		// Bail if geolocation class not available
		if ( ! class_exists( 'WC_Geolocation' ) ) { return false; }
		
		// Get user location information
		$geo      = new WC_Geolocation(); // Get WC_Geolocation instance object
		$user_ip  = $geo->get_ip_address(); // Get user IP
		$user_geo = $geo->geolocate_ip( $user_ip ); // Get geolocated user data.
		$user_geo['country_name'] = array_key_exists( 'country', $user_geo ) && $user_geo['country'] != '' ? WC()->countries->countries[ $user_geo['country'] ] : '';
		$user_geo['ip'] = $user_ip;

		return $user_geo;
	}



	/**
	 * Map an associative array of html attributes to a string of html attributes.
	 *
	 * @param   array  $k  Attributes keys. Will be escaped with `esc_attr`.
	 * @param   array  $v  Attributes values. Will be escaped with `esc_attr`, when passing a URL as the value please provide a escaped string with `esc_url`.
	 *
	 * @return  string     A string that represent the attribute in html format `key="value"` or only `key` when the attribute value is boolean and `true`.
	 */
	public function map_html_attributes( $k, $v ) {
		if ( is_bool( $v ) ) {
			return $v ? esc_attr( $k ) : null;
		}
		return sprintf( '%s="%s"', esc_attr( $k ), esc_attr( $v ) );
	}



	/**
	 * Parse the data from the `post_data` request parameter into an `array`. Alias for the method with the same name from the `FluidCheckout` main class.
	 *
	 * @return  array  Post data for all checkout fields parsed into an `array`.
	 */
	public function get_parsed_posted_data() {
		// Bail if class not available
		if ( ! class_exists( 'FluidCheckout_Steps' ) ) { return; }

		return FluidCheckout_Steps::instance()->get_parsed_posted_data();
	}



	/**
	 * Get object by class name from registered hooks.
	 *
	 * @param   string  $class_name  Class name.
	 *
	 * @return  mixed                The first object for the class found in registered hooks, or `null` if not found.
	 */
	public function get_object_by_class_name_from_hooks( $class_name ) {
		global $wp_filter;

		foreach ( $wp_filter as $tag => $callbacks ) {
			foreach ( $callbacks as $priority => $priority_callbacks) {
				foreach ( $priority_callbacks as $callback ) {
					try {
						// Check target class
						if ( is_array( $callback ) && array_key_exists( 'function', $callback ) && is_array( $callback[ 'function' ] ) && array_key_exists( 0, $callback[ 'function' ] ) && $callback[ 'function' ][0] instanceof $class_name ) {
							return $callback[ 'function' ][0];
						}
					} catch ( Exception $ex ) {
						// Ignore and continue.
					}
				}
			}
		}

		// Return null if not found
		return null;
	}

	/**
	 * Get hook callbacks by class name.
	 *
	 * @param   string  $tag         Hook name.
	 * @param   string  $class_name  Class name.
	 * @param   int     $priority    Hook priority.
	 *
	 * @return  array|bool           Array of hooked functions for the class, or `false` if no functions were found.
	 */
	public function get_hooked_function_for_class( $tag, $class_name, $priority ) {
		global $wp_filter;

		// Bail if hook tag doesn't exist
		if ( ! array_key_exists( $tag, $wp_filter ) ) { return false; }

		$callbacks = $wp_filter[ $tag ]->callbacks;

		// Bail if hook priority doesn't exist
		if ( ! is_array( $callbacks ) || ! array_key_exists( $priority, $callbacks ) ) { return false; }

		$priority_callbacks = $callbacks[ $priority ];
		
		// Bail if priority callbacks are not on the expected format
		if ( ! is_array( $priority_callbacks ) ) { return false; }
		
		$class_callbacks = array();

		foreach ( $priority_callbacks as $callback ) {
			// Check target class
			if ( is_array( $callback ) && array_key_exists( 'function', $callback ) && is_array( $callback[ 'function' ] ) && array_key_exists( 0, $callback[ 'function' ] ) && $callback[ 'function' ][0] instanceof $class_name ) {
				$class_callbacks[] = $callback;
			}
		}

		// Return false if no functions hooked
		return $class_callbacks && count( $class_callbacks ) > 0 ? $class_callbacks : false;
	}

	/**
	 * Get hook callbacks by priority.
	 *
	 * @param   string  $tag         Hook name.
	 * @param   int     $priority    Hook priority.
	 *
	 * @return  array|bool           Array of hooked functions for the priority value, or `false` if no functions were found.
	 */
	public function get_hooked_function_for_priority( $tag, $priority ) {
		global $wp_filter;

		// Bail if hook tag doesn't exist
		if ( ! array_key_exists( $tag, $wp_filter ) ) { return false; }

		$callbacks = $wp_filter[ $tag ]->callbacks;

		// Bail if hook priority doesn't exist
		if ( ! is_array( $callbacks ) || ! array_key_exists( $priority, $callbacks ) ) { return false; }

		$priority_callbacks = $callbacks[ $priority ];

		// Return false if no functions hooked
		return $priority_callbacks && count( $priority_callbacks ) > 0 ? $priority_callbacks : false;
	}

	/**
	 * Remove hook callback by class name.
	 *
	 * @param   string  $tag             Hook name.
	 * @param   string  $function_array  Callable array containing the Object and function name.
	 * @param   int     $priority        Hook priority.
	 *
	 * @return  bool                     `true` when the function was found and removed, `false` otherwise.
	 */
	public function remove_filter_for_class( $tag, $function_array, $priority ) {
		// Bail if function_array isn't an array or doens't have 2 string values
		if ( ! is_array( $function_array ) || count( $function_array ) < 2 || ! is_string( $function_array[1] ) ) { return false; }

		$class_name = $function_array[0];
		$function_name = $function_array[1];
		$class_callbacks = $this->get_hooked_function_for_class( $tag, $class_name, $priority );

		// Bail when no hooks found for that class
		if ( ! is_array( $class_callbacks ) ) { return false; }

		foreach ( $class_callbacks as $callback ) {
			if ( $callback['function'][1] == $function_name ) {
				remove_filter( $tag, $callback['function'], $priority );
			}
		}

		return true;
	}

	/**
	 * Remove hook callback by class name (alias for `remove_filter_for_class`).
	 * @see `remove_filter_for_class`
	 *
	 * @param   string  $tag             Hook name.
	 * @param   string  $function_array  Callable array containing the Object and function name.
	 * @param   int     $priority        Hook priority.
	 *
	 * @return  bool                     `true` when the function was found and removed, `false` otherwise.
	 */
	public function remove_action_for_class( $tag, $function_array, $priority ) {
		return $this->remove_filter_for_class( $tag, $function_array, $priority );
	}

	/**
	 * Remove hook callback for anonymous functions (closure).
	 *
	 * @param   string  $tag         Hook name.
	 * @param   int     $priority    Hook priority.
	 * @param   bool    $first_only  Whether to remove all occurencies or only the first one. Defaults to only the first occurency.
	 *
	 * @return  bool                 `true` when the function was found and removed, `false` otherwise.
	 */
	public function remove_filter_for_closure( $tag, $priority, $all_occurencies = false ) {
		// Get callbacks for the priority value
		$priority_callbacks = $this->get_hooked_function_for_priority( $tag, $priority );

		// Bail when no hooks found for that class
		if ( ! is_array( $priority_callbacks ) ) { return false; }

		foreach ( $priority_callbacks as $callback ) {
			if ( $callback['function'] instanceof Closure ) {
				remove_filter( $tag, $callback['function'], $priority );
				
				// Skip removing other occurencies, when not removing all occurencies
				if ( ! $all_occurencies ) { break; }
			}
		}

		return true;
	}

	/**
	 * Remove hook callback for anonymous functions (closure)(alias for `remove_filter_for_closure`).
	 *
	 * @param   string  $tag         Hook name.
	 * @param   int     $priority    Hook priority.
	 * @param   bool    $first_only  Whether to remove all occurencies or only the first one. Defaults to only the first occurency.
	 *
	 * @return  bool                 `true` when the function was found and removed, `false` otherwise.
	 */
	public function remove_action_for_closure( $tag, $priority, $all_occurencies = false ) {
		return $this->remove_filter_for_closure( $tag, $priority, $all_occurencies );
	}



	/**
	 * Get the plugin version number for this or other plugins.
	 *
	 * @param   string       $main_plugin_file  (optional) The plugin folder and main file name for the plugin to get the version number from. Ie. `woocommerce/woocommerce.php`.
	 *                                          Defaults to main file of this plugin.
	 *
	 * @return  string|bool                     The plugin version number, or `false` of the plugin was not found.
	 */
	public function get_plugin_version( $main_plugin_file = 'fluid-checkout/fluid-checkout.php' ) {
		$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . $main_plugin_file;

		if ( file_exists( $plugin_file ) ) {
			return get_file_data( $plugin_file , ['Version' => 'Version'], 'plugin')['Version'];
		}

		return false;
	}

}

FluidCheckout::instance();
