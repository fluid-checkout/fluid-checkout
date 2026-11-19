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
	var _originalScrollSmootherCreate;



	/**
	 * METHODS
	 */



	/**
	 * Maybe stop the theme smooth scroller.
	 */
	var maybeStopScrollSmoother = function() {
		// Bail if smooth scroller is not available
		if ( 'undefined' === typeof ScrollSmoother || ! ScrollSmoother.get() ) { return; }

		// Stop the smooth scroller
		ScrollSmoother.get().kill();
	};



	/**
	 * Create the theme smooth scroller and stop it right away.
	 *
	 * @param   {Object}  vars  The smooth scroller options passed by the theme.
	 *
	 * @return  {Object}        The stopped smooth scroller.
	 */
	var createStoppedScrollSmoother = function( vars ) {
		// Create and stop the smooth scroller
		var smoother = _originalScrollSmootherCreate.call( ScrollSmoother, vars );
		smoother.kill();

		return smoother;
	};

	/**
	 * Stop the theme smooth scroller right when the theme creates it.
	 * Stopping it later leaves a frame where the smooth scroller moves the content up behind the admin bar.
	 */
	var stopScrollSmootherOnCreate = function() {
		// Bail if smooth scroller is not available
		if ( 'undefined' === typeof ScrollSmoother ) { return; }

		// Replace the create method, as the theme never uses the smooth scroller after creating it
		_originalScrollSmootherCreate = ScrollSmoother.create;
		ScrollSmoother.create = createStoppedScrollSmoother;
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Stop smooth scrolling when the theme starts it
		stopScrollSmootherOnCreate();

		// Maybe stop smooth scrolling already started
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
