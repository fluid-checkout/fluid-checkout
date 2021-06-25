<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Rede WooCommerce (by Rede).
 */
class FluidCheckout_RedeWooCommerce extends FluidCheckout {

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
		// Add Rede payment gateway checkout fields to the persisted data skip list
		add_filter( 'fc_customer_persisted_data_skip_fields', array( $this, 'add_rede_persisted_data_skip_fields' ), 10, 2 );
	}



	/**
	 * Adds delivery forecast after method name.
	 *
	 * @param  array  $skip_field_keys     Checkout field keys to skip from saving to the session.
	 * @param  array  $parsed_posted_data  All parsed post data.
	 */
	public function add_rede_persisted_data_skip_fields( $skip_field_keys, $parsed_posted_data ) {
		$rede_fields_keys = array(
			'rede_credit_number',
			'rede_credit_installments',
			'rede_credit_holder_name',
			'rede_credit_expiry',
			'rede_credit_cvc',
		);

		return array_merge( $skip_field_keys, $rede_fields_keys );
	}

}

FluidCheckout_RedeWooCommerce::instance();
