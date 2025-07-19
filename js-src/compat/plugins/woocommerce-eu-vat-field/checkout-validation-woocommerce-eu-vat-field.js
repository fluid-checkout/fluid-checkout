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
		sdiFieldSelector: '#billing_it_sid_pec',
		codiceFiscaleFieldSelector: '#billing_it_codice_fiscale',
		nifNieFieldSelector: '#billing_es_nif_nie',

		vatValidationFieldSelector: '.woocommerce-eu-vat-field-is-valid',
		vatUniquenessFieldSelector: '.woocommerce-eu-vat-field-is-unique',
		sdiValidationFieldSelector: '.woocommerce-eu-vat-field-is-cdi-field-valid',
		codiceFiscaleValidationFieldSelector: '.woocommerce-eu-vat-field-is-codice-fiscale-field-valid',
		nifNieValidationFieldSelector: '.woocommerce-eu-vat-field-is-nif-nie-field-valid',

		validationMessages: {
			vat_not_valid:     'Vat number is invalid',
			vat_not_unique:    'Vat number has been already associated to another user.',
			sdi_not_valid:     'SDI/Pec has an invalid format. Please check!',
			vat_empty:         'Vat field cannot be empty. Enter a valid vat or remove the SDI/Pec field content.',
			codice_fiscale_not_valid: 'Codice Fiscale has an invalid format.',
			nif_nie_not_valid: 'NIF / NIE code has an invalid format. Please check!',
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
	 * Check if form row is a SDI field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a SDI field.
	 * */
	var isSdiField = function( field, formRow, validationEvent ) {
		if ( ! field.matches( _settings.sdiFieldSelector ) ) { return false; }

		return true;
	}

	/**
	 * Check if form row is a Codice Fiscale field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a Codice Fiscale field.
	 */
	var isCodiceFiscaleField = function( field, formRow, validationEvent ) {
		if ( ! field.matches( _settings.codiceFiscaleFieldSelector ) ) { return false; }

		return true;
	}

	/**
	 * Check if form row is a NIF/NIE field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a NIF/NIE field.
	 */
	var isNifNieField = function( field, formRow, validationEvent ) {
		if ( ! field.matches( _settings.nifNieFieldSelector ) ) { return false; }

		return true;
	}



	/**
	 * Validate VAT number.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the VAT number is valid. Returns `true` if the VAT number is valid, `false` otherwise.
	 */
	 var validateVatNumber = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.vatValidationFieldSelector );

		// Return data-error as message if field is invalid
		if ( field.value && validationStatusField && '' === validationStatusField.value ) {
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
	 * @return {Boolean}                   Whether the VAT number is unique. Returns `true` if the VAT number is unique, `false` otherwise.
	 */
	var validateVatUniqueness = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.vatUniquenessFieldSelector );

		// Return data-error as message if field is invalid
		if ( field.value && validationStatusField && '' === validationStatusField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.vat_not_unique };
		}

		// Field is valid
		return { valid: true };
	};

	/**
	 * Validate Codice Fiscale field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the Codice Fiscale is valid. Returns `true` if the Codice Fiscale is valid, `false` otherwise.
	 */
	var validateSdiField = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.sdiValidationFieldSelector );

		// Get VAT field
		var vatField = document.querySelector( _settings.vatFieldSelector );

		// Return data-error as message if VAT field is empty
		if ( field.value && vatField && '' === vatField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.vat_empty };
		}

		// Return data-error as message if field is invalid
		if ( field.value && validationStatusField && '' === validationStatusField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.sdi_not_valid };
		}

		// Field is valid
		return { valid: true };
	};

	var validateCodiceFiscaleField = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.codiceFiscaleValidationFieldSelector );

		// Return data-error as message if field is invalid
		if ( field.value && validationStatusField && '' === validationStatusField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.codice_fiscale_not_valid };
		}

		// Field is valid
		return { valid: true };
	};

	var validateNifNieField = function( field, formRow, validationEvent ) {
		// Get hidden field with validation status
		var validationStatusField = document.querySelector( _settings.nifNieValidationFieldSelector );

		// Return data-error as message if field is invalid
		if ( field.value && validationStatusField && '' === validationStatusField.value ) {
			// Return as invalid
			return { valid: false, message: _settings.validationMessages.nif_nie_not_valid };
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
		CheckoutValidation.registerValidationType( 'sdi_invalid', 'sdi-invalid', isSdiField, validateSdiField );
		CheckoutValidation.registerValidationType( 'codice_fiscale_invalid', 'codice-fiscale-invalid', isCodiceFiscaleField, validateCodiceFiscaleField );
		CheckoutValidation.registerValidationType( 'nif_nie_invalid', 'nif-nie-invalid', isNifNieField, validateNifNieField );
	}



	/**
	 * Maybe validate plugin fields.
	 */
	var maybeValidateFields = function() {
		var fields = [
			document.querySelector( _settings.vatFieldSelector ),
			document.querySelector( _settings.sdiFieldSelector ),
			document.querySelector( _settings.codiceFiscaleFieldSelector),
			document.querySelector( _settings.nifNieFieldSelector )
		];
		
		// Maybe validate fields
		if ( window.CheckoutValidation ) {
			for ( var i = 0; i < fields.length; i++ ) {
				var field = fields[i];
				if ( ! field ) { continue; }

				CheckoutValidation.validateField( field, 'change' );
			}
		}
	}



	/**
	 * Maybe clear validation errors for the element.
	 * 
	 * @param  {Element}  element  The element to clear validation errors for.
	 */
	var maybeClearValidationErrors = function( element ) {
		// Bail if CheckoutValidation is not available
		if ( ! window.CheckoutValidation ) { return; }

		// Bail if element is not available
		if ( ! element ) { return; }
		
		// Clear validation results for the element
		CheckoutValidation.clearValidationResults( element, element.closest( '.form-row' ) );
	}



	/**
	 * Handle captured `input` event and route to the appropriate functions.
	 */
	var handleInput = function( e ) {
		// PLUGIN FIELDS WITH VALIDATION
		if ( e.target.matches( _settings.vatFieldSelector ) || e.target.matches( _settings.sdiFieldSelector ) || e.target.matches( _settings.codiceFiscaleFieldSelector ) || e.target.matches( _settings.nifNieFieldSelector ) ) {
			maybeClearValidationErrors( e.target );
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

		// Add event listeners
		window.addEventListener( 'input', handleInput );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document ).on( 'updated_checkout', maybeValidateFields );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
