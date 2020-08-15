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
		newAddressFormActiveClass: 'active',

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
