/**
 * Autocomplete address with Ziptastic API data.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.Ziptastic = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasInitialized = false;
	var _publicMethods = { }

	/**
	 * VARIABLES
	 */
	var _settings = {
		hasJQuery: ( $ != null ),
		ziptasticAPIKey: false,
		zipFields: '#billing_postcode, #shipping_postcode',
		fieldWrappers: '.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields__field-wrapper, .woocommerce-address-fields__field-wrapper',
		cityFields: '#billing_city, #shipping_city',
		stateFields: '#billing_state, #shipping_state',
		countryFields: '#billing_country, #shipping_country',
		minChars: 5,
	}



	/**
	 * METHODS
	 */



	/*!
	* Merge two or more objects together.
	* (c) 2017 Chris Ferdinandi, MIT License, https://gomakethings.com
	* @param   {Boolean}  deep     If true, do a deep (or recursive) merge [optional]
	* @param   {Object}   objects  The objects to merge together
	* @returns {Object}            Merged values of defaults and options
	*/
	var extend = function () {
		// Variables
		var extended = {};
		var deep = false;
		var i = 0;

		// Check if a deep merge
		if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
			deep = arguments[0];
			i++;
		}

		// Merge the object into the extended object
		var merge = function (obj) {
			for (var prop in obj) {
				if (obj.hasOwnProperty(prop)) {
					// If property is an object, merge properties
					if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
						extended[prop] = extend(extended[prop], obj[prop]);
					} else {
						extended[prop] = obj[prop];
					}
				}
			}
		};

		// Loop through each object and conduct a merge
		for (; i < arguments.length; i++) {
			var obj = arguments[i];
			merge(obj);
		}

		return extended;
	};

	

	/**
	 * Process successful zip api call and set new field values.
	 * @param  {Element} target Element that triggered autocomplete event.
	 * @param  {Object} data    Data returned by ziptastic.
	 */
	var processAutocomplete = function( target, data ) {
		var wrapper = target.closest( _settings.fieldWrappers );

		// Bail if wrapper not found
		if ( ! wrapper ) { return; }

		var city = wrapper.querySelector( _settings.cityFields ),
				state = wrapper.querySelector( _settings.stateFields ),
				country = wrapper.querySelector( _settings.countryFields );

		// Update city value
		if ( city && city.value != data.city ) {
			city.value = data.city;
			city.dispatchEvent( new CustomEvent( 'change' ), { 'bubbles': true } );
		}

		// Update country value
		if ( country && country.value != data.country ) {
			if ( _settings.hasJQuery && $( country ).data( 'select2' ) ) {
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
			if ( _settings.hasJQuery && $( state ).data( 'select2' ) ) {
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
		if ( ! _settings.ziptasticAPIKey ) { return; }
		
		var wrapper = target.closest( _settings.fieldWrappers );
		var countryField = wrapper.querySelector( _settings.countryFields );
		
		// Default to US
		var country = 'US';

		// Try get country from form field
		if ( countryField && countryField.value != '' ) {
			country = countryField.value;
		}

		var xhr = new XMLHttpRequest();
		xhr.open('GET', '//zip.getziptastic.com/v3/'+country+'/'+zip);
		xhr.setRequestHeader('Content-Type', 'application/json');
		xhr.setRequestHeader('x-key', _settings.ziptasticAPIKey);
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
		if ( ! _settings.ziptasticAPIKey ) { return; }

		// Bail if target not defined or not focused
		if ( ! e.target || ! e.target.matches( ':focus' ) ) { return; }

		// Bail some keys where presses
		var skipKeys = [
			9, // Tab
			27, // Esc
		];
		if ( e.type == 'keyup' && skipKeys.includes( e.keyCode ) ) { return; }

		var value = e.target.value;

		// Check if field has enough chars
		if ( value.length < _settings.minChars ) { return; }

		triggerAutocomplete( e.target, value );
	};



	/**
	 * Handle captured keyup event and route to appropriate function.
	 * @param  {Event} e Event data.
	 */
	var handleTriggerEvents = function( e ) {
		if ( e.target.matches( _settings.zipFields ) ) {
			maybeTriggerAutocomplete( e );
		}
	};



	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		// Bail if server defined vars not found
		if ( ! window.wfcZiptasticVars ) { return; }
		
		_settings = extend( _settings, wfcZiptasticVars );

		// Add event listeners
		window.addEventListener( 'keyup', handleTriggerEvents, true );
		window.addEventListener( 'change', handleTriggerEvents, true );

		_hasInitialized = true;
	};

	
	//
	// Public APIs
	//
	return _publicMethods;

});
