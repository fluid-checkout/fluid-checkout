<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WCFM - WooCommerce Multivendor Marketplace (by WC Lovers).
 */
class FluidCheckout_WCFMMultiVendorMarketplace extends FluidCheckout {

	/**
	 * Location field keys used by WCFM at checkout.
	 *
	 * @var array
	 */
	private $location_field_keys = array(
		'wcfmmp_user_location',
		'wcfmmp_user_location_lat',
		'wcfmmp_user_location_lng',
	);



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

		// Move checkout location map and field to shipping section
		add_action( 'init', array( $this, 'maybe_reposition_checkout_location_map' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_reposition_checkout_location_fields' ), 100 );

		// Output location fields when shipping address is not required (e.g. local pickup)
		add_action( 'fc_checkout_after_step_shipping_fields_inside', array( $this, 'maybe_output_checkout_location_fields_without_shipping_address' ), 40 );

		// Maybe clear required flag for delivery location when local pickup is selected
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_clear_delivery_location_required_for_local_pickup' ), 110 );

		// Shipping address review text
		add_filter( 'fc_substep_text_shipping_address_field_keys_skip_list', array( $this, 'add_delivery_location_field_step_review_text_skip_list' ), 10 );
		add_filter( 'fc_substep_shipping_address_text_lines', array( $this, 'add_substep_text_lines_shipping_address' ), 10 );

		// Maybe set substep as incomplete when delivery location is missing
		add_filter( 'fc_is_substep_complete_shipping_address', array( $this, 'maybe_set_substep_incomplete_shipping_address' ), 10 );

		// Checkout validation
		add_action( 'woocommerce_checkout_process', array( $this, 'maybe_validate_delivery_location' ), 10 );
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

		foreach ( $this->location_field_keys as $field_key ) {
			// Skip if field is not available
			if ( ! isset( $fields[ $field_key ] ) ) { continue; }

			woocommerce_form_field( $field_key, $fields[ $field_key ], $checkout->get_value( $field_key ) );
		}
	}

	/**
	 * Maybe clear required flag for delivery location when local pickup is selected.
	 *
	 * @param   array  $fields  Checkout fields.
	 */
	public function maybe_clear_delivery_location_required_for_local_pickup( $fields ) {
		// Bail if delivery location field is not available
		if ( ! isset( $fields[ 'shipping' ][ 'wcfmmp_user_location' ] ) ) { return $fields; }

		// Bail if local pickup is not selected
		if ( ! $this->is_local_pickup_selected() ) { return $fields; }

		// Clear required flag so pickup checkout is not blocked by the delivery pin
		$fields[ 'shipping' ][ 'wcfmmp_user_location' ][ 'required' ] = false;

		return $fields;
	}



	/**
	 * Add delivery location fields to the shipping address review text skip list.
	 *
	 * @param   array  $field_keys_skip_list  Field keys to skip in the review text.
	 */
	public function add_delivery_location_field_step_review_text_skip_list( $field_keys_skip_list ) {
		return array_merge( $field_keys_skip_list, $this->location_field_keys );
	}

	/**
	 * Add delivery location lines to the shipping address substep review text.
	 *
	 * @param   array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_address( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get delivery location values
		$location = WC()->checkout()->get_value( 'wcfmmp_user_location' );
		$lat = WC()->checkout()->get_value( 'wcfmmp_user_location_lat' );
		$lng = WC()->checkout()->get_value( 'wcfmmp_user_location_lng' );

		// Bail if delivery location is empty
		if ( empty( $location ) ) { return $review_text_lines; }

		// Intentionally use the text domain from the WCFM plugin
		$review_text_lines[] = '<strong>' . esc_html__( 'Delivery Location', 'wc-multivendor-marketplace' ) . '</strong>';
		$review_text_lines[] = esc_html( $location );

		// Maybe add coordinates on a single line
		if ( ! empty( $lat ) && ! empty( $lng ) ) {
			$review_text_lines[] = esc_html( $lat . ' ' . $lng );
		}

		return $review_text_lines;
	}



	/**
	 * Maybe set the shipping address substep as incomplete when delivery location is required and missing.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_address( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Bail if delivery location is not required
		if ( ! $this->is_delivery_location_required() ) { return $is_substep_complete; }

		// Get delivery location values
		$location = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location' );
		$lat = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lat' );
		$lng = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lng' );

		// Maybe set step as incomplete
		if ( empty( $location ) || empty( $lat ) || empty( $lng ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}

	/**
	 * Maybe validate delivery location on place order.
	 */
	public function maybe_validate_delivery_location() {
		// Bail if delivery location is not required
		if ( ! $this->is_delivery_location_required() ) { return; }

		// Get delivery location values
		$location = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location' );
		$lat = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lat' );
		$lng = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'wcfmmp_user_location_lng' );

		// Bail if all values are present
		if ( ! empty( $location ) && ! empty( $lat ) && ! empty( $lng ) ) { return; }

		wc_add_notice( __( 'Please select a delivery location on the map.', 'fluid-checkout' ), 'error' );
	}



	/**
	 * Whether the delivery location field is currently required.
	 */
	public function is_delivery_location_required() {
		// Bail if plugin is not active
		if ( ! class_exists( 'WCFMmp' ) ) { return false; }

		// Bail if checkout is not available
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) { return false; }

		// Bail if local pickup is selected
		if ( $this->is_local_pickup_selected() ) { return false; }

		// Get shipping fields
		$fields = WC()->checkout()->get_checkout_fields( 'shipping' );

		// Bail if delivery location field is not available
		if ( ! isset( $fields[ 'wcfmmp_user_location' ] ) ) { return false; }

		return array_key_exists( 'required', $fields[ 'wcfmmp_user_location' ] ) && true === (bool) $fields[ 'wcfmmp_user_location' ][ 'required' ];
	}

	/**
	 * Whether a local pickup shipping method is currently selected.
	 */
	public function is_local_pickup_selected() {
		// Prefer PRO local pickup detection when available
		if ( class_exists( 'FluidCheckout_PRO_CheckoutLocalPickup' ) && method_exists( FluidCheckout_PRO_CheckoutLocalPickup::instance(), 'is_shipping_method_local_pickup_selected' ) ) {
			return FluidCheckout_PRO_CheckoutLocalPickup::instance()->is_shipping_method_local_pickup_selected();
		}

		// Bail if session is not available
		if ( ! function_exists( 'WC' ) || ! WC()->session ) { return false; }

		// Get chosen shipping methods
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );

		// Bail if chosen shipping methods are not available
		if ( empty( $chosen_methods ) || ! is_array( $chosen_methods ) ) { return false; }

		// All chosen methods must be local pickup
		$has_chosen_method = false;
		foreach ( $chosen_methods as $method_id ) {
			// Skip empty method ids
			if ( empty( $method_id ) ) { continue; }

			$has_chosen_method = true;

			// Bail if any chosen method is not local pickup
			if ( ! is_string( $method_id ) || 0 !== strpos( $method_id, 'local_pickup' ) ) {
				return false;
			}
		}

		return $has_chosen_method;
	}

}

FluidCheckout_WCFMMultiVendorMarketplace::instance();
