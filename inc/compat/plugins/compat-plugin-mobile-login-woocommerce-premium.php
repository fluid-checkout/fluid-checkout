<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: OTP Login/Signup Woocommerce Premium (by XootiX).
 */
class FluidCheckout_MobileLoginWoocommercePremium extends FluidCheckout {

	/**
	 * Class name for the plugin which this compatibility class is related to.
	 */
	public const CLASS_NAME = 'awp_checkout_otp';



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
		// Contact step hooks
		$this->contact_step_hooks();

		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_plugin_phone_field_args' ), 10000 ); // Set hight priority to run after the plugin's function
	}



	/*
	* Add or remove hooks when phone field is displayed on the contact step.
	*/
	public function contact_step_hooks() {
		// Bail if phone field is not set to be displayed on the contact step
		if ( 'contact' !== FluidCheckout_Settings::instance()->get_option( 'fc_billing_phone_field_position' ) ) { return; }

		// Add plugin's phone field to contact fields
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_plugin_phone_field_to_contact_fields' ), 10 );

		// TODO: Maybe remove phone field from billing address data
		// add_filter( 'fc_billing_substep_text_address_data', array( $this, 'remove_phone_address_data' ), 10 );
	}



	/**
	 * Add the phone field from the plugin to the list of fields to display on the contact step.
	 *
	 * @param   array  $display_fields  List of fields to display on the contact step.
	 */
	public function add_plugin_phone_field_to_contact_fields( $display_fields ) {
		$display_fields[] = 'xoo-ml-reg-phone';
		return $display_fields;
	}



	/**
	 * Maybe change the plugin's phone field arguments.
	 *
	 * @param   array  $fields  The billing fields.
	 */
	public function maybe_change_plugin_phone_field_args( $fields ) {
		// Define variables
		$field_key = 'xoo-ml-reg-phone';

		// Bail if field is not present
		if ( ! array_key_exists( $field_key, $fields[ 'billing' ] ) ) { return $fields; }

		// Bail if necessary plugin functions are not available
		if ( ! function_exists( 'xoo_ml_helper' ) || ! method_exists( xoo_ml_helper(), 'get_phone_option' ) ) { return $fields; }

		// Get plugin settings
		$settings = xoo_ml_helper()->get_phone_option();

		// Bail if default phone field is not set to be displayed on the contact step
		// if ( ! in_array( 'billing_phone', FluidCheckout_Steps::instance()->get_contact_step_display_field_ids() ) ) { return $fields; }

		// Get default phone field priority
		$default_phone_field_priority = 30;
		if ( array_key_exists( 'billing_phone', $fields ) ) {
			$default_phone_field_priority = $fields[ 'billing_phone' ][ 'priority' ];
		}

		// Set priority to a higher value to display after the default phone field if both are displayed
		$fields[ 'billing' ][ $field_key ][ 'priority' ] = $default_phone_field_priority + 5;

		// Maybe remove 'form-row-first' class inherited from the default phone field
		$index = array_search( 'form-row-first', $fields[ 'billing' ][ $field_key ][ 'wc_cont_class' ], true );
		if ( false !== $index ) {
			unset( $fields[ 'billing' ][ $field_key ][ 'wc_cont_class' ][ $index ] );
		}

		// Maybe set plugin's phone field as required
		if ( array_key_exists( 'r-phone-field', $settings ) ) {
			$fields[ 'billing' ][ $field_key ][ 'required' ] = $settings[ 'r-phone-field' ];
		}

		return $fields;
	}

}

FluidCheckout_MobileLoginWoocommercePremium::instance();
