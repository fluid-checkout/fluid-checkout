/**
 * Manage the Google Address suggestions and autocomplete address fields
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.GoogleAddressAutocomplete = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		bodyClass: 'has-google-autocomplete',

		autocompleteInputSelector: '#address_1, #shipping_address_1, #billing_address_1',
		addressGroupSelector: '.woocommerce-shipping-fields, .woocommerce-billing-fields', // TODO: add group selector for address in account pages
		
		autocompleteDefaultOptions: {
			fields: [ 'address_components' ],
			types: [ 'geocode' ],
		},
		componentRestrictions: {},
		componentValueType: {
			street_number: 'short_name',
			route: 'long_name',
			locality: 'long_name',
			administrative_area_level_1: 'short_name',
			country: 'short_name',
			postal_code: 'short_name',
		},
		// TODO: Possibly need to set different address_components combination for each country, similar to WC locales
		fieldIdComponent: {
			address_1: [ 'street_number', 'route' ],
			city: 'locality',
			state: 'administrative_area_level_1',
			country: 'country',
			postal_code: 'postal_code',
		},

	};



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



	var fillAddress = function( place, input, autocomplete ) {
		
		var groupElement = input.closest( _settings.addressGroupSelector );
		console.log(place);
		
		// TODO: Clear address fields
		// TODO: Check if country is allowed for the address type

		// TODO: First set country value
		// TODO: Await a few milliseconds

		// place.address_components.forEach( function( component ) {
		// 	var fieldType = component.types[0];
		// 	var fieldValue = component[ _settings.componentValueType[ fieldType ] ];

		// 	var fieldId = _settings.componentFieldId[ fieldType ];
			
		// 	if ( fieldId ) {
		// 		// var addressType = 'shipping'; // TODO: Get address type from groupElement
		// 		var field = groupElement.querySelector( '[id$="'+fieldId+'"]' );
		// 		field.value = fieldValue;
	
		// 		console.log( fieldId + ': ' + fieldValue );
		// 	}

		// } );
	}



	var initField = function( input ) {
		// Maybe set country restrictions
		if ( _settings.componentRestrictions.hasOwnProperty( input.id ) ) {
			var inputComponentsRestrictions = _settings.componentRestrictions[ input.id ];
			_settings.autocompleteDefaultOptions.componentRestrictions = inputComponentsRestrictions;
		}
		
		var autocomplete = new google.maps.places.Autocomplete( input, _settings.autocompleteDefaultOptions );
		var onPlaceChange = function() {
			var place = autocomplete.getPlace();
	
			// Check if user selected an address
			if ( place.address_components ) {
				fillAddress( place, input, autocomplete );
			}
		}
		autocomplete.addListener( 'place_changed', onPlaceChange );
	}


	var initFields = function() {
		var inputs = document.querySelectorAll( _settings.autocompleteInputSelector );
		inputs.forEach( initField );
	}
	

	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		_settings = extend( _settings, options );
		
		initFields();
		// TODO: Initialize fields after updated_checkout event to re-initialize address complete on billing field because the content element is replaced entirely

		// Finish initialization
		document.body.classList.add( _settings.bodyClass );
		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
