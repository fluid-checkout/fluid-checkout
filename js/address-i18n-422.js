/**
 * Address Internationalization Script
 * 
 * Replaces the original WooCommerce `address-i18n.js`.
 */

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

		overrideLocaleAttributes: [],
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

			if ( field.find( 'label .required' ).length === 0 ) {
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
			if ( _settings.overrideLocaleAttributes.length > 0 && window.fcSettings && window.fcSettings.checkoutFields ) {
				// Determine address field group
				var addressFieldGroup = 'address';
				if ( wrapper.hasClass( 'woocommerce-billing-fields' ) ) {
					addressFieldGroup = 'billing';
				} else if ( wrapper.hasClass( 'woocommerce-shipping-fields' ) ) {
					addressFieldGroup = 'shipping';
				}

				// Determine field key prefix
				var field_key_prefix = addressFieldGroup + '_';

				// Determine field key
				var field_key = key;
				if ( 'address' !== addressFieldGroup ) {
					field_key = field_key_prefix + key;
				}

				// Get fields for the current group
				var groupFields = window.fcSettings.checkoutFields[ addressFieldGroup ];

				// Check whether group fields exist
				if ( groupFields ) {
					// Get field attributes for the current field
					var checkoutField = groupFields[ field_key ];

					// Check whether field attributes exist
					if ( checkoutField ) {
						// Maybe replace field attribute
						$.each( checkoutField, function( attr_key, attr_value ) {
							if ( _settings.overrideLocaleAttributes.indexOf( attr_key ) > -1 ) {
								fieldLocale[ attr_key ] = attr_value;
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
				! field.find( 'label' ).length
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

			// Hidden fields.
			if ( 'state' !== key ) {
				if ( typeof fieldLocale.hidden !== 'undefined' && true === fieldLocale.hidden ) {
					field.hide().find( ':input' ).val( '' );
				} else {
					field.show();
				}
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
		} );

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
	$( document.body )
		// CHANGE: Use extracted function to process country to state changing
		.on( 'country_to_state_changing', process_country_to_state_changing )
		// CHANGE: Use extracted function to process country to state changing when event `updated_checkout` is triggered
		.on( 'updated_checkout', process_country_to_state_changing_updated_checkout )
		.trigger( 'wc_address_i18n_ready' );
});
