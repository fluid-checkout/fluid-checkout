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
		mapElementsSelector: '.inchoo-gls-map',
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
	 * Trigger update checkout.
	 */
	var triggerCheckoutUpdate = function() {
		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// SHIPPING METHOD RADIO FIELDS
		if ( e.target.matches( _settings.shippingMethodOptionSelector ) ) {
			removeHiddenInput();
		}
		// PICKUP POINT SELECTION FIELDS
		else if ( e.target.matches( _settings.mapElementsSelector ) ) {
			triggerCheckoutUpdate();
		}
	}



	/**
	 * Add event listener for map elements.
	 */
	var addMapElementsEventListeners = function() {
		var mapElements = document.querySelectorAll( _settings.mapElementsSelector );

		// Add "change" event listeners to all map elements
		for ( var i = 0; i < mapElements.length; i++ ) {
			var mapElement = mapElements[i];
			mapElement.addEventListener( 'change', handleChange );
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
		addMapElementsEventListeners();
		window.addEventListener( 'change', handleChange );

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
