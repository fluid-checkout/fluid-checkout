/**
 * Handle updates of the MyParcel delivery options.
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.MyParcelUpdateHandler = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		addressFieldsSelector: '#shipping_address_1, #shipping_address_2, #shipping_street_name, #shipping_house_number, #shipping_city, #shipping_state, #shipping_postcode, #shipping_country',
		updateWaitTime: 500,
	};




	/**
	 * METHODS
	 */


	/**
	 * Update the delivery options from MyParcel.
	 */
	var updateDeliveryOptions = function () {
		// Bail if MyParcelFrontend is not available
		if ( ! window.MyParcelFrontend ) return;

		MyParcelFrontend.updateAddress();
	}
	var debouncedUpdateDeliveryOptions;



	/**
	 * Handle captured `keydown` event and route to the appropriate functions.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// ADDRESS FIELDS
		if ( e.target.closest( _settings.addressFieldsSelector ) ) {
			if ( null !== debouncedUpdateDeliveryOptions ) {
				debouncedUpdateDeliveryOptions();
			}
		}
	};

	/**
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// ADDRESS FIELDS
		if ( e.target.closest( _settings.addressFieldsSelector ) ) {
			if ( null !== debouncedUpdateDeliveryOptions ) {
				debouncedUpdateDeliveryOptions();
			}
		}
	}



	/**
	 * Initialize intl phone number inputs
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		// Initialize debounced functions
		debouncedUpdateDeliveryOptions = FCUtils.debounce( updateDeliveryOptions, _settings.updateWaitTime );

		// Add event listeners
		document.addEventListener( 'keydown', handleKeyDown, true );
		document.addEventListener( 'change', handleChange, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
