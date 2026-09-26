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
	 * Product page URL for the Address Book add-on.
	 */
	const PRODUCT_URL = 'https://fluidcheckout.com/fc-address-book/';



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

		// Address Book migration after the main Address Book options (locked until the add-on replaces these settings)
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_migration_settings' ), 30, 2 );
	}



	/**
	 * Get the promotional settings card shown at the top of the Address Book tab when the add-on is not active.
	 */
	public function get_promo_settings() {
		// Bail if the add-on feature is already unlocked
		if ( FluidCheckout_Admin_Settings_Access::instance()->is_unlocked( self::FEATURE ) ) { return array(); }

		return array(
			array(
				'title'            => __( 'Address Book', 'fluid-checkout' ),
				'type'             => 'fc_promo',
				'id'               => 'fc_pro_address_book_promo',
				'is_card'          => true,
				'promo'            => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-book-promo', self::PRODUCT_URL, self::FEATURE ),
				'tagline'          => __( 'Let customers save multiple shipping and billing addresses and pick the right one at checkout.', 'fluid-checkout' ),
				'features'         => array(
					__( 'Save multiple shipping and billing addresses on the customer account.', 'fluid-checkout' ),
					__( 'Choose which address to use at checkout and on the cart page.', 'fluid-checkout' ),
					__( 'Optional private labels so customers can tell addresses apart (for example, "Mom\'s House").', 'fluid-checkout' ),
					__( 'Migrate existing WooCommerce shipping and billing addresses into the address book.', 'fluid-checkout' ),
				),
				'learn_more_url'   => add_query_arg(
					array(
						'mtm_campaign' => 'addons',
						'mtm_kwd'      => 'address-book-promo-learn-more',
						'mtm_source'   => 'lite-plugin',
					),
					self::PRODUCT_URL
				),
				'learn_more_label' => __( 'Learn more', 'fluid-checkout' ),
				'plugin_file'      => 'fc-address-book/fc-address-book.php',
				'plugin_slug'      => 'fc-address-book',
				'purchase_url'     => add_query_arg(
					array(
						'mtm_campaign' => 'addons',
						'mtm_kwd'      => 'address-book-promo-purchase',
						'mtm_source'   => 'lite-plugin',
					),
					self::PRODUCT_URL
				),
				'purchase_price'   => '59 EUR',
			),
		);
	}



	/**
	 * Get the locked placeholder settings for Address Book migration on the Address Book tab.
	 */
	public function get_locked_migration_settings() {
		return array(
			array(
				'title'    => __( 'Address Book Migration', 'fluid-checkout' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'fc_pro_address_book_migration_options',
				'promo'    => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-book-migration', self::PRODUCT_URL, self::FEATURE ),
			),

			array(
				'title'    => __( 'WooCommerce Addresses', 'fluid-checkout' ),
				'desc'     => '',
				'desc_tip'          => __( 'Copy existing shipping and billing addresses from WooCommerce into the customers\' address book.', 'fluid-checkout' ),
				'id'       => 'fc_pro_address_book_migration',
				'type'     => 'fc_address_book_migration',
				'autoload' => false,
				'disabled' => true,
				'requires' => self::FEATURE,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'fc_pro_address_book_migration_options',
			),
		);
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
			array_merge(
				$this->get_promo_settings(),
				array(
					array(
						'title'             => __( 'Address Book', 'fluid-checkout' ),
						'type'              => 'title',
						'desc'              => '',
						'id'                => 'fc_pro_address_book_options',
						'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-book', self::PRODUCT_URL, self::FEATURE ),
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
						'type'              => 'sectionend',
						'id'                => 'fc_pro_address_book_options',
					),
				)
			)
		);

		return $settings;
	}

	/**
	 * Add Address Book migration settings to the Address Book tab.
	 * The Address Book add-on replaces the locked placeholders with the migration controls.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_migration_settings( $settings, $current_section ) {
		// Bail if not on the address book section
		if ( 'address_book' !== $current_section ) { return $settings; }

		/**
		 * Filter the Address Book migration settings displayed on the Address Book tab.
		 * The Address Book add-on replaces the locked placeholder settings with its own settings.
		 *
		 * @param  array  $settings  Locked placeholder settings.
		 */
		$migration_settings = apply_filters( 'fc_admin_address_book_migration_settings', $this->get_locked_migration_settings() );

		// Bail if migration settings are not valid
		if ( ! is_array( $migration_settings ) ) { return $settings; }

		return array_merge( is_array( $settings ) ? $settings : array(), $migration_settings );
	}

}

return new WC_Settings_FluidCheckout_AddressBook_Settings();
