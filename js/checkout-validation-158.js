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
	var _validationTypes = {};
	var _settings = {
		bodyClass: 'fc-checkout-validation--active',
		formSelector: 'form.checkout',
		formRowSelector: '.form-row, .shipping-method__package',
		validateFieldsSelector: '.input-text, select, .shipping-method__options',
		clearValidationCountryChangedSelector: '#state, #shipping_state, #billing_state',
		alwaysValidateFieldsSelector: '',
		select2Selector: '.select2, .select2-hidden-accessible',
		typeRequiredSelector: '.validate-required',
		typeEmailSelector: '.validate-email',
		typeConfirmationSelector: '[data-confirm-with]',
		typeShippingMethodSelector: '.shipping-method__package',
		validClass: 'woocommerce-validated',
		invalidClass: 'woocommerce-invalid',
		validationMessages: {
			required:         'This is a required field.',
			email:            'This is not a valid email address.',
			confirmation:     'This field does not match the related',
		},
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
	 * @param  {Field}   field         Field to validate.
	 * @param  {Element} formRow       Form row related to the field.
	 * @param  {String}  message       Message to add.
	 * @param  {String}  invalidClass  Type of error used to identify which message is related to which error.
	 */
	var addInlineMessage = function( field, formRow, message, invalidClass ) {
		// Bail if field not valid
		if ( ! field ) { return; }

		// Bail if message is empty
		if ( ! message || message.length == 0 ) { return; }

		var referenceNode = field;

		// Change reference field for select2
		if ( isSelect2Field( field ) ) {
			var newReference = field.parentNode.querySelector( '.select2-container' );
			if ( newReference ) { referenceNode = newReference; }
		}

		// Create message element and add it after the field.
		var parent = field.parentNode;
		var element = document.createElement( 'span' );
		element.className = 'woocommerce-error invalid-' + invalidClass;
		element.innerText = message;
		parent.insertBefore( element, referenceNode.nextSibling );
	};


	/**
	 * Remove inline message from the field.
	 * @param  {Field} field      Field to validate.
	 * @param  {Element} formRow  Form row related to the field.
	 * @param  {String}  invalidClass  Type of error used to identify which message is related to which error.
	 */
	var removeInlineMessage = function( field, formRow, invalidClass ) {
		var messageElements = formRow.querySelectorAll( 'span.woocommerce-error.invalid-' + invalidClass );
		for ( var i = 0; i < messageElements.length; i++ ) {
			messageElements[ i ].parentNode.removeChild( messageElements[ i ] );
		}
	}

	

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
	_publicMethods.hasValue = function( field ) {
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
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if required.
	 */
	var isRequiredField = function( field, formRow ) {
		if ( ! formRow.matches( _settings.typeRequiredSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate required field.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 */
	var validateRequired = function( field, formRow ) {
		// Bail if field does not have a value
		if ( ! _publicMethods.hasValue( field ) ) { return { valid: false, message: _settings.validationMessages.required }; }

		return { valid: true };
	};



	/**
	 * Check if form row is email field.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if is email field.
	 */
	var isEmailField = function( field, formRow ) {
		if ( ! formRow.matches( _settings.typeEmailSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate email field.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 */
	var validateEmail = function( field, formRow ) {
		// Bail if does not have value
		if ( ! _publicMethods.hasValue( field ) ) { return { valid: true }; }

		/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
		var emailPattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

		// Validate email value
		if ( ! emailPattern.test( field.value ) ) { return { valid: false, message: _settings.validationMessages.email }; }

		return { valid: true };
	};



	/**
	 * Check if form row is a confirmation field.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if is a confimation field.
	 */
	var isConfirmationField = function( field, formRow ) {
		if ( ! formRow.querySelector( _settings.typeConfirmationSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate confirmation field.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 */
	var validateConfirmation = function( field, formRow ) {
		// Bail if does not have value
		if ( ! _publicMethods.hasValue( field ) ) { return { valid: false }; }

		// Get confirmation field
		var form = formRow.closest( 'form' );
		var confirmWith = form ? form.querySelector( field.getAttribute( 'data-confirm-with' ) ) : null;

		// Validate fields have same value
		if ( confirmWith && field.value == confirmWith.value ) { return { valid: false, message: _settings.validationMessages.confirmation }; }

		return { valid: true };
	};

	/**
	 * Check if form row is required.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 * @return {Boolean}          True if required.
	 */
	 var isShippingMethodField = function( field, formRow ) {
		if ( ! formRow.matches( _settings.typeShippingMethodSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate required field.
	 * @param  {Field}    field Field for validation.
	 * @param  {Element}  formRow Form row element.
	 */
	var validateShippingMethod = function( field, formRow ) {
		var selectedShippingMethod = formRow.querySelector( 'input[type="radio"]:checked' );

		// Bail if field does not have a value
		if ( ! selectedShippingMethod ) { return { valid: false, message: _settings.validationMessages.required }; }

		return { valid: true };
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

		// Test if field needs validation from any validation type
		var validationTypeNames = Object.getOwnPropertyNames( _validationTypes );
		for ( var i = 0; i < validationTypeNames.length; i++) {
			var validationTypeName = validationTypeNames[i];
			var validationType = _validationTypes[ validationTypeName ];
			if ( validationType.needsValidation( field, formRow ) ) {
				return true;
			}
		}

		return false;
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
		var validationResultsNames = Object.getOwnPropertyNames( validationResults );
		for ( var i = 0; i < validationResultsNames.length; i++ ) {
			var validationTypeName = validationResultsNames[ i ];
			var validationType = _validationTypes[ validationTypeName ];
			var result = validationResults[ validationTypeName ].valid;
			var message = validationResults[ validationTypeName ].message;
			var invalidClass = validationType.invalidClass;

			// Remove messages for the current validation type
			removeInlineMessage( field, formRow, invalidClass );

			// Toggle validation `invalidClass` according to validation `result`
			formRow.classList.toggle( _settings.invalidClass +'-'+ validationType.invalidClass, true !== result );

			// Maybe set field as invalid
			if ( true !== result ) {
				valid = false;
				addInlineMessage( field, formRow, message, invalidClass );
			}
		}

		// Toggle general field valid/invalid classes
		formRow.classList.toggle( _settings.validClass, valid );
		formRow.classList.toggle( _settings.invalidClass, !valid );

		return valid;
	};



	/**
	 * Clear the state fields validation status classes when the field loses the value due changes to the country fields.
	 *
	 * @param   jQuery.Event  event    Event object as a `jQuery.Event`.
	 * @param   string        country  Selected country code value of the related country field.
	 * @param   jQuery.fn     wrapper  jQuery object representing the field wrapper element related to the country field that was changed. See variable `wrapper_selectors` ~LN103 of the `country-select.js`.
	 */
	var maybeClearStateFields = function( event, country, wrapper ) {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }
		
		var wrappersList = $( wrapper ).toArray();

		wrappersList.forEach( function( wrapperItem ) {
			
			var fields = Array.from( wrapperItem.querySelectorAll( _settings.clearValidationCountryChangedSelector ) );
			
			fields.forEach( function( field ) {
				
				if ( '' == field.value ) {
					var formRow = field.closest( _settings.formRowSelector );
					_publicMethods.clearValidationResults( field, formRow );
				}

			} );

		} );
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
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		_publicMethods.registerValidationType( 'required', 'required-field', isRequiredField, validateRequired );
		_publicMethods.registerValidationType( 'email', 'email', isEmailField, validateEmail );
		_publicMethods.registerValidationType( 'confirmation', 'confirmation-field', isConfirmationField, validateConfirmation );
		_publicMethods.registerValidationType( 'shipping-method', 'shipping-method-field', isShippingMethodField, validateShippingMethod );
	}



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
			formRow.classList.remove( _settings.invalidClass +'-'+ type );
		}

		// Remove valid/invalid classes
		formRow.classList.remove( _settings.validClass );
		formRow.classList.remove( _settings.invalidClass );
	};



	/**
	 * Test multiple validations on the passed field.
	 * @param  {Field} field    Field for validation.
	 * @return {Boolean}        True if field is valid.
	 */
	_publicMethods.validateField = function( field, validateHidden ) {
		// Bail if field is null
		if ( ! field ) { return true; }

		var validationResults = {},
			formRow = getFormRow( field );

		// Bail if formRow not found
		if ( ! formRow ) { return true; }

		// Bail if hidden to the user
		if ( ! isAlwaysValidate( field ) && validateHidden !== true && isFieldHidden( field ) ) { return true; }

		// Bail if field doesn't need validation
		if ( ! needsValidation( field, formRow ) ) { return true; }

		// Execute validate field for all applicable validation types
		var validationTypeNames = Object.getOwnPropertyNames( _validationTypes );
		for ( var i = 0; i < validationTypeNames.length; i++) {
			var validationTypeName = validationTypeNames[i];
			var validationType = _validationTypes[ validationTypeName ];
			if ( validationType.needsValidation( field, formRow ) ) {
				validationResults[ validationTypeName ] = validationType.validate( field, formRow );
			}
		}

		// TODO: Maybe trigger validation of related fields (ie zip > State, Country)

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

		for ( var i = 0; i < fields.length; i++ ) {
			if ( ! _publicMethods.validateField( fields[i], validateHidden ) ) {
				all_valid = false;
			}
		}

		return all_valid;
	};



	/**
	 * Register a new validation type.
	 *
	 * @param   String    validationType      A `snake_case` string representing the type of validation. Used as the validation type property on the `_validationTypes` object.
	 * @param   String    invalidClass        CSS class to be used on the `form-row` related to the field when the validation fails.
	 * @param   Function  fnNeedsValidation   A function to check if the field needs validation, should return `true` when the field needs validation.
	 * @param   Function  fnValidate          A function to validate the form field, should accept 2 parameters being `field` and `formRow`, both expected to be an HTMLElement.
	 *
	 * @return  Boolean                  `true` when the registration of the validation type has been successful, `false` otherwise.
	 */
	_publicMethods.registerValidationType = function( validationType, invalidClass, fnNeedsValidation, fnValidate ) {
		// Bail if _validationTypes not initialized
		if ( ! _validationTypes ) { return false; }

		// Bail if validationType or invalidClass not a string
		if ( typeof validationType !== 'string' || typeof invalidClass !== 'string' ) { return false; }

		// Bail if fnNeedsValidation or fnValidate are not functions
		if ( ! ( fnNeedsValidation instanceof Function ) || ! ( fnValidate instanceof Function ) ) { return false; }
		
		// Bail if validation type already registered
		if ( _validationTypes.hasOwnProperty( validationType ) ) {
			console.log( 'Validation type "' + validationType + '" already registered.' );
			return false;
		}

		// Register validation type
		_validationTypes[ validationType ] = {
			invalidClass: invalidClass,
			needsValidation: fnNeedsValidation,
			validate: fnValidate,
		}

		return true;
	}



	/**
	 * Return the registered validation types.
	 *
	 * @return  Object  Object with the registered validation types as properties.
	 */
	_publicMethods.getValidationTypes = function() {
		return _validationTypes;
	}

	

	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = extend( _settings, options );

		// Register validation types
		registerValidationTypes();

		if ( _hasJQuery ) {
			$( _settings.formSelector ).on( 'input validate change', _settings.validateFieldsSelector, handleValidateEvent );
			
			// Run on checkout or cart changes
			$( document ).on( 'load_ajax_content_done', _publicMethods.init );
			$( document ).on( 'country_to_state_changed', maybeClearStateFields );
		}

		// Add body class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
