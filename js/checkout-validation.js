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
      _formRowSelector          = '.form-row',
      _select2Selector          = '.select2, .select2-hidden-accessible',
      _confirmationSelector     = '[data-confirm-with]',
      _invalidConfirmationClass = 'woocommerce-invalid-confirmation',
      _requiredSelector         = '.validate-required',
      _validClass               = 'woocommerce-validated',
      _invalidClass             = 'woocommerce-invalid',
      _invalidRequiredClass     = 'woocommerce-invalid-required-field'
      ;



	/**
	 * METHODS
	 */
  

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
  var is_required = function( formRow ) {
    // TODO: Polyfill `matches`
    if ( formRow.matches( _requiredSelector ) ) { return true; }
    return false;
  };



  /**
   * Validate required field.
   * @param  {Field} target Target field for validation.
   */
  var validate_required = function( target, formRow ) {
    // Bail if has value
    if ( has_value( target ) ) { return true; }

    // Return classes for invalid field
    return _invalidRequiredClass;
  };



  /**
   * Check if form row is a confirmation field.
   * @param  {Element}  formRow Form row element.
   * @return {Boolean}          True if is a confimation field.
   */
  var is_confirmation_field = function( formRow ) {
    // TODO: Polyfill `matches`
    if ( formRow.querySelector( _confirmationSelector ) ) { return true; }
    return false;
  };



  /**
   * Validate confirmation field.
   * @param  {Field} target Target field for validation.
   */
  var validate_confirmation = function( target, formRow ) {
    // Get target confirmation field
    var form = formRow.closest( 'form' );
    var confirmWith = form ? form.querySelector( target.getAttribute( 'data-confirm-with' ) ) : null;

    // Bail if does not have value
    if ( has_value( target ) && confirmWith && target.value == confirmWith.value ) { return true; }

    // Return classes for invalid field
    return _invalidRequiredClass;
  };



  /**
   * Check if field needs validation.
   * @param  {Element} formRow Form row for validation.
   * @return {Boolean}         True if field needs any validation.
   */
  var needs_validation = function( formRow ) {
    if ( is_required( formRow ) ) { return true; }
    if ( is_confirmation_field( formRow ) ) { return true; }
    return false;
  };



  /**
   * Process validation results of one field.
   * @param  {Field} target             Field targeted to validation.
   * @param  {Element} formRow          Form row element.
   * @param  {Boolean} valid            Whether field is valid or not.
   * @param  {Array} validationResults Validation results array.
   */
  var process_validation_results = function( target, formRow, valid, validationResults ) {
    // Iterate validation results
    for ( var i = 0; i < validationResults.length; i++ ) {
      // Remove invalidation classes from the field
      if ( true === validationResults[i] ) {
        // TODO: Polyfill `classList`
        formRow.classList.remove( _invalidClass );
        formRow.classList.remove( _invalidRequiredClass );
      }
      // Add invalidation classes to the field
      else {
        valid = false;
        formRow.classList.add( validationResults[i] );
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
        valid = true,
        // TODO: Polyfill `closest`
        formRow = target.closest( _formRowSelector );

    // Bail if formRow not found
    if ( !formRow ) { return; }


    // Bail if field doesn't need validation
    if ( ! needs_validation( formRow ) ) { return; }

    // Perform validations
    if ( is_required( formRow ) ) { validationResults.push( validate_required( target, formRow ) ); }
    if ( is_confirmation_field( formRow ) ) { validationResults.push( validate_confirmation( target, formRow ) ); }

    // TODO: Trigger validation of related fields (ie zip > State, Country)
    // TODO: Trigger validation of fields with value at load and after each ajax reload

    // Process results
    process_validation_results( target, formRow, valid, validationResults );
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
      $( 'form.checkout' ).on( 'input validate change', '.input-text, select, input:checkbox', handleValidateEvent );
    }
  };



  // Add event listeners
  window.addEventListener( 'load', init );
  // document.addEventListener( 'blur', handleLeaveField, true );

  // Run on checkout or cart changes
  $(document).on( 'load_ajax_content_done', init );

})( jQuery );
