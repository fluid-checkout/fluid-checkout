/**
 * Rebuild floating labels for Flatsome theme.
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
		root.FlatsomeFloatLabels = factory(root);
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
	 * Maybe rebuild floating labels component.
	 */
	var maybeRebuildFloatLabels = function() {
		// Add jQuery event listeners
		if ( window.floatlabels && typeof floatlabels.rebuild === 'function' ) {
			// Get current focused element
			var currentFocusedElement = document.activeElement;
			var value = currentFocusedElement.value;
			
			// Rebuild floating labels
			floatlabels.rebuild();
			
			// Re-focus element
			maybe_refocus_element( currentFocusedElement, value );
		}
	}

	/**
	 * Re-focus and keep value of the element that was previously focused.
	 */
	var maybe_refocus_element = function( currentFocusedElement, currentValue ) {
		// Bail if no element to focus
		if ( null === currentFocusedElement ) { return; }

		requestAnimationFrame( function() {
			var elementToFocus;

			// Try findind the `select2` focusable element
			if ( currentFocusedElement.matches( '.fc-select2-field' ) ) {
				elementToFocus = document.querySelector( '.form-row[id="' + currentFocusedElement.id + '"] .select2-selection' );
			}
			// Try findind the the current focused element after updating updated element by ID
			else if ( currentFocusedElement.id ) {
				elementToFocus = document.getElementById( currentFocusedElement.id );
			}
			// Try findind the updated element by classes
			else if ( currentFocusedElement.getAttribute( 'name' ) ) {
				var nameAttr = currentFocusedElement.getAttribute( 'name' );
				elementToFocus = document.querySelector( '[name="'+nameAttr+'"]' );
			}

			// Try setting focus if element is found
			if ( elementToFocus && elementToFocus !== document.activeElement ) {
				elementToFocus.focus();

				// Set keyboard track position back to that previously to update
				setTimeout( function(){
					// Try to set the same track position
					if( null !== elementToFocus.selectionStart && null !== elementToFocus.selectionEnd ) {
						if ( currentFocusedElement.selectionStart && currentFocusedElement.selectionEnd ) {
							elementToFocus.selectionStart = currentFocusedElement.selectionStart;
							elementToFocus.selectionEnd = currentFocusedElement.selectionEnd;
						}
						// Otherwise try set the track position to the end of the field
						// @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/setSelectionRange
						// @see https://html.spec.whatwg.org/multipage/input.html#concept-input-apply
						else {
							elementToFocus.selectionStart = elementToFocus.selectionEnd = Number.MAX_SAFE_INTEGER || 10000;
						}
					}
				}, 0 );
			}
		} );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		if ( _hasJQuery ) {
			// Rebuild on updates
			$( document.body ).on( 'init_checkout updated_checkout', maybeRebuildFloatLabels );
		}

		// Rebuild on initialization
		setTimeout( maybeRebuildFloatLabels, 100 );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
