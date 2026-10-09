/**
 * Manage Fluid Checkout admin settings page behavior (conditionals, actions bar).
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

		settingsRowSelector:                   '.fc-settings-field, tr',
		layoutOptionsSelector:                 '.fc-settings-sectioned-buttons, .fc-settings-radio-options',
		layoutOptionSelector:                  '.fc-settings-sectioned-buttons__option, .fc-settings-radio-option',
		isSelectedClass:                       'is-selected',
		disabledFieldClass:                    'fc-settings-field--disabled',

		contentSelector:                       '.fc-settings-content',
		actionsSelector:                       '.fc-settings-actions',
		formSelector:                          '[data-fc-settings-form]',
		saveButtonSelector:                    '[data-fc-settings-save]',
		isBusyClass:                           'is-busy',
		isPinnedClass:                         'is-pinned',

		hiddenClass:                           'hidden',

		i18n: {
			saving:                            'Saving...',
		},
	};
	var _triggerFieldIds = [];
	var _actionsSyncScheduled = false;



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
	 * Prefers named form controls so values from other settings tabs are used
	 * (all tabs stay in the DOM during SPA navigation).
	 *
	 * @param   {string}   fieldId  The full field id including prefix.
	 * @return  {Element}           The trigger field element, or null.
	 */
	var getTriggerFieldElement = function( fieldId ) {
		var checkedRadio;
		var namedElement;

		// Prefer checked radio by name (layout / template selectors)
		checkedRadio = document.querySelector( 'input[type="radio"][name="' + fieldId + '"]:checked' );
		if ( checkedRadio ) { return checkedRadio; }

		// Fall back to the first radio in the group
		checkedRadio = document.querySelector( 'input[type="radio"][name="' + fieldId + '"]' );
		if ( checkedRadio ) { return checkedRadio; }

		// Prefer named inputs (checkboxes, selects, text) over id-only mirrors
		namedElement = document.querySelector( '[name="' + fieldId + '"]' );
		if ( namedElement ) { return namedElement; }

		// Fall back to element id
		return document.getElementById( fieldId );
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
	 * Get the visibility container for a conditional field.
	 *
	 * @param   {Element}  element  The conditional field element.
	 * @return  {Element}           The container element, or null.
	 */
	var getConditionalContainer = function( element ) {
		// Bail if element is not valid
		if ( ! element ) { return null; }

		// Prefer the nearest settings field row
		var settingsField = element.closest( '.fc-settings-field' );
		if ( settingsField ) { return settingsField; }

		// Fall back to a nested fieldset then a table row for WC embeds
		var fieldset = element.closest( 'fieldset' );
		if ( fieldset ) { return fieldset; }

		return element.closest( 'tr' );
	}

	/**
	 * Whether a container is currently hidden by conditional visibility.
	 *
	 * @param   {Element}  container  The container element.
	 * @return  {boolean}
	 */
	var isContainerHidden = function( container ) {
		// Bail if container is not valid
		if ( ! container ) { return false; }

		return container.classList.contains( _settings.hiddenClass ) || 'none' === container.style.display;
	}

	/**
	 * Whether a conditional field value matches the trigger value.
	 * Supports a single value or an OR list separated by commas or pipes
	 * (e.g. `optional,required` or `optional|required` when paired in an AND list).
	 *
	 * @param   {string}  conditionValue  Expected value(s) from data-conditional-value.
	 * @param   {string}  fieldValue      Current trigger field value.
	 * @return  {boolean}
	 */
	var doesConditionalValueMatch = function( conditionValue, fieldValue ) {
		var allowedValues;
		var i;

		// Bail if condition or field value is missing
		if ( null === conditionValue || undefined === conditionValue || null === fieldValue || undefined === fieldValue ) {
			return false;
		}

		// Exact match for a single value
		if ( conditionValue === fieldValue ) {
			return true;
		}

		// Otherwise match against a comma- or pipe-separated OR list
		allowedValues = String( conditionValue ).split( /[,|]/ );
		for ( i = 0; i < allowedValues.length; i++ ) {
			if ( allowedValues[ i ].trim() === fieldValue ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parse comma-separated trigger ids from a conditional field attribute.
	 *
	 * @param   {string}  conditionIds  Value of data-conditional-id.
	 * @return  {Array}                 Trimmed non-empty trigger keys.
	 */
	var parseConditionalIds = function( conditionIds ) {
		var parts;
		var result = [];
		var i;

		// Bail if condition ids are missing
		if ( null === conditionIds || undefined === conditionIds || '' === conditionIds ) {
			return result;
		}

		parts = String( conditionIds ).split( ',' );
		for ( i = 0; i < parts.length; i++ ) {
			var part = parts[ i ].trim();
			if ( part ) {
				result.push( part );
			}
		}

		return result;
	}

	/**
	 * Whether a conditional field lists the given trigger key in data-conditional-id.
	 *
	 * @param   {Element}  conditionalField  The conditional field element.
	 * @param   {string}   triggerKey        Trigger key without field id prefix.
	 * @return  {boolean}
	 */
	var conditionalFieldDependsOnTrigger = function( conditionalField, triggerKey ) {
		var conditionIds;
		var i;

		// Bail if arguments are not valid
		if ( ! conditionalField || ! triggerKey ) { return false; }

		conditionIds = parseConditionalIds( conditionalField.getAttribute( _settings.conditionalFieldKeyAttribute ) );
		for ( i = 0; i < conditionIds.length; i++ ) {
			if ( conditionIds[ i ] === triggerKey ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Evaluate whether all conditional id/value pairs currently match (AND).
	 * A single id with a comma-separated value list still uses OR matching for that value.
	 *
	 * @param   {Element}  conditionalField  The conditional field element.
	 * @return  {boolean}
	 */
	var doAllConditionalConditionsMatch = function( conditionalField ) {
		var conditionIds;
		var conditionValuesRaw;
		var conditionValues;
		var i;
		var triggerElement;
		var expectedValue;
		var triggerFieldRow;

		// Bail if element is not valid
		if ( ! conditionalField ) { return false; }

		conditionIds = parseConditionalIds( conditionalField.getAttribute( _settings.conditionalFieldKeyAttribute ) );
		conditionValuesRaw = conditionalField.getAttribute( _settings.conditionalFieldValueAttribute );

		// Bail if no trigger ids
		if ( ! conditionIds.length ) { return false; }

		// Single trigger: OR list in data-conditional-value (existing behavior)
		if ( 1 === conditionIds.length ) {
			triggerElement = getTriggerFieldElement( _settings.fieldIdPrefix + conditionIds[ 0 ] );
			if ( ! triggerElement ) { return false; }

			triggerFieldRow = getConditionalContainer( triggerElement );
			if ( triggerFieldRow && isContainerHidden( triggerFieldRow ) ) {
				return false;
			}

			return doesConditionalValueMatch( conditionValuesRaw, getFieldValue( triggerElement ) );
		}

		// Multiple triggers: pair each id with the value at the same index (AND)
		conditionValues = String( conditionValuesRaw || '' ).split( ',' );
		for ( i = 0; i < conditionIds.length; i++ ) {
			triggerElement = getTriggerFieldElement( _settings.fieldIdPrefix + conditionIds[ i ] );
			expectedValue = conditionValues[ i ] ? conditionValues[ i ].trim() : '';

			// Missing trigger or value mismatch hides the field
			if ( ! triggerElement || ! doesConditionalValueMatch( expectedValue, getFieldValue( triggerElement ) ) ) {
				return false;
			}

			triggerFieldRow = getConditionalContainer( triggerElement );
			if ( triggerFieldRow && isContainerHidden( triggerFieldRow ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the field row is a locked (disabled) settings field kept visible for discovery.
	 *
	 * @param   {Element}  fieldRow  The visibility container element.
	 * @return  {boolean}
	 */
	var isDisabledSettingsField = function( fieldRow ) {
		var settingsField;

		// Bail if field row is not valid
		if ( ! fieldRow ) { return false; }

		// Direct match on the settings field row
		if ( fieldRow.classList.contains( _settings.disabledFieldClass ) ) {
			return true;
		}

		// Nested fieldsets still belong to a locked settings field row
		settingsField = fieldRow.closest( '.fc-settings-field' );
		return !!( settingsField && settingsField.classList.contains( _settings.disabledFieldClass ) );
	}



	/**
	 * Maybe process conditional fields related to a trigger element.
	 *
	 * @param   {Element}  triggerElement  The field that controls related conditional fields.
	 */
	var maybeProcessConditionalFields = function( triggerElement ) {
		var triggerKey;
		var allConditionalFields;
		var i;
		var conditionalField;
		var fieldRow;
		var isVisible;

		// Bail if element is not valid
		if ( ! triggerElement ) { return; }

		triggerKey = getTriggerKey( triggerElement );

		// Bail if trigger key is missing
		if ( ! triggerKey ) { return; }

		// Scan all conditionals; attribute may list multiple trigger ids (AND)
		allConditionalFields = document.querySelectorAll( _settings.conditionalFieldsSelector );

		// Maybe show/hide related conditional fields
		for ( i = 0; i < allConditionalFields.length; i++ ) {
			conditionalField = allConditionalFields[ i ];

			// Skip fields that do not depend on this trigger
			if ( ! conditionalFieldDependsOnTrigger( conditionalField, triggerKey ) ) { continue; }

			fieldRow = getConditionalContainer( conditionalField );

			// Skip if field row is not found
			if ( ! fieldRow ) { continue; }

			// Define visibility state
			// - Keep locked fields visible for discovery
			// - Hide field if any paired condition is not met
			isVisible = true;
			if ( ! isDisabledSettingsField( fieldRow ) ) {
				isVisible = doAllConditionalConditionsMatch( conditionalField );
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
		var conditionalFields;
		var i;
		var j;
		var conditionalField;
		var conditionIds;
		var fieldValue;
		var fieldId;
		var fieldElement;

		// Get conditional fields
		conditionalFields = document.querySelectorAll( _settings.conditionalFieldsSelector );

		// Build list of conditional field trigger ids
		for ( i = 0; i < conditionalFields.length; i++ ) {
			conditionalField = conditionalFields[ i ];
			conditionIds = parseConditionalIds( conditionalField.getAttribute( _settings.conditionalFieldKeyAttribute ) );
			fieldValue = conditionalField.getAttribute( _settings.conditionalFieldValueAttribute );

			// Skip if condition field id or value is not set
			if ( ! conditionIds.length || ! fieldValue ) { continue; }

			// Register each trigger id from an AND list
			for ( j = 0; j < conditionIds.length; j++ ) {
				fieldId = _settings.fieldIdPrefix + conditionIds[ j ];

				// Skip if field id is already added to conditionals
				if ( _triggerFieldIds.includes( fieldId ) ) { continue; }

				_triggerFieldIds.push( fieldId );
			}
		}

		// Maybe process conditional fields
		for ( i = 0; i < _triggerFieldIds.length; i++ ) {
			fieldId = _triggerFieldIds[ i ];
			fieldElement = getTriggerFieldElement( fieldId );

			// Skip if field element is not found
			if ( ! fieldElement ) { continue; }

			// Process conditional fields
			maybeProcessConditionalFields( fieldElement );
		}
	}



	/**
	 * Pin the actions bar to the viewport bottom, matching the center column width.
	 * Needed when a tall WP admin menu forces page scroll past the settings layout.
	 */
	var syncActionsBarPosition = function() {
		var actions = document.querySelector( _settings.actionsSelector );
		var content = document.querySelector( _settings.contentSelector );
		var rect;

		// Bail if the actions bar is missing or hidden
		if ( ! actions || actions.hasAttribute( 'hidden' ) ) { return; }

		// Bail if the content column is missing
		if ( ! content ) { return; }

		rect = content.getBoundingClientRect();

		// Bail if layout metrics are not ready yet
		if ( rect.width <= 0 ) { return; }

		actions.style.left = Math.round( rect.left ) + 'px';
		actions.style.width = Math.round( rect.width ) + 'px';
		actions.style.right = 'auto';
		actions.classList.add( _settings.isPinnedClass );
	};

	/**
	 * Schedule a single actions-bar position sync on the next animation frame.
	 */
	var scheduleActionsBarSync = function() {
		// Bail if a sync is already queued
		if ( _actionsSyncScheduled ) { return; }

		_actionsSyncScheduled = true;

		window.requestAnimationFrame( function() {
			_actionsSyncScheduled = false;
			syncActionsBarPosition();
		} );
	};



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
	 * Sync selected class on layout / template option buttons.
	 *
	 * @param  {Element}  input  Changed radio input.
	 */
	var syncLayoutOptionSelectedState = function( input ) {
		var group;
		var options;
		var i;

		// Bail if input is not a radio inside a layout options group
		if ( ! input || 'radio' !== input.type ) { return; }

		group = input.closest( _settings.layoutOptionsSelector );

		// Bail if not inside a layout options group
		if ( ! group ) { return; }

		options = group.querySelectorAll( _settings.layoutOptionSelector );

		// Iterate options and mark the checked one as selected
		for ( i = 0; i < options.length; i++ ) {
			options[ i ].classList.toggle( _settings.isSelectedClass, options[ i ].contains( input ) && input.checked );
		}
	};

	/**
	 * Handle change events.
	 *
	 * @param   {Event}  event  The change event.
	 */
	var handleChange = function( event ) {
		// LAYOUT OPTION SELECTED STATE
		syncLayoutOptionSelectedState( event.target );

		// CONDITIONAL FIELDS TRIGGER
		var triggerFieldId = resolveTriggerFieldId( event.target );
		if ( triggerFieldId ) {
			// Maybe process conditional fields
			maybeProcessConditionalFields( event.target );
		}
	}



	/**
	 * Get the saving label from settings.
	 *
	 * @return  {string}  Saving label.
	 */
	var getSavingLabel = function() {
		return _settings.i18n && _settings.i18n.saving ? _settings.i18n.saving : 'Saving...';
	};

	/**
	 * Show the saving state on all settings save buttons.
	 * Locks each button to its current width so the label change does not shrink it.
	 */
	var setSaveButtonsSavingState = function() {
		var buttons = document.querySelectorAll( _settings.saveButtonSelector );
		var i;
		var button;
		var width;

		// Iterate save buttons
		for ( i = 0; i < buttons.length; i++ ) {
			button = buttons[ i ];

			// Bail if already in the saving state
			if ( button.classList.contains( _settings.isBusyClass ) ) { continue; }

			// Lock width to the idle label size before changing the text
			width = Math.ceil( button.getBoundingClientRect().width );
			if ( width > 0 ) {
				button.style.minWidth = width + 'px';
			}

			button.textContent = getSavingLabel();
			button.classList.add( _settings.isBusyClass );
		}

		// Disable after the submit has started so browsers do not cancel the POST
		window.setTimeout( function() {
			var saveButtons = document.querySelectorAll( _settings.saveButtonSelector );
			var j;

			// Iterate save buttons
			for ( j = 0; j < saveButtons.length; j++ ) {
				saveButtons[ j ].disabled = true;
			}
		}, 0 );
	};

	/**
	 * Handle settings form submit events.
	 *
	 * @param   {Event}  event  The submit event.
	 */
	var handleSubmit = function( event ) {
		// Bail if not the settings form
		if ( ! event.target || ! event.target.matches || ! event.target.matches( _settings.formSelector ) ) { return; }

		// Show saving state on save buttons
		setSaveButtonsSavingState();
	};



	/**
	 * Initialize component and set related handlers.
	 *
	 * @param   {Object}  options  Optional settings overrides (e.g. fieldIdPrefix).
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = extend( true, _settings, options );

		// Initialize conditionals
		initializeConditionals();

		// Pin the actions bar before the first paint settles
		syncActionsBarPosition();

		// Event handlers
		window.addEventListener( 'click', handleClick, true );
		window.addEventListener( 'change', handleChange, true );
		window.addEventListener( 'submit', handleSubmit, true );
		// Capture scroll: tall WP admin menu scroll must keep the bar on the viewport
		window.addEventListener( 'scroll', scheduleActionsBarSync, true );
		window.addEventListener( 'resize', scheduleActionsBarSync );

		// Set initialized flag
		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
