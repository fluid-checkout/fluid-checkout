<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Tilopay (by Tilopay).
 */
class FluidCheckout_PymntplPayPalWooCommerce extends FluidCheckout {

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
	 */
	public function maybe_change_payment_gateway_title( $title, $id = null ) {
		// Bail if not Tilopay payment method
		if ( 'tilopay' !== $id ) { return $title; }

		// Bail if title changed from payment method settings
		if ( ! empty( $title ) && __( 'Pay with', 'tilopay' ) !== $title ) { return $title; }

		// Change title to "Pay with Tilopay"
		$title = __( 'Pay with', 'tilopay' ) . '<span class="screen-reader-text">' . __( 'Tilopay', 'tilopay' ) . '</span>';

		return $title;
	}


	/**
	 * Maybe change the payment method icons.
	 */
	public function maybe_change_payment_gateway_icon_html( $icon_html, $id = null ) {
		// Bail if not Tilopay payment method
		if ( 'tilopay' !== $id ) { return $icon_html; }

		// Get payment method instance.
		$gateways_available = WC()->payment_gateways->get_available_payment_gateways();
		$gateway = array_key_exists( $id, $gateways_available ) ? $gateways_available[ $id ] : null;

		// Bail if payment method instance is not found.
		if ( ! $gateway ) { return $icon_html; }

		// Replace the icons with the Tilopay logo.
		$icon_html = '<img alt="" src="' . TPAY_PLUGIN_URL . '/assets/images/tilopay_color.png" />';

		// Additional icons
		if ( is_array( $gateway->tpay_logo_options ) && ! empty( $gateway->tpay_logo_options ) ) {
			foreach ( $gateway->tpay_logo_options as $key => $value ) {
				if ( in_array( $value, array( 'visa', 'mastercard', 'american_express', 'sinpemovil', 'credix', 'sistema_clave' ) ) ) {
					// others
					$icon_html .= '<img alt="" src="' . TPAY_PLUGIN_URL . '/assets/images/' . esc_attr( $value ) . '.svg" />';
				}
			}

			// Tasa cero
			if ( in_array('tasa_cero', $gateway->tpay_logo_options ) ) {
				$icon_html .= '<img alt="" src="' . TPAY_PLUGIN_URL . '/assets/images/tasa-cero.png" />';
			}

			// Mini cuotas
			if ( in_array( 'mini_cuotas', $gateway->tpay_logo_options ) ) {
				$icon_html .= '<img alt="" src="' . TPAY_PLUGIN_URL . '/assets/images/minicuotas.png" />';
			}
		}
		
		return $icon_html;
	}

}

FluidCheckout_PymntplPayPalWooCommerce::instance();
