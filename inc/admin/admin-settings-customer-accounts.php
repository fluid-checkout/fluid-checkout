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
			'customer_accounts' => __( 'Customer accounts', 'fluid-checkout' ),
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
		if ( 'customer_accounts' !== $current_section ) { return $settings; }

		// Define conditionals for the username and password fields
		$account_fields_conditional_id = 'woocommerce_enable_signup_and_login_from_checkout|woocommerce_enable_myaccount_registration';
		if ( class_exists( 'FluidCheckout_Admin_Settings_Access' ) && FluidCheckout_Admin_Settings_Access::instance()->is_unlocked( 'pro' ) ) {
			$account_fields_conditional_id .= '|fc_pro_enable_delayed_account_creation';
		}

		/**
		 * Filters the Customer accounts settings.
		 *
		 * @since 5.0.0
		 *
		 * @param array $settings Array with all settings for the current section.
		 */
		return apply_filters(
			'fc_customer_accounts_settings',
			array(

				array(
					'title' => __( 'Guest checkout', 'fluid-checkout' ),
					'type'  => 'title',
					'desc'  => __( 'Guest checkout, login, and account creation options from the WooCommerce Accounts settings page. They are managed here for your convenience, and so you can use different values per settings profile.', 'fluid-checkout' ),
					'id'    => 'fc_guest_checkout_options',
				),

				array(
					'title'                 => __( 'Guest checkout', 'fluid-checkout' ),
					'desc'                  => __( 'Allow customers to checkout without an account', 'fluid-checkout' ),
					'desc_tip'              => __( '<strong>Recommended</strong> so customers are not asked to create an account or log in during checkout, which can lower conversion rates.', 'fluid-checkout' ) . ' <br><br>' . __( 'When disabled, customers must log in or create an account to complete the purchase.', 'fluid-checkout' ),
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
					'desc'                  => __( 'Allow customers to create an account from the My account page', 'fluid-checkout' ),
					'desc_tip'              => __( 'Customers can register a new account from the My account page.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_enable_myaccount_registration',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_enable_myaccount_registration' ),
					'autoload'              => false,
				),
				array(
					'title'                 => __( 'Username', 'fluid-checkout' ),
					'desc'                  => __( 'Generate from customer\'s name', 'fluid-checkout' ),
					'desc_tip'              => __( '<strong>Recommended</strong> so customers are not asked to choose a username during checkout, which can lower conversion rates.', 'fluid-checkout' ) . ' <br><br>' . __( 'A username is generated from the customer name, or from the email address when a name cannot be used.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_registration_generate_username',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_registration_generate_username' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => $account_fields_conditional_id,
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
				),
				array(
					'title'                 => __( 'Password', 'fluid-checkout' ),
					'desc'                  => __( 'Send password setup link', 'fluid-checkout' ),
					'desc_tip'              => __( '<strong>Recommended</strong> so customers are not asked to set a password during checkout, which can lower conversion rates.', 'fluid-checkout' ) . ' <br><br>' . __( 'Customers receive an email to create their password securely.', 'fluid-checkout' ),
					'id'                    => 'woocommerce_registration_generate_password',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'woocommerce_registration_generate_password' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => $account_fields_conditional_id,
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
					'desc'  => __( 'Let registered customers complete checkout without logging in and associate the order with their existing account when contact details match.', 'fluid-checkout' ),
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
					'desc'  => __( 'Let customers create an account after placing their order, from the thank you page.', 'fluid-checkout' ),
					'id'    => 'fc_pro_delayed_account_creation_options',
					'promo' => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'delayed-account-creation' ),
				),

				array(
					'type'              => 'fc_paragraph',
					'desc'              => __( 'Options in this section are available when guest checkout and account matching are enabled. Enable both features above to configure delayed account creation.', 'fluid-checkout' ),
					'id'                => 'fc_pro_delayed_account_creation_prerequisites_notice_guest',
					'custom_attributes' => array(
						'data-conditional-id'    => 'woocommerce_enable_guest_checkout',
						'data-conditional-value' => 'no',
					),
				),

				array(
					'type'              => 'fc_paragraph',
					'desc'              => __( 'Options in this section are available when guest checkout and account matching are enabled. Enable both features above to configure delayed account creation.', 'fluid-checkout' ),
					'id'                => 'fc_pro_delayed_account_creation_prerequisites_notice_matching',
					'custom_attributes' => array(
						'data-conditional-id'    => 'woocommerce_enable_guest_checkout,fc_pro_enable_account_matching',
						'data-conditional-value' => 'yes,no',
					),
				),

				array(
					'title'                 => __( 'Account creation', 'fluid-checkout' ),
					'desc'                  => __( 'Enable delayed account creation', 'fluid-checkout' ),
					'desc_tip'              => __( 'Enable delayed account creation when both guest checkout and account matching are enabled.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_enable_delayed_account_creation',
					'type'                  => 'checkbox',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_delayed_account_creation' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'woocommerce_enable_guest_checkout,fc_pro_enable_account_matching',
						'data-conditional-value' => 'yes,yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Section position', 'fluid-checkout' ),
					'desc_tip'              => __( 'Choose in which position to display the section.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_delayed_account_creation_section_position',
					'type'                  => 'fc_select',
					'options'               => array(
						'after_order_overview'         => __( 'After the order overview', 'fluid-checkout' ),
						'before_customer_details'      => __( 'Before customer details section', 'fluid-checkout' ),
						'after_customer_details'       => __( 'After customer details section', 'fluid-checkout' ),
						'on_sidebar'                   => __( 'On the sidebar', 'fluid-checkout' ),
					),
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_delayed_account_creation_section_position' ),
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_pro_enable_delayed_account_creation',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'title'                 => __( 'Account creation benefits', 'fluid-checkout' ),
					'desc_tip'              => __( 'Leaving the field empty will display the default text.', 'fluid-checkout' ),
					'id'                    => 'fc_pro_delayed_account_creation_benefits',
					'type'                  => 'fc_textarea',
					'default'               => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_delayed_account_creation_benefits' ),
					'placeholder'           => _x( "✔ Checkout faster next time\n✔ Manage your payment preferences\n✔ View order status and history", 'Delayed account creation benefits placeholder text', 'fluid-checkout' ),
					'css'                   => 'width:100%; min-height: 80px;',
					'custom_attributes'     => array(
						'data-conditional-id'    => 'fc_pro_enable_delayed_account_creation',
						'data-conditional-value' => 'yes',
					),
					'autoload'              => false,
					'disabled'              => true,
					'requires'              => 'pro',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'fc_pro_delayed_account_creation_options',
				),

			)
		);
	}

}

FluidCheckout_Settings_AccountMatching::hooks();
