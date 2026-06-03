<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Klaviyo (by Klaviyo)
 */
class FluidCheckout_Klaviyo extends FluidCheckout {

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

		// Signup
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_signup_checkout_field_to_contact_step' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_signup_checkbox_field_args' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Mobile consent compliance
		$this->sms_compliance_hooks();
	}

	/**
	 * Add or remove mobile consent compliance hooks.
	 */
	public function sms_compliance_hooks() {
		// Bail if mobile consent not enabled
		if ( ! $this->is_mobile_consent_enabled() ) { return; }

		// Remove mobile consent compliance fields
		remove_filter( 'woocommerce_after_checkout_billing_form', 'kl_mobile_compliance_text', 10 );
		remove_filter( 'woocommerce_after_checkout_billing_form', 'kl_sms_compliance_text', 10 );

		// SMS compliance checkbox
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_sms_compliance_checkbox_field_args' ), 100 );
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'maybe_add_sms_compliance_checkout_field_to_contact_step' ), 10 );
	}



	/**
	 * Get the mobile compliance callback name available for the active Klaviyo version.
	 */
	public function get_mobile_compliance_function_name() {
		// Klaviyo 3.7+.
		if ( function_exists( 'kl_mobile_compliance_text' ) ) { return 'kl_mobile_compliance_text'; }

		// Klaviyo <= 3.6.x.
		if ( function_exists( 'kl_sms_compliance_text' ) ) { return 'kl_sms_compliance_text'; }

		return false;
	}



	/**
	 * Whether mobile marketing consent is enabled in Klaviyo settings.
	 */
	public function is_mobile_consent_enabled() {
		// Get plugin settings
		$klaviyo_settings = FluidCheckout_Settings::instance()->get_option( 'klaviyo_settings' );

		// Bail if settings not valid
		if ( ! is_array( $klaviyo_settings ) || empty( $klaviyo_settings ) ) { return false; }

		// Bail if SMS list ID is not set
		if ( ! array_key_exists( 'klaviyo_sms_list_id', $klaviyo_settings ) || empty( $klaviyo_settings[ 'klaviyo_sms_list_id' ] ) ) { return false; }

		// Klaviyo 3.3+ (SMS and/or WhatsApp).
		if ( function_exists( 'kl_any_mobile_channel_enabled' ) ) {
			return kl_any_mobile_channel_enabled( $klaviyo_settings );
		}

		// Legacy: SMS subscribe checkbox only.
		if ( array_key_exists( 'klaviyo_sms_subscribe_checkbox', $klaviyo_settings ) && $klaviyo_settings[ 'klaviyo_sms_subscribe_checkbox' ] ) {
			return true;
		}

		// Otherwise, return false
		return false;
	}



	/**
	 * Add the signup checkbox field to the contact step.
	 * 
	 * @param   array  $display_fields  The display fields.
	 */
	public function add_signup_checkout_field_to_contact_step( $display_fields ) {
		// Checkbox fields
		$display_fields[] = 'kl_newsletter_checkbox';

		return $display_fields;
	}



	/**
	 * Add the SMS compliance checkbox field to the contact step.
	 * 
	 * @param   array  $display_fields  The display fields.
	 */
	public function maybe_add_sms_compliance_checkout_field_to_contact_step( $display_fields ) {
		// Bail if billing phone field is not in the list to be displayed in the contact step
		if ( ! in_array( 'billing_phone', $display_fields ) ) { return $display_fields; }

		// Add SMS compliance field
		$display_fields[] = 'kl_sms_consent_checkbox';

		return $display_fields;
	}



	/**
	 * Maybe change the signup checkbox field arguments.
	 * 
	 * @param   array  $fields  The checkout fields.
	 */
	public function maybe_change_signup_checkbox_field_args( $fields ) {
		// Bail if billing fields not available
		if ( ! is_array( $fields ) || ! array_key_exists( 'billing', $fields ) ) { return $fields; }

		// Bail if signup checkbox is not set in billing fields
		if ( ! array_key_exists( 'kl_newsletter_checkbox', $fields[ 'billing' ] ) ) { return $fields; }

		// Change field args
		$fields[ 'billing' ][ 'kl_newsletter_checkbox' ][ 'priority' ] = 200;
		$fields[ 'billing' ][ 'kl_newsletter_checkbox' ][ 'class' ] = array( 'kl_newsletter_checkbox_field', 'form-row-wide' );

		return $fields;
	}



	/**
	 * Maybe change the SMS compliance checkbox field arguments.
	 * 
	 * @param   array  $fields  The checkout fields.
	 */
	public function maybe_change_sms_compliance_checkbox_field_args( $fields ) {
		// Bail if billing fields not available
		if ( ! is_array( $fields ) || ! array_key_exists( 'billing', $fields ) ) { return $fields; }

		// Bail if SMS consent checkbox is not set in billing fields
		if ( ! array_key_exists( 'kl_sms_consent_checkbox', $fields[ 'billing' ] ) ) { return $fields; }

		// Initialize variables
		$sms_compliance_text = '';
		$mobile_compliance_callback = $this->get_mobile_compliance_function_name();

		// Bail if mobile compliance callback not available
		if ( false === $mobile_compliance_callback || ! function_exists( $mobile_compliance_callback ) ) { return $fields; }

		// Get compliance text for the SMS compliance checkbox
		ob_start();
		call_user_func( $mobile_compliance_callback );
		$sms_compliance_text = ob_get_clean();

		// Change field args
		$fields[ 'billing' ][ 'kl_sms_consent_checkbox' ][ 'priority' ] = 210;
		$fields[ 'billing' ][ 'kl_sms_consent_checkbox' ][ 'description' ] = $sms_compliance_text;

		return $fields;
	}

}

FluidCheckout_Klaviyo::instance();
