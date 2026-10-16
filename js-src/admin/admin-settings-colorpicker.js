/**
 * Color picker fields on the Fluid Checkout settings page.
 * Mirrors WooCommerce settings colorpick behavior without relying on table cells.
 *
 * DEPENDS ON:
 * - jQuery Iris (via `$.fn.iris` only)
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
	var _suppressIrisChange = false;
	var _previewFrameDocumentsBound = new WeakSet();
	var _publicMethods = {};
	var _settings = {
		wrapSelector:                  '.fc-settings-wrap',
		colorInputSelector:            '.fc-settings-wrap .colorpick',
		colorInputClass:               'colorpick',
		previewClass:                  'colorpickpreview',
		previewSelector:               '.colorpickpreview',
		controlSelector:               '.fc-settings-field__control',
		irisPickerSelector:            '.iris-picker',
		irisSquareClass:               'iris-square-value',
		irisErrorClass:                'iris-error',
		colorDataReadyKey:             'fcColorpickReady',
		colorDataOriginalKey:          'originalValue',
		colorPattern:                  /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
		defaultIrisColor:              '#2271b1',
		emptyPreviewBackground:        'transparent',
		settingsPreviewSelector:       '.fc-settings-preview',
		settingsPreviewFrameSelector:  '.fc-settings-preview__frame',
	};



	/**
	 * METHODS
	 */



	/**
	 * Whether a node is inside the settings wrap.
	 *
	 * @param   {Element|null}  element  DOM node.
	 * @return  {boolean}                  True when the node is inside the settings wrap.
	 */
	var isInsideSettingsWrap = function( element ) {
		return !!( element && element.closest && element.closest( _settings.wrapSelector ) );
	};



	/**
	 * Get the color input associated with an event target, if any.
	 *
	 * @param   {Element|null}  target  Event target.
	 * @return  {HTMLInputElement|null}  Color input element.
	 */
	var getColorInputFromTarget = function( target ) {
		var input;

		// Bail if the target is not inside the settings wrap
		if ( ! isInsideSettingsWrap( target ) ) { return null; }

		input = target.closest( '.' + _settings.colorInputClass );

		return input;
	};



	/**
	 * Get the settings field control that owns a color input.
	 *
	 * @param   {HTMLInputElement}  input  Color input element.
	 * @return  {Element}                  Control wrapper, or the input parent as fallback.
	 */
	var getColorControl = function( input ) {
		var control = input.closest( _settings.controlSelector );

		return control ? control : input.parentElement;
	};



	/**
	 * Dispatch a bubbling change event on a color input.
	 *
	 * @param  {HTMLInputElement}  input  Color input element.
	 */
	var dispatchColorInputChange = function( input ) {
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};



	/**
	 * Trigger a deferred change event on a color input after Iris updates.
	 *
	 * @param  {HTMLInputElement}  input  Color input element.
	 */
	var scheduleColorInputChange = function( input ) {
		window.setTimeout( function() {
			dispatchColorInputChange( input );
		} );
	};



	/**
	 * Hide every Iris picker on the page.
	 */
	var hideAllIrisPickers = function() {
		var pickers = document.querySelectorAll( _settings.irisPickerSelector );
		var i;

		// Iterate Iris picker elements
		for ( i = 0; i < pickers.length; i++ ) {
			pickers[ i ].style.display = 'none';
		}
	};



	/**
	 * Color value for Iris when the input is empty or not a valid hex string.
	 *
	 * @param   {HTMLInputElement}  input  Color input element.
	 * @return  {string}                  Valid hex color for Iris UI.
	 */
	var getInitialIrisColor = function( input ) {
		var value = ( input.value || '' ).trim();

		if ( _settings.colorPattern.test( value ) ) {
			return value;
		}

		return _settings.defaultIrisColor;
	};



	/**
	 * Whether a color input holds a valid hex value.
	 *
	 * @param   {HTMLInputElement}  input  Color input element.
	 * @return  {boolean}                  True when the value is a valid hex color.
	 */
	var hasValidColorValue = function( input ) {
		return _settings.colorPattern.test( ( input.value || '' ).trim() );
	};



	/**
	 * Set Iris picker UI color without persisting a value on empty settings fields.
	 * Iris always mirrors `option( 'color' )` into the input; clear it when the setting is empty.
	 *
	 * @param  {HTMLInputElement}  input            Color input element.
	 * @param  {boolean}           [keepInputEmpty] When true, clear the input after syncing Iris.
	 */
	var syncIrisColorPreservingEmptyInput = function( input, keepInputEmpty ) {
		// Bail if jQuery or Iris is not available
		if ( ! _hasJQuery || 'function' !== typeof $.fn.iris ) { return; }

		if ( 'undefined' === typeof keepInputEmpty ) {
			keepInputEmpty = ! hasValidColorValue( input );
		}

		_suppressIrisChange = true;
		$( input ).iris( 'option', 'color', getInitialIrisColor( input ) );

		// Maybe keep the saved setting empty while Iris uses a valid UI color internally
		if ( keepInputEmpty ) {
			input.value = '';
		}

		_suppressIrisChange = false;
	};



	/**
	 * Sync the preview swatch with the color input value.
	 *
	 * @param  {HTMLInputElement}  input  Color input element.
	 */
	var updateColorPreviewFromInput = function( input ) {
		var control = getColorControl( input );
		var preview = control.querySelector( _settings.previewSelector );

		// Bail if the field has no preview swatch
		if ( ! preview ) { return; }

		if ( hasValidColorValue( input ) ) {
			preview.style.backgroundColor = input.value.trim();
		}
		else {
			preview.style.backgroundColor = _settings.emptyPreviewBackground;
		}
	};



	/**
	 * Handle Iris color changes for a settings color field.
	 *
	 * @param  {Event}   event  Iris change event.
	 * @param  {Object}  ui     Iris UI payload.
	 */
	var handleIrisColorChange = function( event, ui ) {
		if ( _suppressIrisChange ) { return; }

		var input = event.target;
		var control = getColorControl( input );
		updateColorPreviewFromInput( input );

		scheduleColorInputChange( input );
	};



	/**
	 * Initialize Iris color pickers for settings color fields.
	 */
	var initColorPickers = function() {
		var colorInputs = document.querySelectorAll( _settings.colorInputSelector );
		var i;
		var input;
		var $input;
		var keepInputEmpty;

		// Bail if jQuery or Iris is not available
		if ( ! _hasJQuery || 'function' !== typeof $.fn.iris ) { return; }

		// Iterate color inputs
		for ( i = 0; i < colorInputs.length; i++ ) {
			input = colorInputs[ i ];
			$input = $( input );
			keepInputEmpty = ! hasValidColorValue( input );

			// Bail if already initialized
			if ( input.dataset[ _settings.colorDataReadyKey ] ) { continue; }

			_suppressIrisChange = true;

			$input.iris( {
				color:  getInitialIrisColor( input ),
				change: handleIrisColorChange,
				hide:   true,
				border: true,
			} );

			// Iris mirrors the UI color into the input; restore an empty saved setting
			if ( keepInputEmpty ) {
				input.value = '';
			}

			updateColorPreviewFromInput( input );

			_suppressIrisChange = false;

			input.dataset[ _settings.colorDataReadyKey ] = '1';
		}
	};



	/**
	 * Show the Iris picker for a color input and hide other pickers.
	 *
	 * @param  {HTMLInputElement}  input  Color input element.
	 */
	var showColorPicker = function( input ) {
		var control = getColorControl( input );
		var picker = control.querySelector( _settings.irisPickerSelector );

		hideAllIrisPickers();

		// Refresh Iris UI for empty fields (invalid color leaves the hue strip black)
		if ( ! hasValidColorValue( input ) ) {
			syncIrisColorPreservingEmptyInput( input, true );
		}

		// Maybe show the picker for this field (CSS keeps `.iris-picker` at `display: none` until shown)
		if ( picker ) {
			picker.style.display = 'block';
		}

		input.dataset[ _settings.colorDataOriginalKey ] = input.value;
	};



	/**
	 * Whether the Iris picker for a color input is visible.
	 *
	 * @param   {HTMLInputElement}  input  Color input element.
	 * @return  {boolean}                  True when that field's picker is open.
	 */
	var isPickerOpenForInput = function( input ) {
		var control = getColorControl( input );
		var picker = control.querySelector( _settings.irisPickerSelector );

		// Bail if the field has no Iris picker element
		if ( ! picker ) { return false; }

		return 'block' === picker.style.display;
	};



	/**
	 * Handle click on a color input.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleColorInputActivate = function( e ) {
		var input = getColorInputFromTarget( e.target );

		// Bail if the event is not for a color input
		if ( ! input ) { return; }

		e.stopPropagation();
		showColorPicker( input );
	};



	/**
	 * Open the picker when a color input is focused, or close it when focus moves elsewhere.
	 *
	 * @param  {Event}  e  Focusin event.
	 */
	var handleDocumentFocusIn = function( e ) {
		var input = getColorInputFromTarget( e.target );

		// Maybe show the picker for the focused color input
		if ( input ) {
			showColorPicker( input );
			return;
		}

		// Bail if focus moved within the picker UI or preview swatch
		if ( shouldKeepIrisPickerOpen( e.target ) ) { return; }

		hideAllIrisPickers();
	};



	/**
	 * Close the picker when a color input loses focus (Tab, Shift+Tab, or another focusable control).
	 *
	 * @param  {Event}  e  Focusout event.
	 */
	var handleColorInputFocusOut = function( e ) {
		var input = getColorInputFromTarget( e.target );

		// Bail if focus left something other than a settings color input
		if ( ! input ) { return; }

		// Bail if focus is moving into the Iris UI for this field
		if ( e.relatedTarget && shouldKeepIrisPickerOpen( e.relatedTarget ) ) { return; }

		window.setTimeout( function() {
			var activeElement = document.activeElement;

			// Bail if focus returned to the same color input
			if ( activeElement === input ) { return; }

			// Bail if focus is on the picker or the field swatch / input
			if ( shouldKeepIrisPickerOpen( activeElement ) ) { return; }

			hideAllIrisPickers();
		} );
	};



	/**
	 * Close pickers on pointer down inside the page preview iframe (events do not bubble to the admin document).
	 */
	var bindSettingsPreviewFrameClose = function() {
		var frame = document.querySelector( _settings.settingsPreviewFrameSelector );

		// Bail if the preview iframe is not on the page
		if ( ! frame ) { return; }

		var bindFrameDocument = function() {
			var frameDocument;

			try {
				frameDocument = frame.contentDocument;
			}
			catch ( err ) {
				return;
			}

			// Bail if the iframe document is unavailable or already bound
			if ( ! frameDocument || _previewFrameDocumentsBound.has( frameDocument ) ) { return; }

			frameDocument.addEventListener( 'mousedown', hideAllIrisPickers );
			_previewFrameDocumentsBound.add( frameDocument );
		};

		bindFrameDocument();
		frame.addEventListener( 'load', bindFrameDocument );
	};



	/**
	 * Restore the previous valid color when Iris marks the value as invalid.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleColorInputChange = function( e ) {
		var input = e.target;
		var originalValue;

		// Bail if the change is not from a settings color input
		if ( ! getColorInputFromTarget( input ) ) { return; }

		if ( input.classList.contains( _settings.irisErrorClass ) ) {
			originalValue = input.dataset[ _settings.colorDataOriginalKey ];

			// Restore a previously valid hex color
			if ( originalValue && originalValue.match( _settings.colorPattern ) ) {
				input.value = originalValue;
				dispatchColorInputChange( input );
			}
			// Otherwise clear the invalid value
			else {
				input.value = '';
				dispatchColorInputChange( input );
			}
		}

		updateColorPreviewFromInput( input );
	};



	/**
	 * Toggle the color picker when the preview swatch is clicked.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handlePreviewClick = function( e ) {
		var preview = e.target.closest( '.' + _settings.previewClass );
		var input;

		// Bail if the click is not on a preview inside the settings wrap
		if ( ! preview || ! isInsideSettingsWrap( preview ) ) { return; }

		input = preview.nextElementSibling;

		// Bail if the preview is not followed by a color input
		if ( ! input || ! input.classList.contains( _settings.colorInputClass ) ) { return; }

		e.stopPropagation();

		// Close the picker when the swatch is clicked while that field's picker is open
		if ( isPickerOpenForInput( input ) ) {
			hideAllIrisPickers();
			return;
		}

		showColorPicker( input );
		input.focus();
	};



	/**
	 * Prevent Iris square clicks from bubbling and closing the picker.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleIrisSquareClick = function( e ) {
		var square = e.target.closest( '.' + _settings.irisSquareClass );

		// Bail if the click is not on an Iris square inside the settings wrap
		if ( ! square || ! isInsideSettingsWrap( square ) ) { return; }

		e.preventDefault();
	};



	/**
	 * Whether a click should leave the open Iris picker visible.
	 *
	 * @param   {Element|null}  target  Event target.
	 * @return  {boolean}                 True when the click interacts with the picker UI or its field controls.
	 */
	var shouldKeepIrisPickerOpen = function( target ) {
		// Bail if the target cannot be matched to ancestors
		if ( ! target || ! target.closest ) { return false; }

		// Bail if the click is inside the open picker
		if ( target.closest( _settings.irisPickerSelector ) ) { return true; }

		// Bail if the click is on the color input or preview swatch (show handlers run on the same body click)
		if ( target.closest( '.' + _settings.colorInputClass ) ) { return true; }
		if ( target.closest( '.' + _settings.previewClass ) ) { return true; }

		return false;
	};



	/**
	 * Hide open Iris pickers when clicking outside them.
	 *
	 * @param  {Event}  e  Mouse event.
	 */
	var handleDocumentClick = function( e ) {
		var target = e.target;

		// Close when interacting with the checkout preview column (including the iframe element)
		if ( target && target.closest && target.closest( _settings.settingsPreviewSelector ) ) {
			hideAllIrisPickers();
			return;
		}

		// Bail if the click should keep the picker open
		if ( shouldKeepIrisPickerOpen( target ) ) { return; }

		hideAllIrisPickers();
	};



	/**
	 * Close the Iris picker when Escape is pressed on a settings color input.
	 *
	 * @param  {Event}  e  Keydown event.
	 */
	var handleColorInputKeyDown = function( e ) {
		// Bail if FCUtils or Escape is unavailable
		if ( ! root.FCUtils || root.FCUtils.keyboardKeys.ESC !== e.key ) { return; }

		// Bail if focus is not on a settings color input
		if ( ! getColorInputFromTarget( e.target ) ) { return; }

		hideAllIrisPickers();
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
		bindSettingsPreviewFrameClose();

		document.body.addEventListener( 'mousedown', handleDocumentClick );
		document.body.addEventListener( 'click', handleColorInputActivate );
		document.body.addEventListener( 'focusin', handleDocumentFocusIn );
		document.body.addEventListener( 'focusout', handleColorInputFocusOut );
		document.body.addEventListener( 'keydown', handleColorInputKeyDown );
		document.body.addEventListener( 'change', handleColorInputChange );
		document.body.addEventListener( 'click', handlePreviewClick );
		document.body.addEventListener( 'click', handleIrisSquareClick );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
