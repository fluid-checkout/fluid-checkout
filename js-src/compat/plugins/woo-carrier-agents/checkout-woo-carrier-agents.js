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
		enteredPostcode: '',
	};



	/**
	 * METHODS
	 */



	/**
	 * Maybe update postcode field with the previously entered value.
	 */
	var maybeUpdatePostcodeField = function() {
		// Get postcode search input field
		var $searchField = $( _settings.searchFieldSelector );

		// Update postcode field with the previously entered value if the new one is not set
		if ( $searchField.length && _settings.enteredPostcode ) {
			$searchField.attr( 'value', _settings.enteredPostcode );
		}

		// Trigger search button click
		$( '#woo-carrier-agents-search-button' ).trigger( 'click' );
	}



	/**
	 * Update entered postcode value.
	 */
	var updatePostcodeValue = function() {
		// Replace the old postcode with the entered value
		_settings.enteredPostcode = $( this ).val();
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

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
