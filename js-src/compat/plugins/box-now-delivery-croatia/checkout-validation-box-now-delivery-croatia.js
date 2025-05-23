/**
 * Manage checkout front-end validation for: BOX NOW Delivery Croatia (by BOX NOW).
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
		root.CheckoutValidationBoxNowDeliveryCroatia = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFieldSelector: '.validate-box-now',
		sectionSelector: '.fc-shipping-method__packages',
		validationMessages: {
			pickup_point_not_selected: 'Selecting a pickup point is required before proceeding.',
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
	var isValidateField = function( field, formRow, validationEvent ) {
		// Bail if not a target shipping method field
		if ( ! field.matches( _settings.typeFieldSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate if the target shipping method collection point is selected.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether a target shipping method collection point has been selected.
	 */
	var validateField = function( field, formRow, validationEvent ) {
		// Bail if target shipping method field is empty
		if ( '' === field.value ) {
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
		CheckoutValidation.registerValidationType( 'box-now-shipping-method', 'box-now-shipping-method', isValidateField, validateField );
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
