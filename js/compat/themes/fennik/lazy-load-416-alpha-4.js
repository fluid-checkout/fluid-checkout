/**
 * Lazy load images for Fennik theme.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 * - LaStudio // Theme objects and methods
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FennikLazyLoad = factory(root);
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
	 * Maybe lazy load images.
	 */
	var maybeLazyLoadImages = function() {
		// Bail if theme objects are not available
		if ( 'undefined' === typeof LaStudio || 'undefined' === typeof LaStudio.global ) { return; }

		// Bail if theme method is not available
		if ( 'function' !== typeof LaStudio.global.makeImageAsLoaded ) { return; }

		// Get all images that need to be loaded
		var images = $( 'body' ).find( '.la-lazyload-image:not([data-element-loaded="true"]), img[data-lazy-src]:not([data-element-loaded="true"]), img[data-lazy-original]:not([data-element-loaded="true"])' );

		// Load images
		images.each( function( idx, element ){
			LaStudio.global.makeImageAsLoaded( element );
		});

		// Trigger events
		$( 'body' ).trigger( 'lastudio-fix-ios-limit-image-resource' ).trigger( 'lastudio-lazy-images-load' ).trigger( 'jetpack-lazy-images-load' ).trigger( 'lastudio-object-fit' );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		if ( _hasJQuery ) {
			// Rebuild on updates
			$( document.body ).on( 'updated_checkout updated_cart_totals', maybeLazyLoadImages );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
