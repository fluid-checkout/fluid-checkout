/**
 * Checkout scripts for: WooCommerce Carrier Agents (by Markup.fi).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutWooCarrierAgents = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		searchFieldSelector: 'input[name="woo-carrier-agents-postcode"]',
		searchButtonSelector: '#woo-carrier-agents-search-button',
		previousPostcodeFieldSelector: '#woo_carrier_agents-entered_postcode',
		radioFieldSelector: '.shipping-method__option input[type="radio"][name^="carrier-agent"]',
		selectFieldSelector: 'select[name^="carrier-agent"]',
		searchRadioFieldSelector: '.woo-carrier-agent',
	};
	var _enteredPostcode = '';


	/**
	 * METHODS
	 */



	/**
	 * Maybe update postcode field with the previously entered value.
	 */
	var maybeUpdatePostcodeField = function() {
		// Get postcode search input field
		var searchField = document.querySelector( _settings.searchFieldSelector );
		var searchButton = document.querySelector( _settings.searchButtonSelector );

		// Maybe update postcode field value
		if ( searchField && _enteredPostcode ) {
			searchField.setAttribute( 'value', _enteredPostcode );
		}

		// Maybe trigger search button click
		if ( searchButton ) {
			searchButton.click();
		}
	}



	/**
	 * Set previously entered postcode value as the current one.
	 */
	var setPreviousPostcodeValue = function() {
		var previousPostcodeField = document.querySelector( _settings.previousPostcodeFieldSelector );

		// Maybe set the previously entered postcode value
		if ( previousPostcodeField && previousPostcodeField.value) {
			_enteredPostcode = previousPostcodeField.value;
		}
	}



	/**
	 * Update postcode value.
	 */
	var updatePostcodeValue = function( element ) {
		// Maybe replace the old postcode with the entered value
		if ( element && element.value ) {
			_enteredPostcode = element.value;
		}
	}



	/**
	 * Trigger update checkout.
	 */
	var maybeTriggerCheckoutUpdate = function( element ) {
		// Bail if the target element is select and its value is empty
		if ( 'select' === element.type && ! element.value ) { return; }

		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// POSTCODE SEARCH RADIO FIELD
		if ( e.target.matches( _settings.searchRadioFieldSelector ) ) {
			maybeTriggerCheckoutUpdate( e.target );
		}
	};



	/**
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// SELECT OR RADIO FIELDS
		if ( e.target.matches( _settings.selectFieldSelector ) || e.target.matches( _settings.radioFieldSelector ) ) {
			maybeTriggerCheckoutUpdate( e.target );
		}
		// POSTCODE SEARCH FIELD
		else if ( e.target.matches( _settings.searchFieldSelector ) ) {
			updatePostcodeValue( e.target );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Set postcode value
		setPreviousPostcodeValue();

		// Add event listeners
		window.addEventListener( 'click', handleClick );
		window.addEventListener( 'change', handleChange );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			// Maybe update postcode field with the previously entered value when switching between shipping methods
			$( document.body ).on( 'updated_checkout', maybeUpdatePostcodeField );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
