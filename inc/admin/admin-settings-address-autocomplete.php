<?php
/**
 * Fluid Checkout Address Autocomplete Settings
 *
 * @package fluid-checkout
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_FluidCheckout_AddressAutocomplete_Settings', false ) ) {
	return new WC_Settings_FluidCheckout_AddressAutocomplete_Settings();
}

/**
 * WC_Settings_FluidCheckout_AddressAutocomplete_Settings.
 * Displays locked placeholders for the Address Autocomplete add-on settings.
 * When the add-on is active, it replaces the placeholders with its own settings.
 */
class WC_Settings_FluidCheckout_AddressAutocomplete_Settings extends WC_Settings_Page {

	/**
	 * Feature slug used to lock and unlock the settings.
	 */
	const FEATURE = 'address_autocomplete';

	/**
	 * Product page URL for the Address Autocomplete add-on.
	 */
	const PRODUCT_URL = 'https://fluidcheckout.com/fc-google-address-autocomplete/';



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

		// Tools settings, runs after the Tools settings are added
		add_filter( 'woocommerce_get_settings_fc_checkout', array( $this, 'add_debug_settings' ), 20, 2 );
	}



	/**
	 * Get the promotional settings card shown at the top of the Address Autocomplete tab when the add-on is not active.
	 */
	public function get_promo_settings() {
		// Bail if the add-on feature is already unlocked
		if ( FluidCheckout_Admin_Settings_Access::instance()->is_unlocked( self::FEATURE ) ) { return array(); }

		return array(
			array(
				'title'            => __( 'Google Address Autocomplete', 'fluid-checkout' ),
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
	public function get_locked_settings() {
		return array_merge(
			$this->get_promo_settings(),
			array(
			array(
				'title'             => __( 'Google Address Autocomplete', 'fluid-checkout' ),
				'type'              => 'title',
				'desc'              => '',
				'id'                => 'fc_gaa_google_address_autocomplete',
				'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete', self::PRODUCT_URL, self::FEATURE ),
			),

			array(
				'title'             => __( 'Google API Key', 'fluid-checkout' ),
				'desc'              => __( 'Paste your Google API key and use the test button to confirm it works for this website.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_google_places_api_key',
				'type'              => 'text',
				'default'           => '',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Google Address Autocomplete', 'fluid-checkout' ),
				'desc'              => __( 'Enable Google Address Autocomplete', 'fluid-checkout' ),
				'desc_tip'          => __( 'Enable address autocompletion using the Google Maps APIs.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_enabled',
				'type'              => 'checkbox',
				'default'           => 'yes',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'title'             => __( 'Google API Language', 'fluid-checkout' ),
				'desc'              => __( 'This language will be used to display the Google Places API address suggestions and addresses will also be autocompleted in this language.', 'fluid-checkout' ),
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
				'desc'              => __( 'Leave empty to accept any type of results, which usually yields better matching results. <br>Select up to 5 types of address search results to return on address autocomplete fields.', 'fluid-checkout' ),
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
				'title'             => __( 'Brasil API Address Autocomplete', 'fluid-checkout' ),
				'type'              => 'title',
				'desc'              => '',
				'id'                => 'fc_gaa_brasil_api',
				'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete-brasil-api', self::PRODUCT_URL, self::FEATURE ),
			),

			array(
				'title'             => __( 'Brasil API', 'fluid-checkout' ),
				'desc'              => __( 'Enable Brasil API Address Autocomplete for CEP fields', 'fluid-checkout' ),
				'desc_tip'          => __( 'Use Brasil API to retrive and autofill addresses using the CEP/Postcode field. Only relevant for Brazilian addresses.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_enabled_brasil_api',
				'type'              => 'checkbox',
				'default'           => 'no',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
			),

			array(
				'desc'              => __( 'Choose which version of the Brasil API CEP method to use.', 'fluid-checkout' ),
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
	 * Get the locked placeholder settings for the Address Autocomplete debug options, displayed on the Tools tab.
	 */
	public function get_locked_debug_settings() {
		return array(
			array(
				'title'             => __( 'Troubleshooting - Address Autocomplete', 'fluid-checkout' ),
				'type'              => 'title',
				'desc'              => '',
				'id'                => 'fc_gaa_debug_options',
				'promo'             => FluidCheckout_Admin::instance()->get_addon_feature_badge_html( 'address-autocomplete-debug', self::PRODUCT_URL, self::FEATURE ),
			),

			array(
				'title'             => __( 'Debug mode', 'fluid-checkout' ),
				'desc'              => __( 'Enable debug mode', 'fluid-checkout' ),
				'desc_tip'          => __( 'When enabled, some information such as the selected "Place" details returned by the API will be logged to the browser console.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_debug_mode',
				'type'              => 'checkbox',
				'default'           => 'no',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
				'checkboxgroup'     => 'start',
				'show_if_checked'   => 'option',
			),

			array(
				'desc'              => __( 'Load unminified assets', 'fluid-checkout' ),
				'desc_tip'          => __( 'Loading unminified assets affects the website performance. Only use this option while troubleshooting.', 'fluid-checkout' ),
				'id'                => 'fc_gaa_load_unminified_assets',
				'type'              => 'checkbox',
				'default'           => 'no',
				'autoload'          => false,
				'disabled'          => true,
				'requires'          => self::FEATURE,
				'checkboxgroup'     => 'end',
				'show_if_checked'   => 'yes',
			),

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
			),

			array(
				'type'              => 'sectionend',
				'id'                => 'fc_gaa_debug_options',
			),
		);
	}



	/**
	 * Add the Address Autocomplete settings.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_settings( $settings, $current_section ) {
		// Bail if not on the address autocomplete section
		if ( 'address_autocomplete' !== $current_section ) { return $settings; }

		/**
		 * Filter the settings for the Address Autocomplete tab.
		 * The Address Autocomplete add-on replaces the locked placeholder settings with its own settings.
		 *
		 * @param  array  $settings  Locked placeholder settings.
		 */
		return apply_filters( 'fc_admin_address_autocomplete_settings', $this->get_locked_settings() );
	}

	/**
	 * Add the Address Autocomplete debug settings to the Tools tab.
	 *
	 * @param   array   $settings         Array with all settings for the current section.
	 * @param   string  $current_section  Current section name.
	 */
	public function add_debug_settings( $settings, $current_section ) {
		// Bail if not on the tools section
		if ( 'tools' !== $current_section ) { return $settings; }

		/**
		 * Filter the Address Autocomplete debug settings displayed on the Tools tab.
		 * The Address Autocomplete add-on replaces the locked placeholder settings with its own settings.
		 *
		 * @param  array  $settings  Locked placeholder settings.
		 */
		$debug_settings = apply_filters( 'fc_admin_address_autocomplete_debug_settings', $this->get_locked_debug_settings() );

		// Bail if debug settings are not valid
		if ( ! is_array( $debug_settings ) ) { return $settings; }

		return array_merge( is_array( $settings ) ? $settings : array(), $debug_settings );
	}

}

return new WC_Settings_FluidCheckout_AddressAutocomplete_Settings();
