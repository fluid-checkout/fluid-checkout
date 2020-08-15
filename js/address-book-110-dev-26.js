/**
 * Checkout steps enhanced features.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.AddressBook = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		
		bodyClass: 'wfc-address-book--active',

		addressBookSelector: '.wfc-address-book',
		newAddressFormSelector: '.wfc-address-book__form-wrapper',
		addressEntrySelector: '.address-book__entry-radio',
		addressEntryNewSelector: '[data-address-book-new]',
		addressFieldsSelector: 'input, select, textarea',
		addressDataAttribute: 'data-address',
		addressTypeAttribute: 'data-address-type',
		newAddressFormActiveClass: 'active',

		select2Selector: '[class*="select2"]',

	}



	/**
	 * METHODS
	 */



	/**
	 * Show or hide the new address form
	 */
	var changeNewAddressFormVisibility = function( addressBook, selectedAddress ) {
		// Bail if address book or selectedAddress not available
		if ( ! addressBook || ! selectedAddress ) { return; }

		var newAddressForm = addressBook.querySelector( '.wfc-address-book__form-wrapper' );

		// Bail if new address form wrapper not available
		if ( ! newAddressForm ) { return; }

		// Show or hide new address form
		if ( selectedAddress.matches( _settings.addressEntryNewSelector ) ) { newAddressForm.classList.add( _settings.newAddressFormActiveClass ); }
		else { newAddressForm.classList.remove( _settings.newAddressFormActiveClass ); }
	}



	/**
	 * Get address data from element
	 */
	var getAddressData = function( selectedAddress ) {
		var addressData = false;

		try {
			var addressDataString = selectedAddress.getAttribute( _settings.addressDataAttribute );
			addressData = JSON.parse( addressDataString );
			return addressData;
		}
		// Bail if can't parse address string into JSON object
		catch( e ) {
			return false;
		}
	}



	/**
	 * Set address field value
	 */
	var setFieldValue = function( field, value ) {
		// Bail if field not provided
		if ( ! field ) { return; }
		
		// Sanitize value
		value = value == undefined || value == null ? '' : value;

		field.value = value;

		if ( $ && field.matches( _settings.select2Selector ) ) {
			$(field).val( value );
			$(field).select2().trigger('change');
		}
	}



	/**
	 * Fill up or clean address form with selected address option
	 */
	var changeAddressFormFields = function( addressBook, selectedAddress ) {
		// Bail if selected address not passed
		if ( ! selectedAddress ) { return; }

		clearShippingFields( addressBook );

		var addressData = getAddressData( selectedAddress );

		// Bail if address data not valid
		if ( ! addressData ) { return; }
		
		var addressType = selectedAddress.getAttribute( _settings.addressTypeAttribute );
		var fieldKeys = Object.keys( addressData );

		for ( var i = 0; i < fieldKeys.length; i++ ) {
			var key = fieldKeys[i];
			var fieldkey = addressType+'_'+fieldKeys[i];
			var field = addressBook.querySelector( '[name="'+fieldkey+'"]' );
			setFieldValue( field, addressData[ key ] );
		}
	}



	/**
	 * Clear address form fields
	 */
	var clearShippingFields = function( addressBook ) {
		// Bail if address book element not passed
		if ( ! addressBook ) { return; }

		var fields = addressBook.querySelectorAll( _settings.addressFieldsSelector );

		for ( var i = 0; i < fields.length; i++ ) {
			var field = fields[i];
			setFieldValue( field, '' );
		}
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// if ( e.target.closest( _settings.editContactSelector ) ) {
		// 	removeUserData();
		// }
	};
	


	/**
	 * Handle captured `change` event and route to the appropriate function.
	 */
	var handleChange = function( e ) {
		if ( e.target.matches( _settings.addressEntrySelector ) ) {
			var addressBook = e.target.closest( _settings.addressBookSelector );
			changeNewAddressFormVisibility( addressBook, e.target );
			changeAddressFormFields( addressBook, e.target );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;
		
		// Add event listeners
		// window.addEventListener( 'click', handleClick );
		window.addEventListener( 'change', handleChange );

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
