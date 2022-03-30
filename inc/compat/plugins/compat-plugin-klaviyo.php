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
		// Bail if klaviyo class not exits
		if ( ! class_exists( 'WooCommerceKlaviyo' ) ) { return; }

		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_checkout_field' ), 10 );
	}



	/**
	 * Add the klaviyo checkbox field to the checkout page.
	 */
	public function add_checkout_field( $args ) {
		$args[] = 'kl_newsletter_checkbox';
		$args[] = 'kl_sms_consent_checkbox';

		return $args;
	}

}

FluidCheckout_Klaviyo::instance();
