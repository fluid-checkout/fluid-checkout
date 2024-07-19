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
	var updatePostcodeValue = function( e ) {
		// Maybe replace the old postcode with the entered value
		if ( e.target && e.target.value ) {
			_enteredPostcode = e.target.value;
		}
	}



	/**
	 * Trigger update checkout.
	 */
	var maybeTriggerCheckoutUpdate = function(e) {
		// Bail if no target element is set
		if ( ! e.target ) { return; }

		// Bail if the target element is select and its value is empty
		if ( 'select' === e.target.type && ! e.target.value ) { return; }

		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
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
			// Trigger update checkout when switching between the pickup terminals
			$( document ).on( 'change', _settings.selectFieldSelector, maybeTriggerCheckoutUpdate );
			$( document ).on( 'change', _settings.radioFieldSelector, maybeTriggerCheckoutUpdate );
			// Use 'click' event to avoid infinite loop of 'updated_checkout' followed by 'change' event when using postcode search
			$( document ).on( 'click', _settings.searchRadioFieldSelector, maybeTriggerCheckoutUpdate );

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
