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
	 * Get the visibility container for a conditional field.
	 * Walks up to the nearest settings field row, including nested fieldsets inside fc_checkboxgroup.
	 *
	 * @param   {Element}  element  The conditional field element.
	 * @return  {Element}           The container element, or null.
	 */
	var getConditionalContainer = function( element ) {
		// Bail if element is not valid
		if ( ! element ) { return null; }

		// Prefer the nearest settings field row
		var settingsField = element.closest( '.fc-settings-field' );
		if ( settingsField ) {
			var control = settingsField.querySelector( '.fc-settings-field__control' );

			// Nested checkboxgroup children share one settings field row with multiple fieldsets
			if ( control ) {
				var fieldsets = [];
				for ( var i = 0; i < control.children.length; i++ ) {
					if ( 'FIELDSET' === control.children[ i ].tagName ) {
						fieldsets.push( control.children[ i ] );
					}
				}

				// Hide only the nested fieldset when multiple fields share the row
				if ( fieldsets.length > 1 ) {
					var nestedFieldset = element.closest( 'fieldset' );
					if ( nestedFieldset && control.contains( nestedFieldset ) ) {
						return nestedFieldset;
					}
				}
			}

			return settingsField;
		}

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

			// Get field containers
			var fieldRow = getConditionalContainer( conditionalField );
			var triggerFieldRow = getConditionalContainer( triggerElement );

			// Skip if field row is not found
			if ( ! fieldRow ) { continue; }

			// Define visibility state
			// - Hide field if condition is not met
			// - Hide related conditional fields if the trigger field itself is hidden
			var isVisible = fieldValueCondition === fieldValue;
			if ( triggerFieldRow && isContainerHidden( triggerFieldRow ) ) {
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
