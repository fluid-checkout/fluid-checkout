/**
 * Utility resources shared across the scripts of Fluid Checkout.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.WooCommonInputEventHandler = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = { }
	var _settings = {
		fieldsSelector: '.woocommerce input, .woocommerce select, .woocommerce textarea',
	}



	/**
	 * METHODS
	 */



	/**
	 * Call Astra Pro `addAnimateClass` without breaking Select2-enhanced fields.
	 *
	 * @param   Element  field  Checkout form field.
	 */
	var callAddAnimateClass = function( field ) {
		// Bail if required function is not available
		if ( 'function' !== typeof window.addAnimateClass ) { return; }

		try {
			addAnimateClass( field );
		} catch ( error ) {
			// Astra Pro uses `:selected` with `querySelector`, which is invalid on Select2 fields.
		}
	};



	/**
	 * Re-run Astra Pro modern input animations after checkout fragments update.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 * @param   Array  data    The updated checkout data.
	 */
	var triggerInputEvents = function( _event, data ) {
		// Bail if required function is not available
		if ( 'function' !== typeof window.addAnimateClass ) { return; }

		requestAnimationFrame( function() {
			// Get fields
			var fields = document.querySelectorAll( _settings.fieldsSelector );
			for ( var i = 0; i < fields.length; i++ ) {
				callAddAnimateClass( fields[ i ] );
			}
		} );

	};



	/**
	 * Handle form field input event and route to the appropriate function.
	 */
	var handleInput = function( e ) {
		// FORM FIELDS
		if ( e.target.matches( _settings.fieldsSelector ) ) {
			// Trigger field label handler
			callAddAnimateClass( e.target );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		// Add event listeners
		window.addEventListener( 'input', handleInput );
		
		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', triggerInputEvents );
		}

		_hasInitialized = true;
	};



	/**
	 * Expose public APIs.
	 */
	return _publicMethods;

});
