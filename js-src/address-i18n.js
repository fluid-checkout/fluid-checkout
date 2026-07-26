/*global wc_address_i18n_params */
jQuery( function( $ ) {

	// wc_address_i18n_params is required to continue, ensure the object exists
	if ( typeof wc_address_i18n_params === 'undefined' ) {
		return false;
	}

	// CHANGE: Add settings
	var _settings = {
		formRowSelector: '.form-row',
		formRowExpansibleSelector: '.form-row.fc-expansible-form-section',
		expansibleToggleSelector: '.fc-expansible-form-section__toggle',
		expansibleContentSelector: '.fc-expansible-form-section__content',
		inputSelector: 'input, select, textarea',
		countryFieldsSelector: '#billing_country, #shipping_country, #country',

		addressFieldGroupSelector: '.woocommerce-billing-fields, .woocommerce-shipping-fields, .woocommerce-address-fields',
		billingAddressFieldGroupSelector: '.woocommerce-billing-fields',
		shippingAddressFieldGroupSelector: '.woocommerce-shipping-fields',
		editAddressFieldsSelector: '.woocommerce-address-fields',

		/**
		 * The `overrideLocaleAttributes` setting defines which attributes in the address field locale
		 * should be overridden by values from the site or plugin settings, instead of being replaced 
		 * by the defaults provided by the country locale information.
		 * 
		 * Expected format:
		 *   An array of strings, where each string is the key of a field attribute to override,
		 *   e.g.: [ 'label', 'placeholder', 'required' ]
		 */
		overrideLocaleAttributes: [],

		/**
		 * The `overrideLocaleFieldAttributes` setting defines which attributes in the address field locale
		 * should be overridden by values from the site or plugin settings, instead of being replaced 
		 * by the defaults provided by the country locale information.
		 * 
		 * Expected format:
		 *   An object, where the key is the field key and the value is an array of attribute keys to override,
		 *   e.g.: { "billing_phone": [ "label", "required" ], "shipping_phone": [ "label", "required" ] }
		 */
		overrideLocaleFieldAttributes: {},

		/**
		 * Edit address fields for My Account edit address pages.
		 *
		 * Expected format:
		 *   An object, where the key is the field key and the value is the field arguments object,
		 *   e.g.: { "shipping_phone": { "label": "Shipping phone", "required": true } }
		 */
		editAddressFields: {},

		/**
		 * Context for edit address field key resolution on My Account edit address pages.
		 *
		 * Expected format:
		 *   {
		 *     source: "woocommerce",
		 *     addressType: "shipping",
		 *     fieldKeyFormat: "prefixed",
		 *     fieldKeyPrefix: "shipping_"
		 *   }
		 *
		 * Extensions such as the Address Book add-on may use `fieldKeyFormat: "unprefixed"` and an empty `fieldKeyPrefix`.
		 */
		editAddressContext: {},
	};
	if ( FCUtils && window.fcSettings && window.fcSettings.addressI18n ) {
		_settings = FCUtils.extendObject( true, _settings, window.fcSettings.addressI18n );
	}
	// CHANGE: END - Add settings

	var locale_json = wc_address_i18n_params.locale.replace( /&quot;/g, '"' ), locale = JSON.parse( locale_json );

	function field_is_required( field, is_required ) {
		if ( is_required ) {
			field.find( 'label .optional' ).remove();
			field.addClass( 'validate-required' );

			// CHANGE: Should also add required markers to fields that are not currently visible
			if ( field.find( 'label .required' ).length === 0 ) {
				// CHANGE: Add title attribute to required marker in label to enhance accessibility
				field.find( 'label' ).append(
					'&nbsp;<abbr class="required" title="' +
					wc_address_i18n_params.i18n_required_text +
					'">*</abbr>'
				);
			}
		} else {
			field.find( 'label .required' ).remove();
			field.removeClass( 'validate-required woocommerce-invalid woocommerce-invalid-required-field' );

			if ( field.find( 'label .optional' ).length === 0 ) {
				field.find( 'label' ).append( '&nbsp;<span class="optional">(' + wc_address_i18n_params.i18n_optional_text + ')</span>' );
			}
		}
	}

	// CHANGE: Add function to get list of locale attributes to override for a specific checkout field
	/**
	 * Get the list of locale attributes to override for a checkout field.
	 *
	 * @param   string  field_key  Checkout field key.
	 *
	 * @return  Array               List of attribute keys to override from checkout field settings.
	 */
	var getFieldLocaleOverrideAttributes = function( field_key ) {
		// Initialize variables
		var fieldOverrides = [];

		// Bail early if no override field attributes are set, return the global overrides
		if (
			! _settings.overrideLocaleFieldAttributes
			|| ! _settings.overrideLocaleFieldAttributes[ field_key ]
			|| ! Array.isArray( _settings.overrideLocaleFieldAttributes[ field_key ] )
		) {
			// Get the global overrides
			fieldOverrides = Array.isArray( _settings.overrideLocaleAttributes ) ? _settings.overrideLocaleAttributes : [];
			return fieldOverrides;
		}

		// Get the per-field locale attribute overrides
		fieldOverrides = _settings.overrideLocaleFieldAttributes[ field_key ];

		// Get the global overrides
		var globalOverrides = Array.isArray( _settings.overrideLocaleAttributes ) ? _settings.overrideLocaleAttributes : [];

		// Combine global and per-field locale attribute overrides, per-field attributes take precedence
		fieldOverrides = globalOverrides.filter( function( attr ) {
			return fieldOverrides.indexOf( attr ) === -1; // Prevent duplicates
		} ).concat( fieldOverrides );

		return fieldOverrides;
	};
	// CHANGE: END - Add function to get list of locale attributes to override for a specific checkout field

	// CHANGE: Add function to resolve field key for address locale attribute overrides
	/**
	 * Resolve the field key used to look up field arguments for locale attribute overrides.
	 *
	 * @param   string  locale_field_key  Locale field key from country locale information.
	 * @param   object  wrapper           jQuery wrapper for the address field group.
	 *
	 * @return  object                    Field key and field arguments source for locale attribute overrides.
	 */
	var getFieldArgsForLocaleOverride = function( locale_field_key, wrapper ) {
		// Initialize variables
		var field_key   = locale_field_key;
		var groupFields = null;

		// Bail if settings are not available
		if ( ! window.fcSettings ) {
			return {
				field_key:   field_key,
				groupFields: groupFields,
			};
		}

		// Edit address page
		if ( wrapper.is( _settings.editAddressFieldsSelector ) && _settings.editAddressFields ) {
			// Get edit address context
			var editAddressContext = _settings.editAddressContext || {};

			// Maybe add field key prefix for prefixed field key format
			if ( 'prefixed' === editAddressContext.fieldKeyFormat ) {
				var field_key_prefix = editAddressContext.fieldKeyPrefix || '';

				// Maybe fallback to address type as field key prefix
				if ( ! field_key_prefix && editAddressContext.addressType ) {
					field_key_prefix = editAddressContext.addressType + '_';
				}

				if ( field_key_prefix ) {
					field_key = field_key_prefix + locale_field_key;
				}
			}

			// Get edit address fields
			groupFields = _settings.editAddressFields;

			return {
				field_key:   field_key,
				groupFields: groupFields,
			};
		}
		// Checkout page
		else if ( window.fcSettings.checkoutFields ) {
			// Determine address field group
			var addressFieldGroup = null;
			if ( wrapper.is( _settings.billingAddressFieldGroupSelector ) ) { addressFieldGroup = 'billing'; }
			else if ( wrapper.is( _settings.shippingAddressFieldGroupSelector ) ) { addressFieldGroup = 'shipping'; }

			// Maybe set field key and group fields
			if ( addressFieldGroup ) {
				field_key   = addressFieldGroup + '_' + locale_field_key;
				groupFields = window.fcSettings.checkoutFields[ addressFieldGroup ];
			}
		}

		return {
			field_key:   field_key,
			groupFields: groupFields,
		};
	};
	// CHANGE: END - Add function to resolve field key for address locale attribute overrides

	// Handle locale
	// CHANGE: Extract function to process country to state changing as it needs to be used when event `updated_checkout` is triggered
	var process_country_to_state_changing = function( event, country, wrapper ) {
		var thisform = wrapper, thislocale;

		// CHANGE: Get current focused element and its value
		var currentFocusedElement = document.activeElement;

		if ( typeof locale[ country ] !== 'undefined' ) {
			thislocale = locale[ country ];
		} else {
			thislocale = locale['default'];
		}

		// CHANGE: Add selector for address fields without prefix
		var $postcodefield = thisform.find( '#postcode_field, #billing_postcode_field, #shipping_postcode_field' ),
			$cityfield     = thisform.find( '#city_field, #billing_city_field, #shipping_city_field' ),
			$statefield    = thisform.find( '#state_field, #billing_state_field, #shipping_state_field' );

		if ( ! $postcodefield.attr( 'data-o_class' ) ) {
			$postcodefield.attr( 'data-o_class', $postcodefield.attr( 'class' ) );
			$cityfield.attr( 'data-o_class', $cityfield.attr( 'class' ) );
			$statefield.attr( 'data-o_class', $statefield.attr( 'class' ) );
		}

		var locale_fields = JSON.parse( wc_address_i18n_params.locale_fields );

		$.each( locale_fields, function( key, value ) {

			var field       = thisform.find( value ),
				fieldLocale = $.extend( true, {}, locale['default'][ key ], thislocale[ key ] );

			// CHANGE: Maybe replace field attributes from locale with attributes from checkout fields
			if ( window.fcSettings ) {
				// Get field key and field arguments source for locale attribute overrides
				var fieldArgsForLocaleOverride = getFieldArgsForLocaleOverride( key, wrapper );
				var field_key                  = fieldArgsForLocaleOverride.field_key;
				var groupFields                = fieldArgsForLocaleOverride.groupFields;

				// Get attributes to override for the current field
				var fieldOverrideAttributes = getFieldLocaleOverrideAttributes( field_key );

				// Maybe replace field attributes from locale with attributes from checkout fields
				if ( fieldOverrideAttributes.length > 0 && groupFields ) {
					// Get field attributes for the current field
					var checkoutField = groupFields[ field_key ];

					// Check whether field attributes exist
					if ( checkoutField ) {
						// Maybe replace field attribute (native JS)
						Object.keys( checkoutField ).forEach( function( attr_key ) {
							if ( fieldOverrideAttributes.indexOf( attr_key ) > -1 ) {
								fieldLocale[ attr_key ] = checkoutField[ attr_key ];
							}
						} );
					}
				}
			}
			// CHANGE: END - Maybe replace field attributes from locale with attributes from checkout fields

			// Labels.
			if ( typeof fieldLocale.label !== 'undefined' ) {
				field.find( 'label' ).html( fieldLocale.label );
			}

			// Placeholders.
			if ( typeof fieldLocale.placeholder !== 'undefined' ) {
				field.find( ':input' ).attr( 'placeholder', fieldLocale.placeholder );
				field.find( ':input' ).attr( 'data-placeholder', fieldLocale.placeholder );
				field.find( '.select2-selection__placeholder' ).text( fieldLocale.placeholder );
			}

			// Use the i18n label as a placeholder if there is no label element and no i18n placeholder.
			if (
				typeof fieldLocale.placeholder === 'undefined' &&
				typeof fieldLocale.label !== 'undefined' &&
				! field.find( 'label:not(.screen-reader-text)' ).length
			) {
				field.find( ':input' ).attr( 'placeholder', fieldLocale.label );
				field.find( ':input' ).attr( 'data-placeholder', fieldLocale.label );
				field.find( '.select2-selection__placeholder' ).text( fieldLocale.label );
			}

			// Required.
			if ( typeof fieldLocale.required !== 'undefined' ) {
				field_is_required( field, fieldLocale.required );
			} else {
				field_is_required( field, false );
			}

			// Priority.
			if ( typeof fieldLocale.priority !== 'undefined' ) {
				field.data( 'priority', fieldLocale.priority );
			}

			// Hidden fields. State visibility (show) is managed by
			// country-select.js, but locale can still hide it.
			if ( true === fieldLocale.hidden ) {
				field.hide().find( ':input' ).val( '' );
			} else if ( 'state' !== key ) {
				field.show();
			}

			// CHANGE: Handle collapsible fields state
			var formRow, fieldCollapsibleToggle, fieldCollapsibleContent;
			if ( window.CollapsibleBlock && field.length > 0 ) {
				formRow = field[0].closest( _settings.formRowExpansibleSelector );
				if ( formRow ) {
					fieldCollapsibleToggle = formRow.querySelector( _settings.expansibleToggleSelector );
					fieldCollapsibleContent = formRow.querySelector( _settings.expansibleContentSelector );
					if ( fieldCollapsibleToggle && fieldCollapsibleContent ) {
						var expandContent = false;

						// Required fields
						if ( typeof fieldLocale.required !== 'undefined' && true === fieldLocale.required ) {
							expandContent = true;
						}
						// Optional fields
						else {
							var input = field[0].querySelector( _settings.inputSelector );
							if ( input && '' !== input.value ) {
								expandContent = true;
							}
						}

						// Optional fields that are also hidden
						if ( 'state' !== key && true === fieldLocale.hidden && ( typeof fieldLocale.required === 'undefined' || false === fieldLocale.required ) ) {
							// Should expand field contents to avoid showing the "+ Add" link button when the field is hidden
							expandContent = true;
						}

						// Expand content
						if ( expandContent ) {
							CollapsibleBlock.collapse( fieldCollapsibleToggle, false ); // Collapse without transitions
							CollapsibleBlock.expand( fieldCollapsibleContent, false, false ); // Expand without transitions and without setting focus
						}
						// Collapse content
						else {
							CollapsibleBlock.collapse( fieldCollapsibleContent, false ); // Collapse without transitions
							CollapsibleBlock.expand( fieldCollapsibleToggle, false, false ); // Expand without transitions and without setting focus
						}
					}
				}
			}
			// CHANGE: END - Handle collapsible fields state

			// Class changes.
			if ( Array.isArray( fieldLocale.class ) ) {
				// CHANGE: Add custom form row classes to be removed
				field.removeClass( 'form-row-first form-row-last form-row-wide form-row-one-third form-row-two-thirds form-row-middle' );
				field.addClass( fieldLocale.class.join( ' ' ) );
			}
		});

		var fieldsets = $(
			'.woocommerce-billing-fields__field-wrapper,' +
			'.woocommerce-shipping-fields__field-wrapper,' +
			'.woocommerce-address-fields__field-wrapper,' +
			'.woocommerce-additional-fields__field-wrapper .woocommerce-account-fields'
		);

		fieldsets.each( function( index, fieldset ) {
			// CHANGE: Change form row selector to exclude nested `.form-row` elements (used for expansible form fields)
			var rows    = $( fieldset ).find( '.form-row:not( .form-row .form-row )' );
			var wrapper = rows.first().parent();

			// Before sorting, ensure all fields have a priority for bW compatibility.
			var last_priority = 0;

			rows.each( function() {
				if ( ! $( this ).data( 'priority' ) ) {
						$( this ).data( 'priority', last_priority + 1 );
				}
				last_priority = $( this ).data( 'priority' );
			} );

			// Sort the fields.
			rows.sort( function( a, b ) {
				var asort = parseInt( $( a ).data( 'priority' ), 10 ),
					bsort = parseInt( $( b ).data( 'priority' ), 10 );

				if ( asort > bsort ) {
					return 1;
				}
				if ( asort < bsort ) {
					return -1;
				}
				return 0;
			});

			// CHANGE: Detach rows and re-attach them in the correct order, without moving the row of the field currently focused.
			// This prevents the field from losing focus and keeps the virtual keyboard on mobile devices open.

			// Get focused row
			var focusedRow, referenceNode;
			var before = true;
			var rowsBefore = [], rowsAfter = [];
			var _rows = rows.toArray();
			for ( var i = 0; i < _rows.length; i++) {
				var row = _rows[ i ];
				if ( row.contains( currentFocusedElement ) ) {
					focusedRow = row;
					referenceNode = focusedRow;
					break;
				}
			}

			// Iterate over rows
			for ( var i = 0; i < _rows.length; i++) {
				var row = _rows[ i ];

				// Maybe skip row with the field currently focused
				if ( row === focusedRow ) {
					before = false;
					continue;
				}

				// Set reference node to last child
				if ( ! focusedRow ) {
					referenceNode = row.parentNode.lastChild;
				}

				// Maybe add row to the before list
				if ( before ) {
					rowsBefore.push( row );
				}
				// Maybe add row to the after list
				else {
					rowsAfter.push( row );
				}
			}

			// Re-attach rows before the field currently focused
			for ( var j = 0; j < rowsBefore.length; j++ ) {
				var row = rowsBefore[ j ];
				row.parentNode.insertBefore( row, referenceNode );
			}

			// Re-attach rows after the field currently focused
			rowsAfter = rowsAfter.reverse();
			for ( var j = 0; j < rowsAfter.length; j++ ) {
				var row = rowsAfter[ j ];
				row.parentNode.insertBefore( row, referenceNode.nextSibling );
			}
			// CHANGE: END - Detach rows and re-attach them in the correct order, without moving the row of the field currently focused.

		} ); // CHANGE: Close the `fieldsets.each` loop (replaces upstream `rows.detach().appendTo`)

		// CHANGE: Re-set focus to the element previously with focus
		FCUtils.maybeRefocusElement( currentFocusedElement );
	};
	// CHANGE: END - Extract function to process country to state changing as it needs to be used when event `updated_checkout` is triggered

	// CHANGE: Add function to handle country to state changing when event `updated_checkout` is triggered
	var process_country_to_state_changing_updated_checkout = function() {		
		// Get all country fields on the page
		var country_fields = document.querySelectorAll( _settings.countryFieldsSelector );

		// Iterate all country fields and process country changing event for each one
		if ( country_fields.length > 0 ) {
			for ( var i = 0; i < country_fields.length; i++ ) {
				var field = country_fields[i];
				var wrapper = field.closest( _settings.addressFieldGroupSelector );
				process_country_to_state_changing( null, field.value, $( wrapper ) );
			}
		}
	}
	// CHANGE: END - Add function to handle country to state changing when event `updated_checkout` is triggered

	// CHANGE: Add event listeners
	$( document.body )
		// CHANGE: Use extracted function to process country to state changing
		.on( 'country_to_state_changing', process_country_to_state_changing )
		// CHANGE: Use extracted function to process country to state changing when event `updated_checkout` is triggered
		.on( 'updated_checkout', process_country_to_state_changing_updated_checkout )
		.trigger( 'wc_address_i18n_ready' );
});
