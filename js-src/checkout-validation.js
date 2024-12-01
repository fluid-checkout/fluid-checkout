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
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _validationTypes = {};
	var _settings = {
		bodyClass:                               'fc-checkout-validation--active',
		formSelector:                            'form.checkout',
		formRowSelector:                         '.form-row, .shipping-method__package',
		inputWrapperSelector:                    '.woocommerce-input-wrapper, .form-row',
		validateFieldsSelector:                  '.input-text, .input-checkbox, input[type="date"], select, .shipping-method__options',
		referenceNodeSelector:                   '.input-text, .input-checkbox, input[type="date"], select, .shipping-method__options', // Usually same as `validateFieldsSelector`
		clearValidationCountryChangedSelector:   '#state, #shipping_state, #billing_state',
		alwaysValidateFieldsSelector:            '',

		select2Selector:                         '.select2, .select2-hidden-accessible',
		select2WrapperSelector:                  '.select2-container',
		selectTomSelector:                       '.ts-hidden-accessible',
		selectTomWrapperSelector:                '.ts-wrapper',

		typeRequiredSelector:                    '.validate-required',
		typeEmailSelector:                       '.validate-email',
		typeConfirmationSelector:                '[data-confirm-with]',
		typeShippingMethodSelector:              '.shipping-method__package',

		validClass:                              'woocommerce-validated',
		invalidClass:                            'woocommerce-invalid',

		validationMessages: {
			required:                            'This is a required field.',
			email:                               'This is not a valid email address.',
			confirmation:                        'This field does not match the related field value.',
		},
	};



	/**
	 * METHODS
	 */
	


	/**
	 * Check if field is hidden to the user.
	 * @param  {Field}  field  Field to test visibility.
	 * @return {Boolean}       True if field is hidden to the user.
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
	 * @param  {Field}  field  Form field.
	 * @return {Element}       Form row related to the passed field.
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

		var inputWrapper = field.closest( _settings.inputWrapperSelector ) || formRow;
		var referenceNode = inputWrapper.querySelector( _settings.referenceNodeSelector );

		// Change reference field for select2
		if ( isSelect2Field( field ) ) {
			var newReference = field.parentNode.querySelector( _settings.select2WrapperSelector );
			if ( newReference ) { referenceNode = newReference; }
		}

		// Change reference field for TomSelect control element 
		if ( isSelectTomField( field ) ) {
			var newReference = field.parentNode.querySelector( _settings.selectTomWrapperSelector );
			if ( newReference ) { referenceNode = newReference; }
		}

		// Change reference field for checkbox
		if ( isCheckboxField( field ) ) {
			var newReference = field.closest( _settings.inputWrapperSelector );
			if ( newReference ) { referenceNode = newReference.lastChild; }
		}

		// Create message element and add it after the field.
		var parent = referenceNode.parentNode;
		var elementId = field.id + '-invalid-' + invalidClass;
		var element = document.createElement( 'span' );
		element.className = 'woocommerce-error invalid-' + invalidClass;
		element.id = elementId;
		element.innerText = message;
		parent.insertBefore( element, referenceNode.nextSibling );

		// Add aria-describedby attribute to the field
		var describedbyValue = field.getAttribute( 'aria-describedby' );
		describedbyValue = describedbyValue ? describedbyValue : '';
		field.setAttribute( 'aria-describedby', describedbyValue + ' ' + elementId );
	};


	/**
	 * Remove inline message from the field.
	 * @param  {Field}    field         Field to validate.
	 * @param  {Element}  formRow       Form row related to the field.
	 * @param  {String}   invalidClass  Type of error used to identify which message is related to which error.
	 */
	var removeInlineMessage = function( field, formRow, invalidClass ) {
		var messageElements = formRow.querySelectorAll( 'span.woocommerce-error.invalid-' + invalidClass );
		for ( var i = 0; i < messageElements.length; i++ ) {
			// Get variables
			var messageElement = messageElements[ i ];
			var elementId = messageElement.id;

			// Maybe remove validation `aria-describedby` attribute from the field
			if ( elementId ) {
				var describedbyValue = field.getAttribute( 'aria-describedby' );
				describedbyValue = describedbyValue ? describedbyValue : '';
				field.setAttribute( 'aria-describedby', describedbyValue.replace( ' ' + elementId, '' ) );
			}

			// Remove message
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
	 * Check field is a TomSelect element.
	 * @param  {Field}  field     Field to check.
	 * @return {Boolean}          True if field is select2.
	 */
	var isSelectTomField = function( field ) {
		if ( field.closest( _settings.selectTomSelector ) ) { return true; }
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
	 * Check if field is a checkbox field.
	 * @param  {Element}  field  Field to check.
	 * @return {Boolean}         True if is a checkbox field.
	 */
	var isCheckboxField = function( field ) {
		if ( field.matches( 'input[type="checkbox"]' ) ) { return true; }
		return false;
	};



	/**
	 * Check if field has value.
	 * @param  {Field}   field  Field to check.
	 * @return {Boolean}        True if field has value.
	 */
	_publicMethods.hasValue = function( field ) {
		// Check for select fields
		if ( isSelectField( field ) ) {
			if ( field.options && field.selectedIndex > -1 && field.options[ field.selectedIndex ].value != '' ) {
				return true;
			}
			else {
				return false;
			}
		}

		// Check for checkbox fields
		if ( isCheckboxField( field ) ) {
			return field.checked;
		}

		// Check for all other fields
		if ( field.value != '' ) { return true; }
		
		return false;
	};



	/**
	 * Check if form row is required.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is required or not.
	 */
	var isRequiredField = function( field, formRow, validationEvent ) {
		if ( ! formRow.matches( _settings.typeRequiredSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate required field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the required field has a value or not.
	 */
	var validateRequired = function( field, formRow, validationEvent ) {
		// Bail if field does not have a value
		if ( ! _publicMethods.hasValue( field ) ) { return { valid: false, message: _settings.validationMessages.required }; }

		return { valid: true };
	};



	/**
	 * Check if form row is email field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is an email address field.
	 */
	var isEmailField = function( field, formRow, validationEvent ) {
		if ( ! formRow.matches( _settings.typeEmailSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate email field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field has a valid email address value.
	 */
	var validateEmail = function( field, formRow, validationEvent ) {
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
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field is a confirmation field that is related to another field in the form.
	 */
	var isConfirmationField = function( field, formRow, validationEvent ) {
		if ( ! field.matches( _settings.typeConfirmationSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate confirmation field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the confirmation field has the same value as the field it is related to.
	 */
	var validateConfirmation = function( field, formRow, validationEvent ) {
		// Bail if does not have value
		if ( ! _publicMethods.hasValue( field ) ) { return { valid: false }; }

		// Get confirmation field
		var form = formRow.closest( 'form' );
		var confirmWith = form ? form.querySelector( field.getAttribute( 'data-confirm-with' ) ) : null;

		// Validate whether fields do not have the same value
		if ( confirmWith && field.value != confirmWith.value ) { return { valid: false, message: _settings.validationMessages.confirmation }; }

		return { valid: true };
	};

	/**
	 * Check if the form row is a shipping method field wrapper.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the form row is a shipping method field wrapper.
	 */
	 var isShippingMethodField = function( field, formRow, validationEvent ) {
		if ( ! formRow.matches( _settings.typeShippingMethodSelector ) ) { return false; }
		return true;
	};

	/**
	 * Validate shipping method field.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether a shipping method has been selected in the form row.
	 */
	var validateShippingMethod = function( field, formRow, validationEvent ) {
		var selectedShippingMethod = formRow.querySelector( 'input[type="radio"]:checked' );

		// Bail if field does not have a value
		if ( ! selectedShippingMethod ) { return { valid: false, message: _settings.validationMessages.required }; }

		return { valid: true };
	};



	/**
	 * Check if field needs validation.
	 * @param  {Field}   field            Field to validate.
	 * @param  {Element} formRow          Form row for validation.
	 * @param  {String}  validationEvent  Event that triggered the validation.
	 * @return {Boolean}                  True if field needs any validation.
	 */
	var needsValidation = function( field, formRow, validationEvent ) {
		// Bail if field should always validate
		if ( isAlwaysValidate( field ) ) { return true; }

		// Test if field needs validation from any validation type
		var validationTypeNames = Object.getOwnPropertyNames( _validationTypes );
		for ( var i = 0; i < validationTypeNames.length; i++) {
			var validationTypeName = validationTypeNames[i];
			var validationType = _validationTypes[ validationTypeName ];
			if ( validationType.needsValidation( field, formRow, validationEvent ) ) {
				return true;
			}
		}

		return false;
	};



	/**
	 * Process validation results of a field.
	 * @param  {Field}    field              Field to validation.
	 * @param  {Element}  formRow            Form row element.
	 * @param  {Array}    validationResults  Validation results array.
	 * @return {Boolean}                     True if all fields are valid.
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

			// Remove class and message for the current validation type
			formRow.classList.remove( _settings.invalidClass +'-'+ validationType.invalidClass );
			removeInlineMessage( field, formRow, invalidClass );

			// Maybe set field as invalid
			if ( true !== result ) {
				valid = false;

				// Maybe display inline message and set field as invalid
				if ( null !== result ) {
					addInlineMessage( field, formRow, message, invalidClass );

					// Add field validation invalid classes for the validation type
					formRow.classList.add( _settings.invalidClass +'-'+ validationType.invalidClass );
				}
			}
		}

		// Toggle general field valid/invalid classes
		formRow.classList.toggle( _settings.validClass, valid );
		formRow.classList.toggle( _settings.invalidClass, ! valid );

		// Set field as invalid for accessibility
		field.setAttribute( 'aria-invalid', ! valid );

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
		// Bail if processing checkout update
		if ( true === window.processing_checkout_update ) { return; }

		// Get variables
		var field = e.target;
		var formRow = e.target.closest( _settings.formRowSelector );

		// Bail if field or formRow not available
		if ( ! field || ! formRow ) { return; }

		// Get correct field when is select2
		if ( isSelect2Field( e.target ) ) {
			if ( formRow ) {
				field = formRow.querySelector( 'select' );
			}
		}

		// Maybe delay validation when user is typing for the first time in the field
		if ( 'input' === e.type && ! formRow.classList.contains( _settings.validClass ) && ! formRow.classList.contains( _settings.invalidClass ) ) {
			_publicMethods.validateFieldDebounced( field, e.type );
		}
		// Otherwise, trigger validation immediatelly
		else {
			_publicMethods.validateField( field, e.type );
		}
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
			formRow.classList.remove( _settings.invalidClass +'-'+ type + '-field' );
		}

		// Remove valid/invalid classes
		formRow.classList.remove( _settings.validClass );
		formRow.classList.remove( _settings.invalidClass );
	};



	/**
	 * Test multiple validations on the passed field.
	 * 
	 * @param  {Field}    field             Field for validation.
	 * @param  {String}   validationEvent   Event that triggered the field validation. Can also be an arbitrary event name.
	 * @param  {Boolean}  validateHidden    True to validate hidden fields.
	 * 
	 * @return {Boolean}                    True if field is valid.
	 */
	_publicMethods.validateField = function( field, validationEvent, validateHidden ) {
		// Bail if field is null
		if ( ! field ) { return true; }

		var validationResults = {},
			formRow = getFormRow( field );

		// Bail if formRow not found
		if ( ! formRow ) { return true; }

		// Bail if hidden to the user
		if ( ! isAlwaysValidate( field ) && true !== validateHidden && isFieldHidden( field ) ) { return true; }

		// Bail if field doesn't need validation
		if ( ! needsValidation( field, formRow, validationEvent ) ) { return true; }

		// Execute validate field for all applicable validation types
		var validationTypeNames = Object.getOwnPropertyNames( _validationTypes );
		for ( var i = 0; i < validationTypeNames.length; i++) {
			var validationTypeName = validationTypeNames[i];
			var validationType = _validationTypes[ validationTypeName ];
			if ( validationType.needsValidation( field, formRow, validationEvent ) ) {
				validationResults[ validationTypeName ] = validationType.validate( field, formRow, validationEvent );
			}
		}

		// TODO: Maybe trigger validation of related fields (ie zip > State, Country)

		// Process results
		return processValidationResults( field, formRow, validationResults );
	};
	/**
	 * Test multiple validations on the passed field, debounced to allow time for the user to interact with the field.
	 * 
	 * @param  {Field}    field             Field for validation.
	 * @param  {String}   validationEvent   Event that triggered the field validation. Can also be an arbitrary event name.
	 * @param  {Boolean}  validateHidden    True to validate hidden fields.
	 * 
	 * @return {Boolean}                    True if field is valid.
	 */
	_publicMethods.validateFieldDebounced = FCUtils.debounce( _publicMethods.validateField, 1000 );



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
			if ( ! _publicMethods.validateField( fields[i], 'validate-all', validateHidden ) ) {
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
		_settings = FCUtils.extendObject( _settings, options );

		// Register validation types
		registerValidationTypes();

		if ( _hasJQuery ) {
			// Validation events
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
