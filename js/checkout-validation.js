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
  var _hasJQuery                = ( $ != null ),
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
    var message = 'This is not a valid email.'; // Fallback message if cannot get from settings
    
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
      var formRow = get_form_row( fields[i] );

      // Continue to next field if form row not found
      if ( !formRow ) { continue; }

      // Proceed if field needs validation
      if ( needs_validation( formRow ) ) {
        if ( is_required_field( formRow ) ) { init_inline_message_required( fields[i], formRow ); }
        if ( is_email_field( formRow ) ) { init_inline_message_email( fields[i], formRow ); }
        if ( is_confirmation_field( formRow ) ) { init_inline_message_confirmation( fields[i], formRow ); }
      }
    }
  };

  

  /**
   * Check field is a select2 element.
   * @param  {Field}  target    Target field.
   * @return {Boolean}          True if field is select2.
   */
  var is_select2_field = function( target ) {
    if ( target.closest( _select2Selector ) ) { return true; }
    return false;
  };



  /**
   * Check if target is a select field.
   * @param  {Element}  target Target field.
   * @return {Boolean}         True if is a select field.
   */
  var is_select_field = function( target ) {
    if ( target.matches( 'select' ) ) { return true; }
    return false;
  };



  /**
   * Check if field has value.
   * @param  {[type]}  target [description]
   * @return {Boolean}        [description]
   */
  var has_value = function( target ) {
    // Check for select 2 field
    if ( is_select_field( target ) ) {
      if ( target.options[ target.selectedIndex ].value != '' ) {
        return true;
      }
      else {
        return false;
      }
    }

    // Check for all other fields
    if ( target.value != '' ) { return true; }
    
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
   * @param  {Field} target Target field for validation.
   */
  var validate_required = function( target, formRow ) {
    // Bail if has value
    if ( has_value( target ) ) { return [ 'required', true ]; }

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
   * @param  {Field} target Target field for validation.
   */
  var validate_email = function( target, formRow ) {
    // Bail if does not have value
    if ( ! has_value( target ) ) { return [ 'email', true ]; }

    /* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
    var emailPattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

    // Validate email value
    if ( emailPattern.test( target.value ) ) { return [ 'email', true ]; }

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
   * @param  {Field} target Target field for validation.
   */
  var validate_confirmation = function( target, formRow ) {
    // Bail if does not have value
    if ( ! has_value( target ) ) { return [ 'confirmation', true ]; }

    // Get target confirmation field
    var form = formRow.closest( 'form' );
    var confirmWith = form ? form.querySelector( target.getAttribute( 'data-confirm-with' ) ) : null;

    // Validate fields have same value
    if ( confirmWith && target.value == confirmWith.value ) { return [ 'confirmation', true ]; }

    // Return classes for invalid field
    return [ 'confirmation', _validationTypes.confirmation ];
  };



  /**
   * Check if field needs validation.
   * @param  {Element} formRow Form row for validation.
   * @return {Boolean}         True if field needs any validation.
   */
  var needs_validation = function( formRow ) {
    if ( is_required_field( formRow ) ) { return true; }
    if ( is_email_field( formRow ) ) { return true; }
    if ( is_confirmation_field( formRow ) ) { return true; }
    return false;
  };



  /**
   * Process validation results of one field.
   * @param  {Field} target             Field targeted to validation.
   * @param  {Element} formRow          Form row element.
   * @param  {Array} validationResults Validation results array.
   */
  var process_validation_results = function( target, formRow, validationResults ) {
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
  };



  /**
   * Handle multiple validations on the passed target field.
   * @param  {Form Field} target Target field for validation.
   */
  var validate_field = function( target ) {
    // Bail if target is null
    if ( ! target ) { return; }

    var validationResults = [],
        formRow = get_form_row( target );

    // Bail if formRow not found
    if ( !formRow ) { return; }


    // Bail if field doesn't need validation
    if ( ! needs_validation( formRow ) ) { return; }

    // Perform validations
    if ( is_required_field( formRow ) ) { validationResults.push( validate_required( target, formRow ) ); }
    if ( is_email_field( formRow ) ) { validationResults.push( validate_email( target, formRow ) ); }
    if ( is_confirmation_field( formRow ) ) { validationResults.push( validate_confirmation( target, formRow ) ); }

    // TODO: Trigger validation of related fields (ie zip > State, Country)
    // TODO: Trigger validation of fields with value at load and after each ajax reload

    // Process results
    process_validation_results( target, formRow, validationResults );
  };


  
  /**
   * Handle document clicks and route to the appropriate function.
   */
  var handleValidateEvent = function( e ) {
    var target = e.target;

    // Get correct target when field is select2
    if ( is_select2_field( e.target ) ) {
      target = e.target.closest( _formRowSelector ).querySelector( 'select' );
    }

    validate_field( target );
  };



  /**
   * Initialize component and set related handlers.
   */
  var init = function() {
    if ( _hasJQuery ) {
      $( _formSelector ).on( 'input validate change', _validateFieldsSelector, handleValidateEvent );
    }

    init_inline_messages();
  };



  // Add event listeners
  window.addEventListener( 'load', init );
  // document.addEventListener( 'blur', handleLeaveField, true );

  // Run on checkout or cart changes
  $(document).on( 'load_ajax_content_done', init );

})( jQuery );
