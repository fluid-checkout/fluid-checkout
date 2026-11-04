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
		fieldSelector:            '[data-fc-settings-css-value]',
		swatchSelector:           '[data-fc-settings-css-value-swatch]',
		inputSelector:            '[data-fc-settings-css-value-input]',
		clearSelector:            '[data-fc-settings-css-value-clear]',
		errorSelector:            '[data-fc-settings-css-value-error]',
		propertyAttribute:        'data-fc-settings-css-value-property',
		colorFormatAttribute:     'data-fc-settings-css-value-color-format',
		isInvalidClass:           'is-invalid',
		swatchDefaultColor:       '#ffffff',
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
	 * Detect the syntax of a CSS color value in the text field.
	 *
	 * @param   {string}  value  CSS color value.
	 * @return  {string}          `hex`, `rgb`, `rgba`, `hsl`, `hsla`, or `hex` as the default for other colors.
	 */
	var getColorValueFormat = function( value ) {
		var trimmed = ( value || '' ).trim();

		if ( /^#[0-9a-f]{3}$/i.test( trimmed ) || /^#[0-9a-f]{6}$/i.test( trimmed ) ) {
			return 'hex';
		}

		if ( /^rgba\(/i.test( trimmed ) ) {
			return 'rgba';
		}

		if ( /^rgb\(/i.test( trimmed ) ) {
			return 'rgb';
		}

		if ( /^hsla\(/i.test( trimmed ) ) {
			return 'hsla';
		}

		if ( /^hsl\(/i.test( trimmed ) ) {
			return 'hsl';
		}

		return 'hex';
	};

	/**
	 * Parse an alpha channel from an `rgba()` or `hsla()` value.
	 *
	 * @param   {string}  value   CSS color value.
	 * @param   {string}  format  `rgba` or `hsla`.
	 * @return  {number|null}       Alpha between 0 and 1, or null when missing.
	 */
	var getAlphaFromColorValue = function( value, format ) {
		var match;

		if ( 'rgba' === format ) {
			match = value.trim().match( /^rgba\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+\s*,\s*([\d.]+)\s*\)$/i );
		}
		else if ( 'hsla' === format ) {
			match = value.trim().match( /^hsla\(\s*[\d.]+\s*,\s*[\d.]+%?\s*,\s*[\d.]+%?\s*,\s*([\d.]+)\s*\)$/i );
		}

		return match ? parseFloat( match[ 1 ] ) : null;
	};

	/**
	 * Convert RGB channels to HSL components used in CSS `hsl()` / `hsla()` values.
	 *
	 * @param   {number}  red    Red channel 0–255.
	 * @param   {number}  green  Green channel 0–255.
	 * @param   {number}  blue   Blue channel 0–255.
	 * @return  {Array}           `[ hue, saturation, lightness ]`.
	 */
	var rgbToHsl = function( red, green, blue ) {
		var r = red / 255;
		var g = green / 255;
		var b = blue / 255;
		var max = Math.max( r, g, b );
		var min = Math.min( r, g, b );
		var hue = 0;
		var saturation = 0;
		var lightness = ( max + min ) / 2;
		var delta;

		if ( max !== min ) {
			delta = max - min;
			saturation = lightness > 0.5 ? delta / ( 2 - max - min ) : delta / ( max + min );

			switch ( max ) {
				case r:
					hue = ( g - b ) / delta + ( g < b ? 6 : 0 );
					break;
				case g:
					hue = ( b - r ) / delta + 2;
					break;
				default:
					hue = ( r - g ) / delta + 4;
					break;
			}

			hue *= 60;
		}

		return [
			Math.round( hue ),
			Math.round( saturation * 100 ),
			Math.round( lightness * 100 ),
		];
	};

	/**
	 * Format a hex color picked in the native swatch using the syntax of the text field.
	 *
	 * @param   {string}  hex            Hex color from the swatch (`#rrggbb`).
	 * @param   {string}  format         Target syntax.
	 * @param   {string}  previousValue  Previous text field value, used to preserve alpha.
	 * @return  {string}                 CSS color in the requested syntax.
	 */
	var formatColorFromHex = function( hex, format, previousValue ) {
		var channels;
		var red;
		var green;
		var blue;
		var hsl;
		var alpha;

		channels = getHexColor( hex ).replace( '#', '' );

		// Bail if the hex color is invalid
		if ( 6 !== channels.length ) {
			return hex;
		}

		red = parseInt( channels.slice( 0, 2 ), 16 );
		green = parseInt( channels.slice( 2, 4 ), 16 );
		blue = parseInt( channels.slice( 4, 6 ), 16 );

		if ( 'rgb' === format ) {
			return 'rgb(' + red + ', ' + green + ', ' + blue + ')';
		}

		if ( 'rgba' === format ) {
			alpha = getAlphaFromColorValue( previousValue, 'rgba' );
			if ( null === alpha ) {
				alpha = 1;
			}
			return 'rgba(' + red + ', ' + green + ', ' + blue + ', ' + alpha + ')';
		}

		if ( 'hsl' === format || 'hsla' === format ) {
			hsl = rgbToHsl( red, green, blue );

			if ( 'hsla' === format ) {
				alpha = getAlphaFromColorValue( previousValue, 'hsla' );
				if ( null === alpha ) {
					alpha = 1;
				}
				return 'hsla(' + hsl[ 0 ] + ', ' + hsl[ 1 ] + '%, ' + hsl[ 2 ] + '%, ' + alpha + ')';
			}

			return 'hsl(' + hsl[ 0 ] + ', ' + hsl[ 1 ] + '%, ' + hsl[ 2 ] + '%)';
		}

		return hex;
	};

	/**
	 * Remember the color syntax used in the text field so swatch picks match it.
	 *
	 * @param  {Element}  field  CSS value field element.
	 */
	var syncColorFormat = function( field ) {
		var input = field.querySelector( _settings.inputSelector );
		var format;

		// Bail if the field has no swatch
		if ( ! field.querySelector( _settings.swatchSelector ) || ! input ) { return; }

		format = getColorValueFormat( input.value.trim() || input.placeholder );
		field.setAttribute( _settings.colorFormatAttribute, format );
	};

	/**
	 * Show the color of a CSS value field on its swatch: the value color, or the color of the default shown as placeholder.
	 *
	 * @param  {Element}  field  CSS value field element.
	 */
	var syncSwatch = function( field ) {
		// Get the swatch and the input of the field
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
		// Get the input and the error of the field
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
		// Get the CSS value fields
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
		// Get the CSS value field of the event
		var field = e.target.closest( _settings.fieldSelector );
		var input;
		var format;
		var previousValue;

		// Bail if the event is not from a CSS value field
		if ( ! field ) { return; }

		// SWATCH
		if ( e.target.matches( _settings.swatchSelector ) ) {
			input = field.querySelector( _settings.inputSelector );
			format = field.getAttribute( _settings.colorFormatAttribute ) || getColorValueFormat( input.value.trim() );
			previousValue = input.value.trim();
			setFieldValue( input, formatColorFromHex( e.target.value, format, previousValue ) );
			syncColorFormat( field );
		}
		// VALUE
		else if ( e.target.matches( _settings.inputSelector ) ) {
			syncColorFormat( field );
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
		// Get the clicked clear button
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
		// Get the CSS value fields
		var fields = document.querySelectorAll( _settings.fieldSelector );
		var i;

		// Iterate CSS value fields
		for ( i = 0; i < fields.length; i++ ) {
			syncColorFormat( fields[ i ] );
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
		// Get the CSS property to check, and the value
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
