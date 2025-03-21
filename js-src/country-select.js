/**
 * Country Select Script
 *
 * Replaces the original WooCommerce `country-select.js`.
 */

/*global wc_country_select_params */
jQuery( function( $ ) {

	// wc_country_select_params is required to continue, ensure the object exists
	if ( typeof wc_country_select_params === 'undefined' ) {
		return false;
	}


	// CHANGE: END - Enhanced select fields with TomSelect
	var usingTomSelect = window.TomSelect && window.fcSettings && fcSettings.use_enhanced_select && 'yes' === fcSettings.use_enhanced_select;
	if ( usingTomSelect && window.FCEnhancedSelect ) {
		// CHANGE: Use TomSelect for select2 fields
		var wc_country_select_tomselect = function() {
			var selector = 'select.state_select';
			FCEnhancedSelect.enhanceFields( selector );
		}

		// Rebuild enhanced fields when changing the selected country
		$( document.body ).on( 'country_to_state_changed', wc_country_select_tomselect );
	}
	// CHANGE: END - Enhanced select fields with TomSelect

	// Select2 Enhancement if it exists
	// CHANGE: maybe disable select2 enhancement if TomSelect is being used
	if ( $().selectWoo && ! usingTomSelect ) {
		var getEnhancedSelectFormatString = function() {
			return {
				'language': {
					errorLoading: function() {
						// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
						return wc_country_select_params.i18n_searching;
					},
					inputTooLong: function( args ) {
						var overChars = args.input.length - args.maximum;

						if ( 1 === overChars ) {
							return wc_country_select_params.i18n_input_too_long_1;
						}

						return wc_country_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
					},
					inputTooShort: function( args ) {
						var remainingChars = args.minimum - args.input.length;

						if ( 1 === remainingChars ) {
							return wc_country_select_params.i18n_input_too_short_1;
						}

						return wc_country_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
					},
					loadingMore: function() {
						return wc_country_select_params.i18n_load_more;
					},
					maximumSelected: function( args ) {
						if ( args.maximum === 1 ) {
							return wc_country_select_params.i18n_selection_too_long_1;
						}

						return wc_country_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
					},
					noResults: function() {
						return wc_country_select_params.i18n_no_matches;
					},
					searching: function() {
						return wc_country_select_params.i18n_searching;
					}
				}
			};
		};

		var wc_country_select_select2 = function() {
			// CHANGE: Run function code after a short delay,
			// to allow components to be completely rendered
			requestAnimationFrame(function(){
				// CHANGE: Allow building `select2` fields while not visible
				$( 'select.country_select, select.state_select' ).each( function() {
					var $this = $( this );

					// CHANGE: Get field ID and open `select2` options element
					var fieldID = $this.attr( 'id' );
					var currentOpenSelect2 = document.querySelector( '.select2-container--open' );
					var shouldReopen = null != currentOpenSelect2 && null != currentOpenSelect2.querySelector( '#select2-' + fieldID + '-results' ); // Intentionally loose comparisson

					// CHANGE: Try to remove rendered `select2` elements before building it again
					$this.off( 'select2:select' );
					$this.parent().find( '.select2-container' ).remove();

					var select2_args = $.extend({
						placeholder: $this.attr( 'data-placeholder' ) || $this.attr( 'placeholder' ) || '',
						label: $this.attr( 'data-label' ) || null,
						width: '100%'
					}, getEnhancedSelectFormatString() );

					$( this )
						.on( 'select2:select', function() {
							$( this ).trigger( 'focus' ); // Maintain focus after select https://github.com/select2/select2/issues/4384
						} )
						.selectWoo( select2_args );

					// CHANGE: Maybe reset focus to element
					// Should not set the focused field value back to previous value as it interferes
					// with other scripts setting the field value dynamically.
					// Not setting the field value here fixes an issue with the Google Address Autocomplete add-on
					// leaving the field empty after selecting an address, if using `select2` for the enhanced dropdown fields.
					FCUtils.maybeRefocusElement( window.fcCurrentFocusedElement );

					// CHANGE: Maybe reopen `select2` field
					setTimeout( function() {
						// Reopen `select2` field if it was open before replacing fields
						if ( shouldReopen && 'function' === typeof $this.selectWoo ) {
							// Remove any open options for `select2` fields
							var currentOpenSelect2 = document.querySelectorAll( '.select2-container--open' );
							for ( var i = 0; i < currentOpenSelect2.length; i++ ) {
								currentOpenSelect2[ i ].remove();
							}

							// Reopen options for this `select2` field
							$this.selectWoo( 'open' );
						}
					}, 50 ); // Arbitrary delay to allow `select2` to be completely rendered
					// CHANGE: END - Maybe reopen `select2` field
				});
			});
		};

		wc_country_select_select2();

		// CHANGE: Rebuild `select2` fields in some cases
		$( document.body ).on( 'country_to_state_changed', wc_country_select_select2 );
		$( document.body ).on( 'updated_checkout', wc_country_select_select2 );
		$( document.body ).on( 'wc_fragments_refreshed', wc_country_select_select2 );
	}

	/* State/Country select boxes */
	var states_json       = wc_country_select_params.countries.replace( /&quot;/g, '"' ),
		states            = JSON.parse( states_json ),
		wrapper_selectors = '.woocommerce-billing-fields,' +
			'.woocommerce-shipping-fields,' +
			'.woocommerce-address-fields,' +
			'.woocommerce-shipping-calculator';

	$( document.body ).on( 'change refresh', 'select.country_to_state, input.country_to_state', function() {
		// Grab wrapping element to target only stateboxes in same 'group'
		var $wrapper = $( this ).closest( wrapper_selectors );

		// CHANGE: Maybe set variables for current focused element,
		// which will set focus to relative `select2` field if a field option currently has the focus.
		FCUtils.maybeSetCurrentFocusedElementGlobalVariablesRelativeSelect2();

		if ( ! $wrapper.length ) {
			$wrapper = $( this ).closest('.form-row').parent();
		}

		var country     = $( this ).val(),
			// CHANGE: Add selector for address fields without prefix
			$statebox     = $wrapper.find( '#state, #billing_state, #shipping_state, #calc_shipping_state' ),
			$parent       = $statebox.closest( '.form-row' ),
			input_name    = $statebox.attr( 'name' ),
			input_id      = $statebox.attr('id'),
			input_classes = $statebox.attr('data-input-classes'),
			value         = $statebox.val(),
			placeholder   = $statebox.attr( 'placeholder' ) || $statebox.attr( 'data-placeholder' ) || '',
			$newstate;

		// CHANGE: Define class names to be removed from state field when changing its type
		var state_field_type_classes = [ 'fc-select-field--hidden', 'fc-select-field--text', 'fc-select-field--select' ];

		// CHANGE: Maybe destroy TomSelect component before replacing the field
		if ( usingTomSelect ) {
			var stateField = $statebox.get( 0 );
			if ( $statebox.length > 0 && stateField.tomselect ) {
				stateField.tomselect.destroy();
			}
		}

		if ( states[ country ] ) {
			if ( $.isEmptyObject( states[ country ] ) ) {
				$newstate = $( '<input type="hidden" />' )
					.prop( 'id', input_id )
					.prop( 'name', input_name )
					.prop( 'placeholder', placeholder )
					.attr( 'data-input-classes', input_classes )
					.addClass( 'hidden ' + input_classes );
				$parent.hide().find( '.select2-container' ).remove();
				$statebox.replaceWith( $newstate );

				// CHANGE: Add class for current type of of the state field
				$parent.removeClass( state_field_type_classes ).addClass( 'fc-select-field--hidden' );

				$( document.body ).trigger( 'country_to_state_changed', [ country, $wrapper ] );
			} else {
				var state          = states[ country ],
					$defaultOption = $( '<option value=""></option>' ).text( wc_country_select_params.i18n_select_state_text );

				if ( ! placeholder ) {
					placeholder = wc_country_select_params.i18n_select_state_text;
				}

				$parent.show();

				if ( $statebox.is( 'input' ) ) {
					$newstate = $( '<select></select>' )
						.prop( 'id', input_id )
						.prop( 'name', input_name )
						.data( 'placeholder', placeholder )
						.attr( 'data-input-classes', input_classes )
						.addClass( 'state_select ' + input_classes );
					$statebox.replaceWith( $newstate );
					// CHANGE: Add selector for address fields without prefix
					$statebox = $wrapper.find( '#state, #billing_state, #shipping_state, #calc_shipping_state' );
				}

				$statebox.empty().append( $defaultOption );

				$.each( state, function( index ) {
					var $option = $( '<option></option>' )
						.prop( 'value', index )
						.text( state[ index ] );
					$statebox.append( $option );
				} );

				$statebox.val( value ).trigger( 'change' );

				// CHANGE: Add class for current type of of the state field
				$parent.removeClass( state_field_type_classes ).addClass( 'fc-select-field--select' );

				$( document.body ).trigger( 'country_to_state_changed', [country, $wrapper ] );
			}
		} else {
			if ( $statebox.is( 'select, input[type="hidden"]' ) ) {
				$newstate = $( '<input type="text" />' )
					.prop( 'id', input_id )
					.prop( 'name', input_name )
					.prop('placeholder', placeholder)
					.attr('data-input-classes', input_classes )
					.addClass( 'input-text  ' + input_classes );
				$parent.show().find( '.select2-container' ).remove();
				$statebox.replaceWith( $newstate );

				// CHANGE: Add class for current type of of the state field
				$parent.removeClass( state_field_type_classes ).addClass( 'fc-select-field--text' );

				$( document.body ).trigger( 'country_to_state_changed', [country, $wrapper ] );
			}
		}

		// Re-focus element
		FCUtils.maybeRefocusElement( window.fcCurrentFocusedElement, window.fcCurrentFocusedElementValue );

		$( document.body ).trigger( 'country_to_state_changing', [country, $wrapper ] );
	});

	$( document.body ).on( 'wc_address_i18n_ready', function() {
		// Init country selects with their default value once the page loads.
		$( wrapper_selectors ).each( function() {
			// CHANGE: Add selector for address fields without prefix
			var $country_input = $( this ).find( '#country, #billing_country, #shipping_country, #calc_shipping_country' );

			if ( 0 === $country_input.length || 0 === $country_input.val().length ) {
				return;
			}

			$country_input.trigger( 'refresh' );
		});
	});
});
