/**
 * Handle updates of UPS pickup location.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.UpsPickupLocationHandler = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};




	/**
	 * METHODS
	 */



	/**
	 * Trigger updating the checkout form fragments after a small delay, to let the other scripts fill up the necessary fields.
	 */
	var delayedTriggerUpdateCheckout = function() {
		requestAnimationFrame( function() {
			// Trigger update checkout
			$( document.body ).trigger( 'update_checkout' );
		} );
	}



	/**
	 * Initialize script.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		// Set jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'pickups-after-choosen', delayedTriggerUpdateCheckout );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
