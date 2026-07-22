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
		// Output location fields when shipping address is not required (e.g. local pickup)
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'maybe_output_checkout_location_fields_without_shipping_address' ), 40 );
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
		wp_register_script( 'wcfmmp_checkout_location_js', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/wc-multivendor-marketplace/wcfmmp-script-checkout-location' ), array( 'jquery' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}



	/**
	 * Replace order summary shipping output to handle vendor-keyed packages.
	 */
	public function maybe_replace_order_summary_shipping_output() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		// Remove the default action and add our own
		remove_action( 'fc_review_order_shipping', array( FluidCheckout_Steps::instance(), 'maybe_output_order_review_shipping_method_chosen' ), 30 );
		add_action( 'fc_review_order_shipping', array( $this, 'output_order_review_shipping_method_chosen' ), 30 );
	}



	/**
	 * Move checkout location map to shipping section.
	 */
	public function maybe_reposition_checkout_location_map() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		global $WCFMmp;

		// Bail if frontend is not available
		if ( ! isset( $WCFMmp->frontend ) ) { return; }

		remove_action( 'woocommerce_after_checkout_billing_form', array( $WCFMmp->frontend, 'wcfmmp_checkout_user_location_map' ), 50 );
		// Use FC hook outside `needs_shipping_address()` so the map still shows for local pickup
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $WCFMmp->frontend, 'wcfmmp_checkout_user_location_map' ), 50 );
	}

	/**
	 * Move checkout location fields to shipping section.
	 *
	 * @param   array  $fields  Checkout fields.
	 */
	public function maybe_reposition_checkout_location_fields( $fields ) {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return $fields; }

		// Bail if address field is not available
		if ( ! isset( $fields[ 'billing' ][ 'wcfmmp_user_location' ] ) ) { return $fields; }

		// Ensure shipping fields section exists
		if ( ! isset( $fields[ 'shipping' ] ) ) {
			$fields[ 'shipping' ] = array();
		}

		// Move the address field to shipping section
		$fields[ 'shipping' ][ 'wcfmmp_user_location' ] = $fields[ 'billing' ][ 'wcfmmp_user_location' ];
		$fields[ 'shipping' ][ 'wcfmmp_user_location' ][ 'priority' ] = 999;

		// Move latitude and longitude fields to shipping section
		if ( isset( $fields[ 'billing' ][ 'wcfmmp_user_location_lat' ] ) ) {
			$fields[ 'shipping' ][ 'wcfmmp_user_location_lat' ] = $fields[ 'billing' ][ 'wcfmmp_user_location_lat' ];
		}
		if ( isset( $fields[ 'billing' ][ 'wcfmmp_user_location_lng' ] ) ) {
			$fields[ 'shipping' ][ 'wcfmmp_user_location_lng' ] = $fields[ 'billing' ][ 'wcfmmp_user_location_lng' ];
		}

		unset( $fields[ 'billing' ][ 'wcfmmp_user_location' ], $fields[ 'billing' ][ 'wcfmmp_user_location_lat' ], $fields[ 'billing' ][ 'wcfmmp_user_location_lng' ] );

		return $fields;
	}

	/**
	 * Output checkout location fields when shipping address is not required.
	 *
	 * Shipping-only fields inside `form-shipping.php` are skipped when
	 * `needs_shipping_address()` is false (e.g. local pickup). Output them here
	 * so the map and distance shipping still have the required inputs.
	 */
	public function maybe_output_checkout_location_fields_without_shipping_address() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return; }

		// Bail if checkout or cart is not available
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() || ! WC()->cart ) { return; }

		// Bail if shipping address is needed (fields already output in the shipping form)
		if ( WC()->cart->needs_shipping_address() ) { return; }

		$checkout = WC()->checkout();
		$fields = $checkout->get_checkout_fields( 'shipping' );
		$location_field_keys = array( 'wcfmmp_user_location', 'wcfmmp_user_location_lat', 'wcfmmp_user_location_lng' );

		foreach ( $location_field_keys as $field_key ) {
			// Skip if field is not available
			if ( ! isset( $fields[ $field_key ] ) ) { continue; }

			woocommerce_form_field( $field_key, $fields[ $field_key ], $checkout->get_value( $field_key ) );
		}
	}



	/**
	 * Output chosen shipping methods for order summary using package keys.
	 *
	 * WCFM stores chosen methods keyed by vendor/package key. We temporarily
	 * map them to numeric indexes so the core FC output can be reused.
	 */
	public function output_order_review_shipping_method_chosen() {
		// Bail if not on checkout or cart page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ! FluidCheckout_Steps::instance()->is_cart_page_or_fragment() ) { return; }

		// Retrieve shipping packages and chosen methods
		$packages = WC()->shipping()->get_packages();

		// Retrieve chosen methods from session
		$chosen_methods = is_callable( array( WC()->session, 'get' ) ) ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();

		// Initialize variables
		$numeric_chosen_methods = array();
		$package_index = 0;

		// Map vendor-keyed chosen methods to numeric indexes for compatibility
		foreach ( $packages as $package_key => $package ) {
			if ( isset( $chosen_methods[ $package_key ] ) ) {
				$numeric_chosen_methods[ $package_index ] = $chosen_methods[ $package_key ];
			}
			elseif ( isset( $chosen_methods[ $package_index ] ) ) {
				$numeric_chosen_methods[ $package_index ] = $chosen_methods[ $package_index ];
			}
			$package_index++;
		}

		// Temporarily set numeric keys, then always restore original session values
		try {
			if ( is_callable( array( WC()->session, 'set' ) ) ) {
				WC()->session->set( 'chosen_shipping_methods', $numeric_chosen_methods );
			}

			FluidCheckout_Steps::instance()->maybe_output_order_review_shipping_method_chosen();
		}
		finally {
			if ( is_callable( array( WC()->session, 'set' ) ) ) {
				WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
			}
		}
	}

}

FluidCheckout_WCFMMultiVendorMarketplace::instance();
