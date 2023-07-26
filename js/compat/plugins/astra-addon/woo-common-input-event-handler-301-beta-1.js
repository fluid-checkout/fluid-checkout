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

	var _hasInitialized = false;
	var _publicMethods = { }
	var _settings = {
		fieldsSelector: '.woocommerce input, .woocommerce select, .woocommerce textarea',
	}



	/**
	 * METHODS
	 */



	/**
	 * Handle form field input event and route to the appropriate function.
	 */
	var handleInput = function( e ) {
		// FORM FIELDS
		if ( e.target.matches( _settings.fieldsSelector ) ) {
			// Trigger field label handler
			if ( 'function' === typeof window.addAnimateClass ) {
				addAnimateClass( e.target );
			}
		}
	};




	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		// Add event listeners
		window.addEventListener( 'input', handleInput );

		_hasInitialized = true;
	};



	/**
	 * Expose public APIs.
	 */
	return _publicMethods;

});
