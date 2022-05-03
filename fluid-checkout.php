<?php
/*
Plugin Name: Fluid Checkout for WooCommerce
Plugin URI: https://fluidcheckout.com/
Description: Provides a distraction free checkout experience for any WooCommerce store. Ask for shipping information before billing in a truly linear multi-step or one-step checkout, add options for gift message, and display a coupon code field at the checkout page that does not distract your customers.
Text Domain: fluid-checkout
Domain Path: /languages
Version: 1.5.8
Author: Fluid Checkout
Author URI: https://fluidcheckout.com/
WC requires at least: 5.0
WC tested up to: 6.1
License URI: http://www.gnu.org/licenses/gpl-3.0.html
License: GPLv3

Copyright (C) 2021 Fluidweb OÜ

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



// Plugin activation
require_once plugin_dir_path( __FILE__ ) . 'inc/plugin-activation.php';
register_activation_hook( __FILE__, array( 'FluidCheckout_Activation', 'on_activation' ) );



/**
 * Plugin Main Class.
 */
class FluidCheckout {

	// A single instance of this class.
	public static $instances = array();
	public static $directory_path;
	public static $directory_url;
	public static $plugin = 'Fluid Checkout for WooCommerce';
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

		if ( ! array_key_exists( $calledClass, self::$instances ) || self::$instances[ $calledClass ] === null ){
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
		$this->load_admin_notices();
		$this->add_features();

		// Run hooks initialization after all plugins have been loaded
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
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'fluid-checkout', false, 'fluid-checkout/languages' );
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

