<?php
/*
Plugin Name: WooCommerce Fluid Checkout
Plugin URI: https://fluidweb.site/plugins/checkout/
Description: WooCommerce Fluid Checkout provides a simple multi-step checkout flow for any WooCommerce store.
Text Domain: woocommerce-fluid-checkout
Version: 1.0.0
Author: FluidWeb
Author URI: https://fluidweb.site/
License: GPLv2

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


/**
 * Main Class.
 */
class FluidCheckout {

	// A single instance of this class.
	public static $instance    = null;
	public static $this_plugin = null;
	public static $woo_checkout_url;
	public static $woo_shop_url;
	public static $woo_cart_url;
	public $basename;
	public $directory_path;
	public $directory_url;
	const PLUGIN               = 'WooCommerce Fluid Checkout';
	const VERSION              = '1.0.0';

	/**
	 * run function.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function run() {
		if ( self::$instance === null )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		self::$this_plugin = plugin_basename( __FILE__ );

		// Load translations
		load_plugin_textdomain( 'woocommerce-fluid-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Define plugin constants
		$this->basename			=	plugin_basename( __FILE__ );
		$this->directory_path	=	plugin_dir_path( __FILE__ );
		$this->directory_url	=	plugin_dir_url( __FILE__ );
		$this->checkout_options =   get_option('wfc_settings');

		$this->hooks();

	}

	public function hooks() {
		add_action( 'init', array( $this, 'set_vars') );

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
	}

	public function set_vars() {

		if( is_admin() || defined( 'DOING_CRON' ) )
			return;

		global $woocommerce;
		self::$woo_checkout_url = wc_get_checkout_url();
		self::$woo_cart_url     = wc_get_cart_url();
		self::$woo_shop_url     = get_permalink( wc_get_page_id( 'shop' ) );

	}

	/**
	 * scripts_styles function.
	 *
	 * @access public
	 * @return void
	 */
	public function scripts_styles() {

		// Bail if not on checkout page.
		if( !is_checkout() ){ return; }

		// TODO: Enable js minification.
		// $min = '.min';
		$min = ''; 

		if ( defined('SCRIPT_DEBUG') && true === SCRIPT_DEBUG ) {
			$min = '';
		}
	    
    wp_enqueue_script( 'fluid-checkout-scripts', plugins_url( "js/fluid-checkout$min.js", __FILE__ ), array( 'jquery' ), self::VERSION, true );

    wp_localize_script( 
    	'fluid-checkout-scripts', 
    	'fluidCheckoutVars', 
    	array( 
    		'woo_checkout_url'  => self::$woo_checkout_url,
    		'woo_cart_url'      => self::$woo_cart_url,
    		'woo_shop_url'      => self::$woo_shop_url,
    	)
    );

		wp_enqueue_script( 'jquery-payment', plugins_url( 'js/jquery.payment.js', __FILE__ ), array( 'jquery' ), self::VERSION );

	  wp_enqueue_style( 'fluid-checkout-style', plugins_url( "css/fluid-checkout-styles$min.css", __FILE__ ), null, self::VERSION );

	}


	/**
	 * Load plugin includes.
	 * @since 1.0.0
	 */
	public function includes() {

		// if Woocommerce is not active, bail
		if( ! $this->is_woocommerce_active() ) {
			return;
		}

		require_once $this->directory_path . 'inc/checkout-page.php';

	}



	/**
	 * Check to see if Woocommerce is active on a single install or network wide.
	 * Otherwise, will display an admin notice.
	 * 
	 * @since 1.0.0
	 */
	public function is_woocommerce_active() {

		// Is Woocommerce active?
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if( ! is_plugin_active( 'woocommerce/woocommerce.php') ) {
			// Admin notice
			$this->requires = 'WooCommerce';
			add_action( 'all_admin_notices', array( $this, 'fluidcheckout_required' ) );
			return false;
		}

		return true;
	}



	/**
	 * Shows required message & deactivates this plugin
	 * @since  1.0.0
	 */
	public function fluidcheckout_required() {
		echo '<div id="message" class="error"><p>'. sprintf( __( '%1$s requires the %2$s plugin to be installed/activated. %1$s has been deactivated.', 'woocommerce-fluid-checkout' ), self::PLUGIN, 'WooCommerce' ) .'</p></div>';
		deactivate_plugins( self::$this_plugin, true );
	}

}

FluidCheckout::run();