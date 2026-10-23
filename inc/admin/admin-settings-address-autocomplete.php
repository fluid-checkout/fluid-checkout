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
 * Settings are locked until Fluid Checkout PRO unlocks the feature `pro`.
 */
class FluidCheckout_Settings_AddressAutocomplete {

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
	}



	/**
	 * Google Places API language select options.
	 */
	public static function get_google_places_api_language_options() {
		$options = include __DIR__ . '/admin-settings-address-autocomplete-language-options.php';

		return is_array( $options ) ? $options : array(
			'' => __( 'Auto-detect', 'fluid-checkout' ),
		);
	}


	/**
	 * Get the Address Autocomplete settings fields.
	 */
	public static function get_feature_settings() {
		return array(
			array(
				'title'             => __( 'Google address autocomplete', 'fluid-checkout' ),
				'type'              => 'title',
				'desc'              => '',
				'id'                => 'fc_gaa_google_address_autocomplete',
				'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'address-autocomplete' ),
				'docs'              => FluidCheckout_Admin::instance()->get_documentation_icon_html( 'https://fluidcheckout.com/docs/getting-started-google-address-autocomplete/' ),
			),

			array(
				'title'             => __( 'Google Places API version', 'fluid-checkout' ),
				'desc'              => '',
				'desc_tip'          => __( 'Choose which version of the Google Places API to use. We recommend moving to the new Places API as soon as possible.', 'fluid-checkout' ) . ' ' . __( 'We recommend using "Places API (New)" as effective March 1, 2025, Google will move the "Places API (Legacy)" version into Legacy status, meaning you will no longer be able to enable this service for new API keys and that that version of the API will eventually not be available anymore. Existing accounts already using the Legacy API may continue to work without disruptions.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_google_places_api_version',
				'type'              => 'select',
				'options'           => array(
					'current'   => _x( 'Places API (New)', 'Google Places API versions', 'fluid-checkout' ) . ' ' . __( '(Recommended)', 'fluid-checkout' ),
					'legacy_v2' => _x( 'Places API (Legacy)', 'Google Places API versions', 'fluid-checkout' ),
				),
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_google_places_api_version' ),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Google API key', 'fluid-checkout' ),
				'desc'              => __( 'Paste your Google API key and use the test button to confirm it works for this website. Make sure the key is valid and allowed for your domain, otherwise the address field might get blocked preventing customers from completing their orders.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_google_places_api_key',
				'type'              => 'fc_gaa_google_places_api_key',
				'default'           => '',
				'autoload'          => false,
				'disabled'          => true,
				'is_option'         => false, // Site-wide wp_option; saved by PRO, not the settings profile
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Google address autocomplete', 'fluid-checkout' ),
				'desc'              => __( 'Enable Google address autocomplete', 'fluid-checkout' ),
				'desc_tip'          => __( 'Enable address autocompletion using the Google Maps APIs.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_enabled',
				'type'              => 'checkbox',
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_enabled' ),
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
				'options'           => self::get_google_places_api_language_options(),
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_google_places_api_language' ),
				'custom_attributes' => array(
					'data-conditional-id'    => 'fc_gaa_enabled',
					'data-conditional-value' => 'yes',
				),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Result types', 'fluid-checkout' ),
				'desc'              => '',
				'desc_tip'          => sprintf( __( 'Leave empty to accept any type of results, which usually yields better matching results. <a href="%s" target="_blank">See documentation for more details</a>. <br>Select up to 5 types of address search results to return on address autocomplete fields. <br>Visit the Google Maps documentation to learn more about the <a href="https://developers.google.com/maps/documentation/javascript/place-types" target="_blank">supported types</a>.', 'fluid-checkout' ), 'https://fluidcheckout.com/docs/fix-result-types-to-empty/' ),
				'id'                => 'fc_gaa_search_results_types_address',
				'type'              => 'multiselect',
				'class'             => 'fc-enhanced-select',
				'options'           => array(),
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_search_results_types_address' ),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
				'custom_attributes' => array(
					'data-conditional-id'           => 'fc_gaa_enabled',
					'data-conditional-value'        => 'yes',
					'data-maximum-selection-length' => 5,
				),
			),

			array(
				'title'             => __( 'Result types (Legacy API)', 'fluid-checkout' ),
				'desc'              => '',
				'desc_tip'          => __( 'Which type of address search results to return when using Places API (Legacy). <br>Visit the documentation of the Legacy Google Places API to learn more about the <a href="https://developers.google.com/maps/documentation/places/web-service/supported_types#table3" target="_blank">supported types</a>.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_search_results_types',
				'type'              => 'select',
				'options'           => array(),
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_search_results_types' ),
				'custom_attributes' => array(
					'data-conditional-id'    => 'fc_gaa_enabled',
					'data-conditional-value' => 'yes',
				),
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
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_company_autocomplete_value_enabled' ),
				'custom_attributes' => array(
					'data-conditional-id'    => 'fc_gaa_enabled',
					'data-conditional-value' => 'yes',
				),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Business search', 'fluid-checkout' ),
				'desc'              => __( 'Enable search for businesses in the company field', 'fluid-checkout' ),
				'desc_tip'          => __( 'Enable suggestion of addresses associated with a business when typing in the company field, then autocomplete all addresses fields when a business is selected from the suggestions.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_company_autocomplete_input_enabled',
				'type'              => 'checkbox',
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_company_autocomplete_input_enabled' ),
				'custom_attributes' => array(
					'data-conditional-id'    => 'fc_gaa_company_autocomplete_value_enabled',
					'data-conditional-value' => 'yes',
				),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Company result types', 'fluid-checkout' ),
				'desc'              => '',
				'desc_tip'          => __( 'Select up to 5 types of address search results to return on autocomplete company fields. <br>Recommended value is <code>establishment</code>, which includes most types of businesses. <br>Leave empty to accept any type of results, including results that are not related to businesses. <br>Visit the Google Maps documentation to learn more about the <a href="https://developers.google.com/maps/documentation/javascript/place-types" target="_blank">supported types</a>.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_search_results_types_company',
				'type'              => 'multiselect',
				'class'             => 'fc-enhanced-select',
				'options'           => array(),
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_search_results_types_company' ),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
				'custom_attributes' => array(
					'data-conditional-id'           => 'fc_gaa_company_autocomplete_value_enabled',
					'data-conditional-value'        => 'yes',
					'data-maximum-selection-length' => 5,
				),
			),

			array(
				'title'             => __( 'Google logo', 'fluid-checkout' ),
				'desc'              => __( 'Show text and logo "Powered by Google"', 'fluid-checkout' ),
				'desc_tip'          => __( 'When checked, the text and logo "Powered by Google" will be displayed at the bottom of the address autocomplete suggestions box.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_show_powered_by_google',
				'type'              => 'checkbox',
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_show_powered_by_google' ),
				'custom_attributes' => array(
					'data-conditional-id'    => 'fc_gaa_enabled',
					'data-conditional-value' => 'yes',
				),
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
				'promo'             => FluidCheckout_Admin::instance()->get_pro_feature_badge_html( 'address-autocomplete-brasil-api' ),
			),

			array(
				'title'             => __( 'Brasil API', 'fluid-checkout' ),
				'desc'              => __( 'Enable Brasil API address autocomplete for CEP fields', 'fluid-checkout' ),
				'desc_tip'          => __( 'Use Brasil API to retrieve and autofill addresses using the CEP/Postcode field. Only relevant for Brazilian addresses.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_enabled_brasil_api',
				'type'              => 'checkbox',
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_enabled_brasil_api' ),
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
					'v1' => _x( 'Version 1', 'Brasil API versions', 'fluid-checkout' ),
					'v2' => _x( 'Version 2', 'Brasil API versions', 'fluid-checkout' ),
				),
				'default'           => FluidCheckout_Settings::instance()->get_option_default( 'fc_gaa_brasil_api_version' ),
				'custom_attributes' => array(
					'data-conditional-id'    => 'fc_gaa_enabled_brasil_api',
					'data-conditional-value' => 'yes',
				),
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'type'              => 'sectionend',
				'id'                => 'fc_gaa_brasil_api',
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
		 * Filters the settings for the Address Autocomplete tab.
		 *
		 * @since 5.0.0
		 */
		$settings = apply_filters(
			'fc_gaa_address_autocomplete_settings',
			self::get_feature_settings()
		);

		return $settings;
	}

}

FluidCheckout_Settings_AddressAutocomplete::hooks();
