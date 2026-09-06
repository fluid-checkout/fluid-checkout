<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Omniva shipping (by Omniva).
 */
class FluidCheckout_OmnivaWooCommerce extends FluidCheckout {

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
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 10 );

		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping_method', array( $this, 'maybe_set_substep_incomplete_shipping_method' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Shipping methods
		$this->checkout_shipping_methods_hooks();
	}

	/**
	 * Add or remove hooks for the shipping methods on the checkout page.
	 */
	public function checkout_shipping_methods_hooks() {
		// Bail if not on the checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		// Remove hooks
		remove_filter( 'woocommerce_cart_shipping_method_full_label', 'OmnivaLt_Frontend::add_logo_to_method', 10, 2 );

		// Shipping methods
		add_filter( 'fc_shipping_method_option_image_html', array( $this, 'maybe_change_shipping_method_option_image_html' ), 10, 2 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Add validation script
		wp_register_script( 'fc-checkout-validation-omniva', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/omniva-woocommerce/checkout-validation-omniva' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-omniva', 'window.addEventListener("load",function(){CheckoutValidationOmniva.init(fcSettings.checkoutValidationOmniva);});' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-validation-omniva' );
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
		$settings[ 'checkoutValidationOmniva' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}


	/**
	 * Add settings to the plugin settings JS object for the checkout validation.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_checkout_validation( $settings ) {
		// Get current values
		$current_validate_field_selector = array_key_exists( 'validateFieldsSelector', $settings ) ? $settings[ 'validateFieldsSelector' ] : '';
		$current_reference_node_selector = array_key_exists( 'referenceNodeSelector', $settings ) ? $settings[ 'referenceNodeSelector' ] : '';
		$current_always_validate_selector = array_key_exists( 'alwaysValidateFieldsSelector', $settings ) ? $settings[ 'alwaysValidateFieldsSelector' ] : '';

		// Prepend new values to existing settings
		$settings[ 'validateFieldsSelector' ] = 'select[name="omnivalt_terminal"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'select[name="omnivalt_terminal"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'select[name="omnivalt_terminal"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}



	/**
	 * Get the selected Omniva terminal id from session, posted data or cookie.
	 *
	 * @param   array|null  $posted_data  Optional posted checkout data.
	 *
	 * @return  string  Selected terminal id, or empty string when none is selected.
	 */
	public function get_selected_terminal_id( $posted_data = null ) {
		$session_field_key = 'omnivalt_terminal_id';
		$field_key = 'omnivalt_terminal';

		// Try session first
		$selected_terminal_id = WC()->session ? WC()->session->get( $session_field_key ) : null;

		// Maybe use posted field value
		if ( empty( $selected_terminal_id ) && is_array( $posted_data ) && array_key_exists( $field_key, $posted_data ) ) {
			$selected_terminal_id = $posted_data[ $field_key ];
		}

		// Maybe use Omniva cookie as fallback, same as the Omniva plugin does at checkout processing
		if ( empty( $selected_terminal_id ) && ! empty( $_COOKIE[ 'omniva_terminal' ] ) ) {
			$selected_terminal_id = wc_clean( wp_unslash( $_COOKIE[ 'omniva_terminal' ] ) );
		}

		// Ignore placeholder values
		if ( empty( $selected_terminal_id ) || 'all' === $selected_terminal_id ) {
			return '';
		}

		return $selected_terminal_id;
	}

	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		$field_key = 'omnivalt_terminal';
		$session_field_key = 'omnivalt_terminal_id';

		// Maybe recover terminal id from Omniva cookie when the select field is empty.
		// Omniva map UI can leave the select empty when the selected option is missing from the field.
		if ( ( ! array_key_exists( $field_key, $posted_data ) || empty( $posted_data[ $field_key ] ) || 'all' === $posted_data[ $field_key ] ) && ! empty( $_COOKIE[ 'omniva_terminal' ] ) ) {
			$posted_data[ $field_key ] = wc_clean( wp_unslash( $_COOKIE[ 'omniva_terminal' ] ) );
		}

		// Bail if field value was not posted
		if ( ! array_key_exists( $field_key, $posted_data ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( $session_field_key, $posted_data[ $field_key ] );

		// Return posted data, maybe with cookie value recovered
		return $posted_data;
	}



	/**
	 * Get whether the shipping method is a local pickup method from this plugin.
	 * 
	 * @param  string  $method_id   The shipping method id.
	 */
	public function is_shipping_method_local_pickup( $method_id ) {
		// Define local pickup shipping method ids
		$local_pickup_methods = array(
			'omnivalt_pt',
			'omnivalt_pn',
			'omnivalt_ps',
		);

		// Check if shipping method is local pickup, including instance ids (e.g. `omnivalt_pt:1`)
		foreach ( $local_pickup_methods as $local_pickup_method ) {
			if ( 0 === strpos( $method_id, $local_pickup_method ) ) {
				return true;
			}
		}

		// Otherwise, not a local pickup shipping method
		return false;
	}

	/**
	 * Check if the shipping method requires pickup location selection by the customer.
	 * 
	 * @param  string  $method_id   The shipping method id.
	 */
	public function shipping_method_needs_pickup_location( $method_id ) {
		// Define local pickup shipping method ids
		$local_pickup_methods = array(
			'omnivalt_pt',
			'omnivalt_ps',
		);

		// Check if shipping method is local pickup, including instance ids (e.g. `omnivalt_pt:1`)
		foreach ( $local_pickup_methods as $local_pickup_method ) {
			if ( 0 === strpos( $method_id, $local_pickup_method ) ) {
				return true;
			}
		}

		// Otherwise, not a local pickup shipping method
		return false;
	}



	/**
	 * Get the customer country from shipping, otherwise billing, otherwise base shop country.
	 */
	public function get_customer_country() {
		// Get country code
		// Try to get shipping country, then billing country, then base shop country
		$country = WC()->customer->get_shipping_country();
		if ( empty( $country ) ) { $country = WC()->customer->get_billing_country(); }
		if ( empty( $country ) ) { $country = WC()->countries->get_base_country(); }

		return $country;
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

			// Skip if no shipping method selected for the package
			if ( empty( $chosen_method ) ) { continue; }

			// Skip if not local pickup shipping method
			if ( ! $this->shipping_method_needs_pickup_location( $chosen_method ) ) { continue; }

			// Get location id
			$selected_terminal_id = $this->get_selected_terminal_id();

			// Maybe set substep as incomplete
			if ( empty( $selected_terminal_id ) ) {
				$is_substep_complete = false;
				break;
			}

			// Keep session in sync when recovered from cookie
			WC()->session->set( 'omnivalt_terminal_id', $selected_terminal_id );
		}

		return $is_substep_complete;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Maybe skip adding pickup point address as review text lines
		if ( true === apply_filters( 'fc_skip_add_pickup_point_info_as_review_text_lines', false ) ) { return $review_text_lines; }

		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if plugin classes or functions not available
		if ( ! class_exists( 'OmnivaLt_Terminals' ) ) { return $review_text_lines; }

		// Get shipping packages
		$packages = WC()->shipping()->get_packages();

		// Check whether target shipping method is selected
		// Iterate shipping packages
		$has_target_shipping_method = false;
		foreach ( $packages as $i => $package ) {
			// Get selected shipping method
			$available_methods = $package['rates'];
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';

			// Check if shipping method is local pickup
			if ( $this->is_shipping_method_local_pickup( $chosen_method ) && $this->shipping_method_needs_pickup_location( $chosen_method ) ) {
				$has_target_shipping_method = true;
				break;
			}
		}

		// Bail if target shipping method is not selected
		if ( ! $has_target_shipping_method ) { return $review_text_lines; }

		// Get location id
		$selected_terminal_id = $this->get_selected_terminal_id();

		// Maybe set add pickup point address as not selected
		// to the review text lines, then bail
		if ( empty( $selected_terminal_id ) ) {
			$review_text_lines[] = '<em>' . __( 'Pickup point not selected yet.', 'fluid-checkout' ) . '</em>';
			return $review_text_lines;
		}

		// Keep session in sync when recovered from cookie
		WC()->session->set( 'omnivalt_terminal_id', $selected_terminal_id );

		// Get terminal data, with country.
		$selected_terminal = OmnivaLt_Terminals::get_terminal_address( $selected_terminal_id, true );

		// Maybe set add pickup point address as not selected
		// to the review text lines, then bail
		if ( empty( $selected_terminal ) ) {
			$review_text_lines[] = '<em>' . __( 'Pickup point not selected yet.', 'fluid-checkout' ) . '</em>';
			return $review_text_lines;
		}

		// Add terminal name as review text line
		$review_text_lines[] = '<strong>' . __( 'Pickup point:', 'fluid-checkout' ) . '</strong>';
		$review_text_lines[] = $selected_terminal;

		return $review_text_lines;
	}



	/**
	 * Maybe change the shipping method option image HTML.
	 * 
	 * @param  string  $html     The HTML of the shipping method option image.
	 * @param  object  $method   The shipping method object.
	 */
	public function maybe_change_shipping_method_option_image_html( $html, $method ) {
		// Bail if not a local pickup shipping method from this plugin
		if ( ! $this->is_shipping_method_local_pickup( $method->id ) ) { return $html; }

		// Get Omniva settings
		$settings = OmnivaLt_Core::get_settings();
		$label_design = $settings[ 'label_design' ] ?? 'classic';

		// Bail if should not show logo
		if ( 'full' !== $label_design && 'logo' !== $label_design ) { return $html; }

		// Get method parameters
		$method_key = OmnivaLt_Omniva_Order::get_method_key_from_id( $method->id );
		$method_params = null;

		// Use `OmnivaLt_Method::get_by_key` method for current Omniva versions
		if ( method_exists( 'OmnivaLt_Method', 'get_by_key' ) ) {
			$method_params = OmnivaLt_Method::get_by_key( $method_key );
		}
		// Otherwise, fall back to the older `OmnivaLt_Helper::get_omniva_method_by_key` method
		elseif ( method_exists( 'OmnivaLt_Helper', 'get_omniva_method_by_key' ) ) {
			$method_params = OmnivaLt_Helper::get_omniva_method_by_key( $method_key );
		}

		// Bail if method parameters not found
		if ( ! $method_params || ! array_key_exists( 'title_logo', $method_params ) ) { return $html; }

		// Get image file
		$image_file = $method_params[ 'title_logo' ];

		// Get customer's country code
		$country = $this->get_customer_country();

		// Maybe get image file by country
		if ( ! empty( $country ) && array_key_exists( 'display_by_country', $method_params ) && array_key_exists( $country, $method_params[ 'display_by_country' ] ) ) {
			$image_file = $method_params[ 'display_by_country' ][ $country ][ 'title_logo' ];
		}

		// Define image HTML
		$html = '<img class="omnivalt-logo" src="' . OMNIVALT_URL . 'assets/img/logos/' . $image_file . '" alt="Omniva"/>';

		return $html;
	}

}

FluidCheckout_OmnivaWooCommerce::instance();
