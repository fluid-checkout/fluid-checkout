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
			// Set variables for current focused element
			FCUtils.setCurrentFocusedElementGlobalVariables();

			// Rebuild floating labels
			floatlabels.rebuild();

			// Re-focus element
			FCUtils.maybeRefocusElement( window.fcCurrentFocusedElement, window.fcCurrentFocusedElementValue );
		}
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
