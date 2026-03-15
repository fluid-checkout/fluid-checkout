/**
 * Manage checkout events triggered when customer interacts with the Rvvup checkout elements.
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
		root.RvvupCheckout = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		modalIframeSelector: '.rvvup-modal .rvvup-iframe',
	};



	/**
	 * METHODS
	 */



	/**
	 * Disable the `beforeunload` warning.
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
	 * Initialize mutation observer to watch for Rvvup modal iframe.
	 */
	var initializeMutationObserver = function() {
		// Bail if modal iframe selector is not set
		if ( ! _settings.modalIframeSelector ) { return; }

		// Get the modal iframe element
		var modalIframe = document.querySelector( _settings.modalIframeSelector );

		// Bail if modal iframe element is not found
		if ( ! modalIframe ) { return; }

		// Create observer that watches for attribute changes (src) on the modal iframe element
		var mutationObserver = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				if ( mutation.type === "attributes" && mutation.attributeName === "src" ) {
					disableBeforeUnloadWarning();
				}
			} );
		} );

		// Start observing the modal iframe for src attribute changes
		mutationObserver.observe( modalIframe, {
			attributes: true,
			attributeFilter: [ "src" ]
		} );
	};



	/**
	 * Handle payment method change and route to the appropriate function.
	 */
	var handlePaymentMethodChange = function() {
		// Re-enable beforeunload warning when user changes payment method
		// Required for cases where the user cancels the payment without completing it,
		// and switches to another payment method.
		enableBeforeUnloadWarning();
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		// Initialize mutation observer
		initializeMutationObserver();

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'payment_method_selected', handlePaymentMethodChange );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
