/**
 * Manage checkout front-end validation for: Brazilian Market for WooCommerce (by Claudio Sanches)
 *
 * DEPENDS ON:
 * - checkout-validation.js // Main checkout validation script from Fluid Checkout
 * 
 * BASED ON:
 * - https://github.com/lourencorodrigo/validator-brazil
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutValidationBrazilianDocuments = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeCPFFieldSelector: '.validate-cpf',
		typeCNPJFieldSelector: '.validate-cnpj',
		validateCPF: 'no',
		validateCNPJ: 'no',
		validationMessages: {
			cpf_invalid: 'The CPF number "{cpf_number}" is invalid.',
			cnpj_invalid: 'The CNPJ number "{cpf_number}" is invalid.',
		},
	};
	var regexClearFormatting = /[\.\-\/]+/g;



	/**
	 * METHODS
	 */



	/**
	 * Check if form row is a CPF field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a CPF field.
	 */
	var isCPFField = function( field, formRow, validationEvent ) {
		// Bail if not a CPF field
		if ( ! formRow.matches( _settings.typeCPFFieldSelector ) ) { return false; }

		return true;
	};

	/**
	 * Verify if a CPF value is valid.
	 *
	 * @param   {string}  cpf  The CPF value, with or without formatting.
	 *
	 * @return  {bool}       `true` if CPF is valid, `false` otherwise.
	 */
	var isCPF = function( cpf ) {
		// Clear formatting
		cpf = cpf.replace( regexClearFormatting, '' );

		// Bail if value is empty
		if ( cpf == "" ) { return false };

		// Bail if it is a known invalid CPF value
		if (
			cpf.length != 11
			|| cpf == "00000000000"
			|| cpf == "11111111111"
			|| cpf == "22222222222"
			|| cpf == "33333333333"
			|| cpf == "44444444444"
			|| cpf == "55555555555"
			|| cpf == "66666666666"
			|| cpf == "77777777777"
			|| cpf == "88888888888"
			|| cpf == "99999999999"
		) {
			return false;
		}

		var add = 0;

		// Check first verification digit
		for ( var i = 0; i < 9; i++ ) {
			add += parseInt( cpf.charAt( i ) ) * ( 10 - i );
		}
		var rev = 11 - ( add % 11 );
		if ( rev == 10 || rev == 11 ) { rev = 0; }

		// Maybe return `false` if verification digit is invalid
		if ( rev != parseInt( cpf.charAt( 9 ) ) ) { return false; }
	  
		add = 0;
	  
		// Check second verification digit
		for ( var i = 0; i < 10; i++ ) {
			add += parseInt( cpf.charAt( i ) ) * ( 11 - i );
		}
		rev = 11 - ( add % 11 );
		if ( rev == 10 || rev == 11 ) rev = 0;

		// Maybe return `false` if verification digit is invalid
		if ( rev != parseInt( cpf.charAt( 10 ) ) ) { return false; }

		// Return `true` otherwise
		return true;
	}

	/**
	 * Validate CPF field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field has a valid CPF value.
	 */
	var validateCPFField = function( field, formRow, validationEvent ) {
		// Bail if field does not have a value, return as "null" to skip validation and let the required validation handle it
		if ( ! CheckoutValidation.hasValue( field ) ) { return { valid: null }; }

		// Clear formatting
		var fieldValue = field.value.replace( regexClearFormatting, '' );

		// Bail if user is still typing and field has not reached the minimum length, return as "null" to avoid showing error messages
		if ( 'input' === validationEvent && fieldValue.length < 11 ) { return { valid: null }; }

		// Bail if invalid CPF number
		if ( ! isCPF( field.value ) ) { return { valid: false, message: _settings.validationMessages.cpf_invalid.replace( '{cpf_number}', field.value ) } }

		// Field is valid
		return { valid: true };
	};



	/**
	 * Check if form row is a CNPJ field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a CNPF field.
	 */
	var isCNPJField = function( field, formRow, validationEvent ) {
		// Bail if not a CNPJ field
		if ( ! formRow.matches( _settings.typeCNPJFieldSelector ) ) { return false; }

		return true;
	};

	/**
	 * Verify if a CNPJ value is valid.
	 *
	 * @param   {string}  cnpj  The CNPJ value, with or without formatting.
	 *
	 * @return  {bool}       `true` if CNPJ is valid, `false` otherwise.
	 */
	var isCNPJ = function( cnpj ) {
		// Clear formatting
		cnpj = cnpj.replace( regexClearFormatting, '' );

		// Bail if empty
		if ( cnpj == "" ) { return false; }

		// Bail if invalid length
		if ( cnpj.length != 14 ) { return false; }

		// Bail if it is a known invalid number
		if (
			cnpj == "00000000000000"
			|| cnpj == "11111111111111"
			|| cnpj == "22222222222222"
			|| cnpj == "33333333333333"
			|| cnpj == "44444444444444"
			|| cnpj == "55555555555555"
			|| cnpj == "66666666666666"
			|| cnpj == "77777777777777"
			|| cnpj == "88888888888888"
			|| cnpj == "99999999999999"
		) {
			return false;
		}

		// Check first verification digit
		var size = cnpj.length - 2;
		var numbers = cnpj.substring( 0, size );
		var digits = cnpj.substring( size );
		var sum = 0;
		var pos = size - 7;
		for ( var i = size; i >= 1; i-- ) {
			sum += numbers.charAt( size - i ) * pos--;
			if ( pos < 2 ) { pos = 9; }
		}
		var result = sum % 11 < 2 ? 0 : 11 - ( sum % 11 );
		
		// Bail if first verification digit is invalid
		if ( result != digits.charAt( 0 ) ) { return false; }

		// Check second verification digit
		size = size + 1;
		numbers = cnpj.substring( 0, size );
		sum = 0;
		pos = size - 7;
		for ( var i = size; i >= 1; i-- ) {
			sum += numbers.charAt( size - i ) * pos--;
			if ( pos < 2 ) { pos = 9; }
		}
		result = sum % 11 < 2 ? 0 : 11 - ( sum % 11 );
		
		// Bail if second verification digit is invalid
		if ( result != digits.charAt( 1 ) ) { return false; }

		return true;
	}

	/**
	 * Validate CNPJ field value.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field has a valid CPF value.
	 */
	var validateCNPJField = function( field, formRow, validationEvent ) {
		// Bail if field does not have a value, return as "null" to skip validation and let the required validation handle it
		if ( ! CheckoutValidation.hasValue( field ) ) { return { valid: null }; }

		// Clear formatting
		var fieldValue = field.value.replace( regexClearFormatting, '' );

		// Bail if user is still typing and field has not reached the minimum length, return as "null" to avoid showing error messages
		if ( 'input' === validationEvent && fieldValue.length < 14 ) { return { valid: null }; }

		// Bail if invalid CNPJ number
		if ( ! isCNPJ( field.value ) ) { return { valid: false, message: _settings.validationMessages.cnpj_invalid.replace( '{cnpj_number}', field.value ) } }

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		// Maybe register CPF validation type
		if ( 'yes' === _settings.validateCPF ) {
			CheckoutValidation.registerValidationType( 'cpf_invalid', 'cpf', isCPFField, validateCPFField );
		}

		// Maybe register CNPJ validation type
		if ( 'yes' === _settings.validateCNPJ ) {
			CheckoutValidation.registerValidationType( 'cnpj_invalid', 'cnpj', isCNPJField, validateCNPJField );
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

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
