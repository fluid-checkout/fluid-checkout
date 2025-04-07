/**
 * Manage checkout events triggered when customer interacts with the PayPal buttons and popups.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.PaymentPluginsPayPalCheckoutEvents = factory(root);
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
	 * Maybe set the checkout page as updatable or not based on the PayPal Checkout events.
	 *
	 * @param   object  e       The event object.
	 * @param   object  source  The source data object from the PayPal plugin.
	 */
	var maybeSetCheckoutUpdatableState = function( e, source ) {
		if ( 'wc_ppcp_on_click' === e.type ) {
			window.can_update_checkout = false;
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		if ( _hasJQuery ) {
			// PayPal Checkout events
			$( document.body ).on( 'wc_ppcp_on_init', maybeSetCheckoutUpdatableState );
			$( document.body ).on( 'wc_ppcp_on_click', maybeSetCheckoutUpdatableState );
			$( document.body ).on( 'wc_ppcp_on_approve', maybeSetCheckoutUpdatableState );
			$( document.body ).on( 'wc_ppcp_on_cancel', maybeSetCheckoutUpdatableState );
			$( document.body ).on( 'wc_ppcp_on_error', maybeSetCheckoutUpdatableState );
			$( document.body ).on( 'wc_ppcp_on_destroy', maybeSetCheckoutUpdatableState );

			_hasInitialized = true;
		}
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
