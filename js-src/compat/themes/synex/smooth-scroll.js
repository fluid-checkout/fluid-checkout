/**
 * Stop smooth scrolling for Synex theme.
 *
 * DEPENDS ON:
 * - jQuery // Run after the theme document ready handlers
 * - ScrollSmoother // GSAP plugin loaded by the theme
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.SynexSmoothScroll = factory(root);
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
	 * Maybe stop the theme smooth scroller.
	 */
	var maybeStopScrollSmoother = function() {
		// Bail if smooth scroller is not available
		if ( 'undefined' === typeof ScrollSmoother || ! ScrollSmoother.get() ) { return; }

		// Stop the smooth scroller, as it moves the page content with transforms,
		// which makes sticky elements follow the content instead of the window
		ScrollSmoother.get().kill();
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Stop smooth scrolling after the theme starts it, in its own document ready handler
		if ( _hasJQuery ) {
			$( document ).ready( maybeStopScrollSmoother );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
