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
		// Maybe replace plugin scripts with modified version
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_replace_plugin_scripts' ), 5 );

		// Maybe replace order summary shipping output
		add_action( 'init', array( $this, 'maybe_replace_order_summary_shipping_output' ), 20 );

		// Move checkout location map and field to shipping section
		add_action( 'init', array( $this, 'maybe_reposition_checkout_location_map' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_reposition_checkout_location_fields' ), 100 );
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
	 * Move checkout location map before shipping section.
	 */
	public function maybe_reposition_checkout_location_map() {
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		global $WCFMmp;
		if ( ! isset( $WCFMmp->frontend ) ) { return; }

		remove_action( 'woocommerce_after_checkout_billing_form', array( $WCFMmp->frontend, 'wcfmmp_checkout_user_location_map' ), 50 );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $WCFMmp->frontend, 'wcfmmp_checkout_user_location_map' ), 50 );
	}

	/**
	 * Move checkout location fields to shipping section.
	 */
	public function maybe_reposition_checkout_location_fields( $fields ) {
		if ( ! class_exists( 'WCFMmp' ) ) { return $fields; }

		if ( ! isset( $fields['billing']['wcfmmp_user_location'] ) ) { return $fields; }

		if ( ! isset( $fields['shipping'] ) ) {
			$fields['shipping'] = array();
		}

		$fields['shipping']['wcfmmp_user_location'] = $fields['billing']['wcfmmp_user_location'];
		$fields['shipping']['wcfmmp_user_location']['priority'] = 999;

		if ( isset( $fields['billing']['wcfmmp_user_location_lat'] ) ) {
			$fields['shipping']['wcfmmp_user_location_lat'] = $fields['billing']['wcfmmp_user_location_lat'];
		}
		if ( isset( $fields['billing']['wcfmmp_user_location_lng'] ) ) {
			$fields['shipping']['wcfmmp_user_location_lng'] = $fields['billing']['wcfmmp_user_location_lng'];
		}

		unset( $fields['billing']['wcfmmp_user_location'], $fields['billing']['wcfmmp_user_location_lat'], $fields['billing']['wcfmmp_user_location_lng'] );

		return $fields;
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
