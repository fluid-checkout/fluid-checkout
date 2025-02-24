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

		// Persisted data
		add_filter( 'fc_skip_checkout_field_value_from_session_or_posted_data', array( $this, 'maybe_skip_checkout_field_value_persisted_data' ), 10, 3 );
	}



	/**
	 * Maybe skip persisted data for the Stripe checkout fields.
	 *
	 * @param   [type]  $should_skip  [$should_skip description]
	 * @param   [type]  $input        [$input description]
	 *
	 * @return  [type]                [return description]
	 */
	public function maybe_skip_checkout_field_value_persisted_data( $should_skip, $input ) {
		// Bail if not WC AJAX request
		if ( ! array_key_exists( 'wc-ajax', $_GET ) ) { return $should_skip; }

		// Define AJAX requests that should skip persisted data
		$target_ajax_requests = array(
			'update_order_review',
			'wc_stripe_get_shipping_options',
			'wc_stripe_update_shipping_method',
			'wc_stripe_create_order',
		);

		// Get AJAX request type
		$wc_ajax = sanitize_text_field( wp_unslash( $_GET[ 'wc-ajax' ] ) );

		// Maybe skip persisted data
		if ( in_array( $wc_ajax, $target_ajax_requests ) ) {
			$should_skip = true;
		}

		return $should_skip;
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
