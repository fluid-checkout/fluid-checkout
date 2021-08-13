<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Stripe Gateway (by WooCommerce).
 */
class FluidCheckout_PaymentMethodStripe extends FluidCheckout {

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
		// Late hooks
		add_action( 'init', array( $this, 'late_hooks' ), 100 );

		// Styles
		add_filter( 'wc_stripe_elements_styling', array( $this, 'change_stripe_fields_styles' ), 10 );
	}
	
	
	
	/**
	 * Add or remove late hooks.
	 */
	public function late_hooks() {
		if ( class_exists( 'WC_Stripe_Payment_Request' ) ) {
			// Remove actions
			remove_action( 'woocommerce_checkout_before_customer_details', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_html' ), 1 );
			remove_action( 'woocommerce_checkout_before_customer_details', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_separator_html' ), 2 );

			// Add actions
			if ( 'yes' === apply_filters( 'fc_woocommerce_gateway_stripe_show_buttons', 'yes' ) && is_array( WC_Stripe_Payment_Request::instance()->stripe_settings ) && array_key_exists( 'payment_request', WC_Stripe_Payment_Request::instance()->stripe_settings ) && 'yes' === WC_Stripe_Payment_Request::instance()->stripe_settings[ 'payment_request' ] && WC_Stripe_Payment_Request::instance()->should_show_payment_request_button() ) {
				add_filter( 'wc_stripe_show_payment_request_on_checkout', '__return_true', 10 );
				add_action( 'fc_checkout_express_checkout', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_html' ), 10 );
			}
		}
	}



	/**
	 * Change styles for the Stripe credit card fields.
	 *
	 * @param   array  $styles  The Stripe elements style properties.
	 */
	public function change_stripe_fields_styles($styles) {
		$styles = array(
			// Notice: Need to pass the default styles values again for `color`, `iconColor` and `::placeholder` because once
			// the styles object is changed Stripe will ignore its defaults and use only what is provided.
			// @see https://docs.woocommerce.com/document/stripe-styling-fields/
			'base' => array(
				'iconColor'     => '#666EE8',
				'color'         => '#31325F',
				'lineHeight'    => '2', // Makes fields taller and easier to see
				'fontSize'      => '16px', // Should be at least 16px to prevent auto-zoom issues on Safari Mobile
				'::placeholder' => array(
					'color' => '#CFD7E0',
				),
			),
		);
		return $styles;
	}


}

FluidCheckout_PaymentMethodStripe::instance();
