/**
 * Manage checkout front-end validation for: WooCommerce EU Vat & B2B (by Lagudi Domenico).
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
		root.CheckoutValidationWooCommerceEUVatField = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		vatFieldSelector: '#billing_eu_vat',
		vatValidationFieldSelector: '.woocommerce-eu-vat-field-is-valid',
		vatUniquenessFieldSelector: '.woocommerce-eu-vat-field-is-unique',
		validationMessages: {
			vat_not_valid:     'Vat number is invalid',
			vat_not_unique:    'Vat number has been already associated to another user.',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Check if form row is a VAT validation status field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a VAT field.
	 */
	var isVatField = function( field, formRow, validationEvent ) {
		if ( ! field.matches( _settings.vatFieldSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate VAT number.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the date is inside the allowed delivery dates range. Returns `true` if the date is accepted for delivery, `false` otherwise.
	 */
	 var validateVatNumber = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.vatValidationFieldSelector );

		// Return data-error as message if field is invalid
		if ( validationStatusField && '' === validationStatusField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.vat_not_valid };
		}

		// Field is valid
		return { valid: true };
	};

	/**
	 * Validate VAT number uniqueness.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the date is enabled for delivery. Returns `true` if the date is enabled, `false` otherwise.
	 */
	var validateVatUniqueness = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.vatUniquenessFieldSelector );

		// Return data-error as message if field is invalid
		if ( validationStatusField && '' === validationStatusField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.vat_not_unique };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'vat_invalid', 'vat-invalid', isVatField, validateVatNumber );
		CheckoutValidation.registerValidationType( 'vat_not_unique', 'vat-not-unique', isVatField, validateVatUniqueness );
	}



	/**
	 * Validate VAT number field.
	 */
	var maybeValidateVatField = function() {
		// Get phone field to re-validate
		var field = document.querySelector( _settings.vatFieldSelector );

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
			$( document ).on( 'updated_checkout', maybeValidateVatField );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
