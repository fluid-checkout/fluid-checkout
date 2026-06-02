/**
 * Manage checkout front-end validation for: YITH WooCommerce Checkout Manager (by YITH).
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
		root.CheckoutValidationYithWooCommerceCheckoutManager = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFieldFormRowSelector: '.validate-vat',
		billingCountryFieldSelector: '#billing_country',

		validationRequiredClass: 'validate-required',
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
		// Bail if VAT validation is not enabled
		if ( ! _settings.is_vat_validation_enabled ) { return false; }

		// Bail if not the target field's form row
		if ( ! formRow.matches( _settings.typeFieldFormRowSelector ) ) { return false; }

		return true;
	};



	/**
	 * Validate the VAT field from the plugin.
	 * ADOPTED FROM: `ywccp_validatevat` function in frontend.js file of the plugin.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether a target shipping method collection point has been selected.
	 */
	var validateField = function( field, formRow, validationEvent ) {
		var billingCountryField = document.querySelector( _settings.billingCountryFieldSelector );
		var VATNumber = field.value;

		// Bail if function is not available
		if ( 'undefined' === typeof checkVATNumber ) { return { valid: true }; }

		// Bail if validation is not required or field value is empty
		if ( ! formRow.classList.contains( _settings.validationRequiredClass ) || '' === VATNumber ) { return { valid: true }; }

		// Bail if billing country is not set
		if ( ! billingCountryField || '' === billingCountryField.value ) { return { valid: true }; }

		// Maybe prepend country code to VAT number
		var prefix = VATNumber.substr( 0, 2 ).toUpperCase();
		var billingCountry = billingCountryField.value;
		if ( prefix !== billingCountry ) {
			VATNumber = billingCountry + VATNumber;
		}

		// Validate VAT number using the plugin's function
		if ( ! checkVATNumber( billingCountry, VATNumber ) ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.invalid_vat };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'yith-woocommerce-checkout-manager-vat', 'yith-woocommerce-checkout-manager-vat', isValidateField, validateField );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

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
