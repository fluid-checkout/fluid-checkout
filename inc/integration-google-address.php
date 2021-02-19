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
		// Check API Keys
		$this->google_places_api_key = get_option( 'wfc_google_address_integration_api_key' );


		// Load hooks
		$this->hooks();
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Check if feature is enabled
		if ( get_option( 'wfc_enable_google_address_integration', 'true' ) === 'true' ) {

			// Check API Key
			if ( $this->google_places_api_key && ! empty( $this->google_places_api_key ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
			}

		}
	}



	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {
		// Bail if not on checkout or edit address page
		if ( ! function_exists( 'is_checkout' ) && ( ! is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) ) { return; }
		// TODO: Check if on checkout or address edit pages
		
		wp_enqueue_script( 'wfc-google-address-autocomplete', self::$directory_url . 'js/google-address-autocomplete'. self::$asset_version . '.js', array(), NULL, true );
		wp_enqueue_script( 'wfc-google-address-api', "https://maps.googleapis.com/maps/api/js?key={$this->google_places_api_key}&libraries=places&callback=GoogleAddressAutocomplete.init", array( 'wfc-google-address-autocomplete' ), NULL, true );
	}

}

FluidCheckout_IntegrationGoogleAddress::instance();
