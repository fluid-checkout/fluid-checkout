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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Signup
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_signup_checkout_field_to_contact_step' ), 10 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_signup_checkbox_field_args' ), 100 );

		// Review text
		add_filter( 'fc_substep_text_display_value_kl_newsletter_checkbox', array( $this, 'change_klaviyo_checkbox_display_value_for_review_text' ), 10, 4 );
		add_filter( 'fc_substep_text_display_value_kl_sms_consent_checkbox', array( $this, 'change_klaviyo_checkbox_display_value_for_review_text' ), 10, 4 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Mobile consent compliance
		$this->sms_compliance_hooks();
	}

	/**
	 * Add or remove mobile consent compliance hooks.
	 */
	public function sms_compliance_hooks() {
		// Get plugin settings
		$klaviyo_settings = FluidCheckout_Settings::instance()->get_option( 'klaviyo_settings' );

		// Bail if settings not valid
		if ( ! is_array( $klaviyo_settings ) || empty( $klaviyo_settings ) ) { return; }

		// Bail if mobile consent not enabled
		if ( ! $this->is_mobile_consent_enabled( $klaviyo_settings ) ) { return; }

		// Klaviyo renamed the compliance callback in newer versions.
		$mobile_compliance_callback = $this->get_mobile_compliance_callback();

		// Move mobile consent compliance fields
		if ( $mobile_compliance_callback ) {
			remove_filter( 'woocommerce_after_checkout_billing_form', $mobile_compliance_callback, 10 );
		}

		// SMS compliance checkbox
		add_filter( 'woocommerce_checkout_fields', array( $this, 'maybe_change_sms_compliance_checkbox_field_args' ), 100 );
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'maybe_add_sms_compliance_checkout_field_to_contact_step' ), 10 );
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
		// Signup
		if ( array_key_exists( 'billing', $fields ) && array_key_exists( 'kl_newsletter_checkbox', $fields[ 'billing' ] ) ) {
			$fields[ 'billing' ][ 'kl_newsletter_checkbox' ][ 'priority' ] = 200;
			$fields[ 'billing' ][ 'kl_newsletter_checkbox' ][ 'class' ] = array( 'kl_newsletter_checkbox_field', 'form-row-wide' );
		}

		return $fields;
	}



	/**
	 * Maybe change the SMS compliance checkbox field arguments.
	 * 
	 * @param   array  $fields  The checkout fields.
	 */
	public function maybe_change_sms_compliance_checkbox_field_args( $fields ) {
		// SMS Compliance
		if ( array_key_exists( 'billing', $fields ) && array_key_exists( 'kl_sms_consent_checkbox', $fields[ 'billing' ] ) ) {
			$sms_compliance_text = '';

			// Get compliance text from the callback available in the active Klaviyo version.
			$mobile_compliance_callback = $this->get_mobile_compliance_callback();
			if ( $mobile_compliance_callback ) {
				ob_start();
				call_user_func( $mobile_compliance_callback );
				$sms_compliance_text = ob_get_clean();
			}

			// Change field args
			$fields[ 'billing' ][ 'kl_sms_consent_checkbox' ][ 'priority' ] = 210;
			$fields[ 'billing' ][ 'kl_sms_consent_checkbox' ][ 'description' ] = $sms_compliance_text;
		}

		return $fields;
	}



	/**
	 * Change Klaviyo checkbox field display value for substep review text.
	 * 
	 * @param   string  $field_display_value  The field display value.
	 * @param   string  $field_value          The field value.
	 * @param   string  $field_key            The field key.
	 * @param   array   $field_args           The field arguments.
	 */
	public function change_klaviyo_checkbox_display_value_for_review_text( $field_display_value, $field_value, $field_key, $field_args ) {
		// Bail if field label is not set
		if ( empty( $field_args[ 'label' ] ) ) { return $field_display_value; }

		$field_label        = $field_args[ 'label' ];
		// TODO: Use the default functions to display checkbox values as `yes` or `no`
		$field_value_text   = ! empty( $field_value ) ? __( 'Yes', 'fluid-checkout' ) : __( 'No', 'fluid-checkout' );
		$show_field_label   = apply_filters( 'fc_substep_text_display_value_show_field_label_checkbox', true );

		return FluidCheckout_Steps::instance()->get_field_display_value_with_pattern( $field_value_text, $field_key, $field_args, $field_label, $show_field_label );
	}



	/**
	 * Whether mobile marketing consent is enabled in Klaviyo settings.
	 *
	 * @param   array  $klaviyo_settings  Klaviyo plugin settings.
	 */
	public function is_mobile_consent_enabled( $klaviyo_settings ) {
		// Bail if Klaviyo settings not valid SMS list ID is not set
		if ( ! is_array( $klaviyo_settings ) || empty( $klaviyo_settings[ 'klaviyo_sms_list_id' ] ) ) { return false; }

		// Klaviyo 3.3+ (SMS and/or WhatsApp).
		if ( function_exists( 'kl_any_mobile_channel_enabled' ) ) {
			return kl_any_mobile_channel_enabled( $klaviyo_settings );
		}

		// Legacy: SMS subscribe checkbox only.
		return ! empty( $klaviyo_settings[ 'klaviyo_sms_subscribe_checkbox' ] );
	}



	/**
	 * Get the mobile compliance callback name available for the active Klaviyo version.
	 */
	public function get_mobile_compliance_callback() {
		// Klaviyo 3.7+.
		if ( function_exists( 'kl_mobile_compliance_text' ) ) { return 'kl_mobile_compliance_text'; }

		// Klaviyo <= 3.6.x.
		if ( function_exists( 'kl_sms_compliance_text' ) ) { return 'kl_sms_compliance_text'; }

		return false;
	}

}

FluidCheckout_Klaviyo::instance();