		// Load features
		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ), 10 );
		add_action( 'after_setup_theme', array( $this, 'load_features' ), 10 );
		add_action( 'after_setup_theme', array( $this, 'load_plugin_compat_features' ), 10 );
		add_action( 'after_setup_theme', array( $this, 'load_theme_compat_features' ), 10 );

		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );
	}



	/**
	 * Load admin notices.
	 * @since 1.2.5
	 */
	private function load_admin_notices() {
		require_once self::$directory_path . 'inc/admin/admin-notices.php';
		require_once self::$directory_path . 'inc/admin/admin-notice-review-request.php';
	}



	/**
	 * Register plugin features.
	 * @since 1.2.0
	 */
	private function add_features() {
		self::$features = array(
			'checkout-steps'                      => array( 'file' => self::$directory_path . 'inc/checkout-steps.php' ),
			'checkout-page-template'              => array( 'file' => self::$directory_path . 'inc/checkout-page-template.php', 'enable_option' => 'fc_enable_checkout_page_template', 'enable_default' => 'yes' ),

			'checkout-fields'                     => array( 'file' => self::$directory_path . 'inc/checkout-fields.php', 'enable_option' => 'fc_apply_checkout_field_args', 'enable_default' => 'yes' ),
			'checkout-hide-optional-fields'       => array( 'file' => self::$directory_path . 'inc/checkout-hide-optional-fields.php', 'enable_option' => 'fc_enable_checkout_hide_optional_fields', 'enable_default' => 'yes' ),
			'checkout-shipping-phone'             => array( 'file' => self::$directory_path . 'inc/checkout-shipping-phone-field.php', 'enable_option' => 'fc_shipping_phone_field_visibility', 'enable_default' => 'no' ),
			'checkout-express-checkout'           => array( 'file' => self::$directory_path . 'inc/checkout-express-checkout.php', 'enable_option' => 'fc_enable_checkout_express_checkout', 'enable_default' => 'yes' ),
			'checkout-local-pickup'               => array( 'file' => self::$directory_path . 'inc/checkout-local-pickup.php', 'enable_option' => 'fc_enable_checkout_local_pickup', 'enable_default' => 'yes' ),
			'checkout-validation'                 => array( 'file' => self::$directory_path . 'inc/checkout-validation.php', 'enable_option' => 'fc_enable_checkout_validation', 'enable_default' => 'yes' ),
			'checkout-gift-options'               => array( 'file' => self::$directory_path . 'inc/checkout-gift-options.php', 'enable_option' => 'fc_enable_checkout_gift_options', 'enable_default' => 'no' ),
			'checkout-coupon-codes'               => array( 'file' => self::$directory_path . 'inc/checkout-coupon-codes.php', 'enable_option' => 'fc_enable_checkout_coupon_codes', 'enable_default' => 'yes' ),
			'checkout-widget-areas'               => array( 'file' => self::$directory_path . 'inc/checkout-widget-areas.php', 'enable_option' => 'fc_enable_checkout_widget_areas', 'enable_default' => 'yes' ),
			'packing-slips'                       => array( 'file' => self::$directory_path . 'inc/packing-slips.php', 'enable_option' => 'fc_enable_packing_slips_options', 'enable_default' => 'yes' ),
		);
	}



	/**
	 * Get the assets version number.
	 */
	public function get_assets_version_number() {
		$asset_version = '-' . preg_replace( '/\./', '', self::$version );
		$min = get_option( 'fc_load_unminified_assets', 'no' ) === 'yes' ? '' : '.min';
		return $asset_version . $min;
	}



	/**
	 * Locate template files from this plugin.
	 * @since 1.0.2
	 */
	public function locate_template( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = $woocommerce->template_url; };

		// Get plugin path
		$plugin_path  = self::$directory_path . 'templates/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;
		}

		// Look for template file in the theme
		if ( ! $_template || apply_filters( 'fc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
			$_template = locate_template( array(
				$template_path . $template_name,
				$template_name,
			) );
		}

		// Use default template
		if ( ! $_template ){
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}



	/**
	 * Load the plugin features
	 * @since 1.2.0
	 */
	public function load_features() {
		// Bail if features list is not valid
		if ( ! is_array( self::$features )  ) { return; }

		// Maybe extend plugin features
		$_features = apply_filters( 'fc_init_features_list', self::$features );

		// Load enqueue
		require_once self::$directory_path . 'inc/enqueue.php';

		// Load each features
		foreach ( $_features as $feature_key => $feature ) {

			$feature_is_enabled = true;
			$file = array_key_exists( 'file', $feature ) ? $feature[ 'file' ] : null;
			$enable_option = array_key_exists( 'enable_option', $feature ) ? $feature[ 'enable_option' ] : null;
			$enable_default = array_key_exists( 'enable_default', $feature ) ? $feature[ 'enable_default' ] : 'no';

			// Check if feature is set to enabled by option value in the database
			if ( $enable_option !== null ) {
				$enable_option_value = get_option( $enable_option, $enable_default );

				// Check option value
				if ( ( is_bool( $enable_option_value ) && $enable_option_value === true ) || ( strval( $enable_option_value ) !== 'no' && strval( $enable_option_value ) !== '0' && strval( $enable_option_value ) !== 'false' ) ) {
					$feature_is_enabled = true;
				}
				else {
					$feature_is_enabled = false;
				}
			}

			// Load feature file if enabled, file exists, and file is inside our plugin folder
			if ( $feature_is_enabled && file_exists( $file ) && strpos( $file, plugin_dir_path( __FILE__ ) ) === 0 ) {
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
		// Get all plugins installed
		$plugins_installed = get_plugins();
		
		foreach ( $plugins_installed as $plugin_file => $plugin_meta ) {
			// Skip plugins not activated
			if ( ! is_plugin_active( $plugin_file ) ) { continue; }

			// Get plugin slug
			$plugin_slug = strpos( $plugin_file, '/' ) !== false ? explode( '/', $plugin_file )[0] : explode( '.', $plugin_file )[0];

			// Maybe skip compat file
			if ( get_option( 'fc_enable_compat_plugin_' . $plugin_slug, true ) === 'false' ) { continue; }

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
			if ( get_option( 'fc_enable_compat_theme_' . $theme_slug, true ) === 'false' ) { continue; }

			// Get current theme's compatibility file name
			$theme_compat_file_path = self::$directory_path . 'inc/compat/themes/compat-theme-' . $theme_slug . '.php';

			// Maybe load theme's compatibility file, and file is inside our plugin folder
			if ( file_exists( $theme_compat_file_path ) && strpos( $theme_compat_file_path, plugin_dir_path( __FILE__ ) ) === 0 ) {
				require_once $theme_compat_file_path;
			}
		}
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
	 * Check if Fluid Checkout PRO is active on a single install or network wide.
	 *
	 * @since 1.5.0
	 */
	public function is_pro_activated() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( 'fluid-checkout-pro/fluid-checkout-pro.php' );
	}



	/**
	 * Display a admin notice regarding the need for WooCommerce to be active.
	 * @since  1.2.0
	 * @param  array  $notices  Admin notices from the plugin.
	 */
	public function add_woocommerce_required_notice( $notices = array() ) {
		// Bail if user does not have enough permissions
		if ( ! current_user_can( 'install_plugins' ) ) { return; }

		if ( is_wp_error( validate_plugin( 'woocommerce/woocommerce.php' ) ) ) {
			$description = sprintf( wp_kses_post( __( '<strong>%1$s</strong> requires <strong>%2$s</strong> to be installed and activated. <a href="%3$s">Go to Plugin Search</a>', 'fluid-checkout' ) ), self::$plugin, 'WooCommerce', admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) );
		}
		else {
			$description = sprintf( wp_kses_post( __( '<strong>%1$s</strong> requires <strong>%2$s</strong> to be installed and activated. <a href="%3$s">Activate WooCommerce</a>', 'fluid-checkout' ) ), self::$plugin, 'WooCommerce', wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' ) );
		}
		
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
		// Get user location
		if ( class_exists( 'WC_Geolocation' ) ) {
			$geo      = new WC_Geolocation(); // Get WC_Geolocation instance object
			$user_ip  = $geo->get_ip_address(); // Get user IP
			$user_geo = $geo->geolocate_ip( $user_ip ); // Get geolocated user data.
			$user_geo['country_name'] = array_key_exists( 'country', $user_geo ) && $user_geo['country'] != '' ? WC()->countries->countries[ $user_geo['country'] ] : '';

			return $user_geo;
		}

		return false;
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

}

FluidCheckout::instance();
