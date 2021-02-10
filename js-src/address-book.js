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

	var _hasJQuery = ( $ != null );
	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		
		bodyClass: 'wfc-address-book--active',

		addressBookSelector: '.wfc-address-book',
		addressBookEntriesWrapper: '.address-book',
		newAddressFormSelector: '.wfc-address-book__form-wrapper',
		addressEntrySelector: '.address-book__entry-radio',
		addressEntryNewSelector: '[data-address-book-new]',
		addressEntrySameAsSelector: '[data-address-book-same]',
		addressFieldsSelector: 'input, select, textarea',
		persistAddressFieldsSelector: '#shipping_first_name, #shipping_last_name, #shipping_phone, #shipping_company, #shipping_address_1, #shipping_address_2, #shipping_country, #shipping_state, #shipping_postcode, #shipping_city, #billing_address_1, #billing_address_2, #billing_country, #billing_state, #billing_postcode, #billing_city, #billing_company',
		addressFieldsCleanSelector: '[name$="_address_id"], #shipping_address_save, #billing_address_save',
		selectedAddressIdSelector: '[name$="_address_id"]:checked',
		formRowSelector: '.form-row',
		select2Selector: '[class*="select2"]',
		copyValueFromFieldSelector: '#billing_first_name, #billing_last_name, #billing_phone',
		fieldEditedSelector: '[data-field-edited]',
		
		addressDataAttribute: 'data-address',
		addressTypeAttribute: 'data-address-type',
		sameAsEntryCheckedAttribute: 'data-address-same-as-checked',
		copyValueToAttribute: 'data-copy-to-field',
		fieldEditedAttribute: 'data-field-edited',
		
		newAddressFormActiveClass: 'active',
		saveAddressHiddenClass: 'hidden',


	}
	var _updateCheckout = true;
	var _copyValueToFieldSelector =  '';



	/**
	 * METHODS
	 */


	// TODO: Maybe move to it's own file and load with require bundle
	/**
	 * Debounce
	 *
	 * Returns a function, that, as long as it continues to be invoked, will not
	 * be triggered. The function will be called after it stops being called for
	 * N milliseconds. If `immediate` is passed, trigger the function on the
	 * leading edge, instead of the trailing.
	 */
	function debounce( func, wait, immediate ) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	}



	/**
	 * Set or unset the new address form as active
	 */
	var changeNewAddressFormVisibility = function( addressBook, selectedAddress ) {
		// Bail if address book or selectedAddresss not available
		if ( ! addressBook || ! selectedAddress ) { return; }

		var newAddressForm = addressBook.querySelector( '.wfc-address-book__form-wrapper' );

		// Bail if new address form wrapper not available
		if ( ! newAddressForm ) { return; }

		// Set new address form state
		if ( selectedAddress.matches( _settings.addressEntryNewSelector ) ) { newAddressForm.classList.add( _settings.newAddressFormActiveClass ); }
		else { newAddressForm.classList.remove( _settings.newAddressFormActiveClass ); }
	}



	/**
	 * Set or unset the address book as having "same as" option selected
	 */
	var changeSameAsOptionSelectedState = function( addressBook, selectedAddress ) {
		// Bail if address book or selectedAddress not available
		if ( ! addressBook || ! selectedAddress ) { return; }

		var addressBookEntriesWrapper = addressBook.querySelector( _settings.addressBookEntriesWrapper );

		// Bail if new address form wrapper not available
		if ( ! addressBookEntriesWrapper ) { return; }

		// Set "same as" option checked state to address book element
		if ( selectedAddress.matches( _settings.addressEntrySameAsSelector ) ) { addressBookEntriesWrapper.setAttribute( _settings.sameAsEntryCheckedAttribute, '1' ); }
		else { addressBookEntriesWrapper.removeAttribute( _settings.sameAsEntryCheckedAttribute ); }
	}



	/**
	 * Get address data from saved address option's attribute
	 */
	var getAddressDataFromAttribute = function( selectedAddress ) {
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
	 * Get address data from input fields
	 */
	var getAddressDataFromFields = function( addressBook, selectedAddress ) {
		// Bail if address book or selected address not valid
		if ( ! addressBook || ! selectedAddress ) { return false; }

		var addressData = {};
		var addressType = selectedAddress.getAttribute( _settings.addressTypeAttribute );
		var fields = addressBook.querySelectorAll( _settings.addressFieldsSelector );

		for ( var i = 0; i < fields.length; i++ ) {
			var field = fields[i];
			var addressFieldName = field.getAttribute( 'name' ).replace( addressType+'_', '' );
			addressData[ addressFieldName ] = field.value;
		}

		return addressData;
	}



	/**
	 * Update data address attribute to the selected address option
	 */
	var updateAddressAttribute = function ( addressBook, selectedAddress ) {
		var addressData = getAddressDataFromFields( addressBook, selectedAddress );
		var addressDataString = JSON.stringify( addressData );
		selectedAddress.setAttribute( _settings.addressDataAttribute, addressDataString );
	}



	/**
	 * Set address field value
	 */
	var setFieldValue = function( field, value ) {
		// Bail if field not provided
		if ( ! field ) { return; }
		
		// Sanitize value
		value = value == undefined || value == null ? '' : value;

		// Set field value
		field.value = value;

		// Set field value for select2 fields
		if ( _hasJQuery && field.matches( _settings.select2Selector ) ) {
			$( field ).val( value );
			$( field ).select2().trigger( 'change' );
		}

		// Clear validation status
		if ( window.CheckoutValidation ) {
			CheckoutValidation.clearValidationResults( field, field.closest( _settings.formRowSelector ) );
		}
	}



	/**
	 * Fill up or clean address form with selected address option
	 */
	var changeAddressFormFields = function( addressBook, selectedAddress ) {
		// Bail if checkout update disabled
		if ( ! _updateCheckout ) return;

		// Bail if selected address not passed
		if ( ! selectedAddress ) { return; }

		clearAddressFields( addressBook );

		var addressData = getAddressDataFromAttribute( selectedAddress );

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
	 * Send selected address to server for persisting it's values
	 */
	var triggerUpdateCheckout = function() {
		// Bail if checkout update disabled
		if ( ! _updateCheckout ) return;

		// Update the checkout
		$( document.body ).trigger( 'update_checkout' );
	}







	/**
	 * Clear address form fields
	 */
	var clearAddressFields = function( addressBook ) {
		// Bail if address book element not passed
		if ( ! addressBook ) { return; }

		_updateCheckout = false;

		var fields = addressBook.querySelectorAll( _settings.addressFieldsSelector );
		for ( var i = 0; i < fields.length; i++ ) {
			var field = fields[i];
			
			// Skip address id fields
			if ( ! field.matches( _settings.addressFieldsCleanSelector ) ) {
				setFieldValue( field, '' );
			}
		}
		
		_updateCheckout = true;
	}



	/**
	 * Handle change to selected address option
	 */
	var changeSelectedAddress = function ( target ) {
		var addressBook = target.closest( _settings.addressBookSelector );
		changeNewAddressFormVisibility( addressBook, target );
		changeSameAsOptionSelectedState( addressBook, target );
		changeAddressFormFields( addressBook, target );
		triggerUpdateCheckout();
	}



	/**
	 * Handle change to persisted address fields
	 */
	var changePersistedAddressFields = function( e ) {
		// Bail if checkout update disabled
		if ( ! _updateCheckout ) return;
		
		var target = e.target;
		var addressBook = target.closest( _settings.addressBookSelector );
		var selectedAddress = addressBook.querySelector( _settings.selectedAddressIdSelector );

		if ( selectedAddress && selectedAddress.matches( _settings.addressEntryNewSelector ) ) {
			updateAddressAttribute( addressBook, selectedAddress );
		}
	}



	/**
	 * Prepare selector of target field of "copy to" function based origin field attributes
	 */
	var prepareCopyValueToFieldsSelector = function() {
		var copyFromFields = document.querySelectorAll( _settings.copyValueFromFieldSelector );

		
		for ( var index = 0; index < copyFromFields.length; index++) {
			var field = copyFromFields[ index ];
			var copyToSelector = field.getAttribute( _settings.copyValueToAttribute );
			
			if ( _copyValueToFieldSelector.indexOf( copyToSelector ) == -1 ) {
				_copyValueToFieldSelector += _copyValueToFieldSelector.length > 0 ? ', ' : '';
				_copyValueToFieldSelector += copyToSelector;
			}
		}
	}



	/**
	 * Copy value from one field to a target field defined as an attribute
	 */
	var copyFieldValue = function( changedField ) {
		var copyToSelector = changedField.getAttribute( _settings.copyValueToAttribute );
		
		// Bail if selector to copy to other fields is empty
		if ( ! copyToSelector || copyToSelector == '' ) { return; }

		var targetFields = document.querySelectorAll( copyToSelector );

		for ( var index = 0; index < targetFields.length; index++ ) {
			var field = targetFields[ index ];
			if ( ! field.matches( _settings.fieldEditedSelector ) ) {
				field.value = changedField.value;
			}
		}
	}



	/**
	 * Mark a field as edited by the user
	 */
	var markFieldAsEdited = function( field ) {
		// Bail if field not valid
		if ( ! field ) { return; }

		field.setAttribute( _settings.fieldEditedAttribute, '1' );
	}
	


	/**
	 * Handle captured `change` event and route to the appropriate function.
	 */
	var handleChange = function( e ) {
		if ( e.target.matches( _settings.addressEntrySelector ) ) {
			changeSelectedAddress( e.target );
		}
		else if ( e.target.matches( _settings.copyValueFromFieldSelector ) ) {
			copyFieldValue( e.target );
		}
		else if ( e.target.matches( _copyValueToFieldSelector ) ) {
			markFieldAsEdited( e.target );
		}
	};



	/**
	 * Handle change to persisted address fields
	 */
	var initEventHandlers = function( e ) {
		window.addEventListener( 'change', handleChange );

		$( document.body ).on( 'updated_checkout', initAddressFieldsEventHandlers );
		initAddressFieldsEventHandlers();
	}
	
	/**
	 * Add event handler to persisted address fields
	 */
	var initAddressFieldsEventHandlers = function() {
		if ( _hasJQuery ) {
			// Need to use jQuery event handler as select2 doesn't fire change event for the underlying select field
			var debouncedChangeHandler = debounce( changePersistedAddressFields, 500 );
			$( _settings.persistAddressFieldsSelector ).off( 'change', debouncedChangeHandler );
			$( _settings.persistAddressFieldsSelector ).on( 'change', debouncedChangeHandler );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;
		
		initEventHandlers();
		prepareCopyValueToFieldsSelector();

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
