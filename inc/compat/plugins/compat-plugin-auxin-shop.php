<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Auxin Shop (by Averta).
 */
class FluidCheckout_AuxinShop extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );
	}



	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Template files
		remove_filter( 'wc_get_template', 'auxshp_get_wc_template', 11, 2 );
		add_filter( 'woocommerce_locate_template', array( $this, 'auxshp_locate_template' ), 90, 3 );

		// Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'deregister_woocommerce_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( FluidCheckout_Enqueue::instance(), 'replace_woocommerce_scripts' ), 20 );
	}



	/**
	 * Remove WooCommerce scripts.
	 */
	public function deregister_woocommerce_scripts() {
		wp_deregister_script( 'woocommerce' );
		wp_deregister_script( 'wc-country-select' );
		wp_deregister_script( 'wc-address-i18n' );
		wp_deregister_script( 'wc-checkout' );
	}



	/**
	 * Locate template files from the Auxin Shop plugin.
	 */
	public function auxshp_locate_template( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = null;

		// Set template path to default value when not provided
		if ( ! $template_path ) { $template_path = $woocommerce->template_url; };

		// Get plugin path
		$plugin_path = AUXSHP()->template_path() . 'woocommerce/';

		// Get the template from this plugin, if it exists
		if ( file_exists( $plugin_path . $template_name ) ) {
			$_template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $_template ){
			$_template = $template;
		}

		// Return what we found
		return $_template;
	}

}

FluidCheckout_AuxinShop::instance();
