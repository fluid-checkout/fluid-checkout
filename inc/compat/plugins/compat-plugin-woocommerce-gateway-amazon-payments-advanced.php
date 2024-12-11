<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Amazon Pay (by WooCommerce).
 */
class FluidCheckout_WooCommerceGatewayAmazonPaymentsAdvanced extends FluidCheckout {

	public $amazon_pay_gateway;
	public $amazon_pay_gateway_legacy;

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

		// Maybe run initialization
		if ( class_exists( 'WC_Gateway_Amazon_Payments_Advanced' ) ) {

			// Get the gateway object
			$this->amazon_pay_gateway = $this->get_object_by_class_name_from_hooks( 'WC_Gateway_Amazon_Payments_Advanced' );
			
			if ( null !== $this->amazon_pay_gateway ) {
				// Run checkout initialization later
				remove_action( 'woocommerce_checkout_init', array( $this->amazon_pay_gateway, 'checkout_init' ), 10 );
				$this->amazon_pay_gateway->checkout_init( WC()->checkout );
			}

		}

		// Maybe run legacy initialization
		if ( class_exists( 'WC_Gateway_Amazon_Payments_Advanced_Legacy' ) ) {

			// Get the Amazon Pay object
			$this->amazon_pay_gateway_legacy = $this->get_object_by_class_name_from_hooks( 'WC_Gateway_Amazon_Payments_Advanced_Legacy' );

			if ( null !== $this->amazon_pay_gateway_legacy ) {
				// Run checkout initialization later
				remove_action( 'woocommerce_checkout_init', array( $this->amazon_pay_gateway_legacy, 'checkout_init' ), 10 );
				$this->amazon_pay_gateway_legacy->checkout_init( WC()->checkout );
			}

		}

	}

}

FluidCheckout_WooCommerceGatewayAmazonPaymentsAdvanced::instance();
