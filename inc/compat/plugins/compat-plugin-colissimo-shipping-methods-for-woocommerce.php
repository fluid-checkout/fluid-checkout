<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with plugin: Colissimo shipping methods for WooCommerce (by Colissimo)
 */
class FluidCheckout_ColissimoShippingMethodsForWooCommerce extends FluidCheckout {

	public $colissimo_pickup_webservice;



	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->set_vars();
		$this->hooks();
	}

	/**
	 * Set vars.
	 */
	public function set_vars() {
		// Get the Colissimo objects
		$this->colissimo_pickup_webservice = $this->get_object_by_class_name_from_hooks( 'LpcPickupWebService' );
	}



	/**
	 * Initialize hooks.
	 */
	public function hooks() {
		// Very late hooks
		add_action( 'wp', array( $this, 'very_late_hooks' ), 100 );
	}

	/**
	 * Add or remove very late hooks.
	 */
	public function very_late_hooks() {
		$this->webservice_map_hooks();
	}

	/**
	 * Replace webservice map hooks.
	 */
	public function webservice_map_hooks() {
		// Bail if Colissimo objects are not found
		if ( ! $this->colissimo_pickup_webservice ) { return; }

		// Replace map function
		remove_action( 'woocommerce_after_shipping_rate', array( $this->colissimo_pickup_webservice, 'addWebserviceMap' ), 10 );
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'add_webservice_map_button_class' ), 10 );

		// Maybe also remove hook from the Google Address Autocomplete plugin
		if ( class_exists( 'FC_GoogleAddressAutocomplete_ColissimoShippingMethodsForWooCommerce' ) && method_exists( FC_GoogleAddressAutocomplete_ColissimoShippingMethodsForWooCommerce::instance(), 'add_webservice_map_without_script' ) ) {
			remove_action( 'woocommerce_after_shipping_rate', array( FC_GoogleAddressAutocomplete_ColissimoShippingMethodsForWooCommerce::instance(), 'add_webservice_map_without_script' ), 10 );
		}
	}



	/**
	 * Add the `button` class to the webservice map trigger button.
	 */
	public function add_webservice_map_button_class( $method, $index = 0 ) {
		// Bail if not Relay Point shipping method
		if ( 'lpc_relay' !== $method->method_id ) { return; }

		// Bail if Colissimo objects are not found
		if ( ! $this->colissimo_pickup_webservice ) { return; }

		// Get HTML for the webservice map
		ob_start();
		if ( class_exists( 'FC_GoogleAddressAutocomplete_ColissimoShippingMethodsForWooCommerce' ) && method_exists( FC_GoogleAddressAutocomplete_ColissimoShippingMethodsForWooCommerce::instance(), 'add_webservice_map_without_script' ) && 'yes' === FC_GoogleAddressAutocomplete_Settings::instance()->get_option( 'fc_gaa_enabled' ) && ! empty( FC_GoogleAddressAutocomplete_Settings::instance()->get_option( 'fc_gaa_google_places_api_key' ) ) ) {
			// Get HTML from the modified function from the Google Address Autocomplete plugin
			FC_GoogleAddressAutocomplete_ColissimoShippingMethodsForWooCommerce::instance()->add_webservice_map_without_script( $method, $index );
		}
		else {
			// Get HTML from the original function
			$this->colissimo_pickup_webservice->addWebserviceMap( $method, $index );
		}
		$html = ob_get_clean();

		// Add `button` class to the webservice map trigger button
		$html = str_replace( 'id="lpc_pick_up_web_service_show_map"', 'id="lpc_pick_up_web_service_show_map" class="button"', $html );

		// Output HTML
		echo $html;
	}

}

FluidCheckout_ColissimoShippingMethodsForWooCommerce::instance();
