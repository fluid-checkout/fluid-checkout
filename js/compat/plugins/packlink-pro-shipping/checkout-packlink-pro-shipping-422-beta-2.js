/**
 * Checkout scripts for: Packlink PRO Shipping (by Packlink Shipping S.L.).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutPacklinkProShipping = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		buttonSelector: '.lp-select-button',
	};



	/**
	 * METHODS
	 */



	/**
	 * Trigget checkout fragments update.
	 */
	var triggerUpdateCheckout = function() {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// CHOOSE PICKUP POINT
		if ( e.target.closest( _settings.buttonSelector ) ) {
			// Add delay to ensure the hidden fields are updated before triggering checkout update
			setTimeout( function(){ triggerUpdateCheckout(); }, 1000 );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
