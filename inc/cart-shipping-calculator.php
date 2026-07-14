<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage shipping address between the cart shipping calculator and the checkout page.
 */
class FluidCheckout_CartShippingCalculator extends FluidCheckout {

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
		// Cart shipping calculator
		add_action( 'woocommerce_calculated_shipping', array( $this, 'set_new_address_data_from_shipping_calculator' ), 10 );
	}



	/**
	 * Undo hooks.
	 */
	public function undo_hooks() {
		// Cart shipping calculator
		remove_action( 'woocommerce_calculated_shipping', array( $this, 'set_new_address_data_from_shipping_calculator' ), 10 );
	}



	/**
	 * Get list of shipping address field keys used in the shipping calculator.
	 */
	public function get_calc_shipping_address_field_post_keys() {
		return array(
			'calc_shipping_country',
			'calc_shipping_state',
			'calc_shipping_city',
			'calc_shipping_postcode',
		);
	}



	/**
	 * Clear shipping address fields that are not present in the shipping calculator.
	 * Prevents leftover street/name data when updating destination from the calculator after a full address was set.
	 */
	public function clear_shipping_address_fields_not_in_calculator() {
		$customer = WC()->customer;

		// Bail if customer object is not available
		if ( ! $customer ) { return; }

		// Hardcoded list — avoid reading customer getters here (Address Book may cache previous saved-entry values in the same request)
		$field_keys_to_clear = array(
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_phone',
		);

		$customer_id = $customer->get_id();
		$can_set_session = method_exists( FluidCheckout_Steps::instance(), 'set_checkout_field_value_to_session' );

		foreach ( $field_keys_to_clear as $field_key ) {
			$setter = "set_$field_key";

			// Clear customer property
			if ( is_callable( array( $customer, $setter ) ) ) {
				$customer->{$setter}( '' );
			}

			// Persist empty to user meta for logged-in customers.
			// WC_Customer_Data_Store_Session skips empty session values on the next request and reloads user meta — leftover streets would otherwise return.
			if ( $customer_id ) {
				update_user_meta( $customer_id, $field_key, '' );
			}

			// Clear checkout session value
			if ( $can_set_session ) {
				FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $field_key, '' );
			}
		}
	}



	/**
	 * Set the customer new shipping address with the values set in the shipping calculator.
	 */
	public function set_new_address_data_from_shipping_calculator() {
		// Initialize variables
		$changed_values = array();
		
		// Get address keys
		$calc_address_field_keys = $this->get_calc_shipping_address_field_post_keys();

		// Iterate fields and seek for changes
		foreach( $calc_address_field_keys as $calc_field_key ) {
			// Get related field keys
			$field_key = str_replace( 'calc_', '', $calc_field_key );

			// Get new value
			$new_field_value = '';
			if ( array_key_exists( $calc_field_key, $_POST ) ) {
				// Get new field value
				$new_field_value = wc_clean( wp_unslash( $_POST[ $calc_field_key ] ?? '' ) );

				// Add to changed values
				$changed_values[ $field_key ] = $new_field_value;
			}
		}

		// Maybe apply changes
		if ( is_array( $changed_values ) && count( $changed_values ) > 0 ) {
			// Only clear leftover street/name when entering a new Address Book calculator address.
			// Regular PRO calculator updates should keep previously filled shipping lines (e.g. after "Same as billing").
			$address_source = array_key_exists( 'shipping_address_source', $_POST ) ? wc_clean( wp_unslash( $_POST[ 'shipping_address_source' ] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( 'new' === $address_source ) {
				$this->clear_shipping_address_fields_not_in_calculator();
			}

			// Iterate changed values and apply changes to the customer data and checkout session
			foreach ( $changed_values as $field_key => $new_field_value ) {
				// Update field values
				WC()->session->set( FluidCheckout_Steps::SESSION_PREFIX . $field_key, $new_field_value );

				// Get the setter method name for the customer property
				$setter = "set_$field_key";

				// Check if the setter method is supported
				if ( is_callable( array( WC()->customer, $setter ) ) ) {
					// Set property value to the customer object using its setter method
					WC()->customer->{$setter}( $new_field_value );
				}
				else {
					// Set property value directly
					WC()->customer->__set( $field_key, $new_field_value );
				}
			}

			// Save/commit changes to the customer object
			WC()->customer->set_calculated_shipping( true );
			WC()->customer->save();
		}
	}

}

FluidCheckout_CartShippingCalculator::instance();
