/**
 * Checkout scripts for: Mondial Relay - WordPress (by Kasutan).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutMondialRelay = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		buttonSelector: '#delivery_point_chosen',
		terminalAddressSelector: '#parcel_shop_info',
		selectedTerminalFieldSelector: '#mondial_relay-terminal',
	};



	/**
	 * METHODS
	 */



	/**
	 * Update checkout fields.
	 */
	var updateCheckout = function() {
		// Create update checkout event
		var event = new Event( 'update_checkout' );

		// Trigger event
		document.body.dispatchEvent( event );
	}


	/**
	 * Update terminal address element value.
	 */
	var updateTerminalAddress = function() {
		var selectedTerminal = document.querySelector( _settings.selectedTerminalFieldSelector );
		var terminalAddressField = document.querySelector( _settings.terminalAddressSelector );
		var terminalAddress = '';

		// Bail if the hidden terminal field is not found
		if ( ! selectedTerminal ) return;

		// Bail if value is empty
		if ( selectedTerminal.value === '' ) return;

		// Transform terminal address to include line breaks
		terminalAddress = tranformTerminalAddress( selectedTerminal.value );

		if ( terminalAddress ) {
			// Update terminal address field with new information
			terminalAddressField.innerHTML = _settings.checkoutMessages.pickup_point_selected + '<br>' + terminalAddress;
		}

	}



	/**
	 * Transform terminal address to include line breaks.
	 */
	var tranformTerminalAddress = function( terminalAddress ) {
		var separator = '-MRWP-';
		var newAddress = '';

		// Bail if terminal address is empty
		if ( terminalAddress === '' ) return '';

		// Split address by separator and join with line breaks
		newAddress = terminalAddress.split( separator ).join( '<br>' );

		// Remove repeated line breaks
		newAddress = newAddress.replace( /<br><br>/g, '<br>' );

		return newAddress;
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Update checkout fields on button click
		if ( _hasJQuery ) {
			// Update checkout when pickup terminal is selected
			$( _settings.buttonSelector ).on( 'click', updateCheckout );

			// Update terminal address information to avoid stripping line breaks
			$( document ).on( 'updated_checkout', updateTerminalAddress );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
