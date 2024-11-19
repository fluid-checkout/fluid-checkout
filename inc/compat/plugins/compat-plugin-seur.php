<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: SEUR Oficial (by SEUR Oficial).
 */
class FluidCheckout_Seur extends FluidCheckout {

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
		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );
		add_action( 'wp', array( $this, 'maybe_set_terminals_field_from_session_to_postdata' ), 20 );

		// Shipping methods hooks
		add_filter( 'fc_shipping_method_option_markup', array( $this, 'change_shipping_method_options_markup_set_selected_value' ), 100, 5 );
		add_action( 'woocommerce_shipping_init', array( $this, 'shipping_methods_hooks' ), 100 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );
		add_filter( 'fc_is_substep_complete_shipping_address', array( $this, 'maybe_set_substep_incomplete_shipping_address' ), 10 );
		add_filter( 'fc_is_substep_complete_billing_address', array( $this, 'maybe_set_substep_incomplete_billing_address' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Phone field
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_set_mobile_phone_field_type' ), 300 );
	}

	/**
	 * Add or remove shipping method hooks.
	 */
	public function shipping_methods_hooks() {
		// Select2 fields
		remove_action( 'wp_footer', 'seur_add_map_type_select2', 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		$checkout_script_deps = 'yes' === FluidCheckout_Settings::instance()->get_option( 'fc_use_enhanced_select_components' ) ? array( 'jquery', 'selectWoo', 'fc-enhanced-select' ) : array( 'jquery', 'selectWoo' );
		wp_register_script( 'fc-checkout-seur', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/seur/checkout-seur' ), $checkout_script_deps, NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-seur', 'window.addEventListener("load",function(){CheckoutSeur.init();})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-seur', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/seur/checkout-validation-seur' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-seur', 'window.addEventListener("load",function(){CheckoutValidationSeur.init(fcSettings.checkoutValidationSeur);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-seur' );
		wp_enqueue_script( 'fc-checkout-validation-seur' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not at checkout
		if( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationSeur' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		$field_key = 'seur_pickup';

		// Bail if field value was not posted
		if ( ! array_key_exists( $field_key, $posted_data ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( $field_key, $posted_data[ $field_key ] );

		// Return unchanged posted data
		return $posted_data;
	}

	/**
	 * Maybe set `$_POST` data for the terminals field.
	 */
	public function maybe_set_terminals_field_from_session_to_postdata() {
		// Bail if not checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Bail if doing ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { return; }

		// Bail if post data is already set
		if ( array_key_exists( 'post_data', $_POST ) ) { return; }

		// Get location id
		$location_id_session = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session( 'seur_pickup' );

		// Maybe set value to `$_POST` data
		if ( ! empty( $location_id_session ) ) {
			$post_data = array(
				'seur_pickup' => $location_id_session,
				'shipping_country' => WC()->checkout->get_value( 'shipping_country' ),
				'shipping_city' => WC()->checkout->get_value( 'shipping_city' ),
				'shipping_postcode' => WC()->checkout->get_value( 'shipping_postcode' ),
			);

			$_POST[ 'post_data' ] = http_build_query( $post_data );
		}
	}


	/**
	 * Get whether the shipping method is a local pickup method from this plugin.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 * @param  object  $method              The shipping method object.
	 * @param  object  $order               The order object.
	 */
	public function is_shipping_method_local_pickup( $shipping_method_id, $method = null, $order = null ) {
		// Get variables
		$custom_name_seur_2shop = get_option( 'seur_2shop_custom_name_field' );
		$custom_name_classic_2shop = get_option( 'seur_classic_int_2shop_custom_name_field' );

		// Get default values if custom names are not set
		if ( empty( $custom_name_seur_2shop ) ) { $custom_name_seur_2shop = 'SEUR 2SHOP'; }
		if ( empty( $custom_name_classic_2shop ) ) { $custom_name_classic_2shop = 'SEUR CLASSIC 2SHOP'; }

		// Maybe set as local pickup shipping method
		if ( is_object( $method ) && ( $method->label === $custom_name_seur_2shop || $method->label === $custom_name_classic_2shop ) ) {
			return true;
		}

		return false;
	}



	/**
	 * Change the shipping method options markup to set the selected value
	 * at the component initialization, rather than after adding the component to the DOM.
	 */
	public function change_shipping_method_options_markup_set_selected_value( $markup, $method, $package_index, $chosen_method, $first ) {
		// Bail if not local pickup shipping method
		if ( ! $this->is_shipping_method_local_pickup( $method->get_method_id(), $method ) ) { return $markup; }

		// Get location id
		$location_id = WC()->checkout->get_value( 'seur_pickup' );

		// Change script in the markup
		// to set location id option as selected
		$replace = "html += '<option value=\"' + (a + 1) + '\">' + (this.o.locations[a].title || ('#' + (a + 1))) + '</option>';";
		$with = "html += '<option value=\"' + (a + 1) + '\" ' + ( (a + 1) == '" . esc_html( $location_id ) . "' ? 'selected=\"selected\"' : '' ) + '>' + (this.o.locations[a].title || ('#' + (a + 1))) + '</option>';";
		$markup = str_replace( $replace, $with, $markup );

		return $markup;
	}



	/**
	 * Set the shipping method substep as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_method( $is_substep_complete ) {
		// Bail if substep is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Get shipping packages
		$packages = WC()->shipping()->get_packages();

		// Iterate shipping packages
		foreach ( $packages as $i => $package ) {
			// Get selected shipping method
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;

			// Skip if no shipping method selected for the package
			if ( empty( $method ) ) { continue; }

			// Skip if not local pickup shipping method
			if ( ! $this->is_shipping_method_local_pickup( $chosen_method, $method ) ) { continue; }

			// Get location id
			$location_id = WC()->checkout->get_value( 'seur_pickup' );

			// Maybe set substep as incomplete
			if ( empty( $location_id ) || 'all' === $location_id ) {
				$is_substep_complete = false;
				break;
			}
		}

		return $is_substep_complete;
	}

	/**
	 * Maybe set the shipping step as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping_address( $is_substep_complete ) {
		// Get fields
		$mobile_phone_field_key = 'shipping_mobile_phone';
		$checkout_fields = WC()->checkout->get_checkout_fields( 'shipping' );

		// Bail if mobile phone field is not set
		if ( ! array_key_exists( $mobile_phone_field_key, $checkout_fields ) ) { return $is_substep_complete; }

		// Check if mobile phone field is required
		$mobile_phone_field = $checkout_fields[ $mobile_phone_field_key ];
		$is_mobile_phone_field_required = array_key_exists( 'required', $mobile_phone_field ) && $mobile_phone_field[ 'required' ];

		// Bail if mobile phone field is not required
		if ( ! $is_mobile_phone_field_required ) { return $is_substep_complete; }

		// Get mobile phone field value
		$mobile_phone = WC()->checkout->get_value( $mobile_phone_field_key );

		// Maybe set step as incomplete
		if ( empty( $mobile_phone ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}

	/**
	 * Maybe set the billing address substep step as incomplete.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_billing_address( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }

		// Get fields
		$mobile_phone_field_key = 'billing_mobile_phone';
		$checkout_fields = WC()->checkout->get_checkout_fields( 'billing' );

		// Bail if mobile phone field is not set
		if ( ! array_key_exists( $mobile_phone_field_key, $checkout_fields ) ) { return $is_substep_complete; }

		// Check if mobile phone field is required
		$mobile_phone_field = $checkout_fields[ $mobile_phone_field_key ];
		$is_mobile_phone_field_required = array_key_exists( 'required', $mobile_phone_field ) && $mobile_phone_field[ 'required' ];

		// Bail if mobile phone field is not required
		if ( ! $is_mobile_phone_field_required ) { return $is_substep_complete; }

		// Get mobile phone field value
		$mobile_phone = WC()->checkout->get_value( $mobile_phone_field_key );

		// Maybe set step as incomplete
		if ( empty( $mobile_phone ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if SEUR function not available
		if ( ! function_exists( 'seur_get_local_pickups' ) ) { return $review_text_lines; }

		// Get shipping packages
		$packages = WC()->shipping()->get_packages();

		// Check whether target shipping method is selected
		// Iterate shipping packages
		$has_target_shipping_method = false;
		foreach ( $packages as $i => $package ) {
			// Get selected shipping method
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$method = $available_methods && array_key_exists( $chosen_method, $available_methods ) ? $available_methods[ $chosen_method ] : null;

			// Check whether the shipping method is a local pickup method from this plugin
			if ( $this->is_shipping_method_local_pickup( $chosen_method, $method ) ) {
				$has_target_shipping_method = true;
				break;
			}
		}

		// Bail if target shipping method is not selected
		if ( ! $has_target_shipping_method ) { return $review_text_lines; }

		// Get location id
		$location_id = WC()->checkout->get_value( 'seur_pickup' );

		// Bail if no location has been selected
		if ( empty( $location_id ) || 'all' === $location_id ) { return $review_text_lines; }

		// Get shipping country, or fallback to billing country
		$country = WC()->checkout->get_value( 'shipping_country' );
		if ( empty( $country ) ) {
			$country = WC()->checkout->get_value( 'billing_country' );
		}

		// Get shipping city, or fallback to billing city
		$city = WC()->checkout->get_value( 'shipping_city' );
		if ( empty( $city ) ) {
			$city = WC()->checkout->get_value( 'billing_city' );
		}

		// Get shipping postcode, or fallback to billing postcode
		$postcode = WC()->checkout->get_value( 'shipping_postcode' );
		if ( empty( $postcode ) ) {
			$postcode = WC()->checkout->get_value( 'billing_postcode' );
		}

		// Get available local pickup locations
		$pickup_locations = seur_get_local_pickups( $country, $city, $postcode );

		// Get selected location data
		// Location id and array index do not match, so we need to subtract 1
		// to get the correct location data as the location id is 1-based
		// and the pickup locations array index is 0-based
		$location_index = intval( $location_id ) - 1;
		$selected_location = array_key_exists( $location_index, $pickup_locations ) ? $pickup_locations[ $location_index ] : null;

		// Maybe set add pickup point address as not selected
		// to the review text lines, then bail
		if ( empty( $selected_location ) ) {
			$review_text_lines[] = '<em>' . __( 'Pickup point not selected yet.', 'fluid-checkout' ) . '</em>';
			return $review_text_lines;
		}

		// Add terminal name as review text line
		$review_text_lines[] = '<strong>' . __( 'Pickup point:', 'fluid-checkout' ) . '</strong>';

		// Get address data
		$address_data = array(
			'company'     => $selected_location[ 'company' ],
			'address_1'   => $selected_location[ 'address' ] . ' ' . $selected_location[ 'numvia' ],
			'city'        => $selected_location[ 'city' ],
			'postcode'    => $selected_location[ 'post_code' ],
		);

		// Add formatted address
		$formatted_address = WC()->countries->get_formatted_address( $address_data );
		$review_text_lines[] = $formatted_address;

		return $review_text_lines;
	}



	/**
	 * Maybe set mobile phone field added by this plugin as type `tel`.
	 * 
	 * @param  array  $field_groups   The checkout field groups.
	 */
	public function maybe_set_mobile_phone_field_type( $field_groups ) {
		// Maybe set billing mobile phone field as type `tel`
		if ( array_key_exists( 'billing', $field_groups ) && array_key_exists( 'billing_mobile_phone', $field_groups[ 'billing' ] ) ) {
			$field_groups[ 'billing' ][ 'billing_mobile_phone' ][ 'type' ] = 'tel';
		}

		// Maybe set shipping mobile phone field as type `tel`
		if ( array_key_exists( 'shipping', $field_groups ) && array_key_exists( 'shipping_mobile_phone', $field_groups[ 'shipping' ] ) ) {
			$field_groups[ 'shipping' ][ 'shipping_mobile_phone' ][ 'type' ] = 'tel';
		}

		return $field_groups;
	}

}

FluidCheckout_Seur::instance();
