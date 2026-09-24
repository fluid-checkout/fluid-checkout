/**
 * Enhance Fluid Checkout settings page select fields with TomSelect.
 */

( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	} else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	} else {
		root.FCAdminSettingsEnhancedSelect = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		selectFieldSelector: '.fc-settings-wrap select.fc-enhanced-select',
		fieldSettings: {
			maxOptions: 999999,
			create: false,
			diacritics: true,
			openOnFocus: true,
		},
		fieldPluginsSingle: [],
		fieldPluginsMulti: [ 'remove_button' ],
	};



	/**
	 * Get the selected values of a select field.
	 *
	 * @param   {Element}       field  The select field.
	 * @return  {Array|string}         Selected values as an array, or a single value when only one is selected.
	 */
	var getSelectValues = function( field ) {
		// Bail if field is not valid
		if ( ! field || ! field.options ) { return; }

		var results = [];
		var options = field.options;
		var currentOption;

		// Iterate options and collect selected values
		for ( var i = 0; i < options.length; i++ ) {
			currentOption = options[ i ];

			// Add selected value to results
			if ( currentOption.selected ) {
				results.push( currentOption.value || currentOption.text );
			}
		}

		// Maybe return a single value for single-select fields
		if ( 1 === results.length && ! field.hasAttribute( 'multiple' ) ) {
			results = results[ 0 ];
		}

		return results;
	}

	/**
	 * Unset autocomplete attribute for the TomSelect search field.
	 */
	var disableFieldAutocomplete = function() {
		var tomselect = this;
		var searchField = tomselect.focus_node;

		// Bail if search field is missing
		if ( ! searchField ) { return; }

		searchField.setAttribute( 'autocomplete', 'off-' + Date.now() );
	}

	/**
	 * Enhance select fields with TomSelect.
	 *
	 * @param  {Element|string}  fieldOrSelector  Optional field or CSS selector. Uses the default selector when omitted.
	 * @param  {object}          settings         Optional TomSelect settings. Uses defaults when omitted.
	 */
	_publicMethods.enhanceFields = function( fieldOrSelector, settings ) {
		// Bail if TomSelect is not available
		if ( 'undefined' === typeof window.TomSelect ) { return; }

		// Maybe use the default selector
		if ( undefined === fieldOrSelector || null === fieldOrSelector ) {
			fieldOrSelector = _settings.selectFieldSelector;
		}

		// Bail if fieldOrSelector is not a select element or a string selector
		if ( 'string' !== typeof fieldOrSelector && ( 'object' !== typeof fieldOrSelector || ! fieldOrSelector.matches( 'select' ) ) ) { return; }

		// Maybe use the default field settings
		if ( undefined === settings || null === settings ) {
			settings = _settings.fieldSettings;
		}

		var fields = [ fieldOrSelector ];

		// Maybe resolve fields from a CSS selector
		if ( 'string' === typeof fieldOrSelector ) {
			try {
				fields = document.querySelectorAll( fieldOrSelector );
			} catch ( error ) {
				console.warn( 'Admin enhanced select: ' + error.message );
				return;
			}
		}

		// Iterate fields and enhance them with TomSelect
		for ( var i = 0; i < fields.length; i++ ) {
			var field = fields[ i ];
			var values = getSelectValues( field );
			var isMultiple = field.hasAttribute( 'multiple' );
			var instance = field.tomselect;
			var fieldSettings;

			// Skip fields that are already enhanced
			if ( instance ) { continue; }

			fieldSettings = Object.assign( {}, settings );

			// Use multi or single plugins based on the select attributes
			if ( isMultiple ) {
				fieldSettings.plugins = _settings.fieldPluginsMulti;
			} else {
				fieldSettings.plugins = _settings.fieldPluginsSingle;
			}

			instance = new TomSelect( field, fieldSettings );

			// Set value without triggering change to avoid feedback loops
			instance.setValue( values, true );

			instance.on( 'focus', disableFieldAutocomplete );
			instance.on( 'blur', disableFieldAutocomplete );
		}
	}



	/**
	 * Initialize enhanced select fields on the settings page.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		_publicMethods.enhanceFields();

		_hasInitialized = true;
	}



	return _publicMethods;

} );
