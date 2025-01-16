<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Revolut Gateway for WooCommerce (by Revolut).
 */
class FluidCheckout_RevolutGatewayForWoocommerce extends FluidCheckout {

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
		// Payment methods
		add_filter( 'woocommerce_gateway_title', array( $this, 'maybe_change_payment_gateway_title' ), 10, 2 );
		add_filter( 'woocommerce_gateway_icon', array( $this, 'maybe_change_payment_gateway_icon_html' ), 10, 2 );
	}



	/**
	 * Maybe change the payment method title.
	 * 
	 * @param  string  $title  Payment method title.
	 * @param  string  $id     Payment method ID.
	 */
	public function maybe_change_payment_gateway_title( $title, $id = null ) {
		// Set payment methods that required the change
		$payment_method_ids = array( 'revolut_pay', 'revolut_cc' );

		// Bail if not Revolut payment method
		if ( ! in_array( $id, $payment_method_ids ) ) { return $title; }

		// Add container used by the plugin to add "Learn more" link via JS
		if ( 'revolut_pay' === $id ) {
			$title .= '<span class="revolut-label-informational-icon" id="revolut-pay-label-informational-icon"></span>';
		} else {
			$title .= '<span class="revolut-label-informational-icon"></span>';
		}

		return $title;
	}



	/**
	 * Maybe change the payment method icons.
	 * 
	 * @param  string  $icon_html  Payment method icon HTML.
	 * @param  string  $id         Payment method ID.
	 */
	public function maybe_change_payment_gateway_icon_html( $icon_html, $id = null ) {
		// Set payment methods that required the change
		$payment_method_ids = array( 'revolut_pay', 'revolut_cc' );

		// Bail if not Revolut payment method
		if ( ! in_array( $id, $payment_method_ids ) ) { return $icon_html; }

		// Remove "Learn more" link container
		if ( 'revolut_pay' === $id ) {
			$icon_html = str_replace( '<span class="revolut-label-informational-icon" id="revolut-pay-label-informational-icon"></span>', '', $icon_html );
		} else {
			$icon_html = str_replace( '<span class="revolut-label-informational-icon"></span>', '', $icon_html );
		}
		
		return $icon_html;
	}

}

FluidCheckout_RevolutGatewayForWoocommerce::instance();
