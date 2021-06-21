<?php
/*
Plugin Name: Fluid Checkout for WooCommerce
Plugin URI: https://fluidcheckout.com/
Description: Provides a Fluid Checkout experience for any WooCommerce store. Ask for shipping information before billing in a truly linear multi-step or one-step checkout, add options for gift message and packaging and display a coupon code field at the checkout page that does not distract your customers. Similar to the Shopify checkout, and even better.
Text Domain: fluid-checkout
Domain Path: /languages
Version: 1.2.0-RC
Author: Fluidweb.co
Author URI: https://fluidweb.co/
License: GPLv2
WC requires at least: 5.0.0
WC tested up to: 5.4.0

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define FC_PLUGIN_FILE.
if ( ! defined( 'FC_PLUGIN_FILE' ) ) {
	define( 'FC_PLUGIN_FILE', __FILE__ );
}


/**
 * Plugin Main Class.
 */
class FluidCheckout {

	// A single instance of this class.
	public static $instances   = array();
	public static $this_plugin = null;
	public static $directory_path;
	public static $directory_url;
	public static $plugin = 'Fluid Checkout for WooCommerce';
	public static $version = ''; // Values set at function `set_plugin_vars`
	public static $asset_version = ''; // Values set at function `set_plugin_vars`

	/**
	 * Hold list of the plugin features to load when initializing.
	 *
	 * @var array
	 */
	private static $features = array();

	/**
	 * Hold cached values for parsed `post_data`.
	 *
	 * @var array
	 */
	private $posted_data = null;


	/**
	 * Singleton instance function.
	 *
	 * @access public
	 * @static
	 * @return void
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
		$this->load_textdomain();
		$this->add_features();
		$this->hooks();
	}



	/**
	 * Define plugin variables.
	 */
	public function set_plugin_vars() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		self::$this_plugin    = plugin_basename( FC_PLUGIN_FILE );
		self::$directory_path = plugin_dir_path( FC_PLUGIN_FILE );
		self::$directory_url  = plugin_dir_url( FC_PLUGIN_FILE );
		self::$version = get_file_data( FC_PLUGIN_FILE , ['Version' => 'Version'], 'plugin')['Version'];
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
		if( ! $this->is_woocommerce_active() ) {
			add_action( 'all_admin_notices', array( $this, 'woocommerce_required_notice' ) );
			return;
		}

		// Load features
		add_action( 'plugins_loaded', array( $this, 'load_features' ) );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_compat_features' ) );
		add_action( 'plugins_loaded', array( $this, 'load_theme_compat_features' ) );


