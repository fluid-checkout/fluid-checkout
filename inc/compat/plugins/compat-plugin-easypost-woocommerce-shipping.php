<?php

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: ELEX EasyPost Shipping Plugin (by ELEXtensions).
 */
class FluidCheckout_EasypostWooCommerceShipping extends FluidCheckout {

	public $class_name = 'WF_Easypost';
	public $class_object = null;


	/**
	 * __construct function.
	 */
	public function __construct( ) {
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Shipping init
		add_action( 'woocommerce_shipping_init', array( $this, 'checkout_fields_hooks' ), 300 );
	}

	/**
	 * Add or remove checkout fields hooks.
	 */
	public function checkout_fields_hooks() {
		// Set class object
		$this->maybe_set_class_object();

		// Bail if class object is not set
		if ( ! $this->class_object ) { return; }

		// Checkout fields
		remove_action( 'woocommerce_checkout_fields', array( $this->class_object, 'wf_easypost_custom_override_checkout_fields' ), 10 );
		add_action( 'woocommerce_checkout_fields', array( $this, 'remove_easypost_field' ), 300 );
		add_filter( 'fc_customer_persisted_data_session_field_keys', array( $this, 'add_easypost_insurance_field_to_session_field_keys' ), 10, 2 );
		add_action( 'fc_shipping_methods_after_packages_inside', array( $this, 'output_custom_insurance_field' ), 10 );
	}



	/**
	 * Maybe set the plugin class object.
	 */
	public function maybe_set_class_object() {
		// Bail if class object is already set
		if ( $this->class_object ) { return; }

		// Bail if class is not available
		$class_name = 'WF_Easypost';
		if ( ! class_exists( $class_name ) ) { return; }

		// Get object
		$this->class_object = FluidCheckout::instance()->get_object_by_class_name_from_hooks( $class_name );
	}



	/**
	 * Remove the Easypost field.
	 */
	public function remove_easypost_field( $fields ) {
		unset( $fields[ 'billing' ][ 'easypost_insurance' ] );
		return $fields;
	}



	/**
	 * Get the Easypost insurance field from the original plugin.
	 */
	public function get_easypost_insurance_field() {
		// Set class object
		$this->maybe_set_class_object();

		// Bail if class object is not set
		if ( ! $this->class_object ) { return false; }

		// Get field
		$fields = $this->class_object->wf_easypost_custom_override_checkout_fields( array( 'billing' => array() ) );

		// Extract the insurance field
		$insurance_field = array_key_exists( 'billing', $fields ) && array_key_exists( 'easypost_insurance', $fields[ 'billing' ] ) ? $fields[ 'billing' ][ 'easypost_insurance' ] : false;

		return $insurance_field;
	}



	/**
	 * Add the easypost insurance field to the session field keys.
	 */
	public function add_easypost_insurance_field_to_session_field_keys( $session_field_keys, $parsed_posted_data ) {
		// Add easypost insurance field to the session field keys
		$session_field_keys[] = 'easypost_insurance';

		return $session_field_keys;
	}

	/**
	 * Output the custom insurance field.
	 */
	public function output_custom_insurance_field( $checkout ) {
		// Get insurance field
		$insurance_field = $this->get_easypost_insurance_field();

		// Bail if insurance field is not set
		if ( ! $insurance_field ) { return; }

		// Get field value from session or posted data
		$field_id = 'easypost_insurance';
		$field_value = FluidCheckout_Steps::instance()->get_checkout_field_value_from_session_or_posted_data( $field_id );

		// Output custom insurance field
		echo '<div class="easypost-insurance-field">';
		woocommerce_form_field( $field_id, $insurance_field, $field_value );
		echo '</div>';
	}

}

FluidCheckout_EasypostWooCommerceShipping::instance();
