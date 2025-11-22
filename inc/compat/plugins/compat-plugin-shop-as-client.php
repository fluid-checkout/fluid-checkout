<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Shop as Client for WooCommerce (by Naked Cat Plugins).
 */
class FluidCheckout_ShopAsClient extends FluidCheckout {

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
		// Checkout fields
		add_filter( 'fc_checkout_contact_step_field_ids', array( $this, 'add_shop_as_client_fields_to_contact_fields' ), 10 );
	}



	/**
	 * Add the "Shop as Client" fields to the list of fields to display on the contact step.
	 *
	 * @param   array  $display_fields  List of fields to display on the contact step.
	 */
	public function add_shop_as_client_fields_to_contact_fields( $display_fields ) {
		$display_fields[] = 'billing_shop_as_client';
		$display_fields[] = 'billing_shop_as_client_create_user';
		return $display_fields;
	}

}

FluidCheckout_ShopAsClient::instance();
