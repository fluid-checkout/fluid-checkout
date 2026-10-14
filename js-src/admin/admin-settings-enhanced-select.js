/**
 * Enhance Fluid Checkout settings page select fields with TomSelect.
 *
 * DEPENDS ON:
 * - TomSelect
 */

( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsEnhancedSelect = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		selectFieldSelector:              '.fc-settings-wrap select:not([data-fc-enhanced-select="no"])',
		settingsActionsSelector:          '.fc-settings-actions',
		maximumSelectionLengthAttribute:  'data-maximum-selection-length',
		dropdownDropupClass:              'fc-ts-dropdown--dropup',
		fieldSettings: {
			maxOptions:    999999,
			create:        false,
			diacritics:    true,
			openOnFocus:   true,
			dropdownParent: 'body',
			dropdownClass:  'ts-dropdown fc-settings-ts-dropdown',
		},
		fieldPluginsSingle: [],
		fieldPluginsMulti:  [ 'remove_button' ],
	};



	/**
	 * METHODS
	 */



	/**
	 * Get the selected values of a select field.
	 *
	 * @param   {Element}       field  The select field.
	 * @return  {Array|string}         Selected values as an array, or a single value when only one is selected.
	 */
	var getSelectValues = function( field ) {
		var results = [];
		var options;
		var currentOption;
		var i;

		// Bail if field is not valid
		if ( ! field || ! field.options ) { return; }

		options = field.options;

		// Iterate options and collect selected values
		for ( i = 0; i < options.length; i++ ) {
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
	};



	/**
	 * Unset autocomplete attribute for the TomSelect search field.
	 */
	var disableFieldAutocomplete = function() {
		var tomselect = this;
		var searchField = tomselect.focus_node;

		// Bail if search field is missing
		if ( ! searchField ) { return; }

		searchField.setAttribute( 'autocomplete', 'off-' + Date.now() );
	};



	/**
	 * Position the Tom Select dropdown above or below the control so it stays in the viewport.
	 *
	 * @param   {object}  tomselect  Tom Select instance.
	 */
	var positionAdminSettingsDropdown = function( tomselect ) {
		var control = tomselect.control;
		var dropdown = tomselect.dropdown;
		var rect;
		var dropdownHeight;
		var content;
		var maxDropdownHeight;
		var estimatedHeight;
		var actionsBar;
		var bottomInset;
		var viewportBottom;
		var spaceBelow;
		var spaceAbove;
		var openUpward;

		// Bail if required elements are missing
		if ( ! control || ! dropdown ) { return; }

		rect = control.getBoundingClientRect();
		dropdownHeight = dropdown.offsetHeight;
		content = dropdown.querySelector( '.ts-dropdown-content' );
		maxDropdownHeight = content ? parseInt( window.getComputedStyle( content ).maxHeight, 10 ) : 0;

		if ( isNaN( maxDropdownHeight ) || maxDropdownHeight <= 0 ) {
			maxDropdownHeight = dropdownHeight;
		}

		estimatedHeight = Math.min( dropdownHeight || maxDropdownHeight, maxDropdownHeight );

		actionsBar = document.querySelector( _settings.settingsActionsSelector );
		bottomInset = 0;

		if ( actionsBar ) {
			bottomInset = actionsBar.getBoundingClientRect().height;
		}

		viewportBottom = window.innerHeight - bottomInset;
		spaceBelow = viewportBottom - rect.bottom;
		spaceAbove = rect.top;
		openUpward = spaceBelow < estimatedHeight && spaceAbove > spaceBelow;

		dropdown.style.width = rect.width + 'px';
		dropdown.style.left = rect.left + window.scrollX + 'px';

		if ( openUpward ) {
			dropdown.classList.add( _settings.dropdownDropupClass );
			dropdown.style.top = ( rect.top + window.scrollY - dropdown.offsetHeight ) + 'px';
		}
		else {
			dropdown.classList.remove( _settings.dropdownDropupClass );
			dropdown.style.top = ( control.offsetHeight + rect.top + window.scrollY ) + 'px';
		}
	};



	/**
	 * Enhance select fields with TomSelect.
	 *
	 * @param  {Element|string}  fieldOrSelector  Optional field or CSS selector. Uses the default selector when omitted.
	 * @param  {object}          settings         Optional TomSelect settings. Uses defaults when omitted.
	 */
	_publicMethods.enhanceFields = function( fieldOrSelector, settings ) {
		var fields = [];
		var i;
		var field;
		var values;
		var isMultiple;
		var instance;
		var fieldSettings;
		var maxSelectionLength;
		var maxItems;

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

		fields = [ fieldOrSelector ];

		// Maybe resolve fields from a CSS selector
		if ( 'string' === typeof fieldOrSelector ) {
			try {
				fields = document.querySelectorAll( fieldOrSelector );
			}
			// Bail when the selector is invalid
			catch ( error ) {
				console.warn( 'Admin enhanced select: ' + error.message );
				return;
			}
		}

		// Iterate fields and enhance them with TomSelect
		for ( i = 0; i < fields.length; i++ ) {
			field = fields[ i ];
			values = getSelectValues( field );
			isMultiple = field.hasAttribute( 'multiple' );
			instance = field.tomselect;
			fieldSettings = Object.assign( {}, settings );

			// Skip fields that are already enhanced
			if ( instance ) { continue; }

			// Use multi or single plugins based on the select attributes
			if ( isMultiple ) {
				fieldSettings.plugins = _settings.fieldPluginsMulti;
			}
			// Otherwise use the single-select plugin list
			else {
				fieldSettings.plugins = _settings.fieldPluginsSingle;
			}

			maxSelectionLength = field.getAttribute( _settings.maximumSelectionLengthAttribute );

			// Maybe limit the number of selected items from the field attribute
			if ( maxSelectionLength ) {
				maxItems = parseInt( maxSelectionLength, 10 );
				if ( ! isNaN( maxItems ) && maxItems > 0 ) {
					fieldSettings.maxItems = maxItems;
				}
			}

			instance = new TomSelect( field, fieldSettings );

			instance.hook( 'instead', 'positionDropdown', function() {
				positionAdminSettingsDropdown( this );
			} );

			// Set value without triggering change to avoid feedback loops
			instance.setValue( values, true );

			instance.on( 'focus', disableFieldAutocomplete );
			instance.on( 'blur', disableFieldAutocomplete );
		}
	};



	/**
	 * Initialize enhanced select fields on the settings page.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		_publicMethods.enhanceFields();

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
