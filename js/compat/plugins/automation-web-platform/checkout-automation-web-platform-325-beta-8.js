/**
 * Checkout scripts for: Wawp - OTP Verification, Order Notifications, and Country Code Selector for WooCommerce (by Wawp).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutWAWP= factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		phoneFieldSelector: '#billing_phone',
	};



	/**
	 * METHODS
	 */



	/**
	 * Trigger update checkout.
	 */
	var triggerCheckoutUpdate = function() {
		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle captured `input` event and route to the appropriate functions.
	 */
	var handleInput = function( e ) {
		// PHONE NUMBER FIELD
		if ( e.target.matches( _settings.phoneFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Add event listeners
		window.addEventListener( 'input', handleInput );

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
