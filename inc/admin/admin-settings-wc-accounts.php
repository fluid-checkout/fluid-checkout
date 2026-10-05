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
		// Run after WooCommerce prints its Accounts settings footer script (default priority 10)
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'output_force_disabled_script' ), 100 );
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
		$settings_url = class_exists( 'FluidCheckout_Admin_Settings_Page' ) ? FluidCheckout_Admin_Settings_Page::instance()->get_settings_url( 'account_matching' ) : '';
		$managed_notice = ! empty( $settings_url )
			? sprintf(
				/* translators: %s: URL to the Fluid Checkout Customer accounts settings page. */
				__( 'This option is managed in Fluid Checkout. <a href="%s">Change it in Customer accounts settings</a>.', 'fluid-checkout' ),
				esc_url( $settings_url )
			)
			: __( 'This option is managed in Fluid Checkout. Change it under Fluid Checkout > Customer accounts.', 'fluid-checkout' );

		// Iterate account settings
		foreach ( $settings as $key => $setting_args ) {
			// Skip settings without an ID or not managed by Fluid Checkout
			if ( ! array_key_exists( 'id', $setting_args ) || ! in_array( $setting_args[ 'id' ], $managed_option_ids, true ) ) { continue; }

			// Disable the field and explain where to manage it
			if ( ! isset( $setting_args[ 'custom_attributes' ] ) || ! is_array( $setting_args[ 'custom_attributes' ] ) ) {
				$setting_args[ 'custom_attributes' ] = array();
			}
			$setting_args[ 'custom_attributes' ][ 'disabled' ] = true;
			$setting_args[ 'disabled' ] = true;
			$setting_args[ 'desc_tip' ] = $managed_notice;
			$settings[ $key ] = $setting_args;
		}

		return $settings;
	}

	/**
	 * Force managed account fields to stay disabled on the WooCommerce Accounts settings page.
	 * WooCommerce toggles username/password options in JS based on other account creation checkboxes.
	 */
	public static function output_force_disabled_script() {
		// Bail if not in the admin area
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) { return; }

		$screen = get_current_screen();

		// Bail if not on the WooCommerce settings screen
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) { return; }

		// Bail if not on the Accounts tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ 'tab' ] ) || 'account' !== sanitize_title( wp_unslash( $_GET[ 'tab' ] ) ) ) { return; }

		$managed_option_ids = wp_json_encode( self::get_managed_option_ids() );
		?>
		<script>
		( function() {
			var managedOptionIds = <?php echo $managed_option_ids; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded option IDs. ?>;
			var watchCheckboxIds = [
				'woocommerce_enable_signup_and_login_from_checkout',
				'woocommerce_enable_myaccount_registration',
				'woocommerce_enable_delayed_account_creation',
				'woocommerce_enable_signup_from_checkout_for_subscriptions',
				'woocommerce_enable_guest_checkout'
			];

			var forceManagedFieldsDisabled = function() {
				managedOptionIds.forEach( function( optionId ) {
					var input = document.getElementById( optionId );
					if ( input ) {
						input.disabled = true;
					}
				} );
			};

			// WooCommerce already ran its Accounts script above; undo its enabled state
			forceManagedFieldsDisabled();

			// Register after WooCommerce so our handlers run last on `change`
			watchCheckboxIds.forEach( function( checkboxId ) {
				var checkbox = document.getElementById( checkboxId );
				if ( checkbox ) {
					checkbox.addEventListener( 'change', forceManagedFieldsDisabled );
				}
			} );
		} )();
		</script>
		<?php
	}

}

FluidCheckout_Settings_WCAccounts::hooks();
