/**
 * Autocomplete address with Ziptastic API data.
 */

(function( $ ){

	'use strict';

	/**
	 * VARIABLES
	 */
	var hasJQuery = ( $ != null ),
		ziptasticAPIKey = false,
		zipFields = '#billing_postcode, #shipping_postcode',
		fieldWrappers = '.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields__field-wrapper, .woocommerce-address-fields__field-wrapper',
		cityFields = '#billing_city, #shipping_city',
		stateFields = '#billing_state, #shipping_state',
		countryFields = '#billing_country, #shipping_country',
		minChars = 5;



	/**
	 * METHODS
	 */
	

	/**
	 * Process successful zip api call and set new field values.
	 * @param  {Element} target Element that triggered autocomplete event.
	 * @param  {Object} data    Data returned by ziptastic.
	 */
	var processAutocomplete = function( target, data ) {
		var wrapper = target.closest( fieldWrappers );

		// Bail if wrapper not found
		if ( ! wrapper ) { return; }

		var city = wrapper.querySelector( cityFields ),
				state = wrapper.querySelector( stateFields ),
				country = wrapper.querySelector( countryFields );

		// Update city value
		if ( city && city.value != data.city ) {
			city.value = data.city;
			city.dispatchEvent( new CustomEvent( 'change' ), { 'bubbles': true } );
		}

		// Update country value
		if ( country && country.value != data.country ) {
			if ( hasJQuery && $( country ).data( 'select2' ) ) {
				// Country is a select2 so it needs to trigger jQuery event
				$( country ).val( data.country ).trigger( 'change' );
			}
			else {
				country.value = data.country;
			}
			
			// Need to dispatch native event for some external components to catch the change
			country.dispatchEvent( new CustomEvent( 'change' ), { 'bubbles': true } );
		}

		// Update state value
		if ( state && state.value != data.state_short ) {
			if ( hasJQuery && $( state ).data( 'select2' ) ) {
				// State is a select2 so it needs to trigger jQuery event
				$( state ).val( data.state_short ).trigger( 'change' );
			}
			else {
				state.value = data.state_short;
			}

			// Need to dispatch native event for some external components to catch the change
			state.dispatchEvent( new CustomEvent( 'change' ), { 'bubbles': true } );
		}
	};



	/**
	 * Trigger ziptastic api to get zip data.
	 * @param  {String} zip Zip or postcode.
	 */
	var triggerAutocomplete = function( target, zip ) {
		// Bail if api_key not defined
		if ( ! ziptasticAPIKey ) { return; }
		
		var wrapper = target.closest( fieldWrappers );
		var countryField = wrapper.querySelector( countryFields );
		
		// Default to US
		var country = 'US';

		// Try get country from form field
		if ( countryField && countryField.value != '' ) {
			country = countryField.value;
		}

		var xhr = new XMLHttpRequest();
		xhr.open('GET', '//zip.getziptastic.com/v3/'+country+'/'+zip);
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.setRequestHeader('x-key', ziptasticAPIKey);
		xhr.onload = function() {
			if ( xhr.status === 200 ) {
				var data = JSON.parse( xhr.responseText );
				processAutocomplete( target, data[0] );
			}
		};
		xhr.send();
	};

	

	/**
	 * Maybe trigger ziptastic api, check if field has enough chars.
	 * @param  {Event} e Event data.
	 */
	var maybeTriggerAutocomplete = function( e ) {
		// Bail if api_key not defined
		if ( ! ziptasticAPIKey ) { return; }

		// Bail if target not defined
		if ( ! e.target ) { return; }

		// Bail some keys where presses
		var skipKeys = [
			9, // Tab
			27, // Esc
		];
		if ( e.type == 'keyup' && skipKeys.includes( e.keyCode ) ) { return; }

		var value = e.target.value;

		// Check if field has enough chars
		if ( value.length < minChars ) { return; }

		triggerAutocomplete( e.target, value );
	};



	/**
	 * Handle captured keyup event and route to appropriate function.
	 * @param  {Event} e Event data.
	 */
	var handleTriggerEvents = function( e ) {
		if ( e.target.matches( zipFields ) ) {
			maybeTriggerAutocomplete( e );
		}
	};



	var init = function() {
		// Bail if server defined vars not found
		if ( ! ziptasticVars ) { return; }

		ziptasticAPIKey = ziptasticVars.api_key;
	};


	// Add event listeners
	window.addEventListener( 'load', init );
	window.addEventListener( 'keyup', handleTriggerEvents, true );
	window.addEventListener( 'change', handleTriggerEvents, true );

})( jQuery );
