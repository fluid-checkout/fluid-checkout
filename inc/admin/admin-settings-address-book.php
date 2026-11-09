<?php
/**
 * Fluid Checkout Address Book Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_AddressBook', false ) ) {
	FluidCheckout_Settings_AddressBook::hooks();
	return;
}

/**
 * FluidCheckout_Settings_AddressBook.
 * Settings are locked until Fluid Checkout PRO unlocks the feature `pro`.
 */
class FluidCheckout_Settings_AddressBook {

	/**
	 * Feature slug used to lock and unlock the settings.
	 */
	const FEATURE = 'pro';



	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );

		// Address Book migration after the main Address Book options (locked until the add-on replaces these settings)
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_migration_settings' ), 30, 2 );

		// Settings page preview
		add_filter( 'fc_admin_settings_preview_tab_modes', array( __CLASS__, 'set_settings_preview_tab_mode' ), 10 );
	}



	/**
	 * Use logged-in preview mode on the Address book settings tab.
	 *
	 * @param   array  $tab_modes  Preview mode by settings tab slug.
	 *
	 * @return  array  Preview mode by settings tab slug.
	 */
	public static function set_settings_preview_tab_mode( $tab_modes ) {
		if ( ! class_exists( 'FluidCheckout_Admin_Preview', false ) ) { return $tab_modes; }

		$tab_modes['address_book'] = FluidCheckout_Admin_Preview::MODE_LOGGED_IN;

		return $tab_modes;
	}



	/**
	 * Get the locked placeholder settings for Address Book migration on the Address Book tab.
	 */
	public static function get_locked_migration_settings() {
		return array(
			array(
				'title'    => __( 'Address book migration', 'fluid-checkout' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'fc_pro_address_book_migration_options',
				'promo'    => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'address-book-migration' ),
			),

			array(
				'title'    => __( 'WooCommerce addresses', 'fluid-checkout' ),
				'desc'     => __( 'Copy existing shipping and billing addresses from WooCommerce into the customers\' address book.', 'fluid-checkout' ),
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
	public static function add_settings( $settings, $current_section ) {
		// Bail if not on the address book section
		if ( 'address_book' !== $current_section ) { return $settings; }

		/**
		 * Filters the Address book settings.
		 *
		 * @since 5.0.0
		 */
		$settings = apply_filters(
			'fc_pro_address_book_settings',
			array(
					array(
						'title'             => __( 'Address book', 'fluid-checkout' ),
						'type'              => 'title',
						'desc'              => '',
						'id'                => 'fc_pro_address_book_options',
						'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'address-book' ),
						'docs'              => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/fc-address-book/' ),
					),

					array(
						'title'             => __( 'Address book', 'fluid-checkout' ),
						'desc'              => __( 'Enable the address book feature', 'fluid-checkout' ),
						'desc_tip'          => __( 'Allow customers to save multiple addresses and define the default address to be used for shipping and billing.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_address_book',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_address_book' ),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => self::FEATURE,
					),
					array(
						'title'             => __( 'Address labels', 'fluid-checkout' ),
						'desc'              => __( 'Allow customers to add a custom label for each address', 'fluid-checkout' ),
						'desc_tip'          => __( 'Address labels make it easier to for customers to distinguish between their saved addresses (ie.: "Mom\'s House"). The address labels are private to the customers, and will not be displayed or printed on the order details page or invoices.', 'fluid-checkout' ),
						'id'                => 'fc_pro_enable_address_book_address_label',
						'type'              => 'checkbox',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_enable_address_book_address_label' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_address_book',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => self::FEATURE,
					),
					array(
						'title'             => __( 'Display layout', 'fluid-checkout' ),
						'desc_tip'          => __( 'Choose how saved addresses are displayed at checkout and on the cart.', 'fluid-checkout' ) . '<br><br>' . __( 'Applies at checkout and on the cart. Saved addresses on the account page always use the cards layout.', 'fluid-checkout' ),
						'id'                => 'fc_pro_address_book_display_layout',
						'type'              => 'select',
						'options'           => array(
							'cards'    => __( 'Address cards', 'fluid-checkout' ),
							'list'     => __( 'Address list', 'fluid-checkout' ),
							'dropdown' => __( 'Dropdown field', 'fluid-checkout' ),
						),
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_address_book_display_layout' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_address_book',
							'data-conditional-value' => 'yes',
						),
						'autoload'          => false,
						'disabled'          => true,
						'requires'          => self::FEATURE,
					),
					array(
						'title'             => __( 'Address search', 'fluid-checkout' ),
						'desc'              => '',
						'desc_tip'          => __( 'Show address search filter on frontend when customer has at least this number of saved addresses available.', 'fluid-checkout' ) . '<br><br>' . __( 'Not used when display layout is set to dropdown field.', 'fluid-checkout' ),
						'id'                => 'fc_pro_address_book_saved_addresses_search_min_count',
						'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_pro_address_book_saved_addresses_search_min_count' ),
						'type'              => 'fc_number',
						'suffix_label'      => __( 'Addresses', 'fluid-checkout' ),
						'custom_attributes' => array(
							'data-conditional-id'    => 'fc_pro_enable_address_book,fc_pro_address_book_display_layout',
							'data-conditional-value' => 'yes,cards|list',
						),
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

	/**
	 * Add Address Book migration settings to the Address Book tab.
	 * The Address Book add-on replaces the locked placeholders with the migration controls.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_migration_settings( $settings, $current_section ) {
		// Bail if not on the address book section
		if ( 'address_book' !== $current_section ) { return $settings; }

		/**
		 * Filters the Address Book migration settings displayed on the Address Book tab.
		 * The Address Book add-on replaces the locked placeholder settings with its own settings.
		 *
		 * @since 5.0.0
		 */
		$migration_settings = apply_filters( 'fc_admin_address_book_migration_settings', self::get_locked_migration_settings() );

		// Bail if migration settings are not valid
		if ( ! is_array( $migration_settings ) ) { return $settings; }

		return array_merge( is_array( $settings ) ? $settings : array(), $migration_settings );
	}

}

FluidCheckout_Settings_AddressBook::hooks();
