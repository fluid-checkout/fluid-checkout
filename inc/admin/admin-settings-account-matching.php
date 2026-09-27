<?php
/**
 * Fluid Checkout Account Matching Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_AccountMatching', false ) ) {
	FluidCheckout_Settings_AccountMatching::hooks();
	return;
}

/**
 * FluidCheckout_Settings_AccountMatching.
 */
class FluidCheckout_Settings_AccountMatching {

	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Sections
		add_filter( 'fc_admin_settings_sections', array( __CLASS__, 'add_sections' ), 10 );

		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add new sections to the Fluid Checkout admin settings tab.
	 *
	 * @param   array  $sections  Admin settings sections.
	 */
	public static function add_sections( $sections ) {
		$sections = array_merge( $sections, array(
			'account_matching' => __( 'Account Matching', 'fluid-checkout' ),
		) );

		return $sections;
	}



	/**
	 * Add new settings to the Fluid Checkout admin settings sections.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_settings( $settings, $current_section ) {
		if ( 'account_matching' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_account_matching_settings',
			array(
				array(
					'title' => __( 'Account Matching', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_pro_account_matching_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'account-matching' ),
				),

				array(
					'title'                 => __( 'Account matching', 'fluid-checkout' ),
					'desc'                  => __( 'Enable the account matching feature', 'fluid-checkout' ),
					'desc_tip'              => __( 'Associate the guest customer\'s orders with their existing account when an account already exists with the customer\'s contact details.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_account_matching',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_account_matching' ),
					'checkboxgroup'         => 'start',
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'desc'                  => __( 'Display message when an account exists with the email address provided', 'fluid-checkout' ),
					'desc_tip'              => __( 'Replaces the account creation fields with a notification and option to log in. In some contexts, it might be recommended to leave this option disabled to protect the privacy of customers.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_account_matching_display_account_exists_message',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_account_matching_display_account_exists_message' ),
					'checkboxgroup'         => '',
					'custom_attributes' => array(
						'data-conditional-id'    => 'fc_pro_enable_account_matching',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_pro_account_matching_options',
				),
			)
		);
	}

}

FluidCheckout_Settings_AccountMatching::hooks();
