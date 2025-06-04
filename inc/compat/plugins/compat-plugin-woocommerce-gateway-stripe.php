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
		// Settings
		add_filter( 'fc_integrations_settings_add', array( $this, 'add_settings' ), 10, 2 );

		// Styles
		// Applies only to NEW Stripe Checkout experience, based on the hook used.
		add_filter( 'wc_stripe_upe_params', array( $this, 'change_stripe_appearance_parameters' ), 10 );

		// Styles
		// Applies only to LEGACY Stripe Checkout experience, based on the hook used.
		add_filter( 'wc_stripe_elements_styling', array( $this, 'change_stripe_fields_styles_legacy' ), 10 );

		// Persisted data
		add_filter( 'fc_skip_checkout_field_value_from_session_or_posted_data', array( $this, 'maybe_skip_checkout_field_value_persisted_data' ), 10, 3 );
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings ) {
		// Add new settings
		$settings_new = array(
			array(
				'title' => __( 'WooCommerce Stripe Gateway', 'fluid-checkout' ),
				'type'  => 'title',
				'id'    => 'fc_integrations_woocommerce_gateway_stripe_gateway_stripe_options',
			),

			array(
				'title'           => __( 'Payment form', 'fluid-checkout' ),
				'desc'            => __( 'Apply styles to the Stripe payment form fields', 'fluid-checkout' ),
				'desc_tip'        => __( 'When enabled, Fluid Checkout will apply styles optimized for compatibility with the plugin. You may want to disable this if you are using custom styles for the Stripe payment form fields.', 'fluid-checkout' ),
				'id'              => 'fc_integration_woocommerce_gateway_stripe_apply_styles',
				'type'            => 'checkbox',
				'default'         => FluidCheckout_Settings::instance()->get_option_default( 'fc_integration_woocommerce_gateway_stripe_apply_styles' ),
				'autoload'        => false,
			),

			array(
				'type' => 'sectionend',
				'id'    => 'fc_integrations_woocommerce_gateway_stripe_gateway_stripe_options',
			),
		);

		$settings = array_merge( $settings, $settings_new );

		return $settings;
	}



	/**
	 * Maybe skip persisted data for the Stripe checkout fields.
	 * Fix the problem with the wrong address data being used when purchasing with Apple Pay or Google Pay from the WooCommerce Stripe Gateway plugin.
	 *
	 * @param   boolean  $should_skip  Whether to skip retrieving the field value from persisted data.
	 * @param   string   $input        Checkout field key (e.g. order_comments).
	 */
	public function maybe_skip_checkout_field_value_persisted_data( $should_skip, $input ) {
		// Bail if not WC AJAX request
		if ( ! array_key_exists( 'wc-ajax', $_GET ) ) { return $should_skip; }

		// Define AJAX requests that should skip persisted data
		$target_ajax_requests = array(
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
	 * Change styles for the Stripe checkout fields for the New Stripe Checkout experience.
	 *
	 * @param   array  $stripe_params   The Stripe Javascript parameters.
	 * 
	 * @see  https://docs.stripe.com/elements/appearance-api
	 */
	public function change_stripe_appearance_parameters( $stripe_params ) {
		// Bail if styles should not be applied
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_woocommerce_gateway_stripe_apply_styles' ) ) { return $stripe_params; }

		// Define default theme for the Stripe Checkout
		$stripe_theme = 'stripe';

		// Maybe set "dark mode" theme for the Stripe Checkout
		// when Fluid Checkout is set to "dark mode".
		if ( class_exists( 'FluidCheckout_DesignTemplates' ) && FluidCheckout_DesignTemplates::instance()->is_dark_mode_enabled() ) {
			$stripe_theme = 'night';
		}

		// Define appearance parameters for the Stripe Checkout
		$appearance_params = (object) [
			'theme' => $stripe_theme,
		];

		// Set the appearance parameters
		$stripe_params[ 'appearance' ] = $appearance_params;
		$stripe_params[ 'blocksAppearance' ] = $appearance_params;

		return $stripe_params;
	}

	/**
	 * Change styles for the Stripe checkout fields for the Legacy Stripe Checkout experience.
	 *
	 * @param   array  $styles  The Stripe elements style properties.
	 */
	public function change_stripe_fields_styles_legacy( $styles ) {
		// Bail if styles should not be applied
		if ( 'yes' !== FluidCheckout_Settings::instance()->get_option( 'fc_integration_woocommerce_gateway_stripe_apply_styles' ) ) { return $styles; }

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

FluidCheckout_WooCommerceGatewayStripe::instance();
