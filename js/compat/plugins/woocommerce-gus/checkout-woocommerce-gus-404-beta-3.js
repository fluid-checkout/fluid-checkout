/**
 * Checkout scripts for: Woocommerce GUS/Regon (by gotoweb.pl).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutWooCommerceGUS = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		VATFieldSelector: '#gus_nip_value',
	};



	/**
	 * METHODS
	 */


	/**
	 * Trigger update checkout.
	 */
	var triggerCheckoutUpdate = function() {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle keypress event event and route to the appropriate functions.
	 */
	var handleKeyDown = function( e ) {
		// VAT FIELD
		if ( e.target.matches( _settings.VATFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Add event listeners
		document.addEventListener( 'keydown', handleKeyDown );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
