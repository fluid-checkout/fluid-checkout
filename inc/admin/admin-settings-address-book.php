<?php
/**
 * Fluid Checkout Address Book Settings
 *
 * @package fluid-checkout
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_AddressBook_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_AddressBook_Settings();
}

/**
 * WC_Settings_FluidCheckout_AddressBook_Settings.
 * Settings are locked until the Address Book add-on unlocks the feature `address_book`.
 */
class WC_Settings_FluidCheckout_AddressBook_Settings extends WC_Settings_Page {

	/**
	 * Feature slug used to lock and unlock the settings.
	 */
	const FEATURE = 'address_book';



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->id = 'fc_checkout';
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Settings
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_settings' ), 10, 2 );
	}



	/**
	 * Add the Address Book settings.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		// Bail if not on the address book section
		if ( 'address_book' !== $current_section ) { return $settings; }

		$settings = apply_filters(
			'fc_pro_address_book_settings',
			array(
				array(
					'title'             => __( 'Address Book', 'fluid-checkout' ),
					'type'              => 'title',
					'desc'              => '',
					'id'                => 'fc_pro_address_book_options',
				),

				array(
					'desc'              => FluidCheckout_Admin::instance()->get_addon_locked_notice_html( __( 'Address Book', 'fluid-checkout' ), 'https://fluidcheckout.com/fc-address-book/?mtm_campaign=addons&mtm_kwd=fc-adb-settings&mtm_source=lite-plugin' ),
					'id'                => 'fc_pro_address_book_locked_notice',
					'type'              => 'fc_paragraph',
					'requires'          => self::FEATURE,
					'locked_only'       => true,
				),

				array(
					'title'             => __( 'Address book', 'fluid-checkout' ),
					'desc'              => __( 'Enable the address book feature', 'fluid-checkout' ),
					'desc_tip'          => __( 'Allow customers to save multiple addresses and define the default address to be used for shipping and billing.', 'fluid-checkout' ),
					'id'                => 'fc_pro_enable_address_book',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_address_book' ),
					'checkboxgroup'     => 'start',
					'show_if_checked'   => 'option',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),
				array(
					'desc'              => __( 'Allow customers to add a custom label for each address', 'fluid-checkout' ),
					'desc_tip'          => __( 'Address labels make it easier to for customers to distinguish between their saved addresses (ie.: "Mom\'s House"). The address labels are private to the customers, and will not be displayed or printed on the order details page or invoices.', 'fluid-checkout' ),
					'id'                => 'fc_pro_enable_address_book_address_label',
					'type'              => 'checkbox',
					'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_address_book_address_label' ),
					'checkboxgroup'     => '',
					'show_if_checked'   => 'yes',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'title'             => __( 'Address book migration', 'fluid-checkout' ),
					'desc'              => __( 'Copy existing shipping and billing addresses into the customers\' address book. <br/>Customers that already have an address book entry will be skipped, even when they do not have any address saved to their account. <br/>You may leave this page while the migration is running and it will continue in the background. <br/><span style="color: #D21F26;"><strong>CAUTION: Please take a full backup of your website before running the migration process.</strong> <br>Once a customer is marked as migrated they cannot be migrated again, even when they have no addresses saved to their account. <br>In case you decide to disable the Address Book feature after customer\'s addresses have been migrated or after customers have saved any new addresses, the shipping and billing addresses used on their last order will be still be available on their account in the WooCommerce way (only one address for shipping and another for billing).</span>', 'fluid-checkout' ),
					'id'                => 'fc_pro_address_book_migration',
					'type'              => 'fc_address_book_migration',
					'autoload'          => false,
					'disabled'          => true,
					'requires'          => self::FEATURE,
				),

				array(
					'type'              => 'sectionend',
					'id'                => 'fc_pro_address_book_options',
				),
			)
		);

		return $settings;
	}

}

return new WC_Settings_FluidCheckout_AddressBook_Settings();
