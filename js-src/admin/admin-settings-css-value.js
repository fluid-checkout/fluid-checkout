/**
 * CSS value fields on the Fluid Checkout settings page.
 *
 * DEPENDS ON:
 * - None (vanilla JS)
 */
( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsCssValue = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		fieldSelector:                     '[data-fc-settings-css-value]',
		swatchSelector:                    '[data-fc-settings-css-value-swatch]',
		inputSelector:                     '[data-fc-settings-css-value-input]',
		clearSelector:                     '[data-fc-settings-css-value-clear]',
		errorSelector:                     '[data-fc-settings-css-value-error]',
		propertyAttribute:                 'data-fc-settings-css-value-property',
		isInvalidClass:                    'is-invalid',
		swatchDefaultColor:                '#ffffff',
	};



	/**
	 * METHODS
	 */



	/**
	 * Get a CSS color as a hex color, as color inputs only take hex colors.
	 *
	 * @param   {string}  color  CSS color value.
	 *
	 * @return  {string}  Hex color, or an empty string when the browser cannot resolve the value as a color.
	 */
	var getHexColor = function( color ) {
		var element;
		var channels;

		// Bail if the value is not a color, or uses CSS variables that the settings page does not define
		if ( ! color || ! CSS.supports( 'color', color ) || -1 !== color.indexOf( 'var(' ) ) { return ''; }

		// Get the color channels from a temporary element, which resolves color names and functions
		element = document.createElement( 'span' );
		element.style.color = color;
		document.body.appendChild( element );
		channels = getComputedStyle( element ).color.match( /\d+(\.\d+)?/g );
		document.body.removeChild( element );

		// Bail if the color channels are not available
		if ( ! channels || channels.length < 3 ) { return ''; }

		// Convert the red, green and blue channels to a hex color
		return '#' + channels.slice( 0, 3 ).map( function( channel ) {
			return ( '0' + Math.round( parseFloat( channel ) ).toString( 16 ) ).slice( -2 );
		} ).join( '' );
	};

	/**
	 * Show the color of a CSS value field on its swatch: the value color, or the color of the default shown as placeholder.
	 *
	 * @param  {Element}  field  CSS value field element.
	 */
	var syncSwatch = function( field ) {
		var swatch = field.querySelector( _settings.swatchSelector );
		var input = field.querySelector( _settings.inputSelector );

		// Bail if the field has no swatch
		if ( ! swatch || ! input ) { return; }

		// Show the value color, or the default color
		swatch.value = getHexColor( input.value.trim() ) || getHexColor( input.placeholder ) || _settings.swatchDefaultColor;
	};

	/**
	 * Set the value of a CSS value field, and trigger the event of a value typed in it.
	 *
	 * @param  {Element}  input  CSS value input.
	 * @param  {string}   value  New value.
	 */
	var setFieldValue = function( input, value ) {
		// Set the value
		input.value = value;

		// Trigger the event of a value typed in the field
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	};



	/**
	 * Show or hide the error of a CSS value field, for values that are not valid for its CSS property.
	 *
	 * @param  {Element}  field  CSS value field element.
	 */
	var syncError = function( field ) {
		var input = field.querySelector( _settings.inputSelector );
		var error = field.parentNode.querySelector( _settings.errorSelector );
		var isValid;

		// Bail if the field has no error to show, as it does not check its values
		if ( ! error ) { return; }

		// Show or hide the error
		isValid = _publicMethods.isValidValue( input );
		field.classList.toggle( _settings.isInvalidClass, ! isValid );
		input.setAttribute( 'aria-invalid', isValid ? 'false' : 'true' );
		error.hidden = isValid;
	};

	/**
	 * Show the errors of all CSS value fields with values that are not valid, such as values saved before the browser checked them.
	 */
	var syncErrors = function() {
		var fields = document.querySelectorAll( _settings.fieldSelector );
		var i;

		// Iterate CSS value fields
		for ( i = 0; i < fields.length; i++ ) {
			syncError( fields[ i ] );
		}
	};



	/**
	 * Handle input on CSS value fields.
	 *
	 * @param  {Event}  e  Input event.
	 */
	var handleInput = function( e ) {
		var field = e.target.closest( _settings.fieldSelector );

		// Bail if the event is not from a CSS value field
		if ( ! field ) { return; }

		// SWATCH
		if ( e.target.matches( _settings.swatchSelector ) ) {
			setFieldValue( field.querySelector( _settings.inputSelector ), e.target.value );
		}
		// VALUE
		else if ( e.target.matches( _settings.inputSelector ) ) {
			syncSwatch( field );
			syncError( field );
		}
	};

	/**
	 * Handle changes of the swatches, passed on as changes of their CSS value fields.
	 * The swatch is not a setting, so other scripts such as the page preview only get the change of the CSS value field.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleChange = function( e ) {
		// Bail if the change is not from a swatch
		if ( ! e.target.matches( _settings.swatchSelector ) ) { return; }

		// Stop the change of the swatch
		e.stopPropagation();

		// Trigger the change of the CSS value field
		e.target.closest( _settings.fieldSelector ).querySelector( _settings.inputSelector ).dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};

	/**
	 * Handle clicks on the clear buttons, which empty the value so the field returns to its default.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleClick = function( e ) {
		var button = e.target.closest( _settings.clearSelector );
		var field;
		var input;

		// Bail if the click is not on a clear button
		if ( ! button ) { return; }

		// Get the input of the field
		field = button.closest( _settings.fieldSelector );
		input = field ? field.querySelector( _settings.inputSelector ) : null;

		// Bail if the input is not available
		if ( ! input ) { return; }

		// Empty the value, and trigger its change as the value is final
		setFieldValue( input, '' );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};



	/**
	 * Show the colors of all CSS value fields on their swatches, also for values the server cannot convert to hex colors.
	 */
	_publicMethods.syncSwatches = function() {
		var fields = document.querySelectorAll( _settings.fieldSelector );
		var i;

		// Iterate CSS value fields
		for ( i = 0; i < fields.length; i++ ) {
			syncSwatch( fields[ i ] );
		}
	};

	/**
	 * Check whether the value of a CSS value input is valid for its CSS property, as the browser reads it.
	 *
	 * @param   {Element}  input  CSS value input.
	 * @return  {boolean}         Whether the value is valid, also when it is empty or the input has no CSS property to check.
	 */
	_publicMethods.isValidValue = function( input ) {
		var property = input.getAttribute( _settings.propertyAttribute );
		var value = input.value.trim();

		return ! property || ! value || CSS.supports( property, value );
	};

	/**
	 * Initialize CSS value fields.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Add event listeners
		document.addEventListener( 'input', handleInput, true );
		document.addEventListener( 'change', handleChange, true );
		document.addEventListener( 'click', handleClick, true );

		// Show the colors on the swatches, and the errors of values that are not valid
		_publicMethods.syncSwatches();
		syncErrors();

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
