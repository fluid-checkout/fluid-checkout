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
		enhancedSelectFieldsSelector:          '.fc-select2-field select',
		enhancedSelectSettings: {
			create: false,
			diacritics: true,
		},
		enhancedSelectPluginsSingle: [],
		enhancedSelectPluginsMulti: [ 'remove_button' ],
	};




	/**
	 * Support for Select2 and SelectWoo jQuery plugins.
	 * Replace `$.fn.select2` and `$.fn.selectWoo` with a dummy function to avoid JS errors when other plugins try to use them.
	 */
	var replaceSelect2JQueryPlugins = function() {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Replace `$.fn.select2` and `$.fn.selectWoo` with a dummy function
		$.fn.select2 = function() { return this; };
		$.fn.selectWoo = function() { return this; };
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
		var results = [];
		var options = field && field.options;
		var currentOption;

		// Iterate options and get selected values
		for ( var i = 0; i < options.length; i++ ) {
			currentOption = options[i];
		
			if ( currentOption.selected ) {
				results.push( currentOption.value || currentOption.text );
			}
		}

		// Maybe return single value
		if ( results.length === 1 ) {
			results = results[0];
		}

		return results;
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
		var values = getSelectValues( field );

		// Set value, without triggering `change` event
		// to avoid infinite loop.
		field.tomselect.setValue( values, true );
	}



	/**
	 * Unset autocomplete attribute for the search field.
	 */
	var unsetAutocompleteAttribute = function() {
		// Get field reference
		var tomselect = this;
		var searchField = tomselect.focus_node;

		// Unset autocomplete attribute for the search field
		searchField.removeAttribute( 'autocomplete' );
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
			var values = getSelectValues( field );

			// Maybe destroy TomSelect instance
			if ( field.tomselect ) {
				field.tomselect.destroy();
			}

			// Maybe add TomSelect plugins for single or multi select
			// Multi select
			if ( field.hasAttribute( 'multiple' ) ) {
				settings.plugins = _settings.enhancedSelectPluginsMulti;
			}
			// Single select
			else {
				settings.plugins = _settings.enhancedSelectPluginsSingle;
			}

			// Enhance field with TomSelect
			new TomSelect( field, settings );

			// Set value, without triggering `change` event
			// to avoid infinite loop.
			field.tomselect.setValue( values, true );

			// Set event listeners
			field.tomselect.on( 'initialize', unsetAutocompleteAttribute );
			field.tomselect.on( 'focus', unsetAutocompleteAttribute );
			field.tomselect.on( 'blur', unsetAutocompleteAttribute );
			field.tomselect.on( 'dropdown_open', maybeScrollToField );
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