		// Template file loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 100, 3 );
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
			'checkout-validation'                 => array( 'file' => self::$directory_path . 'inc/checkout-validation.php', 'enable_option' => 'fc_enable_checkout_validation', 'enable_default' => 'yes' ),
			'checkout-gift-options'               => array( 'file' => self::$directory_path . 'inc/checkout-gift-options.php', 'enable_option' => 'fc_enable_checkout_gift_options', 'enable_default' => 'no' ),
			'checkout-coupon-codes'               => array( 'file' => self::$directory_path . 'inc/checkout-coupon-codes.php', 'enable_option' => 'fc_enable_checkout_coupon_codes', 'enable_default' => 'yes' ),
			'checkout-widget-areas'               => array( 'file' => self::$directory_path . 'inc/checkout-widget-areas.php', 'enable_option' => 'fc_enable_checkout_widget_areas', 'enable_default' => 'yes' ),
		);
	}



	/**
	 * scripts_styles function.
	 *
	 * @access public
	 * @return void
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

			// Load feature file if enabled and file exists
			if ( $feature_is_enabled && file_exists( $file ) ) {
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
		// Bail if visiting admin pages
		if ( is_admin() ) { return; }

		// Get active plugins
		$plugins_installed = get_option('active_plugins');

		foreach ( $plugins_installed as $plugin_file ) {
			// Get plugin slug
			$plugin_slug = strpos( $plugin_file, '/' ) !== false ? explode( '/', $plugin_file )[0] : explode( '.', $plugin_file )[0];

			// Maybe skip compat file
			if ( get_option( 'fc_enable_compat_plugin_' . $plugin_slug, true ) === 'false' ) { continue; }

			// Get plugin file path
			$plugin_compat_file_path = self::$directory_path . 'inc/compat/plugins/compat-plugin-' . $plugin_slug . '.php';

			// Maybe load plugin's compatibility file
			if ( file_exists( $plugin_compat_file_path ) ) {
				include_once( $plugin_compat_file_path );
			}
		}
	}



	/**
	 * Load themes compatibility features.
	 * @since 1.2.0
	 */
	public function load_theme_compat_features() {
		// Bail if visiting admin pages
		if ( is_admin() ) { return; }

		// Get currently active theme and child theme
		$theme_slugs = array( get_template(), get_stylesheet() );

		foreach ( $theme_slugs as $theme_slug ) {
			// Maybe skip compat file
			if ( get_option( 'fc_enable_compat_theme_' . $theme_slug, true ) === 'false' ) { continue; }

			// Get current theme's compatibility file name
			$theme_compat_file_path = self::$directory_path . 'inc/compat/themes/compat-theme-' . $theme_slug . '.php';

			// Maybe load theme's compatibility file
			if ( file_exists( $theme_compat_file_path ) ) {
				include_once( $theme_compat_file_path );
			}
		}
	}



	/**
	 * Check to see if Woocommerce is active on a single install or network wide.
	 * Otherwise, will display an admin notice.
	 *
	 * @since 1.0.0
	 */
	public function is_woocommerce_active() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}



	/**
	 * Shows required message & deactivates this plugin
	 * @since  1.0.0
	 */
	public function woocommerce_required_notice() {
		echo '<div id="message" class="error"><p>'. sprintf( __( '<strong>%1$s requires %2$s to be installed and active. You can <a href="%3$s">download %2$s here</a></strong>.', 'fluid-checkout' ), self::$plugin, 'WooCommerce', 'https://woocommerce.com' ) .'</p></div>';
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
	 * @param   array  $k  Attributes keys.
	 * @param   array  $v  Attributes values.
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
	 * Parse the data from the `post_data` request parameter into an `array`.
	 *
	 * @return  array  Post data for all checkout fields parsed into an `array`.
	 */
	public function get_parsed_posted_data() {
		// Return cached parsed data
		if ( is_array( $this->posted_data ) ) {
			return $this->posted_data;
		}

		// Get sanitized posted data as a string
		$posted_data = isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : '';

		// Parsing posted data into an array
		$new_posted_data = array();
		$vars = explode( '&', $posted_data );
		foreach ( $vars as $k => $value ) {
			$v = explode( '=', urldecode( $value ) );
			$new_posted_data[ $v[0] ] = array_key_exists( 1, $v) ? wc_clean( wp_unslash( $v[1] ) ) : null;
		}

		// Updated cached posted data
		$this->posted_data = $new_posted_data;

		return $this->posted_data;
	}



	/**
	 * Get hook callbacks by class name.
	 */
	public function get_hooked_function_for_class( $tag, $class_name, $priority ) {
		global $wp_filter;

		// Bail if hook tag doesn't exist
		if ( ! array_key_exists( $tag, $wp_filter ) ) { return false; }

		$callbacks = $wp_filter[ $tag ]->callbacks;
		$priority_callbacks = $callbacks[ $priority ];
		$class_callbacks = array();

		foreach ( $priority_callbacks as $callback ) {
			// Check target class
			if ( $callback[ 'function' ][0] instanceof $class_name ) {
				$class_callbacks[] = $callback;
			}
		}

		// Return false if no functions hooked
		return count( $class_callbacks ) > 0 ? $class_callbacks : false;
	}

	/**
	 * Remove hook callback by class name.
	 */
	public function remove_filter_for_class( $tag, $function_array, $priority ) {
		// Bail if function_array isn't an array or doens't have 2 string values
		if ( ! is_array( $function_array ) || count( $function_array ) < 2 || ! is_string( $function_array[0] ) || ! is_string( $function_array[1] ) ) { return false; }

		$class_name = $function_array[0];
		$function_name = $function_array[1];
		$class_callbacks = $this->get_hooked_function_for_class( $tag, $class_name, $priority );

		// Bail when no hooks found for that class
		if ( ! $class_callbacks ) { return false; }

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
	 */
	public function remove_action_for_class( $tag, $function_array, $priority ) {
		return $this->remove_filter_for_class( $tag, $function_array, $priority );
	}

}

FluidCheckout::instance();
