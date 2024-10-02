/**
 * Checkout scripts for: Customer Email Verification (by zorem).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutCustomerEmailVerification = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		formSelector:                            'form.checkout',
		emailFieldSelector:                      '#billing_email',
	}



	/**
	 * METHODS
	 */



	/**
	 * Validate email field.
	 * 
	 * @param Field  field  The email field to validate.
	 * @param Event  e      The event object.
	 */
	var maybeValidateEmailField = function( field, e ) {
		// // Trigger checkout update before validation
		// $( document.body ).trigger( 'update_checkout' );

		// // Validate email field after a delay
		// setTimeout( function() {
		// 	CheckoutValidation.validateField( field, e.type );
		// }, 1000 );

		// Get email field to re-validate
		var field = document.querySelector( _settings.emailFieldSelector );

		// Maybe trigger field valiation
		if ( window.CheckoutValidation ) {
			CheckoutValidation.validateField( field, 'change' );
		}
	}
	/**
	 * Test multiple validations on the passed field, debounced to allow time for the user to interact with the field.
	 * 
	 * @param  {Field}    field             Field for validation.
	 * @param  {String}   validationEvent   Event that triggered the field validation. Can also be an arbitrary event name.
	 * @param  {Boolean}  validateHidden    True to validate hidden fields.
	 * 
	 * @return {Boolean}                    True if field is valid.
	 */
	var maybeValidateEmailFieldDebounced = FCUtils.debounce( maybeValidateEmailField, 1000 );



	/**
	 * Maybe update email field when an AJAX call completes.
	 * 
	 * @param Event           e              The event object.
	 * @param XMLHttpRequest  jqXHR          The jQuery XMLHttpRequest object.
	 * @param object          jqXHRSettings  The jQuery AJAX settings object.
	 */
	var maybeUpdateEmailField = function( e, jqXHR, jqXHRSettings ) {
		// Bail if plugin settings object is not available
		if ( ! window.cev_ajax_object ) { return; };

		// Bail if AJAX call was not successful
		if ( 200 !== jqXHR.status ) { return; };

		// Bail if AJAX call was not to the plugin URL
		if ( cev_ajax_object.ajax_url !== jqXHRSettings.url ) { return; };

		// Bail if not verification action
		if ( -1 === jqXHRSettings.data.indexOf( 'action=checkout_page_verify_code' ) ) { return; };

		// Bail if call was not successful
		if ( ! jqXHR.responseJSON || ! jqXHR.responseJSON.success ) { return; };

		// Trigger checkout update before validation
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			// Validation events
			$( _settings.formSelector ).on( 'input validate change', _settings.emailFieldSelector, maybeValidateEmailFieldDebounced );
			$( document ).ajaxComplete( maybeUpdateEmailField );
			$( document ).on( 'updated_checkout', maybeValidateEmailField );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
