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
		add_filter( 'fc_checkout_coupons_script_settings', array( $this, 'change_js_settings_coupon_codes' ), 300 );
	}


	
	/**
	 * Change JS settings to enable snackbar notices for coupon code messages.
	 */
	public function get_updated_js_settings_for_snackbar( $settings ) {
		$settings[ 'useGeneralNoticesSection' ] = 'yes';
		$settings[ 'suppressSuccessMessages' ] = 'no';

		return $settings;
	}

	/**
	 * Define whether changes to JS settings are needed.
	 */
	public function needs_js_settings_changes() {
		// Bail if not on checkout page
		if ( ! FluidCheckout_Steps::instance()->is_checkout_page_or_fragment() ) { return false; }

		// Get plugin settings
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}

		// Bail if plugin settings not available
		if ( ! is_array( $shopkit_settings ) ) { return false; }

		// Bail if not using snackbar notices
		$snackbar = isset( $shopkit_settings[ 'snackbar_notices' ] ) && true == $shopkit_settings[ 'snackbar_notices' ] ? true : false;
		if ( ! $snackbar ) { return false; }

		// Bail if not using snackbar notices on specific pages (cart or checkout)
		if ( ! isset( $shopkit_settings[ 'snackbar_checkout' ] ) || true != $shopkit_settings[ 'snackbar_checkout' ] ) { return false; }

		return true;
	}

	/**
	 * Change the JS settings for coupon codes.
	 *
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function change_js_settings_coupon_codes( $settings ) {
		// Bail if does not need JS settings changes
		if ( ! $this->needs_js_settings_changes() ) { return $settings; }

		// Change settings
		$settings = $this->get_updated_js_settings_for_snackbar( $settings );

		return $settings;
	}

}

FluidCheckout_KadenceWooExtras::instance();
