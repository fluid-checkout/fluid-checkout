<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Mailchimp for WooCommerce (by Mailchimp).
 */
class FluidCheckout_MailchimpForWooCommerce extends FluidCheckout {

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

		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10 );

		// Subscribe box
		add_filter( 'woocommerce_billing_fields', array( $this, 'maybe_add_newsletter_as_checkout_field' ), 100 );
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'maybe_add_newsletter_to_contact_fields' ), 100 );
		add_filter( 'woocommerce_form_field_mailchimp_newsletter', array( $this, 'maybe_change_newsletter_field_html' ), 10, 4 );
		add_filter( 'fc_hide_optional_fields_skip_list', array( $this, 'prevent_hide_optional_fields' ), 10 );
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Subscribe box
		$this->subscribe_box_late_hooks();
	}

	/**
	 * Add or remove subscribe box hooks.
	 */
	public function subscribe_box_late_hooks() {
		// Bail if option to move subscribe box is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_mailchimp_force_subscribe_checkbox_position' ) ) { return; }

		// Bail if Mailchimp List Key is not set
		if ( ! function_exists( 'mailchimp_get_api_key' ) || empty( mailchimp_get_api_key() ) ) { return; }

		// Get settings from the plugin
		$service = MailChimp_Newsletter::instance();
		$render_on = $service->getOption( 'mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form' );

		// Move subscribe box
		remove_action( $render_on, array( $service, 'applyNewsletterField' ) );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {

		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'Mailchimp for WooCommerce', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_mailchimp_options',
			),

			array(
				'title'           => __( 'Subscribe checkbox', 'fluid-checkout' ),
				'desc'            => __( 'Override the position for the subscribe checkbox on the checkout page and always display it on the contact step', 'fluid-checkout' ),
				'id'              => 'fc_integration_mailchimp_force_subscribe_checkbox_position',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_mailchimp_force_subscribe_checkbox_position' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_mailchimp_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe add the newsletter checkbox as a checkout field to allow sorting by priority.
	 *
	 * @param   array  $fields   The billing fields.
	 */
	public function maybe_add_newsletter_as_checkout_field( $fields ) {
		// Bail if option to move subscribe box is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_mailchimp_force_subscribe_checkbox_position' ) ) { return; }

		// Bail if field is already added
		if ( array_key_exists( 'mailchimp_woocommerce_newsletter', $fields ) ) { return $fields; }

		// Get email field priority
		$email_field_priority = 10;
		if ( array_key_exists( 'billing_email', $fields ) ) {
			$email_field_priority = $fields[ 'billing_email' ][ 'priority' ];
		}

		// Add Mailchimp newsletter field
		$fields[ 'mailchimp_woocommerce_newsletter' ] = array(
			'label'       => '', // Intetionally empty, the fields will be replaced with the Mailchimp output function.
			'type'        => 'mailchimp_newsletter',
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
			'priority'    => $email_field_priority + 5,
		);

		return $fields;
	}

	/**
	 * Add the newsletter checkbox to the list of fields to display on the contact step.
	 *
	 * @param   array  $display_fields  List of fields to display on the contact step.
	 */
	public function maybe_add_newsletter_to_contact_fields( $display_fields ) {
		// Bail if option to move subscribe box is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_mailchimp_force_subscribe_checkbox_position' ) ) { return; }

		$display_fields[] = 'mailchimp_woocommerce_newsletter';
		return $display_fields;
	}

	/**
	 * Maybe change HTML code of the Mailchimp newsletter field.
	 *
	 * @param   string  $field  The field html.
	 * @param   string  $key    The field key.
	 * @param   array   $args   The field args.
	 * @param   mixed   $value  The field value.
	 */
	public function maybe_change_newsletter_field_html( $field, $key, $args, $value ) {
		// Bail if option to move subscribe box is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_mailchimp_force_subscribe_checkbox_position' ) ) { return $field; }

		// Get the Mailchimp service
		$service = MailChimp_Newsletter::instance();

		// Get the field html
		ob_start();
		$service->applyNewsletterField( WC()->checkout );
		$field = ob_get_clean();

		return $field;
	}

	/**
	 * Prevent hiding optional the Mailchimp newsletter field behind a link button.
	 *
	 * @param   array  $skip_list  List of optional fields to skip hidding.
	 */
	public function prevent_hide_optional_fields( $skip_list ) {
		$skip_list[] = 'mailchimp_woocommerce_newsletter';
		return $skip_list;
	}

}

FluidCheckout_MailchimpForWooCommerce::instance();
