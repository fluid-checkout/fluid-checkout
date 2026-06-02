/**
 * Manage checkout front-end validation for: Woocommerce GUS/Regon (by gotoweb.pl).
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
		root.CheckoutValidationWooCommerceGUS = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFieldSelector: '#gus_nip_value',
		validationStatusFieldSelector: '.validate-woocommerce-gus',

		validationErrorAttribute: 'data-error',
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
		// Bail if not a hungarian shipping method field
		if ( ! field.matches( _settings.typeFieldSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate VAT field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field has been verified.
	 */
	var validateField = function( field, formRow, validationEvent ) {
		// Get hidden field with verification status
		var validationStatusField = document.querySelector( _settings.validationStatusFieldSelector );

		// Return data-error as message if field is invalid
		if ( field.value && validationStatusField && '' === validationStatusField.value ) {
			var errorCode = validationStatusField.getAttribute( _settings.validationErrorAttribute );

			// Return as invalid
			return { valid: false, message: _settings.validationMessages[ errorCode ] };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'woocommerce-gus', 'woocommerce-gus', isValidateField, validateField );
	}



	/**
	 * Maybe validate VAT number field.
	 */
	var maybeValidateVATField = function() {
		// Get phone field to re-validate
		var field = document.querySelector( _settings.typeFieldSelector );

		// Bail if field has no value
		if ( ! field.value ) { return; }

		// Maybe trigger field valiation
		if ( window.CheckoutValidation ) {
			CheckoutValidation.validateField( field, 'change' );
		}
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

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document ).on( 'updated_checkout', maybeValidateVATField );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
