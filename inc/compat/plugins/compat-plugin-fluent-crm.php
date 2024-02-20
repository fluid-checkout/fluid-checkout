<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Fluent CRM integration.
 */
class FluidCheckout_FluentCRM extends FluidCheckout {

	public $fluent_campaign_woo_init;

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
	}
	
	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		// Order notes
		$this->subscribe_box_hooks();
	}

	/**
	 * Add or remove subscribe box hooks.
	 */
	public function subscribe_box_hooks() {
		// Get the fluent campaign woo init objects
		$this->fluent_campaign_woo_init = $this->get_object_by_class_name_from_hooks( 'FluentCampaign\App\Services\Integrations\WooCommerce\WooInit' );

		// Bail if class or object not available
		if ( null === $this->fluent_campaign_woo_init ) { return; }

		// Move subscribe box
		remove_action( 'woocommerce_checkout_billing', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 999 );
		remove_action( 'woocommerce_before_order_notes', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 999 );
		add_action( 'fc_checkout_contact_after_fields', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 10 );
	}

}

FluidCheckout_FluentCRM::instance();
