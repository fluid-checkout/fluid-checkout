<?php

/**
 * Customizations to the checkout page.
 */
class FluidCheckout_IntegrationGoogleAddress extends FluidCheckout {

	/**
	 * API Key used for calling the Google Places API
	 *
	 * @var String
	 */
	public $google_places_api_key = null;

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Get API Key
		$this->google_places_api_key = get_option( 'wfc_google_address_integration_api_key' );

		// Load hooks
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Check if feature is enabled and API Key added
		if ( get_option( 'wfc_enable_google_address_integration', 'true' ) !== 'true' || ! $this->google_places_api_key || empty( $this->google_places_api_key ) ) { return; }

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Google Autocomplete Settings
		add_filter( 'wfc_js_settings', array( $this, 'add_google_address_js_settings' ), 10 );

		// Change position of address fields
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_default_locale_fields_priority' ), 10 );
	}



	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {
		// Bail if not on checkout or edit address page
		if ( ! function_exists( 'is_checkout' ) && ( ! is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) ) { return; }
		
		wp_enqueue_script( 'wfc-google-address-autocomplete', self::$directory_url . 'js/google-address-autocomplete'. self::$asset_version . '.js', array(), NULL, true );
		wp_enqueue_script( 'wfc-google-address-api', "https://maps.googleapis.com/maps/api/js?key={$this->google_places_api_key}&libraries=places&callback=GoogleAddressAutocomplete.init", array( 'wfc-google-address-autocomplete' ), NULL, true );
	}



	/**
	 * Add Google Address Autocomplete settings to the plugin settings JS object.
	 * 
	 * @param   array  $settings  JS settings object of the plugin.
	 */
	public function add_google_address_js_settings( $settings ) {
		
		$settings[ 'googleAutoCompleteSettings' ] = apply_filters( 'wfc_google_autocomplete_js_settings', array(
			'localeComponents' => array(
				'BR' => array(
					'city' => 'administrative_area_level_2',
					'address_1' => array( 'route', 'street_number' ),
					'components_separator' => ', ',
				),
				'NL' => array(
					'address_1' => array( 'route', 'street_number' ),
				),
			),
		) );

		return $settings;
	}



	/**
	 * Change default locale fields priority.
	 * 
	 * @param   array  $fields  Default address fields args.
	 */
	public function change_default_locale_fields_priority( $fields ) {
		if ( array_key_exists( 'country', $fields ) ) { $fields['country']['priority'] = 85; }
		return $fields;
	}

}

FluidCheckout_IntegrationGoogleAddress::instance();
