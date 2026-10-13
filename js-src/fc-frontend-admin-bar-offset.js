/**
 * Sync admin bar visibility with body class for checkout header/progress bar offset.
 * Only applies offset when WordPress admin bar is actually visible in the viewport.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCFrontendAdminBarOffset = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		classInView: 'fc-admin-bar-in-view',
		adminBarSelector: '#wpadminbar',
		bodyAdminBarClass: 'admin-bar',
	}



	/**
	 * METHODS
	 */



	/**
	 * Update body class based on admin bar visibility.
	 *
	 * @param {boolean} isInView Whether the admin bar is in the viewport.
	 */
	var updateBodyClass = function( isInView ) {
		// Bail if body is not found
		if ( ! document.body ) { return; }

		// Toggle class based on admin bar visibility
		document.body.classList.toggle( _settings.classInView, isInView );
	}

	/**
	 * Check if admin bar is in viewport (synchronous check for initial state).
	 *
	 * @return {boolean} True if admin bar is visible in viewport.
	 */
	var isAdminBarInView = function() {
		// Get admin bar element
		var adminBar = document.getElementById( 'wpadminbar' );

		// Bail if admin bar is not found
		if ( ! adminBar ) { return false; }

		// Get bounding client rect and style
		var rect = adminBar.getBoundingClientRect();
		var style = root.getComputedStyle( adminBar );

		// Bail if admin bar is not visible
		if ( style.display === 'none' || style.visibility === 'hidden' ) { return false; }

		// Otherwise, return as visible
		return rect.bottom > 0 && rect.top < root.innerHeight;
	}



	/**
	 * Initialize observers for admin bar visibility.
	 */
	var initializeObservers = function() {
		// Get admin bar element
		var adminBar = document.getElementById( 'wpadminbar' );

		// Observe visibility changes (scroll, resize, etc.)
		if ( 'IntersectionObserver' in root ) {
			var observer = new IntersectionObserver(
				function ( entries ) {
					if ( entries.length > 0 ) {
						updateBodyClass( entries[0].isIntersecting );
					}
				},
				{
					root: null,
					rootMargin: '0px',
					threshold: 0
				}
			);
			observer.observe( adminBar );
		}
		// Fallback: check on scroll and resize
		else {
			var checkVisibility = function () {
				updateBodyClass( isAdminBarInView() );
			};
			root.addEventListener( 'scroll', checkVisibility, { passive: true } );
			root.addEventListener( 'resize', checkVisibility );
		}
	}



	/**
	 * Initialize component and set admin bar visibility observer.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if body has no admin-bar class
		if ( ! document.body || ! document.body.classList.contains( _settings.bodyAdminBarClass ) ) { return; }

		// Set initial state before first paint
		updateBodyClass( isAdminBarInView() );

		// Initialize observers
		initializeObservers();

		// Set initialized flag
		_hasInitialized = true;
	}



	//
	// Public APIs
	//
	return _publicMethods;
});
