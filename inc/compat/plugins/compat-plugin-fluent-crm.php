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
		// Get the fluent campaign woo init objects
		$this->fluent_campaign_woo_init = $this->get_object_by_class_name_from_hooks( 'FluentCampaign\App\Services\Integrations\WooCommerce\WooInit' );
		
		if ( null !== $this->fluent_campaign_woo_init ) {
			remove_action( 'woocommerce_checkout_billing', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 999 );
			add_action( 'fc_checkout_contact_after_fields', array( $this->fluent_campaign_woo_init, 'addSubscribeBox' ), 10 );
		}
	}

}

FluidCheckout_FluentCRM::instance();
