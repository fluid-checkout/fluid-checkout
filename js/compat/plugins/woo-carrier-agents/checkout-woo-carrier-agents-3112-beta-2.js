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
		if ( _enteredPostcode ) {
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

		_enteredPostcode = previousPostcodeField.value;
	}



	/**
	 * Update postcode value.
	 */
	var updatePostcodeValue = function() {
		// Replace the old postcode with the entered value
		_enteredPostcode = this.value;
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Set postcode value
		setPreviousPostcodeValue();

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			// Maybe update postcode field with the previously entered value when switching between shipping methods
			$( document.body ).on( 'updated_checkout', maybeUpdatePostcodeField );

			// Update postcode value when the field changes
			$( document ).on( 'change', _settings.searchFieldSelector, updatePostcodeValue );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
