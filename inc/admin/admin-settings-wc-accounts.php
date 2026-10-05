<?php
/**
 * WooCommerce Accounts Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_WCAccounts', false ) ) {
	FluidCheckout_Settings_WCAccounts::hooks();
	return;
}

/**
 * FluidCheckout_Settings_WCAccounts.
 */
class FluidCheckout_Settings_WCAccounts {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// WooCommerce Accounts Settings
		add_filter( 'woocommerce_get_settings_account', array( __CLASS__, 'change_account_settings_args' ), 100, 2 );
	}



	/**
	 * Option IDs managed on the Fluid Checkout Customer accounts settings page.
	 */
	public static function get_managed_option_ids() {
		return array(
			'woocommerce_enable_guest_checkout',
			'woocommerce_enable_checkout_login_reminder',
			'woocommerce_enable_signup_and_login_from_checkout',
			'woocommerce_registration_generate_username',
			'woocommerce_registration_generate_password',
		);
	}

	/**
	 * Disable account options managed in Fluid Checkout and point merchants there.
	 *
	 * @param  array   $settings         WooCommerce account settings fields.
	 * @param  string  $current_section  Current settings section ID.
	 */
	public static function change_account_settings_args( $settings, $current_section ) {
		$managed_option_ids = self::get_managed_option_ids();
		$managed_notice = __( 'This option is managed in Fluid Checkout. Change it under Fluid Checkout > Customer accounts.', 'fluid-checkout' );

		// Iterate account settings
		foreach ( $settings as $key => $setting_args ) {
			// Skip settings without an ID or not managed by Fluid Checkout
			if ( ! array_key_exists( 'id', $setting_args ) || ! in_array( $setting_args[ 'id' ], $managed_option_ids, true ) ) { continue; }

			// Disable the field and explain where to manage it
			if ( ! isset( $setting_args[ 'custom_attributes' ] ) || ! is_array( $setting_args[ 'custom_attributes' ] ) ) {
				$setting_args[ 'custom_attributes' ] = array();
			}
			$setting_args[ 'custom_attributes' ][ 'disabled' ] = true;
			$setting_args[ 'desc_tip' ] = $managed_notice;
			$settings[ $key ] = $setting_args;
		}

		return $settings;
	}

}

FluidCheckout_Settings_WCAccounts::hooks();
