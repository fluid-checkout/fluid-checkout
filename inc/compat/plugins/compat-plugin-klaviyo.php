<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Klaviyo integration.
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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Signup
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_signup_checkout_field_to_contact_step' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_signup_checkbox_field_args' ), 100 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// SMS compliance
		$this->sms_compliance_hooks();
	}

	/**
	 * Add or remove SMS compliance hooks.
	 */
	public function sms_compliance_hooks() {
		// Get plugin settings
		$klaviyo_settings = FluidCheckout_Settings::instance()->get_option( 'klaviyo_settings' );

		// Bail if settings not valid
		if ( ! is_array( $klaviyo_settings ) || empty( $klaviyo_settings ) ) { return; }

		// Bail if SMS subscribe checkbox not enabled
		if ( ! isset( $klaviyo_settings[ 'klaviyo_sms_subscribe_checkbox' ] ) || ! $klaviyo_settings[ 'klaviyo_sms_subscribe_checkbox' ] || empty( $klaviyo_settings[ 'klaviyo_sms_list_id' ] ) ) { return; }

		// Klaviyo renamed the compliance callback in newer versions.
		$sms_compliance_callback = $this->get_sms_compliance_callback();

		// Move SMS compliance fields
		if ( $sms_compliance_callback ) {
			remove_filter( 'woocommerce_after_checkout_billing_form', $sms_compliance_callback, 10 );
		}

		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_sms_compliance_checkbox_field_args' ), 100 );
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'maybe_add_sms_compliance_checkout_field_to_contact_step' ), 10 );
	}



	/**
	 * Add the signup checkbox field to the contact step.
	 */
	public function add_signup_checkout_field_to_contact_step( $display_fields ) {
		// Checkbox fields
		$display_fields[] = 'kl_newsletter_checkbox';

		return $display_fields;
	}



	/**
	 * Add the SMS compliance checkbox field to the contact step.
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
	 */
	public function maybe_change_signup_checkbox_field_args( $fields ) {
		// Signup
		if ( array_key_exists( 'billing', $fields ) && array_key_exists( 'kl_newsletter_checkbox', $fields[ 'billing' ] ) ) {
			$fields[ 'billing' ][ 'kl_newsletter_checkbox' ][ 'priority' ] = 200;
			$fields[ 'billing' ][ 'kl_newsletter_checkbox' ][ 'class' ] = array( 'kl_newsletter_checkbox_field', 'form-row-wide' );
		}

		return $fields;
	}



	/**
	 * Maybe change the SMS compliance checkbox field arguments.
	 */
	public function maybe_change_sms_compliance_checkbox_field_args( $fields ) {
		// SMS Compliance
		if ( array_key_exists( 'billing', $fields ) && array_key_exists( 'kl_sms_consent_checkbox', $fields[ 'billing' ] ) ) {
			$sms_compliance_text = '';

			// Get SMS compliance text from the callback available in the active Klaviyo version.
			$sms_compliance_callback = $this->get_sms_compliance_callback();
			if ( $sms_compliance_callback ) {
				ob_start();
				call_user_func( $sms_compliance_callback );
				$sms_compliance_text = ob_get_clean();
			}

			// Change field args
			$fields[ 'billing' ][ 'kl_sms_consent_checkbox' ][ 'priority' ] = 210;
			$fields[ 'billing' ][ 'kl_sms_consent_checkbox' ][ 'description' ] = $sms_compliance_text;
		}

		return $fields;
	}



	/**
	 * Get the SMS compliance callback name available for the active Klaviyo version.
	 */
	public function get_sms_compliance_callback() {
		// Klaviyo 3.8+.
		if ( function_exists( 'kl_mobile_compliance_text' ) ) { return 'kl_mobile_compliance_text'; }

		// Klaviyo <= 3.6.x.
		if ( function_exists( 'kl_sms_compliance_text' ) ) { return 'kl_sms_compliance_text'; }

		return false;
	}

}

FluidCheckout_Klaviyo::instance();
