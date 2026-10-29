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

		$settings_url = class_exists( 'FluidCheckout_Admin_Settings_Page' ) ? FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'checkout' ) : '';
		$managed_notice = ! empty( $settings_url )
			? sprintf(
				/* translators: %s: URL to the Fluid Checkout checkout settings page. */
				__( 'This option is managed in Fluid Checkout. The shipping destination is always set to "Default to customer shipping address" when Fluid Checkout is activated. Customers can still provide different shipping and billing addresses during checkout.<br><br>An option for setting the default billing address to be the same as the shipping address is available in the <a href="%s">Fluid Checkout settings</a>.', 'fluid-checkout' ),
				esc_url( $settings_url )
			)
			: __( 'This option is managed in Fluid Checkout. The shipping destination is always set to "Default to customer shipping address" when Fluid Checkout is activated. Customers can still provide different shipping and billing addresses during checkout.<br><br>An option for setting the default billing address to be the same as the shipping address is available at WooCommerce > Settings > Fluid Checkout.', 'fluid-checkout' );

		// Iterate shipping settings
		foreach ( $settings as $key => $setting_args ) {
			// Skip settings other than shipping destination
			if ( ! array_key_exists( 'id', $setting_args ) ||  $setting_args[ 'id' ] !== 'woocommerce_ship_to_destination' ) { continue; }

			// Disable shipping destination options and change tooltip/description explaining why it was disabled
			if ( ! isset( $setting_args[ 'custom_attributes' ] ) || ! is_array( $setting_args[ 'custom_attributes' ] ) ) {
				$setting_args[ 'custom_attributes' ] = array();
			}
			$setting_args[ 'custom_attributes' ][ 'disabled' ] = true;
			// Use `desc` (not help tip) so the settings page link is clickable
			$setting_args[ 'desc_tip' ] = false;
			$setting_args[ 'desc' ] = $managed_notice;
			$settings[ $key ] = $setting_args;
		}

		return $settings;
	}

}

FluidCheckout_Settings_WCShipping::hooks();
