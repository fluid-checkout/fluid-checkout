/**
 * Sync admin bar visibility with body class for checkout header/progress bar offset.
 * Only applies offset when WordPress admin bar is actually visible in the viewport.
 *
 * DEPENDS ON:
 * - None
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCAdminBarOffset = factory(root);
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
		if ( ! document.body ) { return; }

		if ( isInView ) {
			document.body.classList.add( _settings.classInView );
		} else {
			document.body.classList.remove( _settings.classInView );
		}
	}

	/**
	 * Check if admin bar is in viewport (synchronous check for initial state).
	 *
	 * @return {boolean} True if admin bar is visible in viewport.
	 */
	var isAdminBarInView = function() {
		var adminBar = document.getElementById( 'wpadminbar' );
		if ( ! adminBar ) { return false; }

		var rect = adminBar.getBoundingClientRect();
		var style = root.getComputedStyle( adminBar );
		if ( style.display === 'none' || style.visibility === 'hidden' ) {
			return false;
		}

		return rect.bottom > 0 && rect.top < root.innerHeight;
	}



	/**
	 * Initialize component and set admin bar visibility observer.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if body has no admin-bar class
		if ( ! document.body || ! document.body.classList.contains( _settings.bodyAdminBarClass ) ) {
			return;
		}

		var adminBar = document.getElementById( 'wpadminbar' );
		if ( ! adminBar ) {
			updateBodyClass( false );
			return;
		}

		// Set initial state before first paint
		updateBodyClass( isAdminBarInView() );

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
		} else {
			// Fallback: check on scroll and resize
			var checkVisibility = function () {
				updateBodyClass( isAdminBarInView() );
			};
			root.addEventListener( 'scroll', checkVisibility, { passive: true } );
			root.addEventListener( 'resize', checkVisibility );
		}

		_hasInitialized = true;
	}



	//
	// Public APIs
	//
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function() { _publicMethods.init(); } );
	} else {
		_publicMethods.init();
	}

	return _publicMethods;
});
