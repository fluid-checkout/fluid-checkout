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

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass:                             'has-fc-enhanced-select',
		formRowSelector:                       '.form-row.fc-select2-field',
		selectFieldSelector:                   '.fc-select2-field select',
		wrapperElementSelector:                '.ts-wrapper',
		inputFieldSelector:                    '.ts-control > input',

		fieldSettings: {
			maxOptions: 999999,
			create: false,
			diacritics: true,
			openOnFocus: false,
		},
		fieldPluginsSingle: [],
		fieldPluginsMulti: [ 'remove_button' ],
	};



	/**
	 * SELECT2 SUPPORT
	 */

	/**
	 * Enhance select fields with TomSelect when trying to use `select2` or `selectWoo` jQuery plugins.
	 */
	var initializeFromSelect2 = function() {
		var $fields = $( this );

		// Iterate fields and enhance them with TomSelect
		$fields.each( function( i, field ) {
			_publicMethods.enhanceFields( field );
		} );

		return this;
	}

	/**
	 * Support for Select2 and SelectWoo jQuery plugins.
	 * Replace `$.fn.select2` and `$.fn.selectWoo` with a dummy function to avoid JS errors when other plugins try to use them.
	 */
	var replaceSelect2JQueryPlugins = function() {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Replace `$.fn.select2` and `$.fn.selectWoo` with a dummy function
		$.fn.select2 = initializeFromSelect2;
		$.fn.selectWoo = initializeFromSelect2;
	}
	// Replace immediatelly.
	replaceSelect2JQueryPlugins();



	/**
	 * METHODS
	 */


	
	/**
	 * Get the selected values of a select field.
	 * 
	 * @param    {Element}       field   The select field.
	 * 
	 * @returns  {Array|string}          The selected values as an array, or a single value if only one is selected.
	 */
	var getSelectValues = function( field ) {
		// Bail if field is not valid
		if ( ! field || ! field.options ) { return; }

		// Initialize results array
		var results = [];

		// Get options
		var options = field.options;
		var currentOption;

		// Iterate options and get selected values
		for ( var i = 0; i < options.length; i++ ) {
			currentOption = options[i];

			// Add selected value to results
			if ( currentOption.selected ) {
				results.push( currentOption.value || currentOption.text );
			}
		}

		// Maybe return single value
		if ( results.length === 1 ) {
			results = results[ 0 ];
		}

		return results;
	}

	/**
	 * Update the selected value of an enhanced select field.
	 *
	 */
	var updateSelectedValue = function( field ) {
		// Bail if field is not valid
		if ( ! field ) { return; }

		// Get updated field value
		var values = getSelectValues( field );

		// Set value, without triggering `change` event
		// to avoid infinite loop.
		field.tomselect.setValue( values, true );
	}



	/**
	 * Unset autocomplete attribute for the search field.
	 */
	var disableFieldAutocomplete = function() {
		// Get field reference
		var tomselect = this;
		var searchField = tomselect.focus_node;

		// Unset autocomplete attribute for the search field
		searchField.setAttribute( 'autocomplete', 'off-' + Date.now() );
	}

	/**
	 * Maybe scroll to the top of the field when opening the dropdown.
	 */
	var maybeScrollToField = function() {
		// Bail if not on target breakpoint or smaller
		if ( ! FCUtils.isCurrentBreakpointOrSmaller( 'phablet' ) ) { return; }

		// Get field reference
		var tomselect = this;
		var searchField = tomselect.focus_node;
		var formRow = searchField.closest( _settings.formRowSelector );

		// Bail if form row is not found
		if ( ! formRow ) { return; }

		// Scroll to the top of the form row
		FCUtils.scrollToElement( formRow, null, 30 );
	}



	/**
	 * Maybe open the dropdown of the field.
	 * 
	 * @param  {Element}  field  The select field.
	 */
	var maybeOpenDropdown = function( field ) {
		// Bail if field is not valid
		if ( ! field ) { return; }

		// Bail if not a TomSelect field
		if ( ! field.tomselect ) { return; }

		// Get TomSelect instance
		var tomselect = field.tomselect;

		// Maybe open field dropdown
		if ( ! tomselect.isOpen ) {
			tomselect.open();
		}
	}

	/**
	 * Maybe close the dropdown of the field.
	 * 
	 * @param  {Element}  field  The select field.
	 */
	var maybeCloseDropdown = function( field ) {
		// Bail if field is not valid
		if ( ! field ) { return; }

		// Bail if not a TomSelect field
		if ( ! field.tomselect ) { return; }

		// Get TomSelect instance
		var tomselect = field.tomselect;

		// Maybe close field dropdown
		if ( tomselect.isOpen ) {
			tomselect.close();
		}
	}

	/**
	 * Maybe toggle the dropdown of the field open/closed status.
	 * 
	 * @param  {Element}  field  The select field.
	 */
	var maybeToggleDropdown = function( field ) {
		// Bail if field is not valid
		if ( ! field ) { return; }

		// Bail if not a TomSelect field
		if ( ! field.tomselect ) { return; }

		// Get TomSelect instance
		var tomselect = field.tomselect;
		var isOpen = tomselect.isOpen;

		// Maybe toggle field dropdown
		requestAnimationFrame( function() {
		if ( isOpen ) {
				maybeCloseDropdown( field );
			}
			else {
				maybeOpenDropdown( field );
			}
		} );
	}



	/** 
	 * Enhance selecct fields with TomSelect.
	 * 
	 * @param  {Element|string}  fieldOrSelector   (optional) Field or CSS selector for the fields to enhance, will use default settings if not defined.
	 * @param  {object}          settings          (optional) Settings for the enhanced select fields, will use default settings if not defined.
	 */
	_publicMethods.enhanceFields = function( fieldOrSelector, settings ) {
		// Bail if TomSelect is not defined
		if ( 'undefined' === typeof TomSelect ) { return; }

		// Get selector from settings if not defined
		if ( undefined === fieldOrSelector || null === fieldOrSelector ) {
			fieldOrSelector = _settings.selectFieldSelector;
		}

		// Bail if not an accepted field or selector is not of type string
		// fieldOrSelector is a `select` Field
		if ( 'string' !== typeof fieldOrSelector && ( 'object' !== typeof fieldOrSelector || ! fieldOrSelector.matches( 'select' ) ) ) { return; }

		// Maybe get default settings
		if ( undefined === settings || null === settings ) {
			settings = _settings.fieldSettings;
		}

		// Get fields to apply the enhanced select
		var fields = [ fieldOrSelector ];

		// Maybe get fields from selector
		if ( 'string' === typeof fieldOrSelector ) {
			try {
				fields = document.querySelectorAll( fieldOrSelector );
			}
			catch( error ) {
				console.warn( 'Enhanced select: ' + error.message );
				return;
			}
		}

		// Iterate fields and enhance them with TomSelect
		for ( var i = 0; i < fields.length; i++ ) {
			// Get field reference and value
			var field = fields[ i ];
			var values = getSelectValues( field );
			var isMultiple = field.hasAttribute( 'multiple' );

			// Maybe destroy TomSelect instance
			if ( field.tomselect ) {
				field.tomselect.destroy();
			}

			// Handle differences between single and multiple select fields
			// Multiple select
			if ( isMultiple ) {
				settings.plugins = _settings.fieldPluginsMulti;
			}
			// Single select
			else {
				settings.plugins = _settings.fieldPluginsSingle;
			}

			// Enhance field with TomSelect
			var instance = new TomSelect( field, settings );

			// Set value, without triggering `change` event
			// to avoid infinite loop.
			instance.setValue( values, true );

			// Set event listeners
			instance.on( 'focus', disableFieldAutocomplete );
			instance.on( 'blur', disableFieldAutocomplete );
			instance.on( 'dropdown_open', maybeScrollToField );
		}
	}



	/**
	 * Handle captured `focus` event and route to the appropriate functions.
	 */
	var handleFocus = function( e ) {
		// SEARCH INPUT FIELD
		if ( e.target.closest( _settings.inputFieldSelector ) ) {
			var wrapper = e.target.closest( _settings.wrapperElementSelector );
			var field = wrapper.parentNode.querySelector( 'select' );
			maybeOpenDropdown( field );
		}
	}

	/**
	 * Handle captured `blur` event and route to the appropriate functions.
	 */
	var handleBlur = function( e ) {
		// SEARCH INPUT FIELD
		if ( e.target.closest( _settings.inputFieldSelector ) ) {
			var wrapper = e.target.closest( _settings.wrapperElementSelector );
			var field = wrapper.parentNode.querySelector( 'select' );
			maybeCloseDropdown( field );
		}
	}

	/**
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// SELECT FIELD
		if ( e.target.closest( _settings.wrapperElementSelector ) ) {
			var wrapper = e.target.closest( _settings.wrapperElementSelector );
			var field = wrapper.parentNode.querySelector( 'select' );

			// Only process if field is a TomSelect instance
			if ( field.tomselect ) {
				// Update selected value
				updateSelectedValue( field );
			}
		}
	}

	/**
	 * Handle captured `click` event and route to the appropriate functions.
	 */
	var handleClick = function( e ) {
		// SELECT FIELD
		if ( e.target.closest( _settings.wrapperElementSelector ) ) {
			var wrapper = e.target.closest( _settings.wrapperElementSelector );
			var field = wrapper.parentNode.querySelector( 'select' );
			maybeToggleDropdown( field );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings with defaults
		_settings = FCUtils.extendObject( _settings, options );

		// Set event listener for enhanced select fields
		document.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'change', handleChange, true );
		document.addEventListener( 'focus', handleFocus, true );
		document.addEventListener( 'blur', handleBlur, true );

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
