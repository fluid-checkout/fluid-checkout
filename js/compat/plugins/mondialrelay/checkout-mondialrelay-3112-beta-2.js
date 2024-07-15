/**
 * Checkout scripts for: SEUR Oficial (by SEUR Oficial).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutMondialRelay = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		buttonSelector: '#delivery_point_chosen',
	};



	/**
	 * METHODS
	 */



	/**
	 * Update checkout fields.
	 */
	var updateCheckout = function() {
		// Create update checkout event
		var event = new Event( 'update_checkout' );

		// Trigger event
		document.body.dispatchEvent( event );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		// Update checkout fields on button click
		if ( _hasJQuery ) {
			$( _settings.buttonSelector ).on( 'click', updateCheckout );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
