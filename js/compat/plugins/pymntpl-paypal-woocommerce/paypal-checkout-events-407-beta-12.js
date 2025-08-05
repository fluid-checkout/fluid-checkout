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
	var _settings = {
		alteredStateFieldSelector: '#pymntpl-paypal-woocommerce-fields_altered',
	};



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
		// Get altered state field
		var alteredStateField = document.querySelector( _settings.alteredStateFieldSelector );
		if ( alteredStateField ) {
			// Set the altered state field to false
			alteredStateField.value = 'true';
		}
	};



	/**
	 * Maybe reload the checkout page.
	 * Required to ensure that the field values are restored back to the original values from the session.
	 * 
	 * @param   object  e       The event object.
	 * @param   object  source  The source data object from the PayPal plugin.
	 */
	var maybeReloadCheckoutPage = function( e, source ) {
		// Get altered state field
		var alteredStateField = document.querySelector( _settings.alteredStateFieldSelector );

		// Bail if the altered state field is not set to true
		if ( ! alteredStateField ||'true' !== alteredStateField.value ) { return; }

		// Reload the checkout page
		window.location.reload();
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		if ( _hasJQuery ) {
			// PayPal Checkout events
			$( document.body ).on( 'wc_ppcp_on_click', maybeSetCheckoutUpdatableState );
			$( document.body ).on( 'wc_ppcp_on_cancel', maybeReloadCheckoutPage );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
