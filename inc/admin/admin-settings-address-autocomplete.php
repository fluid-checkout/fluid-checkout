<?php
/**
 * Fluid Checkout Address Autocomplete Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FluidCheckout_Settings_AddressAutocomplete', false ) ) {
	FluidCheckout_Settings_AddressAutocomplete::hooks();
	return;
}

/**
 * FluidCheckout_Settings_AddressAutocomplete.
 * Displays locked placeholders for the Address Autocomplete add-on settings.
 * When the add-on is active, it replaces the placeholders with its own settings.
 */
class FluidCheckout_Settings_AddressAutocomplete {

	/**
	 * Feature slug used to lock and unlock the settings.
	 */
	const FEATURE = 'address_autocomplete';

	/**
	 * Product page URL for the Address Autocomplete add-on.
	 */
	const PRODUCT_URL = 'https://fluidcheckout.com/fc-google-address-autocomplete/';



	/**
	 * Initialize hooks.
	 */
	public static function hooks() {
		// Settings
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_settings' ), 10, 2 );

		// Tools settings, runs after the Tools settings are added
		add_filter( 'fc_admin_settings', array( __CLASS__, 'add_debug_settings' ), 20, 2 );
	}



	/**
	 * Get the promotional settings card shown at the top of the Address Autocomplete tab when the add-on is not active.
	 */
	public static function get_promo_settings() {
		// Bail if the add-on feature is already unlocked
		if ( FluidCheckout_Admin_Settings_Access::instance()->is_unlocked( self::FEATURE ) ) { return array(); }

		return array(
			array(
				'title'            => __( 'Google address autocomplete', 'fluid-checkout' ),
				'type'             => 'fc_promo',
				'id'               => 'fc_gaa_address_autocomplete_promo',
				'is_card'          => true,
				'promo'            => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete-promo', self::PRODUCT_URL, self::FEATURE ),
				'tagline'          => __( 'Collect correct address details the first time and cut down on fields customers need to fill in.', 'fluid-checkout' ),
				'features'         => array(
					__( 'Autocomplete address fields with Google Places suggestions.', 'fluid-checkout' ),
					__( 'Reduce typos and delivery issues with verified address data.', 'fluid-checkout' ),
					__( 'Optional company and business search in the company field.', 'fluid-checkout' ),
					__( 'Brasil API CEP autocomplete for Brazilian addresses.', 'fluid-checkout' ),
				),
				'learn_more_url'   => add_query_arg(
					array(
						'mtm_campaign' => 'addons',
						'mtm_kwd'      => 'address-autocomplete-promo-learn-more',
						'mtm_source'   => 'lite-plugin',
					),
					self::PRODUCT_URL
				),
				'learn_more_label' => __( 'Learn more', 'fluid-checkout' ),
				'plugin_file'      => 'fc-google-address-autocomplete/fc-google-address-autocomplete.php',
				'plugin_slug'      => 'fc-google-address-autocomplete',
				'purchase_url'     => add_query_arg(
					array(
						'mtm_campaign' => 'addons',
						'mtm_kwd'      => 'address-autocomplete-promo-purchase',
						'mtm_source'   => 'lite-plugin',
					),
					self::PRODUCT_URL
				),
				'purchase_price'   => '29 EUR',
			),
		);
	}

