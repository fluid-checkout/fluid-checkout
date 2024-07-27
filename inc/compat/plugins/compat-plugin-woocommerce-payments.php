<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooPayments (by WooCommerce).
 */
class FluidCheckout_WooCommercePayments extends FluidCheckout {

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
		// Same as addresses
		add_filter( 'fc_is_billing_same_as_shipping_checked', array( $this, 'maybe_set_skip_setting_address_data_to_same_as_address' ), 500 );
		add_filter( 'fc_is_shipping_same_as_billing_checked', array( $this, 'maybe_set_skip_setting_address_data_to_same_as_address' ), 500 );
	}



	/**
	 * Maybe set skip setting address data to same as billing/shipping address when processing express checkout payments for this plugin.
	 *
	 * @param   bool  $is_address_same_as   Whether address is same as billing or shipping address.
	 */
	public function maybe_set_skip_setting_address_data_to_same_as_address( $is_address_same_as ) {
		// Bail if not doing AJAX request to create account from express checkout payments from this plugin
		if ( ! array_key_exists( 'wc-ajax', $_GET ) || 'wcpay_create_order' !== sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ) ) ) { return $is_address_same_as; }

		// Otherwise, set as not same address
		return false;
	}

}

FluidCheckout_WooCommercePayments::instance();
