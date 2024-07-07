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
	 * Session field name.
	 */
	public const SESSION_FIELD_NAME = 'carrier-agent';


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
		// Postcode search section
		add_filter( 'woo_carrier_agents_search_output', array( $this, 'maybe_change_postcode_search_placement' ), 10 );

		// Fetch shipping method IDs from the plugin
		add_action( 'init', array( $this, 'fetch_button_option_values' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Maybe set step as incomplete
		add_filter( 'fc_is_step_complete_shipping', array( $this, 'maybe_set_step_incomplete_shipping' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );
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

		$shipping_methods = $class_object->get_carrier_titles();

		// Bail if not array
		if ( ! is_array( $shipping_methods ) ) { return; }

		// Turn 2 dimensional array into 1 dimensional with keys as values
		array_walk_recursive( $shipping_methods, function( $value, $key ) {
			$this->carrier_agent_ids[] = $key;
		} );
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
		if ( isset( $hooks['woocommerce_checkout_order_review'] ) ) {
			unset( $hooks['woocommerce_checkout_order_review'] );
		}
		
		// Add postcode search to the shipping methods section if it's not already there
		if ( ! in_array( 'fc_shipping_methods_after_packages_inside', $hooks ) ) {
			// Target hook name and priority
			$hooks['fc_shipping_methods_after_packages_inside'] = 10;
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
		// Field key used for carrier agent data
		$field_key = 'carrier-agents-data';

		// Bail if field value was not posted
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( $field_key, $posted_data[ $field_key ] );
		WC()->session->set( self::SESSION_FIELD_NAME, $posted_data[ self::SESSION_FIELD_NAME ] );

		// Return unchanged posted data
		return $posted_data;
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
	 * Set the shipping step as incomplete when shipping method is Hungarian Pickup Points and no pickup point is selected.
	 *
	 * @param   bool  $is_step_complete  Whether the step is complete or not.
	 */
	public function maybe_set_step_incomplete_shipping( $is_step_complete ) {
		// Bail if step is already incomplete
		if ( ! $is_step_complete ) { return $is_step_complete; }
		
		// Bail if Woo Carrier Agent is not selected as a shipping method
		if ( ! $this->is_shipping_method_carrier_agent_selected() ) { return $is_step_complete; }

		// Get pickup location of the selected carrier agent
		$selected_pickup_location = WC()->session->get( 'carrier-agent' );

		// Maybe set step as incomplete if a carrier agent is not yet selected
		if ( ! $selected_pickup_location ) {
			$is_step_complete = false;
		}

		return $is_step_complete;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get currently selected shipping methods
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		// Bail if there are no shipping methods selected
		if ( empty( $chosen_shipping_methods ) ) { return $review_text_lines; }

		// Check whether target shipping method is selected
		$has_target_shipping_method = false;
		foreach ( $chosen_shipping_methods as $shipping_method_id ) {
			// Check if currently chosen shipping method is a Woo Carrier Agent
			$matching_carrier_agent_id = $this->get_matching_woo_carrier_agent_id( $shipping_method_id );

			// Break if a Woo Carrier Agent shipping method matches the selected shipping method
			if ( $matching_carrier_agent_id ) {
				$has_target_shipping_method = true;
				break;
			}
		}

		// Bail if target shipping method is not selected
		if ( ! $has_target_shipping_method ) { return $review_text_lines; }

		// Get selected carrier agent
		$data = WC()->session->get( 'carrier-agents-data' );
		$selected_agent = WC()->session->get( self::SESSION_FIELD_NAME );

		// Bail if there is no selected carrier agent
		if ( empty( $selected_agent ) ) { return $review_text_lines; }

		// Get IDs
		$carrier_instance_id = key( $selected_agent );
		$agent_id = $selected_agent[ $carrier_instance_id ];

		// Generate array key to get selected carrier agent name
		$array_key = sprintf( '%s][%s][title', $carrier_instance_id, $agent_id );

		// Get carrier agent name
		$agent_name = '';
		if ( isset( $data[ $array_key ] ) ) {
			$agent_name = sanitize_text_field( $data[ $array_key ] );
		}

		// Add carrier agent name as review text line if not empty
		if ( ! empty( $agent_name ) ) {
			$review_text_lines[] = $agent_name;
		}

		return $review_text_lines;
	}

}

FluidCheckout_WooCarrierAgents::instance();
