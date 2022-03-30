<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: MailerLite - WooCommerce integration.
 */
class FluidCheckout_WooMailerLite extends FluidCheckout {

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
		// Bail if woo mailer lite class not exits
		if ( ! class_exists( 'Woo_Mailerlite' ) || ! function_exists( 'woo_ml_get_option' ) ) { return; }

		$checkout          = woo_ml_get_option( 'checkout', 'no' );
		$checkout_position = woo_ml_get_option( 'checkout_position', 'checkout_billing' );

		if ( 'yes' === $checkout ) {
			remove_action( 'woocommerce_' . $checkout_position, 'woo_ml_checkout_label', 20 );

			$checkout_new_position = 'fc_checkout_contact_after_fields';

			if ( 'checkout_billing' === $checkout_position ) {
				$checkout_new_position = 'fc_before_checkout_billing_only_form';
			} else if ( 'checkout_shipping' === $checkout_position ) {
				$checkout_new_position = 'woocommerce_after_order_notes';
			} else if ( 'review_order_before_submit' === $checkout_position ) {
				$checkout_new_position = 'woocommerce_review_order_before_submit';
			}

			add_action( $checkout_new_position, 'woo_ml_checkout_label', 20 );
		}
	}

}

FluidCheckout_WooMailerLite::instance();
