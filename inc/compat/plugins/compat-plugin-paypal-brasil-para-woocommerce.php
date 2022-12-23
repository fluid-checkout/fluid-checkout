<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: PayPal Brasil para WooCommerce (by Paypal).
 */
class FluidCheckout_PaypalBrasilParaWooCommerce extends FluidCheckout {
	
	public $paypal_brasil_gateway;



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

		// Shipping address data
		add_filter( 'wc_ppp_brasil_user_data', array( $this, 'maybe_copy_billing_to_shipping_address_data' ), 10 );
		add_filter( 'wc_ppp_brasil_user_data', array( $this, 'maybe_clear_shipping_address_not_brazil' ), 20 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {

		// Maybe run initialization
		if ( class_exists( 'PayPal_Brasil_SPB_Gateway' ) ) {
			// Get the gateway object
			$this->paypal_brasil_gateway = $this->get_object_by_class_name_from_hooks( 'PayPal_Brasil_SPB_Gateway' );
			
			if ( null !== $this->paypal_brasil_gateway ) {
				// Move PayPal button to custom place order buttons
				remove_action( 'woocommerce_review_order_before_submit', array( $this->paypal_brasil_gateway, 'html_before_submit_button' ), 10 );
				remove_action( 'woocommerce_review_order_after_submit', array( $this->paypal_brasil_gateway, 'html_after_submit_button' ), 10 );
				add_action( 'fc_place_order_custom_buttons', array( $this->paypal_brasil_gateway, 'html_before_submit_button' ), 20 );
				add_action( 'fc_place_order_custom_buttons', array( $this->paypal_brasil_gateway, 'html_after_submit_button' ), 20 );
			}
		}
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

		// Use parsed billing address data as the shipping address
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

		// Clear address data
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
