<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: PayPal Brasil para WooCommerce (by Paypal).
 */
class FluidCheckout_PaypalBrasilParaWooCommerce extends FluidCheckout {

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
		// Shipping address data
		add_filter( 'wc_ppp_brasil_user_data', array( $this, 'maybe_copy_billing_to_shipping_address_data' ), 10 );
		add_filter( 'wc_ppp_brasil_user_data', array( $this, 'maybe_clear_shipping_address_not_brazil' ), 20 );
	}



	/**
	 * Maybe copy the billing address data to shipping address when the shipping address is not available for the order.
	 *
	 * @param   array  $data  The payment method data.
	 */
	public function maybe_copy_billing_to_shipping_address_data( $data ) {
		// Bail if cart needs shipping address
		// In this case, the shipping address data should not be overwitten from billing
		if ( WC()->cart->needs_shipping_address() ) { return $data; }

		// Get posted data
		$posted_data = $this->get_parsed_posted_data();

		// Bail if billing country is not Brazil
		if ( ! array_key_exists( 'billing_country', $posted_data ) || 'BR' !== $posted_data[ 'billing_country' ] ) { return $data; }

		// Used parsed billing data as the shipping address
		$data['postcode'] = array_key_exists( 'billing_postcode', $posted_data ) ? $posted_data[ 'billing_postcode' ] : '';
		$data['address'] = array_key_exists( 'billing_address_1', $posted_data ) ? $posted_data[ 'billing_address_1' ] : '';
		$data['address_2'] = array_key_exists( 'billing_address_2', $posted_data ) ? $posted_data[ 'billing_address_2' ] : '';
		$data['city'] = array_key_exists( 'billing_city', $posted_data ) ? $posted_data[ 'billing_city' ] : '';
		$data['state'] = array_key_exists( 'billing_state', $posted_data ) ? $posted_data[ 'billing_state' ] : '';
		$data['country'] = array_key_exists( 'billing_country', $posted_data ) ? $posted_data[ 'billing_country' ] : '';
		
		return $data;
	}



	/**
	 * Maybe clear the shipping address data when country is not Brazil (BR).
	 *
	 * @param   array  $data  The payment method data.
	 */
	public function maybe_clear_shipping_address_not_brazil( $data ) {
		// Bail if billing country is not Brazil
		if ( ! array_key_exists( 'country', $data ) || 'BR' === $data[ 'country' ] ) { return $data; }

		// Used parsed billing data as the shipping address
		$data['postcode'] = '';
		$data['address'] = '';
		$data['address_2'] = '';
		$data['city'] = '';
		$data['state'] = '';
		$data['country'] = '';
		
		return $data;
	}

}

FluidCheckout_PayPalBrasilParaWooCommerce::instance();
