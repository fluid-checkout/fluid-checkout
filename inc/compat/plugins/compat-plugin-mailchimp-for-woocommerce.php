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
	}

	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		// Subscribe box
		$this->subscribe_box_hooks();
	}

	/**
	 * Add or remove subscribe box hooks.
	 */
	public function subscribe_box_hooks() {
		// Bail if option to move subscribe box is not enabled
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_mailchimp_force_subscribe_checkbox_position' ) ) { return; }
		
		// Bail if Mailchimp List Key is not set
		if ( ! function_exists( 'mailchimp_get_api_key' ) || empty( mailchimp_get_api_key() ) ) { return; }

		// Get settings from the plugin
		$service = MailChimp_Newsletter::instance();
		$render_on = $service->getOption( 'mailchimp_checkbox_action', 'woocommerce_after_checkout_billing_form' );

		// Move subscribe box
		remove_action( $render_on, array( $service, 'applyNewsletterField' ) );
		add_action( 'fc_checkout_after_contact_fields', array( $service, 'applyNewsletterField' ) );
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

}

FluidCheckout_MailchimpForWooCommerce::instance();
