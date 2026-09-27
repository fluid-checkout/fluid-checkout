<?php
/**
 * WooCommerce Checkout Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_WCShipping', false ) ) {
	FluidCheckout_Settings_WCShipping::hooks();
	return;
}

/**
 * FluidCheckout_Settings_WCShipping.
 */
class FluidCheckout_Settings_WCShipping {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// WooCommerce Shipping Settings
		add_filter( 'woocommerce_get_settings_shipping', array( __CLASS__, 'change_shipping_destination_settings_args' ), 100, 2 );
	}



	public static function change_shipping_destination_settings_args( $settings, $current_section ) {
		// Bail if not on shipping options section
		if ( $current_section != 'options' ) { return $settings; }

		// Iterate shipping settings
		foreach ( $settings as $key => $setting_args ) {
			// Skip settings other than shipping destination
			if ( ! array_key_exists( 'id', $setting_args ) ||  $setting_args[ 'id' ] !== 'woocommerce_ship_to_destination' ) { continue; }

			// Disable shipping destination options and change tooltip/description explaining why it was disabled
			$setting_args[ 'custom_attributes' ]['disabled'] = true;
			$setting_args[ 'desc' ] = __( 'The shipping destination is always set to "Default to customer shipping address" when Fluid Checkout is activated. Customers can still provide different shipping and billing addresses during checkout, an option for setting the default billing address to be the same as the shipping address is available at WooCommerce > Settings > Fluid Checkout.', 'fluid-checkout' );
			$settings[ $key ] = $setting_args;
		}

		return $settings;
	}

}

FluidCheckout_Settings_WCShipping::hooks();
