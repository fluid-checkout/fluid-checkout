<?php
/*
Plugin Name: WooCommerce Fluid Checkout
Plugin URI: https://fluidweb.co/
Description: A simple multi-step checkout fluid experience for any WooCommerce store.
Text Domain: woocommerce-fluid-checkout
Version: 1.0.8
Author: Fluidweb.co
Author URI: https://fluidweb.co/
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

// Define WFC_PLUGIN_FILE.
if ( ! defined( 'WFC_PLUGIN_FILE' ) ) {
  define( 'WFC_PLUGIN_FILE', __FILE__ );
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
  const PLUGIN = 'WooCommerce Fluid Checkout';
	// Version is updated automatically by gulp
	const VERSION = '1.0.8';
	const ASSET_VERSION = '-108';



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

    if ( self::$instances[ $calledClass ] === null ){
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
    $this->hooks();
  }



  /**
   * Define plugin variables.
   */
  public function set_plugin_vars() {
    self::$this_plugin    = plugin_basename( WFC_PLUGIN_FILE );
    self::$directory_path = plugin_dir_path( WFC_PLUGIN_FILE );
    self::$directory_url  = plugin_dir_url( WFC_PLUGIN_FILE );
  }



  /**
	 * Load plugin updater.
	 */
	public function load_updater() {
		require_once self::$directory_path . 'inc/vendor/fluidweb-updater/plugin-updater-bitbucket.php';
		if ( class_exists( 'Fluidweb_PluginUpdater_Bitbucket' ) ) {
			new Fluidweb_PluginUpdater_Bitbucket(
				__FILE__,
				'fluidweb-co/woocommerce-fluid-checkout',
				get_option( '_fluidcheckout_repo_user' ),
				get_option( '_fluidcheckout_repo_pass' ), // TODO: FIX SECURITY - should not save plain text password
				get_option( '_fluidcheckout_allow-beta-updates' )
			);
		}
	}



  /**
   * Load plugin textdomain.
   */
  public function load_textdomain() {
    load_plugin_textdomain( 'woocommerce-fluid-checkout', false, untrailingslashit( self::$directory_path ).'/languages' );
  }



  /**
   * Initialize hooks.
   */
  public function hooks() {
    add_action( 'plugins_loaded', array( $this, 'includes' ) );

    // Template loader
    add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
  }



  /**
   * scripts_styles function.
   *
   * @access public
   * @return void
   */
  public function maybe_get_minified_suffix() {
    $min = '.min';

    // Load uncompressed file if on DEBUG mode
    if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) {
      $min = '';
    }

    return $min;
  }



  /*
   * Locate template files from this plugin.
   * @since 1.0.2
   */
  public function locate_template( $template, $template_name, $template_path ) {
   
    global $woocommerce;
   
    $_template = $template;

   
    if ( ! $template_path ) $template_path = $woocommerce->template_url;
   
    // Get plugin path
    $plugin_path  = untrailingslashit( self::$directory_path ) . '/templates/';
   
    // Look within passed path within the theme
    $template = locate_template(
      array(
        $template_path . $template_name,
        $template_name
      )
    );
   
    // Get the template from this plugin, if it exists
    if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
      $template = $plugin_path . $template_name;
    }
   
    // Use default template
    if ( ! $template ){
      $template = $_template;
    }
   
    // Return what we found
    return $template;
  }



  /**
   * Load plugin includes.
   * @since 1.0.0
   */
  public function includes() {

    // if Woocommerce is not active, bail
    if( ! $this->is_woocommerce_active() ) {
      add_action( 'all_admin_notices', array( $this, 'woocommerce_required_notice' ) );
      deactivate_plugins( self::$this_plugin, true );
      return;
    }

    require_once self::$directory_path . 'inc/checkout-steps.php';
    require_once self::$directory_path . 'inc/checkout-field-types.php';
    require_once self::$directory_path . 'inc/checkout-validation.php';
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
      return false;
    }

    return true;
  }



  /**
   * Shows required message & deactivates this plugin
   * @since  1.0.0
   */
  public function woocommerce_required_notice() {
    echo '<div id="message" class="error"><p>'. sprintf( __( '%1$s requires the %2$s plugin to be installed/activated. %1$s has been deactivated.', 'woocommerce-fluid-checkout' ), self::PLUGIN, 'WooCommerce' ) .'</p></div>';
  }

}

FluidCheckout::instance();