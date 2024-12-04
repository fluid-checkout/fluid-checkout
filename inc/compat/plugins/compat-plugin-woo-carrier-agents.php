<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Carrier Agents (by Markup.fi).
 */
class FluidCheckout_WooCarrierAgents extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'Woo_Carrier_Agents';

	/**
	 * Session field name for the selected pickup location.
	 */
	public const SESSION_FIELD_NAME = 'carrier-agent';

	/**
	 * Session field name for the carrier agent data.
	 */
	public const SESSION_FIELD_NAME_DATA = 'carrier-agents-data';



	/**
	 *	Carrier agent IDs.
	 *
	 * @var array
	 */
	public $carrier_agent_ids = array();



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

		// Output hidden fields
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Postcode search section
		add_filter( 'woo_carrier_agents_search_output', array( $this, 'maybe_change_postcode_search_placement' ), 10 );

		// Fetch shipping method IDs from the plugin
		add_action( 'init', array( $this, 'fetch_button_option_values' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping', array( $this, 'maybe_set_substep_incomplete_shipping' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Checkout validation settings
		add_filter( 'fc_checkout_validation_script_settings', array( $this, 'change_js_settings_checkout_validation' ), 10 );
	}



	/**
	 * Register assets.
	 */
	public function register_assets() {
		// Checkout scripts
		wp_register_script( 'fc-checkout-woo-carrier-agents', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-carrier-agents/checkout-woo-carrier-agents' ), array( 'jquery', 'fc-utils' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-woo-carrier-agents', 'window.addEventListener("load",function(){CheckoutWooCarrierAgents.init(fcSettings.checkoutWooCarrierAgents);})' );

		// Add validation script
		wp_register_script( 'fc-checkout-validation-woo-carrier-agents', FluidCheckout_Enqueue::instance()->get_script_url( 'js/compat/plugins/woo-carrier-agents/checkout-validation-woo-carrier-agents' ), array( 'jquery', 'fc-utils', 'fc-checkout-validation' ), NULL, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script( 'fc-checkout-validation-woo-carrier-agents', 'window.addEventListener("load",function(){CheckoutValidationWooCarrierAgents.init(fcSettings.checkoutValidationWooCarrierAgents);})' );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_assets() {
		// Scripts
		wp_enqueue_script( 'fc-checkout-woo-carrier-agents' );
		wp_enqueue_script( 'fc-checkout-validation-woo-carrier-agents' );
	}

	/**
	 * Maybe enqueue assets.
	 */
	public function maybe_enqueue_assets() {
		// Bail if not on checkout page.
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return; }

		$this->enqueue_assets();
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Add validation settings
		$settings[ 'checkoutValidationWooCarrierAgents' ] = array(
			'validationMessages'  => array(
				'pickup_point_not_selected' => __( 'Selecting a pickup point is required before proceeding.', 'fluid-checkout' ),
			),
		);

		return $settings;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_carrier_agent_selected() ) { return; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Get agent (shipping method) ID
		$agent_id = $this->get_numeric_carrier_agent_id();

		// Maybe get selected terminal ID
		$selected_terminal_id = '';
		if ( is_array( $selected_terminal ) && ! empty( $selected_terminal[ $agent_id ] ) ) {
			// Get the terminal ID
			$selected_terminal_id = $selected_terminal[ $agent_id ];
		}

		// Get previously entered postcode
		$entered_postcode = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( 'woo-carrier-agents-postcode' );

		// Output custom hidden fields
		echo '<div id="woo_carrier_agents-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="woo_carrier_agents-terminal_id" name="woo_carrier_agents-terminal_id" value="'. esc_attr( $selected_terminal_id ) .'" class="validate-woo-carrier-agents">';
		echo '<input type="hidden" id="woo_carrier_agents-entered_postcode" name="woo_carrier_agents-entered_postcode" value="'. esc_attr( $entered_postcode ) .'">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Maybe change selected terminal ID to the cookie value.
	 *
	 * @param  array  $selected_terminal  The selected terminal.
	 */
	public function maybe_change_terminal_id_to_cookie( $selected_terminal ) {
		// Get agent (shipping method) ID
		$agent_id = $this->get_numeric_carrier_agent_id();

		// Bail if agent ID or selected terminal ID are not available
		if ( ! $agent_id || empty( $selected_terminal[ $agent_id ] ) ) { return $selected_terminal; }

		// Construct cookie name
		$cookie_name = 'woo_carrier_agent_' . $agent_id . '_value';

		// Bail if cookie is not set
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) { return $selected_terminal; }

		// Get cookie value
		$cookie_value = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( $_COOKIE[ $cookie_name ] ) : '';

		// Bail if cookie value is empty
		if ( empty( $cookie_value ) ) { return $selected_terminal; }

		// Change selected terminal ID to the cookie value
		$selected_terminal[ $agent_id ] = $cookie_value;

		return $selected_terminal;
	}



	/**
	 * Fetch shipping method IDs from the plugin.
	 */
	public function fetch_button_option_values() {
		// Bail if class is not available
		if ( ! class_exists( self::CLASS_NAME ) ) { return; }

		// Get object
		$class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( self::CLASS_NAME );

		// Bail if class method is not available
		if ( ! method_exists( $class_object, 'get_carrier_titles' ) ) { return; }

		// Get shipping method titles from the plugin
		$shipping_methods = $class_object->get_carrier_titles();

		// Bail if not array
		if ( ! is_array( $shipping_methods ) ) { return; }

		// Turn 2 dimensional array into 1 dimensional with keys as values
		$this->carrier_agent_ids = array_keys( $shipping_methods );
	}



	/**
	 * Maybe change postcode search placement to inside the shipping methods.
	 * 
	 * @param   array  $hooks  Action hooks for the postcode search.
	 */
	public function maybe_change_postcode_search_placement( $hooks ) {
		// `$hooks` needs to be an array
		if ( ! is_array( $hooks ) ) { $hooks = array(); }

		// Remove postcode search from the order summary section if it exists
		if ( isset( $hooks[ 'woocommerce_checkout_order_review' ] ) ) {
			unset( $hooks[ 'woocommerce_checkout_order_review' ] );
		}
		
		// Add postcode search to the shipping methods section if it's not already there
		if ( ! in_array( 'fc_shipping_methods_after_packages_inside', $hooks ) ) {
			// Target hook name and priority
			$hooks[ 'fc_shipping_methods_after_packages_inside' ] = 10;
		}
		
		return $hooks;
	}



	/**
	 * Get Woo Carrier Agent ID if it matches the shipping method ID.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function get_matching_woo_carrier_agent_id( $shipping_method_id ) {
		// Bail if no carrier agent IDs are available
		if ( empty( $this->carrier_agent_ids ) ) { return; }

		// Check if any carrier agent IDs starts with the given shipping method ID
		foreach ( $this->carrier_agent_ids as $carrier_agent_id ) {
			if ( 0 === strpos( $shipping_method_id, $carrier_agent_id ) ) {
				return $carrier_agent_id;
			}
		}
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		// Bail if field values were not posted
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) || ! array_key_exists( self::SESSION_FIELD_NAME_DATA, $posted_data ) ) { return $posted_data; }

		// Get selected terminal from posted data
		$selected_terminal = $posted_data[ self::SESSION_FIELD_NAME ];

		// Maybe change terminal ID when using the "Select" display style for AJAX calls
		if ( wp_doing_ajax() && 'select' === FluidCheckout_Settings::instance()->get_option( 'woo_carrier_agents_display_style', 'search' ) ) { 
			// Maybe change the terminal ID to the cookie value
			$selected_terminal = $this->maybe_change_terminal_id_to_cookie( $selected_terminal );
		}

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( self::SESSION_FIELD_NAME_DATA, $posted_data[ self::SESSION_FIELD_NAME_DATA ] );
		WC()->session->set( self::SESSION_FIELD_NAME, $selected_terminal );

		// Return unchanged posted data
		return $posted_data;
	}



	/**
	 * Get currently selected number carrier agent ID.
	 */
	public function get_numeric_carrier_agent_id() {
		// Get currently selected shipping method
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if there are no shipping methods selected
		if ( empty( $chosen_shipping_methods ) ) { return; }

		// Check whether target shipping method is selected
		foreach ( $chosen_shipping_methods as $shipping_method_id ) {
			// Get the first shipping method ID available
			if ( $shipping_method_id ) {
				// Extract the numeric value of the shipping method ID
				$carrier_agent_id_parts = explode( ':', $shipping_method_id );
				$carrier_agent_id = end( $carrier_agent_id_parts );

				// Return carrier agent ID if it's not empty and is numeric
				if ( ! empty( $carrier_agent_id ) && is_numeric( $carrier_agent_id ) ) {
					return $carrier_agent_id;
				}
			}
		}
	}



	/**
	 * Check whether Woo Carrier Agent is selected as a shipping method.
	 */
	public function is_shipping_method_carrier_agent_selected() {
		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if there are no shipping methods selected
		if ( empty( $chosen_shipping_methods ) ) { return; }

		// Check whether target shipping method is selected
		foreach ( $chosen_shipping_methods as $shipping_method_id ) {
			// Check if currently chosen shipping method is a Woo Carrier Agent
			$matching_carrier_agent_id = $this->get_matching_woo_carrier_agent_id( $shipping_method_id );

			// Return true if a Woo Carrier Agent shipping method matches the selected shipping method
			if ( $matching_carrier_agent_id ) {
				return true;
			}
		}
	}



	/**
	 * Set the shipping substep as incomplete when shipping method is Woo Carrier Agents and no pickup point is selected.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping( $is_substep_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_substep_complete ) { return $is_substep_complete; }
		
		// Bail if Woo Carrier Agent is not selected as a shipping method
		if ( ! $this->is_shipping_method_carrier_agent_selected() ) { return $is_substep_complete; }

		// Get pickup location of the selected carrier agent
		$selected_pickup_location = WC()->session->get( 'carrier-agent' );

		// Maybe set step as incomplete if a carrier agent is not yet selected
		if ( ! $selected_pickup_location ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Transform terminal data into a multidimensional array.
	 *
	 * @param   array  $terminal_data  The terminal data.
	 */
	public function transform_terminal_data( $terminal_data ) {
		// Iterate through each item in the terminal data array
		foreach ( $terminal_data as $key => $value ) {
			// Split the key into parts divided by ']['
			$parts = explode( '][', $key );

			// Extract the agent ID from the first part
			$agent_id = array_shift( $parts );

			// Create a reference to the current level of the new array
			$level_reference = &$terminal_data_transformed[ $agent_id ];

			// For each part of the original key build new level of the nested array
			foreach ( $parts as $part ) {
				$level_reference = &$level_reference[ $part ];
			}
			
			// Assign the original array value to the last level of the new array
			$level_reference = $value;
		}

		return $terminal_data_transformed;
	}



	/**
	 * Get terminal data for the selected carrier agent.
	 */
	public function get_terminal_data() {
		// Get data for all fetched terminals
		$terminal_data = WC()->session->get( self::SESSION_FIELD_NAME_DATA );

		// Bail if terminal data is not available
		if ( ! is_array( $terminal_data ) || empty( $terminal_data ) ) { return; }

		// Transform terminal data into a more usable format
		$terminal_data = $this->transform_terminal_data( $terminal_data );

		return $terminal_data;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_carrier_agent_selected() ) { return $review_text_lines; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Bail if there is no selected terminal
		if ( empty( $selected_terminal ) ) { return $review_text_lines; }

		// Get terminal data
		$terminal_data = $this->get_terminal_data();

		// Bail if terminal data is not available
		if ( ! $terminal_data ) { return $review_text_lines; }

		// Get agent (shipping method) ID 
		$agent_id = $this->get_numeric_carrier_agent_id();

		// Bail if agent ID is not available
		if ( ! $agent_id ) { return $review_text_lines; }

		// Get terminal ID
		$terminal_id = '';
		if ( isset( $selected_terminal[ $agent_id ] ) ) {
			$terminal_id = $selected_terminal[ $agent_id ];
		}

		// Get terminal name
		$terminal_name = '';
		if ( isset( $terminal_data[ $agent_id ][ $terminal_id ]['title'] ) ) {
			$terminal_name = sanitize_text_field( $terminal_data[ $agent_id ][ $terminal_id ]['title'] );
		}

		// Add terminal name as review text line if not empty
		if ( ! empty( $terminal_name ) ) {
			$review_text_lines[] = $terminal_name;
		}

		// Set address data as empty array
		$address_data = array();

		// Connect address data keys with terminal data keys
		$address_data_keys = array(
			'address_1' => 'street_address',
			'city'      => 'city',
			'postcode'  => 'postcode',
			'country'   => 'country',
		);

		// Get selected terminal data
		$selected_terminal_data = array();
		if ( isset( $terminal_data[ $agent_id ][ $terminal_id ] ) ) {
			$selected_terminal_data = $terminal_data[ $agent_id ][ $terminal_id ];
		}

		// Assign terminal data to the corresponding address data keys
		foreach ( $address_data_keys as $address_key => $data_key ) {
			if ( isset( $selected_terminal_data[ $data_key ] ) ) {
				$address_data[ $address_key ] = $selected_terminal_data[ $data_key ];
			}
		}

		// Add formatted address data as review text line if not empty
		if ( ! empty( $address_data ) ) {
			$address_data = WC()->countries->get_formatted_address( $address_data );
			$review_text_lines[] = $address_data;
		}

		return $review_text_lines;
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
		$settings[ 'validateFieldsSelector' ] = 'input[name="woo_carrier_agents-terminal_id"]' . ( ! empty( $current_validate_field_selector ) ? ', ' : '' ) . $current_validate_field_selector;
		$settings[ 'referenceNodeSelector' ] = 'input[name="woo_carrier_agents-terminal_id"]' . ( ! empty( $current_reference_node_selector ) ? ', ' : '' ) . $current_reference_node_selector;
		$settings[ 'alwaysValidateFieldsSelector' ] = 'input[name="woo_carrier_agents-terminal_id"]' . ( ! empty( $current_always_validate_selector ) ? ', ' : '' ) . $current_always_validate_selector;

		return $settings;
	}

}

FluidCheckout_WooCarrierAgents::instance();
