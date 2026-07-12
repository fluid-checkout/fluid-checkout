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
		modalRvvupIframeSelector:         '.rvvup-modal .rvvup-iframe',
		modalCardinalIframeSelector:      '#Cardinal-CCA-IFrame',
	};



	/**
	 * METHODS
	 */



	/**
	 * Disable the `beforeunload` warning.
	 */
	var disableUnloadAndPaymentUpdates = function() {
		window.can_prevent_unload = false;
		window.can_update_payment_methods = false;
	};

	/**
	 * Enable the `beforeunload` warning.
	 */
	var enableUnloadAndPaymentUpdates = function() {
		window.can_prevent_unload = true;
		window.can_update_payment_methods = true;
	};



	/**
	 * Process modal iframe src mutation.
	 *
	 * @param   {Object}  mutation  The mutation object.
	 */
	var processModalIframeSrcMutation = function( mutation ) {
		// Bail if mutation type is not `attributes`
		if ( mutation.type !== "attributes" ) { return; }

		// Bail if mutation attribute name is not `src`
		if ( mutation.attributeName !== "src" ) { return; }

		// Disable payment method update and beforeunload warning
		disableUnloadAndPaymentUpdates();
	};

	/**
	 * Initialize mutation observer to watch for Rvvup modal iframe.
	 */
	var initializeRvvupModalMutationObserver = function() {
		// Bail if modal iframe selector is not set
		if ( ! _settings.modalRvvupIframeSelector ) { return; }

		// Get the modal iframe element
		var modalRvvupIframe = document.querySelector( _settings.modalRvvupIframeSelector );

		// Bail if modal iframe element is not found
		if ( ! modalRvvupIframe ) { return; }

		// Create observer that watches for attribute changes (src) on the modal iframe element
		var mutationObserver = new MutationObserver( function( mutationsList ) {
			mutationsList.forEach( processModalIframeSrcMutation );
		} );

		// Observe
		mutationObserver.observe( modalRvvupIframe, {
			attributes: true,
			attributeFilter: [ "src" ]
		} );
	};



	/**
	 * Initialize mutation observer to watch for Cardinal element added or removed from the DOM.
	 */
	var initializeCardinalElementAddedMutationObserver = function() {
		// Bail if modal iframe selector is not set
		if ( ! _settings.modalCardinalIframeSelector ) { return; }

		// Create a mutation observer to monitor DOM changes (children added/removed) on document body
		var observer = new MutationObserver( function( mutationsList ) {
			for ( var i = 0; i < mutationsList.length; i++ ) {
				// Get the mutation
				var mutation = mutationsList[ i ];

				// Maybe skip mutation if not added nodes
				if ( mutation.type !== 'childList' ) { continue; }

				// Get added and removed nodes
				var addedNodes = mutation.addedNodes ? mutation.addedNodes : [];
				var removedNodes = mutation.removedNodes ? mutation.removedNodes : [];

				// Iterate through added nodes
				for ( var j = 0; j < addedNodes.length; j++ ) {
					// Get the added node
					var addedNode = addedNodes[ j ];

					// Detects when the target element is added to the DOM
					// Checks if the added node or any of its descendants matches the selector
					if (
						( addedNode.nodeType === 1 && addedNode.matches && addedNode.matches( _settings.modalCardinalIframeSelector ) ) ||
						( addedNode.nodeType === 1 && addedNode.querySelector && addedNode.querySelector( _settings.modalCardinalIframeSelector ) )
					) {
						// Disable payment method update and beforeunload warning
						disableUnloadAndPaymentUpdates();
						return;
					}
				}

				// Iterate through removed nodes
				for ( var k = 0; k < removedNodes.length; k++ ) {
					// Get the removed node
					var removedNode = removedNodes[ k ];

					// Detects when the target element is removed from the DOM
					// Checks if the removed node or any of its descendants matches the selector
					if (
						( removedNode.nodeType === 1 && removedNode.matches && removedNode.matches( _settings.modalCardinalIframeSelector ) ) ||
						( removedNode.nodeType === 1 && removedNode.querySelector && removedNode.querySelector( _settings.modalCardinalIframeSelector ) )
					) {
						// Enable payment method update and beforeunload warning
						enableUnloadAndPaymentUpdates();
						return;
					}
				}
			}
		} );	

		// Observe
		observer.observe( document.body, { childList: true, subtree: true } );
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		// Initialize mutation observers
		initializeRvvupModalMutationObserver();
		initializeCardinalElementAddedMutationObserver();

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
