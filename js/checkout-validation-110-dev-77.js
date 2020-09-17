/**
 * Manage checkout front-end validation.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutValidation = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasInitialized = false;
	var _hasJQuery = ( $ != null );
	var _publicMethods = { };
	var _settings = {
		bodyClass: 'wfc-checkout-validation--active',
		formSelector: 'form.checkout',
		formRowSelector: '.form-row',
		validateFieldsSelector: '.input-text, select',
		alwaysValidateFieldsSelector: '',
		select2Selector: '.select2, .select2-hidden-accessible',
		typeRequiredSelector: '.validate-required',
		typeEmailSelector: '.validate-email',
		typeConfirmationSelector: '[data-confirm-with]',
		validClass: 'woocommerce-validated',
		invalidClass: 'woocommerce-invalid',
		validationMessages: {
			required:         'This is a required field.',
			email:            'This is not a valid email address.',
			confirmation:     'This field does not match the related',
		},
	};

	var _validationTypes = {
		required:         'required-field',
		email:            'email',
		confirmation:     'confirmation-field',
	};



	/**
	 * METHODS
	 */



	/*!
	* Merge two or more objects together.
	* (c) 2017 Chris Ferdinandi, MIT License, https://gomakethings.com
	* @param   {Boolean}  deep     If true, do a deep (or recursive) merge [optional]
	* @param   {Object}   objects  The objects to merge together
	* @returns {Object}            Merged values of defaults and options
	*/
	var extend = function () {
		// Variables
		var extended = {};
		var deep = false;
		var i = 0;

		// Check if a deep merge
		if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
			deep = arguments[0];
			i++;
		}

		// Merge the object into the extended object
		var merge = function (obj) {
			for (var prop in obj) {
				if (obj.hasOwnProperty(prop)) {
					// If property is an object, merge properties
					if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
						extended[prop] = extend(extended[prop], obj[prop]);
					} else {
						extended[prop] = obj[prop];
					}
				}
			}
		};

		// Loop through each object and conduct a merge
		for (; i < arguments.length; i++) {
			var obj = arguments[i];
			merge(obj);
		}

		return extended;
    };
	


	/**
	 * Check if field is hidden to the user.
	 * @param  {Field}  field Field to test visibility.
	 * @return {Boolean}      True if field is hidden to the user.
	 */
	var isFieldHidden = function( field ) {
		return ( field.offsetParent === null );
	};



	/**
	 * Check if field is in allow list for always validate.
	 * @param  {Field}  field  Field to test for allow list.
	 * @return {Boolean}       True if field is in allow list for always validate.
	 */
	var isAlwaysValidate = function( field ) {
		// Bail if field not found or selector empty
		if ( ! field || ! _settings.alwaysValidateFieldsSelector ) { return false; }

		// Check if field is in allow list
		if ( field.matches( _settings.alwaysValidateFieldsSelector ) ) { return true; }
		return false;
	};

	

	/**
	 * Get the form-row element related to the field.
	 * @param  {Field} field Form field.
	 * @return {Element}     Form row related to the passed field.
	 */
	var getFormRow = function( field ) {
		// Bail if field not valid
		if ( !field ) { return; }

		return field.closest( _settings.formRowSelector );
	};



	/**
	 * Add markup for inline message of required fields.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row related to the field.
	 * @param  {String} message   Message to add.
	 * @param  {String} typeClass Type of error used to identify which message to display on validation.
	 */
	var addInlineMessageMarkup = function( field, formRow, message, typeClass ) {
		// Bail if field not valid
		if ( !field ) { return; }

		var referenceNode = field;

		// Change reference field for select2
		if ( isSelect2Field( field ) ) {
			var newReference = field.parentNode.querySelector( '.select2-container' );
			if ( newReference ) { referenceNode = newReference; }
		}

		// Create message element and add it after the field.
		var parent = field.parentNode;
		var element = document.createElement( 'span' );
		element.className = 'woocommerce-error invalid-' + typeClass;
		element.innerText = message;
		parent.insertBefore( element, referenceNode.nextSibling );
	};



	/**
	 * Add markup for inline message of required fields.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row related to the field.
	 */
	var initInlineMessageRequired = function( field, formRow ) {
		var message = _settings.validationMessages.required;
		addInlineMessageMarkup( field, formRow, message, 'required-field' );
	};



	/**
	 * Add markup for inline message of email fields.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row related to the field.
	 */
	var initInlineMessageEmail = function( field, formRow ) {
		var message = _settings.validationMessages.email;
		addInlineMessageMarkup( field, formRow, message, 'email' );
	};



	/**
	 * Add markup for inline message of confirmation fields.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row related to the field.
	 */
	var initInlineMessageConfirmation = function( field, formRow ) {
		var message = _settings.validationMessages.confirmation;

		// Try get message from field attributes
		if ( field.getAttribute( 'data-invalid-confirm-with' ) ) {
			message = field.getAttribute( 'data-invalid-confirm-with' );
		}

		addInlineMessageMarkup( field, formRow, message, 'confirmation-field' );
	};



	/**
	 * Initalize inline validation messages.
	 */
	var initInlineMessages = function() {
		var form = document.querySelector( _settings.formSelector );

		// Bail if form not found
		if ( !form ) { return; }

		var fields = form.querySelectorAll( _settings.validateFieldsSelector );

		for (var i = 0; i < fields.length; i++) {
			var field = fields[i],
					formRow = getFormRow( field );

			// Continue to next field if form row not found
			if ( !formRow ) { continue; }

			// Proceed if field needs validation
			if ( needsValidationMessage( field, formRow ) ) {
				if ( isRequiredField( formRow ) ) { initInlineMessageRequired( fields[i], formRow ); }
				if ( isEmailField( formRow ) ) { initInlineMessageEmail( fields[i], formRow ); }
				if ( isConfirmationField( formRow ) ) { initInlineMessageConfirmation( fields[i], formRow ); }
			}
		}
	};

	

	/**
	 * Check field is a select2 element.
	 * @param  {Field}  field     Field to check.
	 * @return {Boolean}          True if field is select2.
	 */
	var isSelect2Field = function( field ) {
		if ( field.closest( _settings.select2Selector ) ) { return true; }
		return false;
	};



	/**
	 * Check if field is a select field.
	 * @param  {Element}  field  Field to check.
	 * @return {Boolean}         True if is a select field.
	 */
	var isSelectField = function( field ) {
		if ( field.matches( 'select' ) ) { return true; }
		return false;
	};



	/**
	 * Check if field has value.
	 * @param  {Field}   field  Field to check.
	 * @return {Boolean}        True if field has value.
	 */
	var hasValue = function( field ) {
		// Check for select 2 field
		if ( isSelectField( field ) ) {
			if ( field.options && field.selectedIndex > -1 && field.options[ field.selectedIndex ].value != '' ) {
				return true;
			}
			else {
				return false;
			}
		}

		// Check for all other fields
		if ( field.value != '' ) { return true; }
		
		return false;
	};



	/**
	 * Check if form row is required.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if required.
	 */
	var isRequiredField = function( formRow ) {
		if ( formRow.matches( _settings.typeRequiredSelector ) ) { return true; }
		return false;
	};



	/**
	 * Validate required field.
	 * @param  {Field} field Field for validation.
	 */
	var validateRequired = function( field, formRow ) {
		// Bail if has value
		if ( hasValue( field ) ) { return [ 'required', true ]; }

		// Return classes for invalid field
		return [ 'required', _validationTypes.required ];
	};



	/**
	 * Check if form row is email field.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if is email field.
	 */
	var isEmailField = function( formRow ) {
		if ( formRow.matches( _settings.typeEmailSelector ) ) { return true; }
		return false;
	};



	/**
	 * Validate email field.
	 * @param  {Field} field Field for validation.
	 */
	var validateEmail = function( field, formRow ) {
		// Bail if does not have value
		if ( ! hasValue( field ) ) { return [ 'email', true ]; }

		/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
		var emailPattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

		// Validate email value
		if ( emailPattern.test( field.value ) ) { return [ 'email', true ]; }

		// Return classes for invalid field
		return [ 'email', _validationTypes.email ];
	};



	/**
	 * Check if form row is a confirmation field.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if is a confimation field.
	 */
	var isConfirmationField = function( formRow ) {
		if ( formRow.querySelector( _settings.typeConfirmationSelector ) ) { return true; }
		return false;
	};



	/**
	 * Validate confirmation field.
	 * @param  {Field} field Field for validation.
	 */
	var validateConfirmation = function( field, formRow ) {
		// Bail if does not have value
		if ( ! hasValue( field ) ) { return [ 'confirmation', true ]; }

		// Get confirmation field
		var form = formRow.closest( 'form' );
		var confirmWith = form ? form.querySelector( field.getAttribute( 'data-confirm-with' ) ) : null;

		// Validate fields have same value
		if ( confirmWith && field.value == confirmWith.value ) { return [ 'confirmation', true ]; }

		// Return classes for invalid field
		return [ 'confirmation', _validationTypes.confirmation ];
	};



	/**
	 * Check if field needs validation.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row for validation.
	 * @return {Boolean}          True if field needs any validation.
	 */
	var needsValidation = function( field, formRow ) {
		// Bail if field should always validate
		if ( isAlwaysValidate( field ) ) { return true; }

		// Test validation types
		if ( isRequiredField( formRow ) ) { return true; }
		if ( isEmailField( formRow ) ) { return true; }
		if ( isConfirmationField( formRow ) ) { return true; }

		return false;
	};



	/**
	 * Check if field needs validation message markup.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row for validation.
	 * @return {Boolean}          True if field needs any validation.
	 */
	var needsValidationMessage = function( field, formRow ) {
		// Check existence of message markup
		if ( formRow.querySelector( '.woocommerce-error' ) ) { return false; }

		// Check if field needs validation
		if ( ! needsValidation( field, formRow ) ) { return false; }
		
		return true;
	};



	/**
	 * Process validation results of a field.
	 * @param  {Field} field             Field to validation.
	 * @param  {Element} formRow          Form row element.
	 * @param  {Array} validationResults Validation results array.
	 * @return {Boolean}           True if all fields are valid.
	 */
	var processValidationResults = function( field, formRow, validationResults ) {
		var valid = true;

		// Iterate validation results
		for ( var i = 0; i < validationResults.length; i++ ) {
			var type    = validationResults[i][0],
					result  = validationResults[i][1];

			// Remove invalidation classes from the field
			if ( true === result ) {
				// TODO: Maybe refactor to use classList.toggle
				// Remove invalid classes for validation type
				formRow.classList.remove( _settings.invalidClass +'-'+ _validationTypes[ type ] );
			}
			// Add invalidation classes to the field
			else {
				valid = false;
				// TODO: Maybe refactor to use classList.toggle
				formRow.classList.add( _settings.invalidClass +'-'+ result );
			}
		}

		// Toggle valid/invalid classes
		formRow.classList.toggle( _settings.validClass, valid );
		formRow.classList.toggle( _settings.invalidClass, ! valid );

		return valid;
	};



	/**
	 * Clear validation results status of a field.
	 * @param  {Field} field             Field to validation.
	 * @param  {Element} formRow          Form row element.
	 */
	_publicMethods.clearValidationResults = function( field, formRow ) {
		// Bail if field or form row invalid
		if ( ! field || ! formRow ) { return; }
		
		// Remove invalid classes for validation types
		var validationTypeKeys = Object.keys( _validationTypes );
		for ( var i = 0; i < validationTypeKeys.length; i++ ) {
			var type = validationTypeKeys[i];
			formRow.classList.remove( _settings.invalidClass +'-'+ _validationTypes[ type ] );
		}

		// Remove valid/invalid classes
		formRow.classList.remove( _settings.validClass );
		formRow.classList.remove( _settings.invalidClass );
	};



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleValidateEvent = function( e ) {
		var field = e.target;

		// Get correct field when is select2
		if ( isSelect2Field( e.target ) ) {
			field = e.target.closest( _settings.formRowSelector ).querySelector( 'select' );
		}

		_publicMethods.validateField( field );
	};



	/**
	 * Test multiple validations on the passed field.
	 * @param  {Field} field    Field for validation.
	 * @return {Boolean}        True if field is valid.
	 */
	_publicMethods.validateField = function( field, validateHidden ) {
		// Bail if field is null
		if ( ! field ) { return true; }

		var validationResults = [],
			formRow = getFormRow( field );

		// Bail if formRow not found
		if ( ! formRow ) { return true; }

		// Bail if hidden to the user
		if ( ! isAlwaysValidate( field ) && validateHidden !== true && isFieldHidden( field ) ) { return true; }

		// Bail if field doesn't need validation
		if ( ! needsValidation( field, formRow ) ) { return true; }

		// Perform validations
		if ( isRequiredField( formRow ) ) { validationResults.push( validateRequired( field, formRow ) ); }
		if ( isEmailField( formRow ) ) { validationResults.push( validateEmail( field, formRow ) ); }
		if ( isConfirmationField( formRow ) ) { validationResults.push( validateConfirmation( field, formRow ) ); }

		// TODO: Trigger validation of related fields (ie zip > State, Country)

		// Process results
		return processValidationResults( field, formRow, validationResults );
	};



	/**
	 * Trigger validation in all fields inside the container.
	 * @param  {Element} container Element to look for fields in, if not passed consider the checkout form as container.
	 * @return {Boolean}           True if all fields are valid.
	 */
	_publicMethods.validateAllFields = function( container, validateHidden ) {
		if ( ! container ) { container = document.querySelector( _settings.formSelector ) }

		var all_valid = true;
		var fields = container.querySelectorAll( _settings.validateFieldsSelector );

		for (var i = 0; i < fields.length; i++) {
			if ( ! _publicMethods.validateField( fields[i], validateHidden ) ) {
				all_valid = false;
			}
		}

		return all_valid;
	};

	

	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		_settings = extend( _settings, window.wfcValidationVars );
		initInlineMessages();
		document.body.classList.add( _settings.bodyClass );

		if ( _hasJQuery ) {
			$( _settings.formSelector ).on( 'input validate change', _settings.validateFieldsSelector, handleValidateEvent );
			
			// Run on checkout or cart changes
			$( document ).on( 'load_ajax_content_done', _publicMethods.init );
			$( document ).on( 'updated_checkout', initInlineMessages );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
