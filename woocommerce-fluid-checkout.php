<?php
/*
Plugin Name: Fluid Checkout for WooCommerce
Plugin URI: https://fluidweb.co/plugins/fluid-checkout/
Description: Provides a fluid checkout experience for any WooCommerce store. Ask for shipping information before billing in a linear and multi-step checkout, add options for gift message and packaging and add a coupon code field at the checkout page that does not distract your customers. Similar to the Shopify checkout, and even better.
Text Domain: woocommerce-fluid-checkout
Domain Path: /languages
Version: 1.2.0-dev-1
Author: Fluidweb.co
Author URI: https://fluidweb.co/
License: GPLv2
WC tested up to: 5.0.0

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

// Define WFC_PLUGIN_FILE.
if ( ! defined( 'WFC_PLUGIN_FILE' ) ) {
	define( 'WFC_PLUGIN_FILE', __FILE__ );
}


/**
 * Plugin Main Class.
 */
class FluidCheckout {
	
	private static $features = array();

	// A single instance of this class.
	public static $instances   = array();
	public static $this_plugin = null;
	public static $directory_path;
	public static $directory_url;
	public static $plugin = 'Fluid Checkout for WooCommerce';
	public static $version = ''; // Values set at function `set_plugin_vars`
	public static $asset_version = ''; // Values set at function `set_plugin_vars`



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
		$this->load_updater();
		$this->add_features();
		$this->hooks();

		// Load premium features
		// require_once self::$directory_path . 'inc/premium/premium-features.php';
	}



	/**
	 * Define plugin variables.
	 */
	public function set_plugin_vars() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		self::$this_plugin    = plugin_basename( WFC_PLUGIN_FILE );
		self::$directory_path = plugin_dir_path( WFC_PLUGIN_FILE );
		self::$directory_url  = plugin_dir_url( WFC_PLUGIN_FILE );
		self::$version = get_file_data( WFC_PLUGIN_FILE , ['Version' => 'Version'], 'plugin')['Version'];
		self::$asset_version = $this->get_assets_version_number();
	}



	/**
	 * Load plugin updater.
	 */
	public function load_updater() {
		// Bail if not on admin pages
		if ( ! is_admin() ) return;

		require_once self::$directory_path . 'inc/vendor/fluidweb-updater/plugin-updater-bitbucket.php';
			
		// Check if updater was loaded correctly and instantiate with options from database
		if ( class_exists( 'Fluidweb_PluginUpdater_Bitbucket' ) ) {
			new Fluidweb_PluginUpdater_Bitbucket(
				__FILE__,
				'fluidweb-co/woocommerce-fluid-checkout',
				get_option( '_fluidcheckout_repo_user' ),
				get_option( '_fluidcheckout_repo_pass' ),
				get_option( '_fluidcheckout_allow-beta-updates' )
			);
		}
	}



	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-fluid-checkout', false, 'woocommerce-fluid-checkout/languages' );
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_features' ) );

		// Template loader
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
	}



	/**
	 * Register plugin features.
	 * @since 1.2.0
	 */
	private function add_features() {
		self::$features = array(
			'checkout-page-template'      => array( 'file' => 'inc/checkout-page-template.php', 'enable_option' => 'wfc_enable_checkout_page_template', 'enable_default' => true ),
			'checkout-steps'              => array( 'file' => 'inc/checkout-steps.php' ),
			'checkout-fields'             => array( 'file' => 'inc/checkout-fields.php' ),
			'checkout-widget-areas'       => array( 'file' => 'inc/checkout-widget-areas.php', 'enable_option' => 'wfc_enable_checkout_widget_areas', 'enable_default' => true ),
			'checkout-validation'         => array( 'file' => 'inc/checkout-validation.php', 'enable_option' => 'wfc_enable_checkout_validation', 'enable_default' => true ),
			'checkout-gift-options'       => array( 'file' => 'inc/checkout-gift-options.php', 'enable_option' => 'wfc_enable_checkout_gift_options', 'enable_default' => true ),
			'cart-widget-areas'           => array( 'file' => 'inc/cart-widget-areas.php', 'enable_option' => 'wfc_enable_checkout_widget_areas', 'enable_default' => true ),
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
		$min = get_option( 'wfc_load_unminified_assets', false ) ? '' : '.min';
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
		if ( ! $_template || apply_filters( 'wfc_override_template_with_theme_file', false, $template, $template_name, $template_path ) ) {
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
		// Check if Woocommerce is activated
		if( ! $this->is_woocommerce_active() ) {
			add_action( 'all_admin_notices', array( $this, 'woocommerce_required_notice' ) );
			return;
		}

		// Bail if features list is not valid
		if ( ! is_array( self::$features )  ) { return; }

		// Maybe extend plugin features
		$_features = apply_filters( 'wfc_init_features_list', self::$features );

		// Load enqueue
		require_once self::$directory_path . 'inc/enqueue.php';

		// Load each features
		foreach ( $_features as $feature_key => $feature ) {
			$feature_is_enabled = true;
			$file = array_key_exists( 'file', $feature ) ? $feature[ 'file' ] : null;
			$enable_option = array_key_exists( 'enable_option', $feature ) ? $feature[ 'enable_option' ] : null;
			$enable_default = array_key_exists( 'enable_default', $feature ) ? $feature[ 'enable_default' ] : null;

			// Set feature as enabled if `enable_default` value is invalid or empty
			if ( ! is_bool( $enable_default ) ) {
				$enable_default = true;
			}

			// Check if feature is set to enabled by option value in the database
			$enable_option_value = get_option( $enable_option, $enable_default );
			$enable_option_value_str = is_bool( $enable_option_value ) && $enable_option_value ? 'true' : strval( $enable_option_value );
			if ( is_string( $enable_option ) && 'true' !== $enable_option_value_str ) {
				$feature_is_enabled = false;
			}
			
			// Load feature file if enabled
			if ( $feature_is_enabled ) {
				require_once self::$directory_path . $file;
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
		echo '<div id="message" class="error"><p>'. sprintf( __( '%1$s requires the %2$s plugin to be installed and activated.', 'woocommerce-fluid-checkout' ), self::PLUGIN, 'WooCommerce' ) .'</p></div>';
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

}

FluidCheckout::instance();
