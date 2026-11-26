/**
 * Manage Helcim Commerce for WooCommerce checkout success with Fluid Checkout.
 *
 * Helcim v5 opens HelcimPay.js after place order and returns redirect `#helcim-pay`
 * with top-level helcim* fields on the AJAX result. Fluid Checkout must not follow
 * that hash while the form stays blocked, and must let Helcim keep those tokens.
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
		checkoutFormSelector:         'form.checkout',
		checkoutPlaceOrderSelector:   '#place_order, .fc-place-order-button',
		helcimIframeSelector:         'iframe#helcimPayIframe',
		loadingClass:                 'fc-loading',
		processingClass:              'processing',
		disabledClass:                'disabled',
		helcimModalFallbackTimeoutMs: 25000,
	};
	var _helcimModalOpen = false;
	var _mutationObserver = null;
	var _helcimModalFallbackTimeoutId = null;



	/**
	 * METHODS
	 */



	/**
	 * Unblock the checkout form processing overlay.
	 *
	 * @param   object  wc_checkout_form  The WooCommerce checkout form controller.
	 */
	var unblockCheckoutForm = function( wc_checkout_form ) {
		// Maybe unblock via the WooCommerce checkout form object
		if ( wc_checkout_form && wc_checkout_form.$checkout_form ) {
			wc_checkout_form.$checkout_form.removeClass( _settings.processingClass ).unblock();
			return;
		}

		// Otherwise unblock the checkout form directly
		$( _settings.checkoutFormSelector ).removeClass( _settings.processingClass ).unblock();
	};

	/**
	 * Disable place order buttons while HelcimPay is open.
	 */
	var disablePlaceOrderButtons = function() {
		$( _settings.checkoutPlaceOrderSelector ).attr( 'disabled', 'disabled' );
		$( _settings.checkoutPlaceOrderSelector ).addClass( _settings.disabledClass );
	};

	/**
	 * Enable place order buttons after HelcimPay closes or fails.
	 */
	var enablePlaceOrderButtons = function() {
		$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.loadingClass );
		$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
		$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.disabledClass );
	};

	/**
	 * Allow checkout fragment updates again.
	 */
	var allowCheckoutUpdates = function() {
		window.can_update_checkout = true;
	};

	/**
	 * Block checkout fragment updates while HelcimPay is open.
	 */
	var blockCheckoutUpdates = function() {
		window.can_update_checkout = false;
	};

	/**
	 * Clear the HelcimPay fallback restore timer.
	 */
	var clearHelcimModalFallbackTimeout = function() {
		// Bail if no fallback timer is active
		if ( null === _helcimModalFallbackTimeoutId ) { return; }

		window.clearTimeout( _helcimModalFallbackTimeoutId );
		_helcimModalFallbackTimeoutId = null;
	};

	/**
	 * Restore checkout UI after Helcim finishes, fails, or the customer closes the modal.
	 */
	var restoreCheckoutUiAfterHelcim = function() {
		// Bail if HelcimPay was not marked as open by this compat layer
		if ( ! _helcimModalOpen ) { return; }

		_helcimModalOpen = false;
		clearHelcimModalFallbackTimeout();
		enablePlaceOrderButtons();
		allowCheckoutUpdates();
	};

	/**
	 * Schedule a fallback restore when HelcimPay never opens or never signals failure.
	 */
	var scheduleHelcimModalFallbackRestore = function() {
		clearHelcimModalFallbackTimeout();

		_helcimModalFallbackTimeoutId = window.setTimeout( function() {
			_helcimModalFallbackTimeoutId = null;

			// Bail if Helcim already restored the UI
			if ( ! _helcimModalOpen ) { return; }

			restoreCheckoutUiAfterHelcim();
		}, _settings.helcimModalFallbackTimeoutMs );
	};

	/**
	 * Mark HelcimPay as open and keep place order disabled.
	 */
	var markHelcimModalOpen = function() {
		_helcimModalOpen = true;
		disablePlaceOrderButtons();
		blockCheckoutUpdates();
	};

	/**
	 * Handle a DOM node added while watching for HelcimPay.
	 *
	 * @param   Node  node  The DOM node that was added.
	 */
	var handleNodeAdded = function( node ) {
		// Bail if node is not an element
		if ( ! node || ! node.matches ) { return; }

		// Maybe mark HelcimPay as open when the iframe is added
		if ( node.matches( _settings.helcimIframeSelector ) ) {
			markHelcimModalOpen();
			return;
		}

		// Otherwise check nested iframes on the added body child
		if ( node.querySelector && node.querySelector( _settings.helcimIframeSelector ) ) {
			markHelcimModalOpen();
		}
	};

	/**
	 * Handle a DOM node removed while watching for HelcimPay.
	 *
	 * @param   Node  node  The DOM node that was removed.
	 */
	var handleNodeRemoved = function( node ) {
		// Bail if HelcimPay was not open
		if ( ! _helcimModalOpen ) { return; }

		// Bail if node is not an element
		if ( ! node || ! node.matches ) { return; }

		// Maybe restore UI when the HelcimPay iframe is removed
		if ( node.matches( _settings.helcimIframeSelector ) ) {
			restoreCheckoutUiAfterHelcim();
			return;
		}

		// Otherwise check nested iframes on the removed body child
		if ( node.querySelector && node.querySelector( _settings.helcimIframeSelector ) ) {
			restoreCheckoutUiAfterHelcim();
		}
	};

	/**
	 * Start MutationObserver to watch for the HelcimPay iframe.
	 */
	var startMutationObserver = function() {
		// Bail if already observing
		if ( _mutationObserver ) { return; }

		_mutationObserver = new MutationObserver( function( mutations ) {
			// Iterate detected mutations
			for ( var i = 0; i < mutations.length; i++ ) {
				var mutation = mutations[ i ];

				// Iterate added nodes
				for ( var j = 0; j < mutation.addedNodes.length; j++ ) {
					handleNodeAdded( mutation.addedNodes[ j ] );
				}

				// Iterate removed nodes
				for ( var k = 0; k < mutation.removedNodes.length; k++ ) {
					handleNodeRemoved( mutation.removedNodes[ k ] );
				}
			}
		} );

		// Watch direct children of body only. HelcimPay appends the iframe (or a wrapper) there.
		_mutationObserver.observe( document.body, {
			childList: true,
			subtree:   false,
		} );
	};



	/**
	 * Handle Helcim place-order success before Fluid Checkout follows the redirect.
	 *
	 * Must return `false` so Fluid Checkout exits on `fc_checkout_request_place_order_success`
	 * without following `#helcim-pay` or treating the response as invalid. Helcim's own
	 * `checkout_place_order_success` handler is triggered here so tokens stay on `result`.
	 *
	 * @param   Event   _event            An unused `jQuery.Event` object.
	 * @param   object  result            The checkout AJAX result from WooCommerce.
	 * @param   object  wc_checkout_form  The WooCommerce checkout form controller.
	 * @return  boolean|undefined         `false` to stop Fluid Checkout success handling.
	 */
	var checkoutFormSubmitHandler = function( _event, result, wc_checkout_form ) {
		// Bail if not a Helcim success response with a checkout token
		if ( ! result || 'success' !== result.result || ! result.helcimCheckoutToken ) { return; }

		// Remove the blockUI overlay so HelcimPay is not covered by the spinner
		unblockCheckoutForm( wc_checkout_form );

		// Track HelcimPay until the iframe appears or Helcim fails without opening it.
		// Place order stays enabled until the iframe is added so a Helcim failure
		// without checkout_error cannot leave the button stuck disabled.
		_helcimModalOpen = true;
		scheduleHelcimModalFallbackRestore();

		// Let Helcim open HelcimPay.js with the tokens from this AJAX result
		$( _settings.checkoutFormSelector ).triggerHandler( 'checkout_place_order_success', [ result, wc_checkout_form ] );

		// Stop Fluid Checkout from navigating to `#helcim-pay`
		return false;
	};

	/**
	 * Restore checkout UI when Helcim triggers a checkout error notice.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 */
	var checkoutErrorHandler = function( _event ) {
		restoreCheckoutUiAfterHelcim();
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Watch for HelcimPay iframe open and close
		startMutationObserver();

		// Place order success before Fluid Checkout follows the redirect
		$( document.body ).on( 'fc_checkout_request_place_order_success', checkoutFormSubmitHandler );

		// Helcim shows a notice when payment cannot be completed
		$( document.body ).on( 'checkout_error', checkoutErrorHandler );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
