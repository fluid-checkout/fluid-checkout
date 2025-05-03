<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Omnisend for WooCommerce (by Omnisend).
 */
class FluidCheckout_OmnisendForWooCommerce extends FluidCheckout {

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
		// Move newsletter checkbox
		remove_action( 'woocommerce_after_checkout_billing_form', 'omnisend_checkbox_custom_checkout_field', 10 );
		add_action( 'fc_checkout_contact_after_fields', array( $this, 'output_newsletter_checkbox' ), 10 );

		// Reset checkbox field value
		add_filter( 'fc_parsed_posted_data_reset_field_keys', array( $this, 'add_checkbox_reset_posted_data_field_key' ), 10, 2 );
	}



	/**
	 * Output the newsletter checkbox from Omnisend.
	 * COPIED AND ADAPTED FROM: omnisend_checkbox_custom_checkout_field()
	 */
	public function output_newsletter_checkbox() {
		// Initialize variables
		$field_key = 'omnisend_newsletter_checkbox';

		// Bail if plugin functions are not available
		if ( ! method_exists( 'Omnisend_Logger', 'hook' ) || ! method_exists( 'Omnisend_Helper', 'is_omnisend_connected' ) ) { return; }
		if ( ! method_exists( 'Omnisend_Settings', 'get_checkout_opt_in_text' ) || ! method_exists( 'Omnisend_Settings', 'get_checkout_opt_in_preselected_status' ) ) { return; }
		
		// Bail if checkbox option is disabled
		if ( Omnisend_Settings::STATUS_ENABLED !== Omnisend_Settings::get_checkout_opt_in_status() ) { return; }

		// Initialize Omnisend hooks
		Omnisend_Logger::hook();

		// Bail if not connected to Omnisend
		$connected = Omnisend_Helper::is_omnisend_connected();
		if ( ! $connected ) { return; }

		// CHANGE: Save field value to variable
		$field_value = WC()->checkout->get_value( $field_key );
		if ( null === $field_value && Omnisend_Settings::STATUS_ENABLED === Omnisend_Settings::get_checkout_opt_in_preselected_status() ) {
			$field_value = 1;
		}
		elseif ( null === $field_value ) {
			$field_value = 0;
		}

		// Output the newsletter checkbox
		woocommerce_form_field(
			$field_key,
			array(
				'type'     => 'checkbox',
				'class'    => array( 'omnisend_newsletter_checkbox_field' ),
				'label'    => Omnisend_Settings::get_checkout_opt_in_text(),
				'value'    => true,
				// CHANGE: Replace ternary operator with variable
				'default'  => $field_value,
				'required' => false,
			),
			// CHANGE: Replace unavailable method call with variable
			$field_value
		);
	}



	/**
	 * Add newsletter checkbox field key to be cleared when not present in posted data.
	 * 
	 * @param   array  $field_keys   Field keys.
	 * @param   array  $posted_data  Posted data.
	 */
	public function add_checkbox_reset_posted_data_field_key( $field_keys, $posted_data ) {
		// Add customer location confirmation field key
		$field_keys[] = 'omnisend_newsletter_checkbox';

		return $field_keys;
	}

}

FluidCheckout_OmnisendForWooCommerce::instance();
