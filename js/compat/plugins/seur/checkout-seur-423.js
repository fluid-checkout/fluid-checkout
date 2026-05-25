/**
 * Checkout scripts for: SEUR Oficial (by SEUR Oficial).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutSeur = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		select2FieldsSelector: '.seur-pickup-select2',
	};



	/**
	 * METHODS
	 */



	/**
	 * Register validation types.
	 */
	var initSelect2Fields = function() {
		setTimeout( function() {
			$( _settings.select2FieldsSelector ).select2();
		}, 30 ); // Arbitrary delay to ensure the fields are properly initialized.
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		// Maybe initialize select2 fields.
		if ( _hasJQuery ) {
			initSelect2Fields();
			$( document.body ).on( 'updated_checkout', initSelect2Fields );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
