/**
 * Manage checkout front-end validation for: Omniva Shipping (by Omniva).
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
		root.CheckoutValidationOmniva = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		typeFieldSelector: 'select[name="omnivalt_terminal"]',
		sectionSelector: '.fc-shipping-method__packages',
		shippingMethodSelector: 'input[name^="shipping_method"]:checked',
		omnivaMethodIdPrefixes: [ 'omnivalt_pt', 'omnivalt_ps' ],
		cookieName: 'omniva_terminal',
		validationMessages: {
			pickup_point_not_selected: 'Selecting a pickup point is required before proceeding.',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Get a cookie value by name.
	 * @param  {String}  name  Cookie name.
	 * @return {String}        Cookie value, or empty string when not found.
	 */
	var getCookie = function( name ) {
		// Bail if Omniva helper is available
		if ( 'function' === typeof window.omniva_getCookie ) {
			return window.omniva_getCookie( name ) || '';
		}

		var cookiePrefix = name + '=';
		var cookies = document.cookie ? document.cookie.split( ';' ) : [];

		for ( var i = 0; i < cookies.length; i++ ) {
			var cookie = cookies[ i ].trim();
			if ( 0 === cookie.indexOf( cookiePrefix ) ) {
				return decodeURIComponent( cookie.substring( cookiePrefix.length ) );
			}
		}

		return '';
	};

	/**
	 * Get the currently selected Omniva terminal id from available sources.
	 * @return {String}  Selected terminal id, or empty string when none.
	 */
	var getSelectedTerminalId = function() {
		// Prefer Omniva map runtime state
		if ( window.omnivaltMap && omnivaltMap.variables && omnivaltMap.variables.selected_terminal_id ) {
			return String( omnivaltMap.variables.selected_terminal_id );
		}

		// Fallback to Omniva cookie used by the plugin itself
		return String( getCookie( _settings.cookieName ) || '' );
	};

	/**
	 * Whether an Omniva pickup method that needs a terminal is selected.
	 * @return {Boolean}  True when an Omniva terminal method is selected.
	 */
	var isOmnivaPickupMethodSelected = function() {
		var selectedMethods = document.querySelectorAll( _settings.shippingMethodSelector );

		for ( var i = 0; i < selectedMethods.length; i++ ) {
			var methodId = selectedMethods[ i ].value || '';

			for ( var j = 0; j < _settings.omnivaMethodIdPrefixes.length; j++ ) {
				if ( 0 === methodId.indexOf( _settings.omnivaMethodIdPrefixes[ j ] ) ) {
					return true;
				}
			}
		}

		return false;
	};

	/**
	 * Ensure the select field can hold the selected terminal value.
	 * Omniva map UI may set a terminal id that is not present as an option
	 * (e.g. when the select was built for a wrong country), which browsers ignore.
	 * @param  {Field}   field       Terminal select field.
	 * @param  {String}  terminalId  Terminal id to apply.
	 */
	var syncSelectedTerminalToField = function( field, terminalId ) {
		// Bail if no terminal id
		if ( ! terminalId || 'all' === terminalId ) { return; }

		var hasOption = false;
		for ( var i = 0; i < field.options.length; i++ ) {
			if ( String( field.options[ i ].value ) === String( terminalId ) ) {
				hasOption = true;
				break;
			}
		}

		// Maybe add a temporary option so the select can keep the value
		if ( ! hasOption ) {
			var option = document.createElement( 'option' );
			option.value = String( terminalId );
			option.text = String( terminalId );
			field.appendChild( option );
		}

		field.value = String( terminalId );
	};



	/**
	 * Check if form row should be validated with this component.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether the field should be validated with this component.
	 */
	var isValidateField = function( field, formRow, validationEvent ) {
		// Bail if not the target field for this validation
		if ( ! field.matches( _settings.typeFieldSelector ) ) { return false; }

		// Bail if Omniva pickup method that needs a terminal is not selected
		if ( ! isOmnivaPickupMethodSelected() ) { return false; }

		return true;
	};



	/**
	 * Validate if a shipping method collection point is selected.
	 * @param  {Field}    field            Field for validation.
	 * @param  {Element}  formRow          Form row element.
	 * @param  {String}   validationEvent  Event that triggered the validation.
	 * @return {Boolean}                   Whether a a shipping method collection point has been selected.
	 */
	var validateField = function( field, formRow, validationEvent ) {
		// Sync selected terminal from Omniva map/cookie into the field before validating
		var selectedTerminalId = getSelectedTerminalId();
		if ( selectedTerminalId ) {
			syncSelectedTerminalToField( field, selectedTerminalId );
		}

		// Bail if the shipping method field is empty
		if ( '' === field.value || 'all' === field.value ) {
			// Scroll to section
			var section = document.querySelector( _settings.sectionSelector );
			if ( section && section.scrollIntoView ) {
				section.scrollIntoView();
			}

			// Return as invalid
			return { valid: false, message: _settings.validationMessages.pickup_point_not_selected };
		}

		// Field is valid
		return { valid: true };
	};



	/**
	 * Register validation types.
	 */
	var registerValidationTypes = function() {
		CheckoutValidation.registerValidationType( 'omniva-shipping-method', 'omniva-shipping-method', isValidateField, validateField );
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
