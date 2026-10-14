/**
 * Color picker fields on the Fluid Checkout settings page.
 * Mirrors WooCommerce settings colorpick behavior without relying on table cells.
 *
 * DEPENDS ON:
 * - jQuery
 * - jQuery Iris
 */
( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsColorPicker = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		wrapSelector:                  '.fc-settings-wrap',
		colorInputSelector:            '.fc-settings-wrap .colorpick',
		colorInputSiblingSelector:     '.colorpick',
		previewSelector:               '.colorpickpreview',
		previewClickSelector:          '.fc-settings-wrap .colorpickpreview',
		controlSelector:               '.fc-settings-field__control',
		irisPickerSelector:            '.iris-picker',
		irisSquareSelector:            '.fc-settings-wrap .iris-square-value',
		irisErrorClass:                'iris-error',
		colorDataReadyKey:             'fcColorpickReady',
		colorDataOriginalKey:          'originalValue',
		colorPattern:                  /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	};



	/**
	 * METHODS
	 */



	/**
	 * Get the settings field control that owns a color input.
	 *
	 * @param   {jQuery}  $input  Color input element.
	 * @return  {jQuery}          Control wrapper, or the input parent as fallback.
	 */
	var getColorControl = function( $input ) {
		var $control = $input.closest( _settings.controlSelector );

		return $control.length ? $control : $input.parent();
	};



	/**
	 * Initialize Iris color pickers for settings color fields.
	 */
	var initColorPickers = function() {
		// Bail if jQuery or Iris is not available
		if ( ! _hasJQuery || 'function' !== typeof $.fn.iris ) { return; }

		$( _settings.colorInputSelector ).each( function() {
			var $input = $( this );

			// Bail if already initialized
			if ( $input.data( _settings.colorDataReadyKey ) ) { return; }

			$input.iris( {
				change: function( event, ui ) {
					getColorControl( $input )
						.find( _settings.previewSelector )
						.css( { backgroundColor: ui.color.toString() } );

					setTimeout( function() {
						$input.trigger( 'change' );
					} );
				},
				hide: true,
				border: true,
			} );

			$input.data( _settings.colorDataReadyKey, true );
		} );
	};



	/**
	 * Show the Iris picker for a color input and hide other pickers.
	 *
	 * @param  {jQuery}  $input  Color input element.
	 */
	var showColorPicker = function( $input ) {
		$( _settings.irisPickerSelector ).hide();
		getColorControl( $input ).find( _settings.irisPickerSelector ).show();
		$input.data( _settings.colorDataOriginalKey, $input.val() );
	};



	/**
	 * Handle click or focus on a color input.
	 *
	 * @param  {Event}  e  Click or focus event.
	 */
	var handleColorInputActivate = function( e ) {
		var $input = $( e.currentTarget );

		e.stopPropagation();
		showColorPicker( $input );
	};



	/**
	 * Restore the previous valid color when Iris marks the value as invalid.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleColorInputChange = function( e ) {
		var $input;
		var originalValue;

		$input = $( e.currentTarget );

		// Bail if the value is still valid
		if ( ! $input.is( '.' + _settings.irisErrorClass ) ) { return; }

		originalValue = $input.data( _settings.colorDataOriginalKey );

		// Restore a previously valid hex color
		if ( originalValue && originalValue.match( _settings.colorPattern ) ) {
			$input.val( originalValue ).trigger( 'change' );
		}
		// Otherwise clear the invalid value
		else {
			$input.val( '' ).trigger( 'change' );
		}
	};



	/**
	 * Open the color picker when the preview swatch is clicked.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handlePreviewClick = function( e ) {
		var $preview = $( e.currentTarget );

		e.stopPropagation();
		$preview.next( _settings.colorInputSiblingSelector ).trigger( 'click' );
	};



	/**
	 * Prevent Iris square clicks from bubbling and closing the picker.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleIrisSquareClick = function( e ) {
		e.preventDefault();
	};



	/**
	 * Hide open Iris pickers when clicking outside them.
	 */
	var handleDocumentClick = function() {
		$( _settings.irisPickerSelector ).hide();
	};



	/**
	 * Initialize color picker behavior on the settings page.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if jQuery is unavailable
		if ( ! _hasJQuery ) { return; }

		// Bail if settings form is not available
		if ( ! document.querySelector( _settings.wrapSelector ) ) { return; }

		initColorPickers();

		$( document.body )
			.on( 'click focus', _settings.colorInputSelector, handleColorInputActivate )
			.on( 'change', _settings.colorInputSelector, handleColorInputChange )
			.on( 'click', _settings.previewClickSelector, handlePreviewClick )
			.on( 'click', _settings.irisSquareSelector, handleIrisSquareClick )
			.on( 'click', handleDocumentClick );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
