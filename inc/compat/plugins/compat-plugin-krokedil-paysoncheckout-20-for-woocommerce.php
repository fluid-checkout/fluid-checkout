<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: PaysonCheckout for WooCommerce (by Krokedil).
 */
class FluidCheckout_KrokedilPaysonCheckout20ForWooCommerce extends FluidCheckout {

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
		if ( $this->is_payson_selected() ) {
			// Template file loader
			remove_filter( 'woocommerce_locate_template', array( FluidCheckout_Steps::instance(), 'locate_template' ), 100, 3 );

			// TODO: Remove functions which use custom template files created by Fluid Checkout
		}
	}



	public function is_payson_selected() {
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		// var_dump( array_keys( $available_gateways ) );

		// Paysoncheckout checkout page.
		if ( array_key_exists( 'paysoncheckout', $available_gateways ) ) {
			// If chosen payment method exists.
			if ( 'paysoncheckout' === WC()->session->get( 'chosen_payment_method' ) ) {
				return true;
			}
			// If chosen payment method does not exist and PCO is the first gateway.
			if ( null === WC()->session->get( 'chosen_payment_method' ) || '' === WC()->session->get( 'chosen_payment_method' ) ) {
				reset( $available_gateways );
				if ( 'paysoncheckout' === key( $available_gateways ) ) {
					return true;
				}
			}
			// If another gateway is saved in session, but has since become unavailable.
			if ( WC()->session->get( 'chosen_payment_method' ) ) {
				if ( ! array_key_exists( WC()->session->get( 'chosen_payment_method' ), $available_gateways ) ) {
					reset( $available_gateways );
					if ( 'paysoncheckout' === key( $available_gateways ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

}

FluidCheckout_KrokedilPaysonCheckout20ForWooCommerce::instance();
