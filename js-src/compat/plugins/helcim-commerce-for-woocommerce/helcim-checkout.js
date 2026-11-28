/**
 * Restore the place order buttons when a payment ends on the checkout page for: Helcim Commerce for WooCommerce (by Helcim).
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce and Fluid Checkout events
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.HelcimCheckout = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		checkoutPlaceOrderSelector:    '#place_order, .fc-place-order-button',
		loadingClass:                  'fc-loading',
		disabledClass:                 'disabled',
		helcimPayEventNamePrefix:      'helcim-pay-js-',
		helcimPayHideEventStatus:      'HIDE',
	};
	var _isPaymentInProgress = false;



	/**
	 * METHODS
	 */



	/**
	 * Restore the place order buttons and checkout updates after a Helcim payment ends without leaving the page.
	 */
	var restorePlaceOrderState = function() {
		// Bail if no Helcim payment is in progress
		if ( ! _isPaymentInProgress ) { return; }

		// Clear the payment state
		_isPaymentInProgress = false;

		// Unblock checkout update requests
		window.can_update_checkout = true;

		// Unblock the place order buttons
		$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.loadingClass );
		$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
		$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.disabledClass );
	};



	/**
	 * Maybe set the Helcim payment as in progress when the place order request opens HelcimPay.
	 *
	 * @param   Event   _event  An unused `jQuery.Event` object.
	 * @param   object  result  The place order request result.
	 */
	var maybeSetPaymentInProgress = function( _event, result ) {
		// Bail if HelcimPay is not opened for this result
		if ( ! result || 'success' !== result.result || ! result.helcimCheckoutToken ) { return; }

		// Set the payment as in progress
		_isPaymentInProgress = true;
	};



	/**
	 * Handle captured `message` event and route to the appropriate functions.
	 *
	 * @param   MessageEvent  e  The message event.
	 */
	var handleMessage = function( e ) {
		// HELCIMPAY MODAL CLOSED
		if ( e.data && 'string' === typeof e.data.eventName && 0 === e.data.eventName.indexOf( _settings.helcimPayEventNamePrefix ) && _settings.helcimPayHideEventStatus === e.data.eventStatus ) {
			restorePlaceOrderState();
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Add event listeners
		window.addEventListener( 'message', handleMessage, true );

		// Add jQuery event listeners
		$( document.body ).on( 'fc_checkout_request_place_order_success', maybeSetPaymentInProgress );
		$( document.body ).on( 'checkout_error', restorePlaceOrderState );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
