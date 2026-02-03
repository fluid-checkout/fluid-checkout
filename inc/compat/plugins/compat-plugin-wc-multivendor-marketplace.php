<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WCFM - WooCommerce Multivendor Marketplace (by WC Lovers).
 */
class FluidCheckout_WCFMMultiVendorMarketplace extends FluidCheckout {

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
		// Replace plugin scripts with modified version
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_plugin_scripts' ), 5 );
		add_action( 'init', array( $this, 'maybe_replace_order_summary_shipping_output' ), 20 );
	}

	/**
	 * Maybe replace plugin scripts with modified version.
	 */
	public function maybe_replace_plugin_scripts() {
		// Bail if not at checkout
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if required class is not available
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		// Replace checkout location script with FC-compatible version
		wp_register_script( 'wcfmmp_checkout_location_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-multivendor-marketplace/wcfmmp-script-checkout-location' ), array( 'jquery' ), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	/**
	 * Replace order summary shipping output to handle vendor-keyed packages.
	 *
	 * WCFM stores chosen shipping methods keyed by vendor/package key (not
	 * by numeric index). The default FC output assumes numeric indexes, so
	 * we swap it with a key-aware version.
	 */
	public function maybe_replace_order_summary_shipping_output() {
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		remove_action( 'fc_review_order_shipping', array( FluidCheckout_Steps::instance(), 'maybe_output_order_review_shipping_method_chosen' ), 30 );
		add_action( 'fc_review_order_shipping', array( $this, 'output_order_review_shipping_method_chosen' ), 30 );
	}

	/**
	 * Output chosen shipping methods for order summary using package keys.
	 *
	 * WCFM stores chosen methods keyed by vendor/package key. We temporarily
	 * map them to numeric indexes so the core FC output can be reused.
	 */
	public function output_order_review_shipping_method_chosen() {
		// Bail if not on checkout or cart page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Retrieve shipping packages and chosen methods
		$packages = WC()->shipping()->get_packages();
		$chosen_methods = is_callable( array( WC()->session, 'get' ) ) ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
		$numeric_chosen_methods = array();
		$package_index = 0;

		// Map vendor-keyed chosen methods to numeric indexes for compatibility
		foreach ( $packages as $package_key => $package ) {
			if ( isset( $chosen_methods[ $package_key ] ) ) {
				$numeric_chosen_methods[ $package_index ] = $chosen_methods[ $package_key ];
			} elseif ( isset( $chosen_methods[ $package_index ] ) ) {
				$numeric_chosen_methods[ $package_index ] = $chosen_methods[ $package_index ];
			}
			$package_index++;
		}

		if ( is_callable( array( WC()->session, 'set' ) ) ) {
			WC()->session->set( 'chosen_shipping_methods', $numeric_chosen_methods );
		}

		FluidCheckout_Steps::instance()->maybe_output_order_review_shipping_method_chosen();

		// Restore original session chosen methods
		if ( is_callable( array( WC()->session, 'set' ) ) ) {
			WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
		}
	}

}

FluidCheckout_WCFMMultiVendorMarketplace::instance();
