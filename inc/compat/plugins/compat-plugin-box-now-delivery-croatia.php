<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: BOX NOW Delivery Croatia (by BOX NOW).
 */
class FluidCheckout_BoxNowDeliveryCroatia extends FluidCheckout {

	/**
	 * The shipping method id.
	 */
	public const SHIPPING_METHOD_ID = 'box_now_delivery';

	/**
	 * Session field name for the selected pickup location.
	 */
	public const SESSION_FIELD_NAME = 'box_now_selected_locker';



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
		// Move shipping method hooks
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_pickup_point_selection_ui' ), 10 );

		// Persisted data
		add_action( 'fc_set_parsed_posted_data', array( $this, 'maybe_set_terminals_field_session_values' ), 10 );

		// Add substep review text lines
		add_filter( 'fc_substep_shipping_method_text_lines', array( $this, 'add_substep_text_lines_shipping_method' ), 10 );

		// Output hidden fields
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_hidden_fields' ), 10 );

		// Maybe set substep as incomplete
		add_filter( 'fc_is_substep_complete_shipping', array( $this, 'maybe_set_substep_incomplete_shipping' ), 10 );

		// Save terminal data to order meta
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_terminal_data_to_order_meta' ), 10 );
	}



	/**
	 * Check whether the shipping method ID is BOX NOW.
	 * 
	 * @param  string  $shipping_method_id  The shipping method ID.
	 */
	public function is_shipping_method_box_now( $shipping_method_id ) {
		return 0 === strpos( $shipping_method_id, self::SHIPPING_METHOD_ID );
	}



	/**
	 * Check whether target shipping method is selected.
	 */
	public function is_shipping_method_selected() {
		$is_selected = false;

		// Check chosen shipping method
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			// Check if target shipping method is selected
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			if ( $chosen_method && $this->is_shipping_method_box_now( $chosen_method ) ) {
				$is_selected = true;
				break;
			}
		}

		return $is_selected;
	}



	/**
	 * Output the pickup point selection UI from the plugin.
	 */
	public function output_pickup_point_selection_ui() {
		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get button label from plugin settings 
		// Use 'fluid-checkout' text domain since the plugin doesn't support translations
		$button_label = FluidCheckout_Settings::instance()->get_option( 'boxnow_button_text', __( 'Pick a Locker', 'fluid-checkout' ) );

		// Get the pickup point selection UI
		$html = '<button type="button" id="box_now_delivery_button" style="display:none;">' . esc_html( $button_label ) . '</button>';

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// If local pickup feature is disabled, output selected terminal data
		// The HTML is copied from 'box-now-delivery-croatia/js/box-now-delivery.js'
		if ( ! empty( $terminal_data ) && ! empty( $terminal_data[ 'address_1' ] ) ) {
			$html .= '<div id="box_now_selected_locker_details">';
			$html .= '<div style="font-family: Arial, sans-serif; margin-top: 10px;">';
			$html .= '<p style="margin-bottom: 10px; color: rgb(132 195 62);"><b>Izabrani paketomat</b></p>';
			$html .= '<p style="margin-bottom: 5px; font-size: 14px;"><b>Ime paketomata:</b> ' . esc_html( $terminal_data[ 'company' ] ) . '</p>';
			$html .= '<p style="margin-bottom: 5px; font-size: 14px;"><b>Adresa paketomata:</b> ' . esc_html( $terminal_data[ 'address_1' ] ) . '</p>';
			$html .= '<p style="margin-bottom: 5px; font-size: 14px;"><b>Poštanski broj:</b> ' . esc_html( $terminal_data[ 'postcode' ] ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
		}

		// Output the pickup point selection UI
		echo $html;
	}



	/**
	 * Get the selected terminal data.
	 */
	public function get_selected_terminal_data() {
		// Get session field value
		$terminal_data = WC()->session->get( self::SESSION_FIELD_NAME );

		// Bail if terminal data is not available
		if ( empty( $terminal_data ) ) { return; }

		// Decode terminal data
		$terminal_data = json_decode( $terminal_data, true );

		// Assign terminal object property values to the corresponding array keys
		$selected_terminal_data = array(
			'company' => isset( $terminal_data[ 'boxnowLockerName' ] ) ? esc_html( $terminal_data[ 'boxnowLockerName' ] ) : '',
			'address_1' => isset( $terminal_data[ 'boxnowLockerAddressLine1' ] ) ? $terminal_data[ 'boxnowLockerAddressLine1' ] : '',
			'postcode' => isset( $terminal_data[ 'boxnowLockerPostalCode' ] ) ? esc_html( $terminal_data[ 'boxnowLockerPostalCode' ] ) : '',
			// The plugin sets the city as the second address line so we use it as the city
			'city' => isset( $terminal_data[ 'boxnowLockerAddressLine2' ] ) ? esc_html( $terminal_data[ 'boxnowLockerAddressLine2' ] ) : '',
			// Set the country explicitly since the plugin only supports Croatia
			'country' => 'HR',
		);

		return $selected_terminal_data;
	}



	/**
	 * Maybe set session data for the terminals field.
	 *
	 * @param  array  $posted_data   Post data for all checkout fields.
	 */
	public function maybe_set_terminals_field_session_values( $posted_data ) {
		// Bail if field value was not posted
		if ( ! array_key_exists( self::SESSION_FIELD_NAME, $posted_data ) ) { return $posted_data; }

		// Save field value to session, as it is needed for the plugin to recover its value
		WC()->session->set( self::SESSION_FIELD_NAME, $posted_data[ self::SESSION_FIELD_NAME ] );

		// Return unchanged posted data
		return $posted_data;
	}



	/**
	 * Add the shipping methods substep review text lines.
	 * 
	 * @param  array  $review_text_lines  The list of lines to show in the substep review text.
	 */
	public function add_substep_text_lines_shipping_method( $review_text_lines = array() ) {
		// Bail if not an array
		if ( ! is_array( $review_text_lines ) ) { return $review_text_lines; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Bail if there is no selected terminal
		if ( empty( $terminal_data ) ) { return $review_text_lines; }

		// Format data
		$formatted_address = WC()->countries->get_formatted_address( $terminal_data );

		// Add formatted address to the review text lines
		$review_text_lines[] = $formatted_address;

		return $review_text_lines;
	}



	/**
	 * Output the custom hidden fields.
	 */
	public function output_custom_hidden_fields( $checkout ) {
		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get selected terminal
		$selected_terminal = WC()->session->get( self::SESSION_FIELD_NAME );

		// Output custom hidden fields
		echo '<div id="box_now-custom_checkout_fields" class="form-row fc-no-validation-icon">';
		echo '<div class="woocommerce-input-wrapper">';
		echo '<input type="hidden" id="box_now-terminal" name="box_now-terminal" value="'. esc_attr( $selected_terminal ) .'" class="validate-box-now">';
		echo '</div>';
		echo '</div>';
	}



	/**
	 * Set the shipping substep as incomplete when no pickup point is selected for the target shipping method.
	 *
	 * @param   bool  $is_substep_complete  Whether the substep is complete or not.
	 */
	public function maybe_set_substep_incomplete_shipping( $is_substep_complete ) {
		// Bail if target shipping method is not selected
		if ( ! $this->is_shipping_method_selected() ) { return; }

		// Get selected terminal data
		$terminal_data = $this->get_selected_terminal_data();

		// Maybe set step as incomplete if terminal data is not set
		if ( empty( $terminal_data ) ) {
			$is_substep_complete = false;
		}

		return $is_substep_complete;
	}



	/**
	 * Save the terminal data to the order meta.
	 * This needs to be done since the plugin only saves the terminal ID.
	 *
	 * @param  int  $order_id  The order ID.
	 */
	public function save_terminal_data_to_order_meta( $order_id ) {
		// Initialize variables
		$field_key = 'box_now-terminal';

		// Get terminal data
		$terminal_data = '';
		if ( isset( $_POST[ $field_key ] ) ) {
			$terminal_data = sanitize_text_field( $_POST[ $field_key ] );
		}

		// Bail if terminal data is empty
		if ( empty( $terminal_data ) ) { return; }

		// Get the order object
		$order = wc_get_order( $order_id );

		// Bail if order was not found
		if ( ! $order ) { return; }

		// Get shipping methods from order
		$shipping_methods = $order->get_shipping_methods();

		// Iterate shipping methods used for the order
		foreach ( $shipping_methods as $method ) {
			// Check whether target shipping method 
			if ( $this->is_shipping_method_box_now( $method->get_method_id() ) ) {
				// Save terminal data to order meta
				// Use session field name as meta key
				$order->update_meta_data( $field_key, $terminal_data );

				// Save order
				$order->save();
				break;
			}
		}
	}

}

FluidCheckout_BoxNowDeliveryCroatia::instance();
