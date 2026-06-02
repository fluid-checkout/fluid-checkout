/**
 * Manage checkout front-end validation for: BRT Fermopoint (by BRT)
 *
 * DEPENDS ON:
 * - checkout-validation.js // Main checkout validation script from Fluid Checkout
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutValidationFermopoint = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFermopointFieldSelector: '.validate-fermopoint',
		fermopointSectionSelector: '#wc_brt_fermopoint_shipping_methods_custom-tr_container',
		validationMessages: {
			fermopoint_not_selected: 'Selecting a collection point is required when shipping with FermoPoint.',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Check if form row is a CNPJ field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a CNPF field.
	 */
	var isFermopointField = function( field, formRow, validationEvent ) {
		// Bail if Fermopoint shipping method not selected
		if ( ! isSelectedBrtFermopointShippingMethod() ) { return false; }

		// Bail if not a Fermopoint field
		if ( ! field.matches( _settings.typeFermopointFieldSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate if a fermopoint collection point is selected.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether a fermopoint collection point has been selected.
	 */
	var validateFermpoint = function( field, formRow, validationEvent ) {
		// Bail if fermopoint field is empty
		if ( '' === field.value ) {
			// Scroll to fermopoint section
			var fermopointSection = document.querySelector( _settings.fermopointSectionSelector );
			if ( fermopointSection && fermopointSection.scrollIntoView ) {
				fermopointSection.scrollIntoView();
			}

			// Return as invalid
			return { valid: false, message: _settings.validationMessages.fermopoint_not_selected };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'fermopoint', 'fermopoint', isFermopointField, validateFermpoint );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Bail if `CheckoutValidation` is not available
		if ( ! window.CheckoutValidation ) { return; }

		// Bail if global functions from Fermpoint script are not available
		if ( ! window.isSelectedBrtFermopointShippingMethod ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Register validation types
		registerValidationTypes();

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
