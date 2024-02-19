/**
 * Manage checkout front-end validation for: SEUR Oficial (by SEUR Oficial).
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
		root.CheckoutValidationSeur = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFieldSelector: 'select[name="seur_pickup"]',
		sectionSelector: '.fc-shipping-method__packages',
		validationMessages: {
			pickup_point_not_selected: 'Selecting a pickup point is required before proceeding.',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Check if form row should be validated with this component.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field should be validated with this component.
	 */
	var isValidateField = function( field, formRow, validationEvent ) {
		// Bail if not the target field for this validation
		if ( ! field.matches( _settings.typeFieldSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate if a SEUR shipping method collection point is selected.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether a SEUR shipping method collection point has been selected.
	 */
	var validateField = function( field, formRow, validationEvent ) {
		// Bail if seur shipping method field is empty
		if ( '' === field.value || 'all' === field.value ) {
			// Scroll to section
			var section = document.querySelector( _settings.sectionSelector );
			if ( section && section.scrollIntoView ) {
				section.scrollIntoView();
			}

			// Return as invalid
			return { valid: false, message: _settings.validationMessages.pickup_point_not_selected };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'seur-shipping-method', 'seur-shipping-method', isValidateField, validateField );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Bail if `CheckoutValidation` is not available
		if ( ! window.CheckoutValidation ) { return; }

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
