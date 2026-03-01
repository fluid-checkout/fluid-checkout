/**
 * Manage checkout events triggered when customer interacts with the Revolut buttons and popups.
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
		root.PaymentPluginsRevolutCheckoutEvents = factory(root);
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
	 * Set checkout to be not updatable.
	 */
	var setCheckoutNotUpdatable = function() {
		window.can_update_checkout = false;
	};

	/**
	 * Set checkout to be updatable.
	 */
	var setCheckoutUpdatable = function() {
		window.can_update_checkout = true;
	};



	/**
	 * Disable the `beforeunload` warning.
	 * 
	 * This is needed because Revolut uses `window.location.href` to redirect after payment,
	 * which triggers the `beforeunload` event after successful payment.
	 */
	var disableBeforeUnloadWarning = function() {
		window.can_prevent_unload = false;
	};

	/**
	 * Enable the `beforeunload` warning.
	 */
	var enableBeforeUnloadWarning = function() {
		window.can_prevent_unload = true;
	};



	/**
	 * Check if the element is a Revolut payment iframe.
	 * 
	 * @param   string  src  The iframe src URL.
	 */
	var isPaymentIframe = function( src ) {
		// Initialize variables
		var isPaymentIframe = false;

		// Bail if iframe's source URL is not related to Revolut
		if ( ! src || -1 === src.indexOf( 'revolut.com' ) ) { return isPaymentIframe; }
		
		// Filter out non-payment iframes
		var nonPaymentPatterns = [ 'upsell', 'banner', 'promotional', 'revolut-pay-button' ];
		for ( var i = 0; i < nonPaymentPatterns.length; i++ ) {
			// Bail out if any non-payment pattern is found in the src URL
			if ( -1 !== src.indexOf( nonPaymentPatterns[i] ) ) { return isPaymentIframe; }
		}

		// Set flag to true
		isPaymentIframe = true;

		return isPaymentIframe;
	};



	/**
	 * Handle node added to DOM.
	 * 
	 * When MutationObserver detects a new node, check if it's a Revolut payment iframe.
	 * If so, disable checkout updates to prevent interference with the payment process.
	 * 
	 * @param   Node  node  The DOM node that was added.
	 */
	var handleNodeAdded = function( node ) {
		// Set checkout as not updatable if the added node is a payment iframe
		if ( 'IFRAME' === node.tagName && isPaymentIframe( node.src ) ) {
			setCheckoutNotUpdatable();
			disableBeforeUnloadWarning();
		}
		// Otherwise, maybe check if the added node contains payment iframes (nested)
		else if ( node.querySelectorAll ) {
			// Get all nested iframes
			var nestedIframes = node.querySelectorAll( 'iframe' );

			// Iterate through nested iframes
			for ( var i = 0; i < nestedIframes.length; i++ ) {
				// Maybe set checkout as not updatable if a nested iframe is a payment iframe
				if ( isPaymentIframe( nestedIframes[i].src ) ) {
					setCheckoutNotUpdatable();
					disableBeforeUnloadWarning();
					return;
				}
			}
		}
	};

	/**
	 * Handle node removed from DOM.
	 * 
	 * When MutationObserver detects a removed node, check if it's a Revolut payment iframe.
	 * If so, re-enable checkout updates since the payment window has closed.
	 * 
	 * @param   Node  node  The DOM node that was removed.
	 */
	var handleNodeRemoved = function( node ) {
		// Check if the removed node is a payment iframe
		if ( 'IFRAME' === node.tagName && isPaymentIframe( node.src ) ) {
			setCheckoutUpdatable();
		}
	};



	/**
	 * Start MutationObserver to watch for Revolut iframes.
	 */
	var startMutationObserver = function() {
		// Create observer that watches for added/removed nodes in the DOM
		var mutationObserver = new MutationObserver( function( mutations ) {
			// Loop through all detected mutations
			for ( var i = 0; i < mutations.length; i++ ) {
				var mutation = mutations[i];
				
				// Check all nodes that were added to the DOM
				for ( var j = 0; j < mutation.addedNodes.length; j++ ) {
					handleNodeAdded( mutation.addedNodes[j] );
				}
				
				// Check all nodes that were removed from the DOM
				for ( var k = 0; k < mutation.removedNodes.length; k++ ) {
					handleNodeRemoved( mutation.removedNodes[k] );
				}
			}
		} );

		// Start observing the body element
		mutationObserver.observe( document.body, {
			childList: true,  // Watch for added/removed child nodes
			subtree: false    // Watch only direct children elements of the body
		} );
	};



	/**
	 * Handle payment method change and route to the appropriate function.
	 */
	var handlePaymentMethodChange = function() {
		// Re-enable beforeunload warning when user changes payment method
		// Required for cases where the user cancels Revolut payment without completing it,
		// and switches to another gateway.
		enableBeforeUnloadWarning();
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		// Start MutationObserver to watch for Revolut payment iframes
		startMutationObserver();

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			jQuery( document.body ).on( 'payment_method_selected', handlePaymentMethodChange );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
