/**
 * EU VAT for WooCommerce - JS
 *
 * @version 4.8.2
 * @since   1.0.0
 *
 * @author  WPFactory
 *
 * @todo    (dev) replace `billing_eu_vat_number` and `billing_eu_vat_number_field` with `wpfactory_wc_eu_vat_get_field_id()`
 * @todo    (dev) customizable event for `billing_company` (currently `input`; could be e.g., `change`)
 */

jQuery( function ( $ ) {
	'use strict';

	// Setup before functions
	var input_timer;                 // timer identifier
	var input_timer_company_require; // timer identifier (company require)
	var input_timer_company_load;    // timer identifier (company require)
	var input_timer_company;         // timer identifier (company)
	var done_input_interval = 1000;  // time in ms

	var valid_vat_but_not_exempted = 'no';

	// Elements
	var vat_input;
	var billing_company;
	var vat_input_customer_choice;
	var valid_vat_but_not_exempted_input;
	var vat_input_billing_country;
	var vat_paragraph;
	var vat_input_label;

	// Init elements
	init_elements();

	// Add progress text
	if ( 'yes' === wpfactory_wc_eu_vat_ajax_object.add_progress_text ) {
		vat_paragraph.append( '<div id="wpfactory_wc_eu_vat_progress"></div>' );
		var progress_text = $( 'div[id="wpfactory_wc_eu_vat_progress"]' );
	}

	// Display VAT details
	if ( 'yes' === wpfactory_wc_eu_vat_ajax_object.show_vat_details ) {
		vat_paragraph.append( '<div id="wpfactory_wc_eu_vat_details"></div>' );
	}

	// Show/hide by billing company
	if ( wpfactory_wc_eu_vat_ajax_object.do_show_hide_by_billing_company ) {
		billing_company.on( 'input', show_hide_by_billing_company );
		$( document.body ).one( 'updated_checkout', function () {
			show_hide_by_billing_company();
			vat_input_billing_country.trigger( 'change' );
		} );
	}

	if ( 'yes_for_company' === wpfactory_wc_eu_vat_ajax_object.is_required ) {
		billing_company.on( 'input', function () {
			is_company_name_not_empty();
		} );

		$( document.body ).one( 'updated_checkout', function () {
			is_company_name_not_empty();
		} );
	}

	// Initial validate
	wpfactory_wc_eu_vat_validate_vat( true );

	// Attach event handlers
	attach_event_handlers();

	// "Fluid Checkout for WooCommerce" plugin compatibility
	if ( wpfactory_wc_eu_vat_ajax_object.do_compatibility_fluid_checkout ) {
		compatibility_fluid_checkout();
	}

	/**
	 * show_hide_by_billing_company.
	 *
	 * @version 4.7.0
	 * @since   4.4.6
	 */
	function show_hide_by_billing_company() {

		if ( '' === $( '#billing_company' ).val() ) {
			$( '#billing_eu_vat_number_field' ).hide();
			$( '#billing_eu_vat_number_valid_vat_but_not_exempted_field' ).hide()
			$( '#billing_eu_vat_number' ).val( '' );
			wpfactory_wc_eu_vat_validate_vat();
		} else {
			$( '#billing_eu_vat_number_field' ).show();
			$( '#billing_eu_vat_number_valid_vat_but_not_exempted_field' ).show()
		}

	}

	/**
	 * compatibility_fluid_checkout.
	 *
	 * @version 4.2.3
	 * @since   4.2.3
	 */
	function compatibility_fluid_checkout() {

		// Vars
		var saved_progress_text;
		var saved_progress_class;
		var saved_vat_paragraph_class;

		/**
		 * fc_checkout_fragments_replace_before.
		 */
		$( document.body ).on( 'fc_checkout_fragments_replace_before', function () {
			saved_progress_text       = progress_text.text();
			saved_progress_class      = progress_text.attr( 'class' );
			saved_vat_paragraph_class = vat_paragraph.attr( 'class' );
		} );

		/**
		 * fc_checkout_fragments_replace_after.
		 */
		$( document.body ).on( 'fc_checkout_fragments_replace_after', function () {

			// Re-init elements
			init_elements();

			// Re-set class
			vat_paragraph.addClass( saved_vat_paragraph_class );

			// Re-add progress div
			if (
				'yes' === wpfactory_wc_eu_vat_ajax_object.add_progress_text &&
				0 == $( '#wpfactory_wc_eu_vat_progress' ).length
			) {
				vat_paragraph.append( '<div id="wpfactory_wc_eu_vat_progress"></div>' );
				progress_text = $( 'div[id="wpfactory_wc_eu_vat_progress"]' );
				// Re-set text and class
				progress_text.text( saved_progress_text );
				progress_text.addClass( saved_progress_class );
			}

			// Re-attach event handlers
			attach_event_handlers();

		} );

	}

	/**
	 * init_elements.
	 *
	 * @version 4.4.0
	 * @since   4.2.3
	 */
	function init_elements() {
		vat_input                        = $( 'input[name="billing_eu_vat_number"]' );
		billing_company                  = $( 'input[name="billing_company"]' );
		vat_input_customer_choice        = $( 'input[name="billing_eu_vat_number_customer_decide"]' );
		valid_vat_but_not_exempted_input = $( 'input[name="billing_eu_vat_number_valid_vat_but_not_exempted"]' );
		vat_input_billing_country        = $( 'select[name="billing_country"]' );
		vat_paragraph                    = $( 'p[id="billing_eu_vat_number_field"]' );
		vat_input_label                  = $( 'label[for="billing_eu_vat_number"]' );
	}

	/**
	 * block_checkout_section.
	 *
	 * @version 4.8.2
	 * @since   4.5.0
	 */
	function block_checkout_section() {
		const $target = $( '#order_review, #payment' );
		if ( $target.length ) {
			$target.block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
		}

		const $place_order = $( '#place_order' );
		if ( $place_order.length ) {
			$place_order.prop( 'disabled', true );
		}
	}

	/**
	 * unblock_checkout_section.
	 *
	 * @version 4.8.2
	 * @since   4.5.0
	 */
	function unblock_checkout_section() {
		const $target = $( '#order_review, #payment' );
		if ( $target.length ) {
			$target.unblock();
		}

		const $place_order = $( '#place_order' );
		if ( $place_order.length ) {
			$place_order.prop( 'disabled', false );
		}
	}

	/**
	 * attach_event_handlers.
	 *
	 * @version 4.7.0
	 * @since   4.2.3
	 */
	function attach_event_handlers() {

		// On blur/input, start the countdown
		const event_trigger = (
			'onblur' === wpfactory_wc_eu_vat_ajax_object.action_trigger ?
			'blur' :
			'input'
		);
		$( document.body ).on( event_trigger, 'input[name="billing_eu_vat_number"]', function () {
			block_checkout_section();
			clearTimeout( input_timer );
			input_timer = setTimeout( () => {
				wpfactory_wc_eu_vat_validate_vat();
			}, done_input_interval );
		} );

		// On country change - re-validate
		$( document.body ).on( 'change', '#billing_country, #shipping_country', function () {
			wpfactory_wc_eu_vat_validate_vat();
		} );

		// Ship to different address
		$( document.body ).on( 'input', '#ship-to-different-address-checkbox', function () {
			wpfactory_wc_eu_vat_validate_vat();
		} );

		// Company name - re-validate
		if ( wpfactory_wc_eu_vat_ajax_object.do_check_company_name ) {
			$( '#billing_company' ).on( 'input', function () {
				clearTimeout( input_timer_company );
				input_timer_company = setTimeout(
					wpfactory_wc_eu_vat_validate_vat,
					done_input_interval
				);
			} );
		}

		// Customer choice
		vat_input_customer_choice.change( function () {
			wpfactory_wc_eu_vat_validate_vat();
		} );

		// Valid VAT but not exempted
		valid_vat_but_not_exempted_input.change( function () {
			wpfactory_wc_eu_vat_validate_vat();
		} );

	}

	/**
	 * is_company_name_not_empty.
	 *
	 * @version 4.5.3
	 */
	function is_company_name_not_empty() {
		if ( '' !== billing_company.val() ) {
			vat_paragraph.removeClass( 'woocommerce-invalid' );
			vat_paragraph.removeClass( 'woocommerce-validated' );
			vat_paragraph.addClass( 'validate-required' );
			vat_input.addClass( 'field-required' );
			vat_input_label.find( "span.optional" ).remove();
			vat_input_label.find( "abbr" ).remove();
			vat_input_label.find( "span.required" ).show();

			// Show required span only once
			if ( vat_input_label.find( 'span.required' ).length === 0 ) {
				vat_input_label.append( '<span class="required" aria-hidden="true">*</span>' );
			} else {
				vat_input_label.find( 'span.required' ).show();
			}
		} else {
			vat_paragraph.removeClass( 'woocommerce-invalid' );
			vat_paragraph.removeClass( 'woocommerce-validated' );
			vat_paragraph.removeClass( 'validate-required' );
			vat_input.removeClass( 'field-required' );
			vat_input_label.find( "abbr" ).hide();
			vat_input_label.find( "span.optional" ).remove();
			vat_input_label.find( "span.required" ).hide();

			if ( vat_input_label.find( 'span.optional' ).length === 0 ) {
				vat_input_label.append( '<span class="optional">' + wpfactory_wc_eu_vat_ajax_object.optional_text + '</span>' );
			}
		}
	}

	/**
	 * wpfactory_wc_eu_vat_validate_vat.
	 *
	 * @version 4.7.7
	 * @since   1.0.0
	 */
	function wpfactory_wc_eu_vat_validate_vat( load = false ) {

		if (
			load &&
			wpfactory_wc_eu_vat_ajax_object.do_compatibility_fluid_checkout
		) {
			load = false;
		}

		var vat_number_to_check = vat_input.val();
		if ( 'billing_country' === $( this ).attr( 'name' ) ) {
			vat_input.trigger( 'input' );
		}

		if (
			'yes' === wpfactory_wc_eu_vat_ajax_object.add_progress_text &&
			'yes' === wpfactory_wc_eu_vat_ajax_object.hide_message_on_preserved_countries &&
			wpfactory_wc_eu_vat_ajax_object.preserve_countries.length > 0
		) {
			if ( $.inArray( vat_input_billing_country.val(), wpfactory_wc_eu_vat_ajax_object.preserve_countries ) >= 0 ) {
				progress_text.hide();
			} else {
				progress_text.show();
			}
		}

		if ( vat_input_customer_choice.length > 0 ) {

			const isChecked = vat_input_customer_choice.is( ':checked' );
			vat_paragraph.removeClass( 'woocommerce-invalid woocommerce-validated' );
			vat_input_label.find( 'span.optional' ).remove();

			if ( isChecked ) {
				vat_paragraph.removeClass( 'validate-required' ).hide();
				vat_input.removeClass( 'field-required' );
				vat_input_label.find( "abbr" ).hide();
				vat_input_label.append( '<span class="optional">' + wpfactory_wc_eu_vat_ajax_object.optional_text + '</span>' );

				if ( '' === vat_number_to_check ) {
					unblock_checkout_section();
					return;
				}

				vat_input.val( '' );
				vat_number_to_check = '';
				load = false;
			} else {
				vat_paragraph.addClass( 'validate-required' ).show();
				vat_input.addClass( 'field-required' );
				vat_input_label.find( "abbr" ).show();
				vat_number_to_check = vat_input.val();
			}
		}

		const customer_decide = vat_input_customer_choice.length ?
			vat_input_customer_choice.is(':checked') :
			false;

		const vat_valid_but_not_exempted = valid_vat_but_not_exempted_input.length ?
			valid_vat_but_not_exempted_input.is(':checked') :
			false;

		vat_paragraph.removeClass( 'woocommerce-invalid' );
		vat_paragraph.removeClass( 'woocommerce-validated' );
		vat_paragraph.removeClass( 'woocommerce-invalid-mismatch' );

		if ( load && '' === vat_number_to_check ) {
			vat_number_to_check = undefined;
		}

		if ( undefined !== vat_number_to_check ) {
			// Validating EU VAT Number through AJAX call
			if ( 'yes' === wpfactory_wc_eu_vat_ajax_object.add_progress_text ) {
				progress_text.text( wpfactory_wc_eu_vat_ajax_object.progress_text_validating );
				progress_text.removeClass();
				progress_text.addClass( 'wpfactory-wc-eu-vat-validating' );
			}

			const vatDetailsDiv = document.getElementById( 'wpfactory_wc_eu_vat_details' );
			if ( vatDetailsDiv ) {
				vatDetailsDiv.innerHTML = '';
			}

			var shipToDifferent = $( '#ship-to-different-address-checkbox' ).is( ':checked' );
			var data = {
				'action'                            : 'wpfactory_wc_eu_vat_validate_action',
				'eu_vat_to_check'                   : vat_number_to_check,
				'billing_country'                   : $( '#billing_country' ).val(),
				'shipping_country'                  : shipToDifferent ? $( '#shipping_country' ).val() : '',
				'billing_company'                   : $( '#billing_company' ).val(),
				'eu_vat_customer_decide'            : customer_decide,
				'eu_vat_valid_vat_but_not_exempted' : vat_valid_but_not_exempted,
				'nonce'                             : $( '#wpfactory_wc_eu_vat_nonce_field' ).val(),
			};

			$.ajax( {
				type: "POST",
				url: wpfactory_wc_eu_vat_ajax_object.ajax_url,
				data: data,
				beforeSend: function (  ){
					block_checkout_section();
				},
				success: function ( resp ) {
					const data = resp;

					if ( ! data ) {
						if ( progress ) {
							progress.innerHTML = '';
						}
						return;
					}

					const isValidation = data.is_validate;
					const cssClasses = data.css_class ? data.css_class.trim().split( /\s+/ ) : [];
					const company_name = data.company;

					if ( isValidation ) {
						vat_input.addClass( 'woocommerce-validated' );
						cssClasses.push( 'wpfactory-wc-eu-vat-valid', 'wpfactory-wc-eu-vat-valid-color' );
					} else {
						vat_input.addClass( 'woocommerce-invalid' );
						cssClasses.push( 'wpfactory-wc-eu-vat-not-valid', 'wpfactory-wc-eu-vat-error-color' );
					}
					if ( progress_text ) {
						progress_text.text( data.messages ?? '' );
						progress_text.removeClass().addClass( cssClasses.join( ' ' ) );
					}

					if (
						'yes' === wpfactory_wc_eu_vat_ajax_object.autofill_company_name &&
						'' !== company_name
					) {
						$( '#billing_company' ).val( company_name );
					}

					if ( data.vat_details && vatDetailsDiv ) {
						const ul = document.createElement( 'ul' );
						Object.values( data.vat_details ).forEach( ( {label, data: value} ) => {
							const li = document.createElement( 'li' );
							li.textContent = `${label}: ${value}`;
							ul.appendChild( li );
						} );
						vatDetailsDiv.replaceChildren( ul );
					}
				},
				complete: function () {
					$( document.body ).trigger( 'update_checkout' );
					unblock_checkout_section();
				}
			} );
		} else {
			// VAT input is empty
			if ( 'yes' === wpfactory_wc_eu_vat_ajax_object.add_progress_text ) {
				progress_text.text( '' );
			}
			if ( vat_paragraph.hasClass( 'validate-required' ) ) {
				// Required
				vat_paragraph.addClass( 'woocommerce-invalid' );
			}

			var refresh_checkout_end = function () {
				$( document.body ).trigger( 'update_checkout' );
				unblock_checkout_section();
			};
			setTimeout( refresh_checkout_end, 800 );
		}

	}

} );