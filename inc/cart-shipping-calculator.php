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

		// Get calculator shipping field keys (ie. `shipping_country`) from the `calc_shipping_*` post keys
		$calc_field_keys = array();
		foreach ( $this->get_calc_shipping_address_field_post_keys() as $calc_field_key ) {
			$calc_field_keys[] = str_replace( 'calc_', '', $calc_field_key );
		}

		// Clear the shipping fields that are copied on "same as billing" but not entered in the calculator
		$field_keys_to_clear = array_diff( FluidCheckout_Steps::instance()->get_shipping_same_billing_fields_keys(), $calc_field_keys );
		$customer_id = $customer->get_id();

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
			FluidCheckout_Steps::instance()->set_checkout_field_value_to_session( $field_key, '' );
		}
	}



	/**
	 * Set the customer new shipping address with the values set in the shipping calculator.
	 */
	public function set_new_address_data_from_shipping_calculator() {
		// "Same as billing" (PRO checkbox or Address Book) and saved Address Book entries set the shipping address
		// directly; the calculator fields must not override those destinations.
		$is_same_as_billing = array_key_exists( 'shipping_same_as_billing', $_POST ) && '1' === wc_clean( wp_unslash( $_POST[ 'shipping_same_as_billing' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$address_source = array_key_exists( 'shipping_address_source', $_POST ) ? wc_clean( wp_unslash( $_POST[ 'shipping_address_source' ] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Bail if shipping same as billing is selected
		if ( $is_same_as_billing ) { return; }

		// Bail if address source is not the shipping calculator (new address)
		if ( null !== $address_source && 'new' !== $address_source ) { return; }

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
			// Applying a calculator destination — clear same-as-billing so checkout uses these values
			FluidCheckout_Steps::instance()->set_shipping_same_as_billing_session( false );

			// Clear leftover street/name fields not present in the calculator.
			// Reached only for the `new`/plain calculator source (other sources bailed above).
			$this->clear_shipping_address_fields_not_in_calculator();

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
