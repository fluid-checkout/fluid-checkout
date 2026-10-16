/**
 * Manage conditional field visibility on Fluid Checkout admin settings pages.
 *
 * DEPENDS ON:
 * - None (vanilla JS)
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCSettingsPage = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		fieldIdPrefix:                         '', // Set at initialization when field ids use a prefix

		conditionalFieldsSelector:             '[data-conditional-id]',
		conditionalFieldsForTriggerSelector:   '[data-conditional-id="###ID###"]',
		conditionalFieldKeyAttribute:          'data-conditional-id',
		conditionalFieldValueAttribute:        'data-conditional-value',

		settingsRowSelector:                   'tr',

		hiddenClass:                           'hidden',
	};
	var _triggerFieldIds = [];



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
	var extend = function () {
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
						extended[prop] = extend(extended[prop], obj[prop]);
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
	 * Get the field value based on field type.
	 *
	 * @param   {Element}  element  The form field element.
	 * @return  {string}            The field value.
	 */
	var getFieldValue = function( element ) {
		// Bail if element is not valid
		if ( ! element ) { return; }

		// Get field value
		var fieldValue = element.value;

		switch ( element.tagName.toLowerCase() ) {
			case 'input':
				switch ( element.type ) {
					case 'checkbox':
						fieldValue = element.checked ? 'yes' : 'no';
						break;
					case 'radio':
						// Use the checked radio from the same name group when available
						if ( element.name ) {
							var checkedRadio = document.querySelector( 'input[type="radio"][name="' + element.name + '"]:checked' );
							fieldValue = checkedRadio ? checkedRadio.value : 'none';
						}
						// Otherwise use the current radio element state
						else {
							fieldValue = element.checked ? element.value : 'none';
						}
						break;
					default:
						fieldValue = element.value;
				}
				break;
			case 'select':
				fieldValue = element.options[ element.selectedIndex ].value;
				break;
			default:
				fieldValue = element.value;
		}

		return fieldValue;
	}

	/**
	 * Get the conditional trigger key for an element (id or name, without field id prefix).
	 *
	 * @param   {Element}  element  The form field element.
	 * @return  {string}            The trigger key used in data-conditional-id attributes.
	 */
	var getTriggerKey = function( element ) {
		// Bail if element is not valid
		if ( ! element ) { return ''; }

		// Prefer element id when available
		if ( element.id ) {
			return element.id.replace( _settings.fieldIdPrefix, '' );
		}

		// Fall back to name (e.g. radio groups without an id)
		if ( element.name ) {
			return element.name.replace( _settings.fieldIdPrefix, '' );
		}

		return '';
	}

	/**
	 * Get the trigger field element by its full field id (with prefix).
	 *
	 * @param   {string}   fieldId  The full field id including prefix.
	 * @return  {Element}           The trigger field element, or null.
	 */
	var getTriggerFieldElement = function( fieldId ) {
		// Try by id first
		var element = document.getElementById( fieldId );
		if ( element ) { return element; }

		// Maybe radio group matched by name
		var checkedRadio = document.querySelector( 'input[type="radio"][name="' + fieldId + '"]:checked' );
		if ( checkedRadio ) { return checkedRadio; }

		// Fall back to the first radio in the group
		return document.querySelector( 'input[type="radio"][name="' + fieldId + '"]' );
	}

	/**
	 * Resolve the registered trigger field id for an event target element.
	 *
	 * @param   {Element}  element  The event target element.
	 * @return  {string}            The registered trigger field id, or null.
	 */
	var resolveTriggerFieldId = function( element ) {
		// Bail if element is not valid
		if ( ! element ) { return null; }

		// Match by element id
		if ( element.id && _triggerFieldIds.includes( element.id ) ) {
			return element.id;
		}

		// Match radio groups by name
		if ( element.name && _triggerFieldIds.includes( element.name ) ) {
			return element.name;
		}

		return null;
	}



	/**
	 * Maybe process conditional fields related to a trigger element.
	 *
	 * @param   {Element}  triggerElement  The field that controls related conditional fields.
	 */
	var maybeProcessConditionalFields = function( triggerElement ) {
		// Bail if element is not valid
		if ( ! triggerElement ) { return; }

		// Get related conditional fields selector
		var triggerKey = getTriggerKey( triggerElement );
		var selector = _settings.conditionalFieldsForTriggerSelector.replace( '###ID###', triggerKey );

		// Get related conditional fields
		var relatedConditionalFields = document.querySelectorAll( selector );

		// Maybe show/hide related conditional fields
		// Loop through each related conditional field
		for ( var i = 0; i < relatedConditionalFields.length; i++ ) {
			// Get conditional field variables
			var conditionalField = relatedConditionalFields[ i ];
			var fieldValueCondition = conditionalField.getAttribute( _settings.conditionalFieldValueAttribute );
			var fieldValue = getFieldValue( triggerElement );

			// Get field row
			var fieldRow = conditionalField.closest( _settings.settingsRowSelector );
			var triggerFieldRow = triggerElement.closest( _settings.settingsRowSelector );

			// Skip if field row is not found
			if ( ! fieldRow ) { continue; }

			// Define visibility state
			// - Hide field if condition is not met
			// - Hide related conditional fields if the trigger field itself is hidden
			var isVisible = fieldValueCondition === fieldValue;
			if ( triggerFieldRow && triggerFieldRow.classList.contains( _settings.hiddenClass ) ) {
				isVisible = false;
			}

			// Maybe show/hide field row
			if ( isVisible ) {
				fieldRow.classList.remove( _settings.hiddenClass );
				fieldRow.style.display = ''; // Clear custom display style
			}
			else {
				fieldRow.classList.add( _settings.hiddenClass );
				fieldRow.style.display = 'none';
			}

			// Maybe process conditional fields for the conditional field,
			// this ensures that fields with nested conditions are displayed/hidden correctly.
			maybeProcessConditionalFields( conditionalField );
		}
	}



	/**
	 * Initialize the list of conditional field triggers.
	 */
	var initializeConditionals = function() {
		// Get conditional fields
		var conditionalFields = document.querySelectorAll( _settings.conditionalFieldsSelector );

		// Build list of conditional field trigger ids
		// Loop through each conditional field
		for ( var i = 0; i < conditionalFields.length; i++ ) {
			var conditionalField = conditionalFields[ i ];
			var fieldId = _settings.fieldIdPrefix + conditionalField.getAttribute( _settings.conditionalFieldKeyAttribute );
			var fieldValue = conditionalField.getAttribute( _settings.conditionalFieldValueAttribute );

			// Skip if condition field id or value is not set
			if ( ! fieldId || ! fieldValue ) { continue; }

			// Skip if field id is already added to conditionals
			if ( _triggerFieldIds.includes( fieldId ) ) { continue; }

			// Add to conditionals
			_triggerFieldIds.push( fieldId );
		}

		// Maybe process conditional fields
		// Loop through each conditional field trigger
		for ( var i = 0; i < _triggerFieldIds.length; i++ ) {
			var fieldId = _triggerFieldIds[ i ];
			var fieldElement = getTriggerFieldElement( fieldId );

			// Skip if field element is not found
			if ( ! fieldElement ) { continue; }

			// Process conditional fields
			maybeProcessConditionalFields( fieldElement );
		}
	}



	/**
	 * Handle click events.
	 *
	 * @param   {Event}  event  The click event.
	 */
	var handleClick = function( event ) {
		// CONDITIONAL FIELDS TRIGGER
		var triggerFieldId = resolveTriggerFieldId( event.target );
		if ( triggerFieldId ) {
			// Maybe process conditional fields
			maybeProcessConditionalFields( event.target );
		}
	}

	/**
	 * Handle change events.
	 *
	 * @param   {Event}  event  The change event.
	 */
	var handleChange = function( event ) {
		// CONDITIONAL FIELDS TRIGGER
		var triggerFieldId = resolveTriggerFieldId( event.target );
		if ( triggerFieldId ) {
			// Maybe process conditional fields
			maybeProcessConditionalFields( event.target );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 *
	 * @param   {Object}  options  Optional settings overrides (e.g. fieldIdPrefix).
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = extend( _settings, options );

		// Initialize conditionals
		initializeConditionals();

		// Event handlers
		window.addEventListener( 'click', handleClick, true );
		window.addEventListener( 'change', handleChange, true );

		// Set initialized flag
		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
