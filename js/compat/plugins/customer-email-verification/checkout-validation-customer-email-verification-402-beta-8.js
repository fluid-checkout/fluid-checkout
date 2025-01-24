/**
 * Manage checkout front-end validation for: Customer Email Verification (by zorem).
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
		root.CheckoutValidationCustomerEmailVerification = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFieldSelector: '#billing_email',
		verificationStatusFieldSelector: '.validate-customer-email-verification',
		sectionSelector: '.fc-contact-fields',
		validationMessages: {
			email_not_verified: 'Please verify your email address.',
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
		// Bail if not a target contact field
		if ( ! field.matches( _settings.typeFieldSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate if the entered email has been verified.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the entered email has been verified.
	 */
	var validateField = function( field, formRow, validationEvent ) {
		// Get hidden field with verification status
		var verificationStatusField = document.querySelector( _settings.verificationStatusFieldSelector );

		// Check if verification status field value is empty
		if ( verificationStatusField && '' === verificationStatusField.value ) {
			// Scroll to section
			var section = document.querySelector( _settings.sectionSelector );
			if ( section && section.scrollIntoView ) {
				section.scrollIntoView();
			}

			// Return as invalid
			return { valid: false, message: _settings.validationMessages.email_not_verified };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'customer-email-verification', 'customer-email-verification', isValidateField, validateField );
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