	/**
	 * Get the locked placeholder settings for the Address Autocomplete tab.
	 */
	public static function get_locked_settings() {
		return array_merge(
			self::get_promo_settings(),
			array(
			array(
				'title'             => __( 'Google address autocomplete', 'fluid-checkout' ),
				'type'              => 'title',
				'desc'              => '',
				'id'                => 'fc_gaa_google_address_autocomplete',
				'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete', self::PRODUCT_URL, self::FEATURE ),
			),

			array(
				'title'             => __( 'Google API key', 'fluid-checkout' ),
				'desc'              => __( 'Paste your Google API key and use the test button to confirm it works for this website.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_google_places_api_key',
				'type'              => 'text',
				'default'           => '',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Google address autocomplete', 'fluid-checkout' ),
				'desc'              => __( 'Enable Google address autocomplete', 'fluid-checkout' ),
				'desc_tip'          => __( 'Enable address autocompletion using the Google Maps APIs.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_enabled',
				'type'              => 'checkbox',
				'default'           => 'yes',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Google API language', 'fluid-checkout' ),
				'desc'              => '',
				'desc_tip'          => __( 'This language will be used to display the Google Places API address suggestions and addresses will also be autocompleted in this language.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_google_places_api_language',
				'type'              => 'select',
				'options'           => array(
					''              => __( 'Auto-detect', 'fluid-checkout' ),
				),
				'default'           => '',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Result types', 'fluid-checkout' ),
				'desc'              => '',
				'desc_tip'          => __( 'Leave empty to accept any type of results, which usually yields better matching results. <br>Select up to 5 types of address search results to return on address autocomplete fields.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_search_results_types_address',
				'type'              => 'multiselect',
				'class'             => 'fc-enhanced-select',
				'options'           => array(),
				'default'           => array(),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Company fields', 'fluid-checkout' ),
				'desc'              => __( 'Enable autocomplete for the company name if available', 'fluid-checkout' ),
				'desc_tip'          => __( 'Enable autocompletion of the company field with the company name information when returned from the Google Maps APIs.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_company_autocomplete_value_enabled',
				'type'              => 'checkbox',
				'default'           => 'no',
				'checkboxgroup'     => 'start',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'desc'              => __( 'Enable search for businesses in the company field', 'fluid-checkout' ),
				'desc_tip'          => __( 'Enable suggestion of addresses associated with a business when typing in the company field, then autocomplete all addresses fields when a business is selected from the suggestions.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_company_autocomplete_input_enabled',
				'type'              => 'checkbox',
				'default'           => 'no',
				'checkboxgroup'     => 'end',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'type'              => 'sectionend',
				'id'                => 'fc_gaa_google_address_autocomplete',
			),

			array(
				'title'             => __( 'Brasil API address autocomplete', 'fluid-checkout' ),
				'type'              => 'title',
				'desc'              => '',
				'id'                => 'fc_gaa_brasil_api',
				'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete-brasil-api', self::PRODUCT_URL, self::FEATURE ),
			),

			array(
				'title'             => __( 'Brasil API', 'fluid-checkout' ),
				'desc'              => __( 'Enable Brasil API address autocomplete for CEP fields', 'fluid-checkout' ),
				'desc_tip'          => __( 'Use Brasil API to retrive and autofill addresses using the CEP/Postcode field. Only relevant for Brazilian addresses.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_enabled_brasil_api',
				'type'              => 'checkbox',
				'default'           => 'no',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'API version', 'fluid-checkout' ),
				'desc_tip'          => __( 'Choose which version of the Brasil API CEP method to use.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_brasil_api_version',
				'type'              => 'select',
				'options'           => array(
					'v1'            => _x( 'Version 1', 'Brasil API versions', 'fluid-checkout' ),
					'v2'            => _x( 'Version 2', 'Brasil API versions', 'fluid-checkout' ),
				),
				'default'           => 'v2',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'type'              => 'sectionend',
				'id'                => 'fc_gaa_brasil_api',
			),
			)
		);
	}

	/**
	 * Get the locked placeholder debug fields for Address Autocomplete.
	 * Inserted into Tools > Troubleshooting when the add-on is not active.
	 */
	public static function get_locked_debug_settings() {
		return array(
			array(
				'title'             => __( 'Google Maps scripts', 'fluid-checkout' ),
				'desc'              => __( 'Do not remove duplicate Google Maps scripts', 'fluid-checkout' ),
				'desc_tip'          => __( 'When enabled, duplicate Google Maps scripts will NOT be removed which can cause errors and performance issues. Only use this option while troubleshooting.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_google_maps_dont_remove_duplicate_scripts',
				'type'              => 'checkbox',
				'default'           => 'no',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
				'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete-debug', self::PRODUCT_URL, self::FEATURE ),
			),
		);
	}



	/**
	 * Add the Address Autocomplete settings.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_settings( $settings, $current_section ) {
		// Bail if not on the address autocomplete section
		if ( 'address_autocomplete' !== $current_section ) { return $settings; }

		/**
		 * Filter the settings for the Address Autocomplete tab.
		 * The Address Autocomplete add-on replaces the locked placeholder settings with its own settings.
		 *
		 * @param  array  $settings  Locked placeholder settings.
		 */
		return apply_filters( 'fc_admin_address_autocomplete_settings', self::get_locked_settings() );
	}

	/**
	 * Add the Address Autocomplete debug settings into Tools > Troubleshooting.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public static function add_debug_settings( $settings, $current_section ) {
		// Bail if not on the tools section
		if ( 'tools' !== $current_section ) { return $settings; }

		/**
		 * Filter the Address Autocomplete debug settings displayed on the Tools tab.
		 * The Address Autocomplete add-on replaces the locked placeholder settings with its own settings.
		 *
		 * @param  array  $settings  Locked placeholder settings.
		 */
		$debug_settings = apply_filters( 'fc_admin_address_autocomplete_debug_settings', self::get_locked_debug_settings() );

		// Bail if debug settings are not valid
		if ( ! is_array( $debug_settings ) || empty( $debug_settings ) ) { return $settings; }

		// Insert into the Troubleshooting section, before its section end
		$result = array();
		foreach ( (array) $settings as $setting ) {
			// Maybe insert Address Autocomplete debug fields before the Troubleshooting section end
			if ( isset( $setting[ 'id' ], $setting[ 'type' ] ) && 'fc_checkout_advanced_debug_options' === $setting[ 'id' ] && 'sectionend' === $setting[ 'type' ] ) {
				foreach ( $debug_settings as $debug_setting ) {
					$result[] = $debug_setting;
				}
			}

			$result[] = $setting;
		}

		return $result;
	}

}

FluidCheckout_Settings_AddressAutocomplete::hooks();
