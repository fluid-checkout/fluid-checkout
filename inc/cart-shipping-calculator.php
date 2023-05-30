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
		$apply_changes = false;
		
		// Get address keys
		$calc_address_field_keys = $this->get_calc_shipping_address_field_post_keys();

		// Persist new address values
		foreach( $calc_address_field_keys as $calc_field_key ) {
			// Get related field keys
			$field_key = str_replace( 'calc_', '', $calc_field_key );

			// Get new value
			$new_field_value = '';
			if ( array_key_exists( $calc_field_key, $_POST ) ) {
				$new_field_value = wc_clean( wp_unslash( $_POST[ $calc_field_key ] ) );
			}

			// Update field values
			WC()->session->set( FluidCheckout_Steps::SESSION_PREFIX . $field_key, $new_field_value );
			WC()->customer->__set( $field_key, $new_field_value );

			$apply_changes = true;
		}

		// Save/commit changes to the customer object
		if ( $apply_changes ) {
			WC()->customer->set_calculated_shipping( true );
			WC()->customer->save();
		}
	}

}

FluidCheckout_CartShippingCalculator::instance();
