/**
 * CSS value fields on the Fluid Checkout settings page, with their color swatch and clear button.
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
	 * Show the colors of all CSS value fields on their swatches, also for values the server cannot convert to hex colors.
	 */
	var syncSwatches = function() {
		var fields = document.querySelectorAll( _settings.fieldSelector );
		var i;

		// Iterate CSS value fields
		for ( i = 0; i < fields.length; i++ ) {
			syncSwatch( fields[ i ] );
		}
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
	 * Handle input on CSS value fields: copy the swatch color to the value, or show the value color on the swatch.
	 *
	 * @param  {Event}  e  Input event.
	 */
	var handleInput = function( e ) {
		var field = e.target.closest( _settings.fieldSelector );

		// Bail if the event is not from a CSS value field
		if ( ! field ) { return; }

		// Maybe copy the swatch color to the value
		if ( e.target.matches( _settings.swatchSelector ) ) {
			setFieldValue( field.querySelector( _settings.inputSelector ), e.target.value );
		}
		// Otherwise, maybe show the value color on the swatch
		else if ( e.target.matches( _settings.inputSelector ) ) {
			syncSwatch( field );
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
	 * Initialize CSS value fields.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Add event listeners
		document.addEventListener( 'input', handleInput );
		document.addEventListener( 'change', handleChange, true );
		document.addEventListener( 'click', handleClick );

		// Show the colors on the swatches
		syncSwatches();

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
