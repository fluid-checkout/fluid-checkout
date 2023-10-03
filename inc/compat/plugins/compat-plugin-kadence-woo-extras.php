<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Kadence Shop Kit (by Kadence WP).
 */
class FluidCheckout_KadenceWooExtras extends FluidCheckout {

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
		// JS settings object
		add_filter( 'fc_js_settings', array( $this, 'add_js_settings' ), 300 );
	}



	/**
	 * Add settings to the plugin settings JS object.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_js_settings( $settings ) {
		// Get plugin settings
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}

		// Bail if plugin settings not available
		if ( ! is_array( $shopkit_settings ) ) { return $settings; }

		// Bail if not using snackbar notices
		$snackbar = isset( $shopkit_settings[ 'snackbar_notices' ] ) && true == $shopkit_settings[ 'snackbar_notices' ] ? true : false;
		if ( ! $snackbar ) { return $settings; }

		// Bail if not using snackbar notices on specific pages (cart or checkout)
		if ( 
			( FluidCheckout_Steps::instance()->is_cart_page_or_fragment() && ( ! isset( $shopkit_settings[ 'snackbar_cart' ] ) || true != $shopkit_settings[ 'snackbar_cart' ] ) )
			|| ( FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() && ( ! isset( $shopkit_settings[ 'snackbar_checkout' ] ) || true != $shopkit_settings[ 'snackbar_checkout' ] ) )
			) {
			return $settings;
		}

		// Define new values for coupon code settings
		$new_settings_coupons = array(
			'useGeneralNoticesSection' => 'yes',
			'suppressSuccessMessages' => 'no',
		);

		// Define new values for cart update settings
		$new_settings_cart_update = array(
			'scrollToNoticesEnabled' => 'no',
		);

		// Update settings
		$settings[ 'checkoutCoupons' ] = array_merge( $settings[ 'checkoutCoupons' ], $new_settings_coupons );
		$settings[ 'cartUpdate' ] = array_merge( $settings[ 'cartUpdate' ], $new_settings_cart_update );

		return $settings;
	}

}

FluidCheckout_KadenceWooExtras::instance();
