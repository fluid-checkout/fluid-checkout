/**
 * Checkout scripts for: Mollie Payments for WooCommerce - Billie
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.MollieBillieCompanyMirror = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		originFieldSelector: '#billing_company',
		targetFieldSelector: '.woocommerce-billing-fields #billing_company, .payment_method_mollie_wc_gateway_billie #billing_company',
	};



	/**
	 * METHODS
	 */



	/**
	 * Maybe copy billing company value to other fields matching fields.
	 */
	var maybeCopyBillingCompanyValue = function ( e ) {
		// Bail if not target element
		if ( ! e.target.matches( _settings.originFieldSelector ) ) { return; }
		
		// Get field value
		var fieldValue = e.target.value;
		
		// Get target fields
		var targetFields = document.querySelectorAll( _settings.targetFieldSelector );
		
		// Iterate target fields and set new value
		for ( var i = 0; i < targetFields.length; i++ ) {
			var targetField = targetFields[ i ];
			targetField.value = fieldValue;
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		document.addEventListener( 'keyup', maybeCopyBillingCompanyValue, true );
		document.addEventListener( 'input', maybeCopyBillingCompanyValue, true );
		document.addEventListener( 'change', maybeCopyBillingCompanyValue, true );

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
