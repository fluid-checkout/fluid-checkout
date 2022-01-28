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

			// Get available payment gateways
			$gateways = WC()->payment_gateways->get_available_payment_gateways();

			// Get plugin version
			$stripe_plugin_version = $this->get_plugin_version();

			// Maybe add actions
			if (
				'yes' === apply_filters( 'fc_woocommerce_gateway_stripe_show_buttons', 'yes' )
				&& is_array( WC_Stripe_Payment_Request::instance()->stripe_settings )
				&& array_key_exists( 'payment_request', WC_Stripe_Payment_Request::instance()->stripe_settings )
				&& 'yes' === WC_Stripe_Payment_Request::instance()->stripe_settings[ 'payment_request' ]
				&& isset( $gateways[ 'stripe' ] )
			) {
				// Remove actions
				remove_action( 'woocommerce_checkout_before_customer_details', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_html' ), 1 );
				remove_action( 'woocommerce_checkout_before_customer_details', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_separator_html' ), 2 );
				
				// Versions up to 5.4.*
				if ( version_compare( $stripe_plugin_version, '5.5.0', '<' ) ) {
					add_filter( 'wc_stripe_show_payment_request_on_checkout', '__return_true', 10 );
					add_action( 'fc_checkout_express_checkout', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_html' ), 10 );
				}
				// Versions 5.5.0+
				else if ( version_compare( $stripe_plugin_version, '5.5.0', '>=' ) ) {
					// Get Stripe button position setting
					$button_locations = WC_Stripe_Payment_Request::instance()->get_button_locations();

					// Check if button should be displayed on the checkout page
					if ( is_array( $button_locations ) && in_array( 'checkout', $button_locations ) ) {
						add_action( 'fc_checkout_express_checkout', array( WC_Stripe_Payment_Request::instance(), 'display_payment_request_button_html' ), 10 );
					}
				}
			}
		}
	}



	/**
	 * Get the plugin version number.
	 */
	public function get_plugin_version() {
		$stripe_plugin_file = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php';
		if ( file_exists( $stripe_plugin_file ) ) {
			return get_file_data( $stripe_plugin_file , ['Version' => 'Version'], 'plugin')['Version'];
		}

		return null;
	}



	/**
	 * Change styles for the Stripe credit card fields.
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


}

FluidCheckout_WooCommerceGatewayStripe::instance();
