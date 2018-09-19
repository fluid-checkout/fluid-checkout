/**
 * Manage checkout front-end validation.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */

(function( $ ){

  'use strict';

  /**
   * VARIABLES
   */
  var _initClass                = 'js-fluid-checkout-validation',
      _hasJQuery                = ( $ != null ),
      _formSelector             = 'form.checkout',
      _formRowSelector          = '.form-row',
      _validateFieldsSelector   = '.input-text, select',
      _select2Selector          = '.select2, .select2-hidden-accessible',
      _typeRequiredSelector     = '.validate-required',
      _typeEmailSelector        = '.validate-email',
      _typeConfirmationSelector = '[data-confirm-with]',
      _validClass               = 'woocommerce-validated',
      _invalidClass             = 'woocommerce-invalid'
      ;
  var _validationTypes = {
    required:         'required-field',
    email:            'email',
    confirmation:     'confirmation-field',
  };



	/**
	 * METHODS
	 */
  


  /**
   * Check if element is hidden to the user.
   * @param  {Element}  el Element to test visibility.
   * @return {Boolean}     True if element is hidden to the user.
   */
  var is_hidden = function( el ) {
    return (el.offsetParent === null);
  };

  

  /**
   * Get the form-row element related to the field.
   * @param  {Field} field Form field.
   * @return {Element}     Form row related to the passed field.
   */
  var get_form_row = function( field ) {
    // Bail if field not valid
    if ( !field ) { return; }

    // TODO: Polyfill `closest`
    return field.closest( _formRowSelector );
  };



  /**
   * Add markup for inline message of required fields.
   * @param  {Field} field      Field to validate.
   * @param  {Element} formRow  Form row related to the field.
   * @param  {String} message   Message to add.
   * @param  {String} typeClass Type of error used to identify which message to display on validation.
   */
  var add_inline_message_markup = function( field, formRow, message, typeClass ) {
    // Bail if field not valid
    if ( !field ) { return; }

    var referenceNode = field;

    // Change reference field for select2
    if ( is_select2_field( field ) ) {
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
  var init_inline_message_required = function( field, formRow ) {
    var message = 'This is a required field.'; // Fallback message if cannot get from settings
    
    // Try get message from settings
    if ( fluidCheckoutValidationVars && fluidCheckoutValidationVars.required_field_message ) {
      message = fluidCheckoutValidationVars.required_field_message;
    }

    add_inline_message_markup( field, formRow, message, 'required-field' );
  };



  /**
   * Add markup for inline message of email fields.
   * @param  {Field} field      Field to validate.
   * @param  {Element} formRow  Form row related to the field.
   */
  var init_inline_message_email = function( field, formRow ) {
    var message = 'This is not a valid email address.'; // Fallback message if cannot get from settings
    
    // Try get message from settings
    if ( fluidCheckoutValidationVars && fluidCheckoutValidationVars.email_field_message ) {
      message = fluidCheckoutValidationVars.email_field_message;
    }

    add_inline_message_markup( field, formRow, message, 'email' );
  };



  /**
   * Add markup for inline message of confirmation fields.
   * @param  {Field} field      Field to validate.
   * @param  {Element} formRow  Form row related to the field.
   */
  var init_inline_message_confirmation = function( field, formRow ) {
    var message = 'This field does not match the related.'; // Fallback message if cannot get from settings
    
    // Try get message from settings
    if ( fluidCheckoutValidationVars && fluidCheckoutValidationVars.confirmation_field_message ) {
      message = fluidCheckoutValidationVars.confirmation_field_message;
    }

    // Try get message from field attributes
    if ( field.getAttribute( 'data-invalid-confirm-with' ) ) {
      message = field.getAttribute( 'data-invalid-confirm-with' );
    }

    add_inline_message_markup( field, formRow, message, 'confirmation-field' );
  };



   /**
    * Initalize inline validation messages.
    */
  var init_inline_messages = function() {
    var form = document.querySelector( _formSelector );

    // Bail if form not found
    if ( !form ) { return; }

    var fields = form.querySelectorAll( _validateFieldsSelector );

    for (var i = 0; i < fields.length; i++) {
      var field = fields[i],
          formRow = get_form_row( field );

      // Continue to next field if form row not found
      if ( !formRow ) { continue; }

      // Proceed if field needs validation
      if ( needs_validation( field, formRow ) ) {
        if ( is_required_field( formRow ) ) { init_inline_message_required( fields[i], formRow ); }
        if ( is_email_field( formRow ) ) { init_inline_message_email( fields[i], formRow ); }
        if ( is_confirmation_field( formRow ) ) { init_inline_message_confirmation( fields[i], formRow ); }
      }
    }
  };

  

  /**
   * Check field is a select2 element.
   * @param  {Field}  field     Field to check.
   * @return {Boolean}          True if field is select2.
   */
  var is_select2_field = function( field ) {
    if ( field.closest( _select2Selector ) ) { return true; }
    return false;
  };



  /**
   * Check if field is a select field.
   * @param  {Element}  field  Field to check.
   * @return {Boolean}         True if is a select field.
   */
  var is_select_field = function( field ) {
    if ( field.matches( 'select' ) ) { return true; }
    return false;
  };



  /**
   * Check if field has value.
   * @param  {Field}   field  Field to check.
   * @return {Boolean}        True if field has value.
   */
  var has_value = function( field ) {
    // Check for select 2 field
    if ( is_select_field( field ) ) {
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
  var is_required_field = function( formRow ) {
    // TODO: Polyfill `matches`
    if ( formRow.matches( _typeRequiredSelector ) ) { return true; }
    return false;
  };



  /**
   * Validate required field.
   * @param  {Field} field Field for validation.
   */
  var validate_required = function( field, formRow ) {
    // Bail if has value
    if ( has_value( field ) ) { return [ 'required', true ]; }

    // Return classes for invalid field
    return [ 'required', _validationTypes.required ];
  };



  /**
   * Check if form row is email field.
   * @param  {Element}  formRow Form row element.
   * @return {Boolean}          True if is email field.
   */
  var is_email_field = function( formRow ) {
    // TODO: Polyfill `matches`
    if ( formRow.matches( _typeEmailSelector ) ) { return true; }
    return false;
  };



  /**
   * Validate email field.
   * @param  {Field} field Field for validation.
   */
  var validate_email = function( field, formRow ) {
    // Bail if does not have value
    if ( ! has_value( field ) ) { return [ 'email', true ]; }

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
  var is_confirmation_field = function( formRow ) {
    // TODO: Polyfill `matches`
    if ( formRow.querySelector( _typeConfirmationSelector ) ) { return true; }
    return false;
  };



  /**
   * Validate confirmation field.
   * @param  {Field} field Field for validation.
   */
  var validate_confirmation = function( field, formRow ) {
    // Bail if does not have value
    if ( ! has_value( field ) ) { return [ 'confirmation', true ]; }

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
  var needs_validation = function( field, formRow ) {
    // Test validation types
    if ( is_required_field( formRow ) ) { return true; }
    if ( is_email_field( formRow ) ) { return true; }
    if ( is_confirmation_field( formRow ) ) { return true; }

    return false;
  };



  /**
   * Process validation results of one field.
   * @param  {Field} field             Field to validation.
   * @param  {Element} formRow          Form row element.
   * @param  {Array} validationResults Validation results array.
   * @return {Boolean}           True if all fields are valid.
   */
  var process_validation_results = function( field, formRow, validationResults ) {
    var valid = true;

    // Iterate validation results
    for ( var i = 0; i < validationResults.length; i++ ) {
      var type    = validationResults[i][0],
          result  = validationResults[i][1];

      // Remove invalidation classes from the field
      if ( true === result ) {
        // Remove invalid classes for validation type
        formRow.classList.remove( _invalidClass +'-'+ _validationTypes[ type ] );
      }
      // Add invalidation classes to the field
      else {
        valid = false;
        formRow.classList.add( _invalidClass +'-'+ result );
      }
    }

    if ( valid ) {
      // Add validation classes
      formRow.classList.add( _validClass );
      formRow.classList.remove( _invalidClass );
    }
    else {
      // Add invalidaton classes
      formRow.classList.remove( _validClass );
      formRow.classList.add( _invalidClass );
    }

    return valid;
  };



  /**
   * Test multiple validations on the passed field.
   * @param  {Field} field    Field for validation.
   * @return {Boolean}        True if field is valid.
   */
  var validate_field = function( field ) {
    // Bail if field is null
    if ( ! field ) { return true; }

    var validationResults = [],
        formRow = get_form_row( field );

    // Bail if formRow not found
    if ( !formRow ) { return true; }

    // Bail if hidden to the user
    if ( is_hidden( field ) ) { return true; }

    // Bail if field doesn't need validation
    if ( ! needs_validation( field, formRow ) ) { return true; }

    // Perform validations
    if ( is_required_field( formRow ) ) { validationResults.push( validate_required( field, formRow ) ); }
    if ( is_email_field( formRow ) ) { validationResults.push( validate_email( field, formRow ) ); }
    if ( is_confirmation_field( formRow ) ) { validationResults.push( validate_confirmation( field, formRow ) ); }

    // TODO: Trigger validation of related fields (ie zip > State, Country)

    // Process results
    return process_validation_results( field, formRow, validationResults );
  };


  
  /**
   * Handle document clicks and route to the appropriate function.
   */
  var handleValidateEvent = function( e ) {
    var field = e.target;

    // Get correct field when is select2
    if ( is_select2_field( e.target ) ) {
      field = e.target.closest( _formRowSelector ).querySelector( 'select' );
    }

    validate_field( field );
  };



  /**
   * Trigger validation in all fields inside the container.
   * @param  {Element} container Element to look for fields in, if not passed consider the checkout form as container.
   * @return {Boolean}           True if all fields are valid.
   */
  var validate_all_fields = function( container ) {
    // Default container to the form
    if ( ! container ) { container = document.querySelector( _formSelector ) }

    var all_valid = true;
    var fields = container.querySelectorAll( _validateFieldsSelector );

    for (var i = 0; i < fields.length; i++) {
      if ( ! validate_field( fields[i] ) ) {
        all_valid = false;
      }
    }

    return all_valid;
  };



  /**
   * Initialize component and set related handlers.
   */
  var init = function() {
    if ( _hasJQuery ) {
      $( _formSelector ).on( 'input validate change', _validateFieldsSelector, handleValidateEvent );
    }

    init_inline_messages();

    // Expose members
    window.fluidCheckoutValidation = {
      validate_all_fields: validate_all_fields,
    };

    // Add init class
    document.body.classList.add( _initClass );
  };



  // Add event listeners
  window.addEventListener( 'load', init );

  // Run on checkout or cart changes
  $(document).on( 'load_ajax_content_done', init );

})( jQuery );
