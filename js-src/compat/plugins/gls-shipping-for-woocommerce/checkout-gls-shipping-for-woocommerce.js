/**
 * Checkout scripts for: GLS Shipping for WooCommerce (by Inchoo).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutGLSShippingForWooCommerce = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		shippingMethodOptionSelector: 'input[type="radio"][name^="shipping_method"]',
		hiddenInputSelector: '#gls-pickup-info-data',
	};


	/**
	 * METHODS
	 */



	/**
	 * Remove hidden input created by GLS Shipping for WooCommerce plugin.
	 */
	var removeHiddenInput = function() {
		// Get postcode search input field
		var hiddenInput = document.querySelector( _settings.hiddenInputSelector );

		// Remove hidden input if exists
		if ( hiddenInput ) {
			hiddenInput.remove();
		}
	}



	/**
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// SHIPPING METHOD RADIO FIELDS
		if ( e.target.matches( _settings.shippingMethodOptionSelector ) ) {
			removeHiddenInput();
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		window.addEventListener( 'change', handleChange );

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
