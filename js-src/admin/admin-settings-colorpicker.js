/**
 * Color picker fields on the Fluid Checkout settings page.
 * Mirrors WooCommerce settings colorpick behavior without relying on table cells.
 */

( function( $ ) {
	'use strict';

	var _hasInitialized = false;



	/**
	 * Get the settings field control that owns a color input.
	 *
	 * @param   {jQuery}  $input  Color input element.
	 * @return  {jQuery}          Control wrapper, or the input parent as fallback.
	 */
	var getColorControl = function( $input ) {
		var $control = $input.closest( '.fc-settings-field__control' );
		return $control.length ? $control : $input.parent();
	};

	/**
	 * Initialize Iris color pickers for settings color fields.
	 */
	var initColorPickers = function() {
		// Bail if Iris is not available
		if ( 'function' !== typeof $.fn.iris ) { return; }

		$( '.fc-settings-wrap .colorpick' ).each( function() {
			var $input = $( this );

			// Bail if already initialized
			if ( $input.data( 'fcColorpickReady' ) ) { return; }

			$input.iris( {
				change: function( event, ui ) {
					getColorControl( $input )
						.find( '.colorpickpreview' )
						.css( { backgroundColor: ui.color.toString() } );

					setTimeout( function() {
						$input.trigger( 'change' );
					} );
				},
				hide: true,
				border: true,
			} );

			$input.data( 'fcColorpickReady', true );
		} );
	};

	/**
	 * Show the Iris picker for a color input and hide other pickers.
	 *
	 * @param  {jQuery}  $input  Color input element.
	 */
	var showColorPicker = function( $input ) {
		$( '.iris-picker' ).hide();
		getColorControl( $input ).find( '.iris-picker' ).show();
		$input.data( 'originalValue', $input.val() );
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
		var $input = $( e.currentTarget );
		var originalValue;

		// Bail if the value is still valid
		if ( ! $input.is( '.iris-error' ) ) { return; }

		originalValue = $input.data( 'originalValue' );

		if ( originalValue && originalValue.match( /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/ ) ) {
			$input.val( originalValue ).trigger( 'change' );
		} else {
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
		$preview.next( '.colorpick' ).trigger( 'click' );
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
		$( '.iris-picker' ).hide();
	};



	/**
	 * Initialize color picker behavior on the settings page.
	 */
	var init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if settings form is not available
		if ( ! document.querySelector( '.fc-settings-wrap' ) ) { return; }

		initColorPickers();

		$( document.body )
			.on( 'click focus', '.fc-settings-wrap .colorpick', handleColorInputActivate )
			.on( 'change', '.fc-settings-wrap .colorpick', handleColorInputChange )
			.on( 'click', '.fc-settings-wrap .colorpickpreview', handlePreviewClick )
			.on( 'click', '.fc-settings-wrap .iris-square-value', handleIrisSquareClick )
			.on( 'click', handleDocumentClick );

		_hasInitialized = true;
	};

	$( init );

} )( jQuery );
