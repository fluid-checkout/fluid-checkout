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
				$new_field_value = wc_clean( wp_unslash( $_POST[ $calc_field_key ] ) );

				// Add to changed values
				$changed_values[ $field_key ] = $new_field_value;
			}
		}

		// Maybe apply changes
		if ( is_array( $changed_values ) && count( $changed_values ) > 0 ) {
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
