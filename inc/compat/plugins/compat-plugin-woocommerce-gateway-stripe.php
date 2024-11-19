<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: WooCommerce Stripe Gateway (by WooCommerce).
 */
class FluidCheckout_WooCommerceGatewayStripe extends FluidCheckout {

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
		// Styles
		// Applies only to Legacy Stripe Checkout experience.
		add_filter( 'wc_stripe_elements_styling', array( $this, 'change_stripe_fields_styles' ), 10 );

		// Styles
		// Applies only to the New Stripe Checkout experience, since version 
		add_filter( 'wc_stripe_upe_params', array( $this, 'change_stripe_appearance_parameters' ), 10 );
	}



	/**
	 * Change styles for the Stripe checkout fields for the Legacy Stripe Checkout experience.
	 *
	 * @param   array  $styles  The Stripe elements style properties.
	 */
	public function change_stripe_fields_styles( $styles ) {
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



	/**
	 * Change styles for the Stripe checkout fields for the New Stripe Checkout experience.
	 *
	 * @param   array  $stripe_params   The Stripe Javascript parameters.
	 * 
	 * @see  https://docs.stripe.com/elements/appearance-api
	 */
	public function change_stripe_appearance_parameters( $stripe_params ) {
		// Define default theme for the Stripe Checkout
		$stripe_theme = 'stripe';

		// Maybe set "dark mode" theme for the Stripe Checkout
		// when Fluid Checkout is set to "dark mode".
		if ( class_exists( 'FluidCheckout_DesignTemplates' ) && FluidCheckout_DesignTemplates::instance()->is_dark_mode_enabled() ) {
			$stripe_theme = 'night';
		}

		// Set the theme for the Stripe Checkout
		$stripe_params[ 'appearance' ] = (object) [
			'theme' => $stripe_theme,
		];

		return $stripe_params;
	 }


}

FluidCheckout_WooCommerceGatewayStripe::instance();
