/**
 * Initialize enhanced select fields with TomSelect, instead of Select2.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCEnhancedSelect = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass:                             'has-fc-enhanced-select',

		enhancedSelectFieldsSelector:          '.fc-select2-field select',
		enhancedSelectSettings: {
			create: false,
			openOnFocus: true,
			selectOnTab: true,
			diacritics: true,
		},
	};



	/**
	 * PROPERTIES
	 */



	/**
	 * Mapping of keyboard keys based on and comparable with `event.key` values.
	 */
	_publicMethods.keyboardKeys = {
		ESC: 'Escape',
		ENTER: 'Enter',
		SPACE: ' ',
		TAB: 'Tab',
		CAPS: 'CapsLock',
		SHIFT: 'Shift',
		FUNCTION: 'Fn',
		CONTROL: 'Control',
		COMMAND_OR_WINDOWS: 'Meta', // This is the `Windows` logo key, or the `Command` or `⌘` key on Mac keyboards.
		ALT: 'Alt',
		ARROW_LEFT: 'ArrowLeft',
		ARROW_RIGHT: 'ArrowRight',
		ARROW_UP: 'ArrowUp',
		ARROW_DOWN: 'ArrowDown',
	}



	/**
	 * Determine which `animationend` event is supported.
	 */
	_publicMethods.animationEndEvent = window.whichAnimationEnd ? window.whichAnimationEnd() : 'animationend';




	/**
	 * METHODS
	 */



	/*!
	* Merge two or more objects together.
	* (c) 2017 Chris Ferdinandi, MIT License, https://gomakethings.com
	* @param   {Boolean}  deep     If true, do a deep (or recursive) merge [optional]
	* @param   {Object}   objects  The objects to merge together
	* @returns {Object}            Merged values of defaults and options
	*/
	_publicMethods.extendObject = function () {
		// Variables
		var extended = {};
		var deep = false;
		var i = 0;

		// Check if a deep merge
		if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
			deep = arguments[0];
			i++;
		}

		// Merge the object into the extended object
		var merge = function (obj) {
			for (var prop in obj) {
				if (obj.hasOwnProperty(prop)) {
					// If property is an object, merge properties
					if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
						extended[prop] = _publicMethods.extendObject(extended[prop], obj[prop]);
					} else {
						extended[prop] = obj[prop];
					}
				}
			}
		};

		// Loop through each object and conduct a merge
		for (; i < arguments.length; i++) {
			var obj = arguments[i];
			merge(obj);
		}

		return extended;
	};



	/**
	 * Returns a function, that, as long as it continues to be invoked, will not
	 * be triggered. The function will be called after it stops being called for
	 * N milliseconds. If `immediate` is passed, trigger the function on the
	 * leading edge, instead of the trailing.
	 *
	 * @param   {[type]}  func       Function to be executed.
	 * @param   {[type]}  wait       Wait time in milliseconds.
	 * @param   {[type]}  immediate  Trigger the function on the leading edge.
	 *
	 * @return  function              Function to be executed, incapsulated in a timed function.
	 */
	_publicMethods.debounce = function ( func, wait, immediate ) {
		var timeout;

		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply( context, args );
			};

			var callNow = immediate && !timeout;
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );

			if ( callNow ) func.apply( context, args );
		};
	};





	/**
	 * Set the variables that track the current focused element and its value.
	 */
	var getCurrentFocusedElementGlobalVariables = function( setToRelativeSelect2 ) {
		// Set defaults
		if ( setToRelativeSelect2 !== true ) {
			setToRelativeSelect2 = false;
		}

		// Set current focused element and value
		var currentfocusedElement = document.activeElement;

		// Maybe set set to relative `select2` field element,
		// if the focus is current on a `select2` field option.
		var select2Options = currentfocusedElement.closest( _settings.select2OptionsSelector );
		if ( setToRelativeSelect2 && select2Options ) {
			var select2ElementId = select2Options.getAttribute( 'id' ).replace( '-results', '-container' );
			currentfocusedElement = document.getElementById( select2ElementId );
		}

		// Maybe set to form row for `select2` fields
		var currentFocusedFormRow = currentfocusedElement.closest( _settings.select2FormRowSelector );
		if ( currentFocusedFormRow && currentFocusedFormRow.querySelector( _settings.select2SelectionSelector ) ) {
			// Remove focus from current element as it will be replaced
			// This fixes an issue where `select2` fields would not work properly
			// after checkout is updated while focus is on a `select2` field
			if ( currentfocusedElement ) { currentfocusedElement.blur(); }

			currentfocusedElement = currentFocusedFormRow;
		}

		return currentfocusedElement;
	}



	/**
	 * Update the selected value of an enhanced select field.
	 * 
	 * @param  {Event}  event  The `change` event.
	 */
	var updateSelectedValue = function( event ) {
		// Get field reference and value
		var field = event.target;

		// Bail if field does not match enhanced select selector
		if ( ! field.matches( _settings.enhancedSelectFieldsSelector ) ) { return; }

		// Bail if field is not a TomSelect field
		if ( ! field.tomselect ) { return; }

		// Get updated field value
		var value = field.value;

		// Set value, without triggering `change` event
		// to avoid infinite loop.
		field.tomselect.setValue( value, true );
	}



	/** 
	 * Enhance selecct fields with TomSelect.
	 * 
	 * @param  {string}  selector   (optional) Selector for the fields to enhance, will use default settings if not defined.
	 * @param  {object}  settings   (optional) Settings for the enhanced select fields, will use default settings if not defined.
	 */
	_publicMethods.enhanceFields = function( selector, settings ) {
		// Bail if TomSelect is not defined
		if ( 'undefined' === typeof TomSelect ) { return; }

		// Get selector from settings if not defined
		if ( undefined === selector || null === selector ) {
			selector = _settings.enhancedSelectFieldsSelector;
		}

		// Bail if selector is not of type string
		if ( 'string' !== typeof selector ) { return; }

		// Maybe get default settings
		if ( undefined === settings || null === settings ) {
			settings = _settings.enhancedSelectSettings;
		}

		// Get fields to apply the enhanced select
		var fields;
		try {
			fields = document.querySelectorAll( selector );
		}
		catch( error ) {
			console.warn( 'Enhanced select: ' + error.message );
			return;
		}

		// Iterate fields and enhance them with TomSelect
		for ( var i = 0; i < fields.length; i++ ) {
			// Get field reference and value
			var field = fields[ i ];
			var value = field.value;

			// Maybe destroy TomSelect instance
			if ( field.tomselect ) {
				field.tomselect.destroy();
			}

			// Enhance field with TomSelect
			new TomSelect( field, settings );

			// Set value, without triggering `change` event
			// to avoid infinite loop.
			field.tomselect.setValue( value, true );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings with defaults
		_settings = _publicMethods.extendObject( _settings, options );

		// Set event listener for enhanced select fields
		document.addEventListener( 'change', updateSelectedValue, true );

		// Initialize fields
		_publicMethods.enhanceFields();

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	}





	/**
	 * Expose public APIs.
	 */
	return _publicMethods;

});
