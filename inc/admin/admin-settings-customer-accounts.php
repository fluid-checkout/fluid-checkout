<?php
/**
 * Fluid Checkout Customer Accounts Settings
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
			'account_matching' => __( 'Customer accounts', 'fluid-checkout' ),
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
		// Bail if not the customer accounts section
		if ( 'account_matching' !== $current_section ) { return $settings; }

		return apply_filters(
			'fc_account_matching_settings',
			array(

				array(
					'title' => __( 'Guest checkout', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'fc_guest_checkout_options',
				),

				array(
					'title'                 => __( 'Guest checkout', 'fluid-checkout' ),
					'desc'                  => __( 'Allow customers to checkout without an account', 'fluid-checkout' ),
					'desc_tip'              => __( 'When disabled, customers must create an account or log in to complete the purchase.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_enable_guest_checkout',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_enable_guest_checkout' ),
					'autoload'              => false,
				),

				array(
					'title'                 => __( 'Login at checkout', 'fluid-checkout' ),
					'desc'                  => __( 'Enable log-in during checkout', 'fluid-checkout' ),
					'desc_tip'              => __( 'Show a link for returning customers to log in from the checkout page.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_enable_checkout_login_reminder',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_enable_checkout_login_reminder' ),
					'autoload'              => false,
				),

				array(
					'title'                 => __( 'Account creation', 'fluid-checkout' ),
					'desc'                  => __( 'Allow customers to create an account during checkout', 'fluid-checkout' ),
					'desc_tip'              => __( 'Customers can create an account before placing their order.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_enable_signup_and_login_from_checkout',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_enable_signup_and_login_from_checkout' ),
					'autoload'              => false,
				),
				array(
					'title'                 => __( 'Username', 'fluid-checkout' ),
					'desc'                  => __( 'Generate username from customer name', 'fluid-checkout' ),
					'desc_tip'              => __( 'Generate a username using the first and/or last name. If neither is usable, the email address is used. When disabled, customers set a username during account creation.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_registration_generate_username',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_registration_generate_username' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'woocommerce_enable_signup_and_login_from_checkout',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
				),
				array(
					'title'                 => __( 'Password', 'fluid-checkout' ),
					'desc'                  => __( 'Send password setup link', 'fluid-checkout' ),
					'desc_tip'              => __( 'New customers receive an email to set up their password. When disabled, customers set a password during account creation.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_registration_generate_password',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_registration_generate_password' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'woocommerce_enable_signup_and_login_from_checkout',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_guest_checkout_options',
				),



				array(
					'title' => __( 'Account matching', 'fluid-checkout' ),
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
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),
				array(
					'title'                 => __( 'Existing account message', 'fluid-checkout' ),
					'desc'                  => __( 'Display message when an account exists with the email address provided', 'fluid-checkout' ),
					'desc_tip'              => __( 'Replaces the account creation fields with a notification and option to log in. In some contexts, it might be recommended to leave this option disabled to protect the privacy of customers.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_account_matching_display_account_exists_message',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_account_matching_display_account_exists_message' ),
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



				array(
					'title' => __( 'Delayed account creation', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => __( 'Let customers create an account after placing their order, from the order confirmation page. Settings for this feature will be available here when it is included in this version.', 'fluid-checkout' ),
					'id'    => 'fc_delayed_account_creation_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'delayed-account-creation' ),
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_delayed_account_creation_options',
				),

			)
		);
	}

}

FluidCheckout_Settings_AccountMatching::hooks();
