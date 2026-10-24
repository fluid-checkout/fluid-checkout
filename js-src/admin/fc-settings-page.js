/**
 * Manage Fluid Checkout admin settings page behavior (conditionals, actions bar).
 *
 * DEPENDS ON:
 * - FCUtils // Settings merge
 * - FCAdminSettingsSetupChecklist // Optional. Syncs getting-started checklist completion state after save.
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

		settingsFieldSelector:                 '.fc-settings-field',
		settingsRowSelector:                   '.fc-settings-field, tr',
		layoutOptionsSelector:                 '.fc-settings-sectioned-buttons, .fc-settings-radio-options',
		layoutOptionSelector:                  '.fc-settings-sectioned-buttons__option, .fc-settings-radio-option',
		isSelectedClass:                       'is-selected',
		disabledFieldClass:                    'fc-settings-field--disabled',

		contentSelector:                       '.fc-settings-content',
		actionsSelector:                       '.fc-settings-actions',
		formSelector:                          '[data-fc-settings-form]',
		saveButtonSelector:                    '[data-fc-settings-save]',
		noticesSelector:                       '[data-fc-settings-notices]',
		isBusyClass:                           'is-busy',
		isPinnedClass:                         'is-pinned',
		noticeVisibleClass:                    'is-visible',
		noticeHidingClass:                     'is-hiding',

		hiddenClass:                           'hidden',

		syncedSelectAttribute:                 'data-fc-settings-sync-group',

		ajaxUrl:                               '',
		saveAction:                            'fc_settings_save',
		noticeDismissDelayMs:                  5000,
		noticeHideTransitionMs:                300,

		i18n: {
			saving:                            'Saving...',
			save:                              'Save settings',
			saved:                             'Your settings have been saved.',
			saveError:                         'Settings could not be saved. Please try again.',
			dismissNotice:                     'Dismiss notice', // aria-label on the floating header notice dismiss button
		},
	};
	var _triggerFieldIds = [];
	var _actionsSyncScheduled = false;
	var _isSavingSettings = false;
	var _settingsNoticeTimeout = null;
	var _modifierHoldTimer = null;
	var _isShowingShortcuts = false;
	var _shortcutLabels = {
		save:           '',
		'expand-preview': '',
	};



	/**
	 * METHODS
	 */

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
	};



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
	};



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
	};



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
	};



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
		var settingsField = element.closest( _settings.settingsFieldSelector );
		if ( settingsField ) { return settingsField; }

		// Fall back to a nested fieldset then a table row for WC embeds
		var fieldset = element.closest( 'fieldset' );
		if ( fieldset ) { return fieldset; }

		return element.closest( 'tr' );
	};



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
	};



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
	};



	/**
	 * Parse trigger ids from a conditional field attribute.
	 * Pipe-separated ids use OR matching; comma-separated ids use AND matching.
	 *
	 * @param   {string}  conditionIds  Value of data-conditional-id.
	 * @return  {Array}                 Trimmed non-empty trigger keys.
	 */
	var parseConditionalIds = function( conditionIds ) {
		var parts;
		var result = [];
		var i;
		var conditionIdsString;

		// Bail if condition ids are missing
		if ( null === conditionIds || undefined === conditionIds || '' === conditionIds ) {
			return result;
		}

		conditionIdsString = String( conditionIds );
		parts = conditionIdsString.indexOf( '|' ) !== -1 ? conditionIdsString.split( '|' ) : conditionIdsString.split( ',' );
		for ( i = 0; i < parts.length; i++ ) {
			var part = parts[ i ].trim();
			if ( part ) {
				result.push( part );
			}
		}

		return result;
	};



	/**
	 * Whether conditional ids in data-conditional-id should match with OR logic.
	 *
	 * @param   {string}  conditionIds  Value of data-conditional-id.
	 * @return  {boolean}
	 */
	var isConditionalOrMatch = function( conditionIds ) {
		return null !== conditionIds && undefined !== conditionIds && String( conditionIds ).indexOf( '|' ) !== -1;
	};



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
	};



	/**
	 * Evaluate whether any pipe-separated conditional id/value pair matches (OR).
	 *
	 * @param   {Element}  conditionalField  The conditional field element.
	 * @return  {boolean}
	 */
	var doAnyConditionalConditionsMatch = function( conditionalField ) {
		var conditionIds;
		var conditionValuesRaw;
		var i;
		var triggerElement;
		var triggerFieldRow;

		// Bail if element is not valid
		if ( ! conditionalField ) { return false; }

		conditionIds = parseConditionalIds( conditionalField.getAttribute( _settings.conditionalFieldKeyAttribute ) );
		conditionValuesRaw = conditionalField.getAttribute( _settings.conditionalFieldValueAttribute );

		// Bail if no trigger ids
		if ( ! conditionIds.length ) { return false; }

		for ( i = 0; i < conditionIds.length; i++ ) {
			triggerElement = getTriggerFieldElement( _settings.fieldIdPrefix + conditionIds[ i ] );

			// Skip if trigger is missing
			if ( ! triggerElement ) { continue; }

			triggerFieldRow = getConditionalContainer( triggerElement );
			if ( triggerFieldRow && isContainerHidden( triggerFieldRow ) ) {
				continue;
			}

			if ( doesConditionalValueMatch( conditionValuesRaw, getFieldValue( triggerElement ) ) ) {
				return true;
			}
		}

		return false;
	};



	/**
	 * Evaluate whether all conditional id/value pairs currently match (AND).
	 * A single id with a comma-separated value list still uses OR matching for that value.
	 * Pipe-separated ids in data-conditional-id use OR matching across triggers.
	 *
	 * @param   {Element}  conditionalField  The conditional field element.
	 * @return  {boolean}
	 */
	var doAllConditionalConditionsMatch = function( conditionalField ) {
		var conditionIdsRaw;
		var conditionIds;
		var conditionValuesRaw;
		var conditionValues;
		var i;
		var triggerElement;
		var expectedValue;
		var triggerFieldRow;

		// Bail if element is not valid
		if ( ! conditionalField ) { return false; }

		conditionIdsRaw = conditionalField.getAttribute( _settings.conditionalFieldKeyAttribute );
		conditionIds = parseConditionalIds( conditionIdsRaw );
		conditionValuesRaw = conditionalField.getAttribute( _settings.conditionalFieldValueAttribute );

		// Bail if no trigger ids
		if ( ! conditionIds.length ) { return false; }

		// Pipe-separated trigger ids: any matching value shows the field
		if ( isConditionalOrMatch( conditionIdsRaw ) ) {
			return doAnyConditionalConditionsMatch( conditionalField );
		}

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
	};



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
		settingsField = fieldRow.closest( _settings.settingsFieldSelector );
		return !!( settingsField && settingsField.classList.contains( _settings.disabledFieldClass ) );
	};



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
	};



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
	};



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
		var triggerFieldId;

		// CONDITIONAL FIELDS TRIGGER
		triggerFieldId = resolveTriggerFieldId( event.target );
		if ( triggerFieldId ) {
			maybeProcessConditionalFields( event.target );
		}
	};

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
	 * Get the sync group key for a settings control.
	 *
	 * @param   {Element}  element  The form control.
	 * @return  {string}            Group key, or an empty string.
	 */
	var getSyncedSelectGroup = function( element ) {
		// Bail if element is not valid
		if ( ! element || ! element.getAttribute ) { return ''; }

		return element.getAttribute( _settings.syncedSelectAttribute ) || '';
	};

	/**
	 * Get every control in a sync group, including controls inside tabs hidden on load.
	 * Inactive settings tabs stay in the DOM, so a hidden copy can still be updated.
	 *
	 * @param   {string}  group  Sync group key.
	 * @return  {Array}          Matching elements.
	 */
	var getSyncedSelects = function( group ) {
		var all;
		var matches = [];
		var i;

		// Bail if group is missing
		if ( ! group ) { return matches; }

		all = document.querySelectorAll( '[' + _settings.syncedSelectAttribute + ']' );

		// Keep controls that belong to this group
		for ( i = 0; i < all.length; i++ ) {
			if ( group === all[ i ].getAttribute( _settings.syncedSelectAttribute ) ) {
				matches.push( all[ i ] );
			}
		}

		return matches;
	};

	/**
	 * Copy a select value to the other controls in its sync group.
	 *
	 * @param   {Element}  source  The select the merchant changed, or the submitted select on load.
	 */
	var syncMirroredSelectValue = function( source ) {
		var group;
		var mirrors;
		var i;

		// Bail if the control is not a select
		if ( ! source || ! source.tagName || 'select' !== source.tagName.toLowerCase() ) { return; }

		group = getSyncedSelectGroup( source );

		// Bail if this select is not in a sync group
		if ( ! group ) { return; }

		mirrors = getSyncedSelects( group );

		// Update the other copies without dispatching change, so this does not loop
		for ( i = 0; i < mirrors.length; i++ ) {
			if ( mirrors[ i ] === source ) { continue; }
			if ( mirrors[ i ].value === source.value ) { continue; }

			mirrors[ i ].value = source.value;
		}
	};

	/**
	 * Align each sync group with the select that is submitted.
	 * Display-only copies have no name, so the named Checkout field is the source on load.
	 */
	var syncMirroredSelectsOnLoad = function() {
		var selects;
		var seen = {};
		var i;
		var j;
		var group;
		var mirrors;
		var source;

		selects = document.querySelectorAll( 'select[' + _settings.syncedSelectAttribute + ']' );

		// Iterate groups once
		for ( i = 0; i < selects.length; i++ ) {
			group = getSyncedSelectGroup( selects[ i ] );

			// Skip groups already synced
			if ( ! group || seen[ group ] ) { continue; }

			seen[ group ] = true;
			mirrors = getSyncedSelects( group );
			source = null;

			// Prefer the select that posts with the form
			for ( j = 0; j < mirrors.length; j++ ) {
				if ( mirrors[ j ].name ) {
					source = mirrors[ j ];
					break;
				}
			}

			// Fall back to the first control in the group
			if ( ! source && mirrors.length ) {
				source = mirrors[ 0 ];
			}

			if ( source ) {
				syncMirroredSelectValue( source );
			}
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

		// SYNCED SELECTS
		syncMirroredSelectValue( event.target );

		// CONDITIONAL FIELDS TRIGGER
		var triggerFieldId = resolveTriggerFieldId( event.target );
		if ( triggerFieldId ) {
			maybeProcessConditionalFields( event.target );
		}
	};



	/**
	 * Get the saving label from settings.
	 *
	 * @return  {string}  Saving label.
	 */
	var getSavingLabel = function() {
		return _settings.i18n && _settings.i18n.saving ? _settings.i18n.saving : 'Saving...';
	};

	/**
	 * Get the idle save button label from settings.
	 *
	 * @return  {string}  Save button label.
	 */
	var getSaveLabel = function() {
		return _settings.i18n && _settings.i18n.save ? _settings.i18n.save : 'Save settings';
	};

	/**
	 * Set the visible label on a save button without removing the shortcut hint.
	 *
	 * @param  {Element}  button  Save button.
	 * @param  {string}   label   Button label text.
	 */
	var setSaveButtonLabel = function( button, label ) {
		var shortcutLabel;
		var child;
		var i;

		// Update the first text node so the shortcut hint element stays in the button
		for ( i = 0; i < button.childNodes.length; i++ ) {
			child = button.childNodes[ i ];

			if ( Node.TEXT_NODE === child.nodeType ) {
				child.textContent = label;
				return;
			}
		}

		shortcutLabel = button.querySelector( '[data-fc-settings-shortcut-label]' );

		// Maybe insert the label before the shortcut hint when the text node is missing
		if ( shortcutLabel ) {
			button.insertBefore( document.createTextNode( label ), shortcutLabel );
			return;
		}

		button.textContent = label;
	};



	/**
	 * Get the first save button that can submit the settings form.
	 *
	 * @return  {Element|null}  Save button, or null when saving is unavailable.
	 */
	var getActiveSaveButton = function() {
		var buttons = document.querySelectorAll( _settings.saveButtonSelector );
		var i;
		var button;

		for ( i = 0; i < buttons.length; i++ ) {
			button = buttons[ i ];

			if ( button.disabled || button.classList.contains( _settings.isBusyClass ) ) { continue; }

			return button;
		}

		return null;
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

			setSaveButtonLabel( button, getSavingLabel() );
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
	 * Restore save buttons after an AJAX save finishes.
	 */
	var resetSaveButtonsState = function() {
		var buttons = document.querySelectorAll( _settings.saveButtonSelector );
		var i;
		var button;

		for ( i = 0; i < buttons.length; i++ ) {
			button = buttons[ i ];
			button.classList.remove( _settings.isBusyClass );
			button.disabled = false;
			button.style.minWidth = '';
			setSaveButtonLabel( button, getSaveLabel() );
		}
	};

	/**
	 * Get the active settings tab slug from the DOM.
	 *
	 * @return  {string}  Tab slug, or empty string when none is active.
	 */
	var getActiveSettingsTabSlug = function() {
		var activeTab = document.querySelector( '.fc-settings-tab.is-active' );

		if ( ! activeTab ) { return ''; }

		return activeTab.getAttribute( 'data-fc-settings-tab' ) || '';
	};

	/**
	 * Dismiss a floating save notice in the settings page header.
	 *
	 * @param   {Element}  notice  Notice element to dismiss.
	 * @param   {Element}  host    Notice container element.
	 */
	var dismissSettingsSaveNotice = function( notice, host ) {
		var hideTransitionMs;

		if ( ! notice || ! host || ! host.contains( notice ) ) { return; }

		if ( _settingsNoticeTimeout ) {
			window.clearTimeout( _settingsNoticeTimeout );
			_settingsNoticeTimeout = null;
		}

		hideTransitionMs = _settings.noticeHideTransitionMs;

		notice.classList.remove( _settings.noticeVisibleClass );
		notice.classList.add( _settings.noticeHidingClass );

		window.setTimeout( function() {
			if ( host.contains( notice ) ) {
				host.removeChild( notice );
			}
		}, hideTransitionMs );
	};

	/**
	 * Show a floating save notice in the settings page header.
	 *
	 * @param   {string}  type     Notice type: success or error.
	 * @param   {string}  message  Notice message.
	 */
	var showSettingsSaveNotice = function( type, message ) {
		var host = document.querySelector( _settings.noticesSelector );
		var notice;
		var noticeMessage;
		var dismissButton;
		var dismissLabel;
		var dismissDelayMs;

		if ( ! host || ! message ) { return; }

		if ( _settingsNoticeTimeout ) {
			window.clearTimeout( _settingsNoticeTimeout );
			_settingsNoticeTimeout = null;
		}

		host.textContent = '';
		notice = document.createElement( 'div' );
		notice.className = 'fc-header__notice fc-header__notice--' + type;
		notice.setAttribute( 'role', 'error' === type ? 'alert' : 'status' );

		noticeMessage = document.createElement( 'span' );
		noticeMessage.className = 'fc-header__notice-message';
		noticeMessage.textContent = message;
		notice.appendChild( noticeMessage );

		dismissLabel = _settings.i18n && _settings.i18n.dismissNotice ? _settings.i18n.dismissNotice : 'Dismiss notice';
		dismissButton = document.createElement( 'button' );
		dismissButton.type = 'button';
		dismissButton.className = 'fc-header__notice-dismiss';
		dismissButton.setAttribute( 'aria-label', dismissLabel );
		dismissButton.innerHTML = '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>';
		dismissButton.addEventListener( 'click', function() {
			dismissSettingsSaveNotice( notice, host );
		} );
		notice.appendChild( dismissButton );

		host.appendChild( notice );

		window.requestAnimationFrame( function() {
			notice.classList.add( _settings.noticeVisibleClass );
		} );

		dismissDelayMs = _settings.noticeDismissDelayMs;

		_settingsNoticeTimeout = window.setTimeout( function() {
			dismissSettingsSaveNotice( notice, host );
		}, dismissDelayMs );
	};

	/**
	 * Save settings through admin AJAX.
	 */
	var saveSettingsViaAjax = function() {
		var form;
		var formData;
		var activeTab;
		var saveErrorMessage;
		var savedMessage;

		if ( _isSavingSettings ) { return; }

		form = document.querySelector( _settings.formSelector );

		// Bail if AJAX save is unavailable
		if ( ! form || ! _settings.ajaxUrl ) { return; }

		_isSavingSettings = true;
		setSaveButtonsSavingState();

		formData = new FormData( form );
		formData.append( 'action', _settings.saveAction );

		activeTab = getActiveSettingsTabSlug();
		if ( activeTab ) {
			formData.set( 'fc_settings_current_tab', activeTab );
		}

		saveErrorMessage = _settings.i18n && _settings.i18n.saveError ? _settings.i18n.saveError : 'Settings could not be saved. Please try again.';
		savedMessage = _settings.i18n && _settings.i18n.saved ? _settings.i18n.saved : 'Your settings have been saved.';

		window.fetch( _settings.ajaxUrl, {
			method:      'POST',
			body:        formData,
			credentials: 'same-origin',
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( response ) {
				var message;

				if ( response && response.success ) {
					message = response.data && response.data.message ? response.data.message : savedMessage;
					showSettingsSaveNotice( 'success', message );

					// Maybe refresh getting-started checklist completion state
					if ( typeof FCAdminSettingsSetupChecklist !== 'undefined' && typeof FCAdminSettingsSetupChecklist.applyCompletionState === 'function' && response.data && response.data.checklist_state ) {
						FCAdminSettingsSetupChecklist.applyCompletionState( response.data.checklist_state );
					}

					return;
				}

				message = response && response.data && response.data.message ? response.data.message : saveErrorMessage;
				showSettingsSaveNotice( 'error', message );
			} )
			.catch( function() {
				showSettingsSaveNotice( 'error', saveErrorMessage );
			} )
			.finally( function() {
				_isSavingSettings = false;
				resetSaveButtonsState();
			} );
	};

	/**
	 * Handle settings form submit events.
	 *
	 * @param   {Event}  event  The submit event.
	 */
	var handleSubmit = function( event ) {
		// Bail if not the settings form
		if ( ! event.target || ! event.target.matches || ! event.target.matches( _settings.formSelector ) ) { return; }

		event.preventDefault();
		saveSettingsViaAjax();
	};



	/**
	 * Whether the current platform should use Mac-style shortcut labels.
	 *
	 * @return  {boolean}
	 */
	var isMacPlatform = function() {
		var platform;

		if ( navigator.userAgentData && navigator.userAgentData.platform ) {
			platform = navigator.userAgentData.platform;
		}
		else {
			platform = navigator.platform || '';
		}

		return -1 !== platform.toUpperCase().indexOf( 'MAC' );
	};

	/**
	 * Build platform-specific shortcut labels and fill them into the markup.
	 */
	var populateShortcutLabels = function() {
		var labels;
		var i;
		var shortcutKey;
		var labelElement;

		if ( isMacPlatform() ) {
			_shortcutLabels.save = '⌘S';
			_shortcutLabels[ 'expand-preview' ] = '⌘⇧P';
		}
		else {
			_shortcutLabels.save = 'Ctrl+S';
			_shortcutLabels[ 'expand-preview' ] = 'Ctrl+Shift+P';
		}

		labels = document.querySelectorAll( '[data-fc-settings-shortcut]' );

		for ( i = 0; i < labels.length; i++ ) {
			shortcutKey = labels[ i ].getAttribute( 'data-fc-settings-shortcut' );
			labelElement = labels[ i ].querySelector( '[data-fc-settings-shortcut-label]' );

			// Skip when the shortcut key or label element is missing
			if ( ! shortcutKey || ! labelElement || ! _shortcutLabels[ shortcutKey ] ) { continue; }

			labelElement.textContent = _shortcutLabels[ shortcutKey ];
		}
	};

	/**
	 * Show keyboard shortcut hints on marked buttons.
	 */
	var showShortcutHints = function() {
		document.body.classList.add( 'is-showing-settings-shortcuts' );
		_isShowingShortcuts = true;
		_modifierHoldTimer = null;
	};

	/**
	 * Hide keyboard shortcut hints.
	 */
	var hideShortcutHints = function() {
		document.body.classList.remove( 'is-showing-settings-shortcuts' );
		_isShowingShortcuts = false;
	};

	/**
	 * Clear the modifier-key hold timer used to reveal shortcuts.
	 */
	var clearModifierHoldTimer = function() {
		if ( ! _modifierHoldTimer ) { return; }

		window.clearTimeout( _modifierHoldTimer );
		_modifierHoldTimer = null;
	};

	/**
	 * Whether the event key is Cmd or Ctrl.
	 *
	 * @param   {KeyboardEvent}  event  Keyboard event.
	 * @return  {boolean}
	 */
	var isShortcutModifierKey = function( event ) {
		return 'Meta' === event.key || 'Control' === event.key || 'MetaLeft' === event.code || 'MetaRight' === event.code || 'ControlLeft' === event.code || 'ControlRight' === event.code;
	};

	/**
	 * Start revealing shortcuts after Cmd or Ctrl is held.
	 */
	var startModifierHoldTimer = function() {
		// Bail if shortcuts are already visible or the timer is running
		if ( _isShowingShortcuts || _modifierHoldTimer ) { return; }

		_modifierHoldTimer = window.setTimeout( showShortcutHints, 1000 );
	};

	/**
	 * Stop the hold timer and hide shortcut hints.
	 */
	var endModifierHold = function() {
		clearModifierHoldTimer();
		hideShortcutHints();
	};

	/**
	 * Start revealing shortcuts after Cmd or Ctrl is held.
	 *
	 * @param   {KeyboardEvent}  event  Keyboard event.
	 */
	var handleModifierHoldKeydown = function( event ) {
		// Bail if this is not Cmd or Ctrl
		if ( ! isShortcutModifierKey( event ) ) { return; }

		// Bail on key repeat while the key is already held
		if ( event.repeat ) { return; }

		startModifierHoldTimer();
	};

	/**
	 * Hide shortcuts when Cmd or Ctrl is released.
	 *
	 * @param   {KeyboardEvent}  event  Keyboard event.
	 */
	var handleModifierHoldKeyup = function( event ) {
		// Bail if this is not Cmd or Ctrl
		if ( ! isShortcutModifierKey( event ) ) { return; }

		// Bail while the other modifier is still held
		if ( event.metaKey || event.ctrlKey ) { return; }

		endModifierHold();
	};

	/**
	 * Hide shortcuts when the window loses focus, unless focus moved into the preview iframe.
	 */
	var handleModifierHoldBlur = function() {
		// Wait for focus to settle so the preview iframe is activeElement when clicked
		window.setTimeout( function() {
			var frame = document.querySelector( '[data-fc-settings-preview-frame]' );

			// Keep the hold state when focus moved into the preview iframe
			if ( frame && document.activeElement === frame ) { return; }

			endModifierHold();
		}, 0 );
	};



	/**
	 * Submit the settings form with the active save button, when available.
	 */
	var maybeSubmitSaveSettings = function() {
		var form;
		var saveButton;

		form = document.querySelector( _settings.formSelector );

		// Bail if the settings form is not on the page
		if ( ! form ) { return; }

		// Bail if a save request is already in progress
		if ( _isSavingSettings ) { return; }

		saveButton = getActiveSaveButton();

		// Bail if an enabled save button is missing
		if ( ! saveButton ) { return; }

		if ( typeof form.requestSubmit === 'function' ) {
			form.requestSubmit( saveButton );
		}
		else {
			saveButton.click();
		}
	};

	/**
	 * Save settings when pressing Ctrl+S or Cmd+S.
	 *
	 * @param   {Event}  event  Keyboard event.
	 */
	var handleKeydown = function( event ) {
		var form;
		var isSaveShortcut;
		var target;
		var inputType;

		// Bail if FCUtils is not available
		if ( ! window.FCUtils ) { return; }

		// Prevent implicit form submit when pressing Enter in settings fields
		if ( FCUtils.keyboardKeys.ENTER === event.key ) {
			form = document.querySelector( _settings.formSelector );
			target = event.target;

			if ( form && target && form.contains( target ) && 'TEXTAREA' !== target.tagName ) {
				if ( target.closest && target.closest( '.iris-picker' ) ) {
					event.preventDefault();
				}
				else if ( 'SELECT' === target.tagName ) {
					event.preventDefault();
				}
				else if ( 'INPUT' === target.tagName ) {
					inputType = ( target.type || 'text' ).toLowerCase();

					if ( -1 !== [ 'text', 'search', 'url', 'tel', 'email', 'password', 'number', 'color' ].indexOf( inputType ) ) {
						event.preventDefault();
					}
				}
			}
		}

		isSaveShortcut = ( event.metaKey || event.ctrlKey ) && ! event.altKey && ! event.shiftKey && FCUtils.keyboardKeys.S === event.key.toLowerCase();

		// Bail if the save shortcut was not pressed
		if ( ! isSaveShortcut ) { return; }

		// Always block the browser save-page shortcut on the settings page
		// (including while a save request is already in progress and save buttons are disabled)
		event.preventDefault();

		maybeSubmitSaveSettings();
	};

	/**
	 * Handle admin shortcuts forwarded from the page preview iframe.
	 *
	 * @param  {CustomEvent}  event  Shortcut event from the preview script.
	 */
	var handleAdminShortcut = function( event ) {
		var shortcut = event.detail ? event.detail.shortcut : '';

		// SAVE
		if ( 'save' === shortcut ) {
			maybeSubmitSaveSettings();
		}
		// MODIFIER HOLD START
		else if ( 'modifierHoldStart' === shortcut ) {
			startModifierHoldTimer();
		}
		// MODIFIER HOLD END
		else if ( 'modifierHoldEnd' === shortcut ) {
			endModifierHold();
		}
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
		_settings = FCUtils.extendObject( _settings, options );

		// Initialize conditionals
		initializeConditionals();

		// Align shared selects, including copies inside tabs that are hidden on load
		syncMirroredSelectsOnLoad();

		// Pin the actions bar before the first paint settles
		syncActionsBarPosition();

		// Fill platform-specific shortcut labels into marked buttons
		populateShortcutLabels();

		// Event handlers
		window.addEventListener( 'click', handleClick, true );
		window.addEventListener( 'change', handleChange, true );
		window.addEventListener( 'submit', handleSubmit, true );
		document.addEventListener( 'keydown', handleKeydown, true );
		document.addEventListener( 'keydown', handleModifierHoldKeydown, true );
		document.addEventListener( 'keyup', handleModifierHoldKeyup, true );
		document.addEventListener( 'fc-settings-admin-shortcut', handleAdminShortcut );
		window.addEventListener( 'blur', handleModifierHoldBlur );
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
