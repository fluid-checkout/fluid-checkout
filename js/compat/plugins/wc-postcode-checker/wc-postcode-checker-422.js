jQuery( function( $ ) {
	let xhr_requests  = {};
	let xhr_responses = {};

	// MyParcel 3.0.5+ compatibility
	if ( typeof wc_myparcel_frontend !== "undefined" && typeof wc_myparcel_frontend.isUsingSplitAddressFields !== "undefined" ) {
		window.myparcel_is_using_split_address_fields = wc_myparcel_frontend.isUsingSplitAddressFields = true;
	}
	// MyParcel 3.0.9+ compatibility
	if ( typeof wcmp_display_settings !== "undefined" && typeof wcmp_display_settings.isUsingSplitAddressFields !== "undefined" ) {
		window.myparcel_is_using_split_address_fields = wcmp_display_settings.isUsingSplitAddressFields = true;
		window.postnl_is_using_split_address_fields = true;
	}

	// provide country fallback if field was removed
	$.each(['billing','shipping'], function(index, form) {
		if ( $('#'+form+'_country').length == 0 && $('.woocommerce-'+form+'-fields').length > 0 ) {
			$('.woocommerce-'+form+'-fields').append('<input id="'+form+'_country" class="country_to_state" value="NL" type="hidden">');
		}
	});

	// CHANGE: Extract the `handle_locale` function from the `country_to_state_changing` event handler.
	var handle_locale = function( event, country, wrapper ) {
		var thisform = wrapper;
		if ( $.inArray( country, wpo_wcnlpc.postcode_field_countries ) !== -1 ) {
			if ( country == 'NL' ) {
				// hide & disable street & city according to settings
				if (wpo_wcnlpc.street_city_visibility == 'hide' || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
					thisform.find('#billing_street_name_field, #shipping_street_name_field, #billing_city_field, #shipping_city_field')
						.find('input')
							.attr('readonly', true);
				}
				if (wpo_wcnlpc.street_city_visibility == 'hide') {
					thisform.find('#billing_street_name_field, #shipping_street_name_field, #billing_city_field, #shipping_city_field')
						.hide();
				}
			} else {
				 // Postcode fields are used outside NL (wpo_wcnlpc.postcode_field_countries)
				 // but don't get validated and thus shouldn't be blocked.
				thisform.find('#billing_street_name_field, #shipping_street_name_field, #billing_city_field, #shipping_city_field')
					.show()
					.find('input')
						.attr('readonly', false);

				// remove postcode checker fields!
				thisform.find('.wcnlpc-error, .wcnlpc-address, .wcnlpc-manual').remove();
			}

			var $postcodefield = thisform.find('#billing_postcode_field, #shipping_postcode_field');
			var $cityfield     = thisform.find('#billing_city_field, #shipping_city_field');
			var $statefield    = thisform.find('#billing_state_field, #shipping_state_field');
			var $emailfield    = thisform.find('#billing_email_field');
			var $phonefield    = thisform.find('#billing_phone_field');
			var $address1field = thisform.find('#billing_address_1_field, #shipping_address_1_field');
			var $address2field = thisform.find('#billing_address_2_field, #shipping_address_2_field');
			var $streetfield   = thisform.find('#billing_street_name_field, #shipping_street_name_field');
			var $numberfield   = thisform.find('#billing_house_number_field, #shipping_house_number_field');
			var $suffixfield   = thisform.find('#billing_house_number_suffix_field, #shipping_house_number_suffix_field');


			// determine if this is the billing or shipping form
			if ( thisform.find('#shipping_postcode_field').length ) {
				var form_name = 'shipping';
			} else {
				var form_name = 'billing';
			}

			// make sure we have the correct layout class (can be overriden the via wpo_wcnlpc_checkout_field_classes filter in php)
			$postcodefield.removeClass( 'form-row-first form-row-last form-row-wide' ).addClass( wpo_wcnlpc['field_classes'][form_name]['postcode'] );
			$numberfield.removeClass( 'form-row-first form-row-last form-row-quart-first form-row-quart' ).addClass( wpo_wcnlpc['field_classes'][form_name]['house_number'] );
			$suffixfield.removeClass( 'form-row-first form-row-last form-row-quart-first form-row-quart' ).addClass( wpo_wcnlpc['field_classes'][form_name]['house_number_suffix'] );

			// fix layout for order notes
			$('#order_comments_field').addClass( 'form-row-wide' );
			
			// moving postcode field (again) to override woocommerce i18n postcode_before_city js
			$postcodefield.insertBefore( $address1field );

			// below is a fallback, should be already set like this under normal circumstances
			if (country == 'NL') {
				$numberfield.insertAfter( $postcodefield );
				$suffixfield.insertAfter( $numberfield );
				$streetfield.insertBefore( $cityfield );

				// WC3.0 reorders using the priority attribute, so we set street to one less than the city field 
				// data-priority & data('priority') are ambiguously used by WooCommerce Checkout Field editor, we pick the minimum value...
				if ($cityfield.data('priority')) {
					var city_prio =  Math.min( $cityfield.data('priority'), $cityfield.attr('data-priority') );
					$streetfield.data('priority',city_prio-1).attr("data-priority", city_prio-1);
				}
				// number & suffix come after postcode
				if ($address1field.data('priority')) {
					var address1_prio = $address1field.data('priority');
					// var postcode_prio = Math.min( $postcodefield.data('priority'), $postcodefield.attr('data-priority') );
					$postcodefield.data('priority',address1_prio-3).attr("data-priority", address1_prio-3);
					$numberfield.data('priority',address1_prio-2).attr("data-priority", address1_prio-2);
					$suffixfield.data('priority',address1_prio-1).attr("data-priority", address1_prio-1);
				}
			} else {
				$numberfield.insertAfter( $streetfield );
				$suffixfield.insertAfter( $numberfield );
				$postcodefield.insertAfter( $suffixfield );

				// WC3.0 reorders using the priority attribute
				// Postcode comes after number & suffix
				if ( $address1field.data('priority') ) {
					var address1_prio = $address1field.data('priority');
					$numberfield.data('priority',address1_prio-3).attr("data-priority", address1_prio-3);
					$suffixfield.data('priority',address1_prio-2).attr("data-priority", address1_prio-2);
					$postcodefield.data('priority',address1_prio-1).attr("data-priority", address1_prio-1);
				}
	
				if (wpo_wcnlpc['checkout_layout'] == 'one_line') {
					$postcodefield.removeClass('form-row-first').addClass('form-row-last');
				}
			}

			$streetfield.removeClass( 'form-row-first form-row-last' ).addClass( wpo_wcnlpc['field_classes'][form_name]['street_name'] );
		} else {
			// make sure city is shown and not read only
			thisform.find('#billing_city_field, #shipping_city_field')
				.show()
				.find('input')
					.attr('readonly', false);

			// remove postcode checker fields!
			thisform.find('.wcnlpc-error, .wcnlpc-address, .wcnlpc-manual').remove();
		}

		// Reposition notices and errors if necessary
		$( '.wcnlpc-error, .wcnlpc-address, .wcnlpc-manual' ).each( function( index ) {
			let $position_before = $( '#'+$( this ).data( 'position_before' ) );
			if ( $position_before.length ) {
				$position_before.before( $( this ) );
			}
		});
	};

	// CHANGE: Declare function to trigger handle locale on `updated_checkout`.
	var trigger_handle_locale = function() {
		// Get postcode fields
		var $postcode_field = $( '#billing_postcode_field, #shipping_postcode_field' );

		// Iterate postcode fields
		$postcode_field.each( function( index ) {
			var wrapper = $( this ).closest( '.form-row' ).parent();
			var countryField = $( this ).closest( '.form-row' ).parent().find( '#billing_country, #shipping_country' );
			var country = countryField.val();

			// Trigger handle locale
			handle_locale( null, country, wrapper );
		});
	};

	$( document.body )
		// Handle locale
		// CHANGE: Call the extracted `handle_locale` function.
		.on( 'country_to_state_changing', handle_locale )

		// CHANGE: Trigger handle locale on `updated_checkout`.
		.on( 'updated_checkout', trigger_handle_locale )

		// Init trigger
		.on('init_checkout', function() {
			$('#billing_country, #shipping_country, .country_to_state').trigger('change');
		})

		// Make sure shipping addresses are correct when enabling
		.on( 'change', '#ship-to-different-address input', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '#shipping_postcode_field input' ).trigger( 'get_postcode' );
			}
		});

		$('#billing_country, #shipping_country, .country_to_state').trigger('change');

		let postcodeHouseNumberInputTimers = {};
		$( document ).on( 'keydown paste change get_postcode', '#billing_postcode_field input, #shipping_postcode_field input, #billing_house_number_field input, #shipping_house_number_field input, #billing_house_number_suffix_field input, #shipping_house_number_suffix_field input', function( event ) {
			let form = ( event.target.id.indexOf( 'billing' ) > -1 ) ? 'billing' : 'shipping';
			let duration = event.type == 'keydown' ? 1000 : 0;
			if ( form in postcodeHouseNumberInputTimers ) {
				clearTimeout( postcodeHouseNumberInputTimers[form] );
			}
			postcodeHouseNumberInputTimers[form] = setTimeout( function () { getPostcode( event ); }, duration );
		});

		let streetNameInputTimers = {};
		$( document ).on( 'keydown paste change recombine_pc_fields', '#billing_street_name_field input, #shipping_street_name_field input', function( event ) {
			let form = ( event.target.id.indexOf( 'billing' ) > -1 ) ? 'billing' : 'shipping';
			let duration = event.type == 'keydown' ? 250 : 0;
			if ( form in postcodeHouseNumberInputTimers ) {
				clearTimeout( streetNameInputTimers[form] );
			}
			streetNameInputTimers[form] = setTimeout( function () { recombinePcFields( event ); }, duration );
		});

		// Check on form init
		$( '#billing_postcode_field input' ).trigger( 'get_postcode' );
		if ( $( '#ship-to-different-address' ).find( 'input' ).is( ':checked' ) ) {
			$( '#shipping_postcode_field input' ).trigger( 'get_postcode' );
		}

		function getPostcode( event ){
			// MyParcel Delivery Options compatibility
			window.myparcel_checkout_updating = true; // prevent MyParcel from fetching data before we're finished

			let form_prefix = ( event.target.id.indexOf( 'billing' ) > -1 ) ? 'billing_' : 'shipping_';
			let form_parent	= $( '#' + event.target.id ).closest( '.form-row' ).parent();

			let country = $( '#'+form_prefix+'country' ).val();
			if ( country != 'NL' ) {
				return;
			};

			// temporarily disable fields when visibility set to 'Show'
			if (wpo_wcnlpc.street_city_visibility == 'show' || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
				block_fields( '#'+form_prefix+'street_name, #'+form_prefix+'city' );
			}

			let postcode            = $( '#'+form_prefix+'postcode' ).val();
			let house_number        = $( '#'+form_prefix+'house_number' ).val();
			let house_number_suffix = $( '#'+form_prefix+'house_number_suffix' ).val();

			// skip if disabled
			if ( $( form_parent ).hasClass('wpo_wcnlpc_disabled') ) {
				// set postcode & house number to validated
				$( form_parent ).find('#billing_postcode_field, #shipping_postcode_field, #billing_house_number_field, #shipping_house_number_field')
					.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' )
					.addClass( 'woocommerce-validated' );
					unblock_fields( '#'+form_prefix+'street_name, #'+form_prefix+'city' );
				return;
			}

			if ( typeof postcode == 'undefined' || typeof house_number == 'undefined' || ! (postcode.length > 0 && house_number.length > 0) ) {
				unblock_fields( '#'+form_prefix+'street_name, #'+form_prefix+'city' );
				return;
			};

			if ( $( form_parent ).find( '.wcnlpc-error' ).length < 1 ) {
				let $position_before = $( '#'+form_prefix+'postcode' ).closest( '.form-row' );
				let $error           = $( '<div class="wcnlpc-error" style="color: red;"></div>' ).data( 'position_before', $position_before.attr( 'id' ) )
				$position_before.before( $error );
			}

			let data = {
				security:            wpo_wcnlpc.nonce,
				postcode:            postcode,
				house_number:        house_number,
				house_number_suffix: house_number_suffix,
			};

			let context = {
				form_parent: form_parent,
				form_prefix: form_prefix
			}

			let cache_key = data.postcode + data.house_number + data.house_number_suffix;
			if ( typeof xhr_responses[cache_key] != 'undefined' ) {
				// we have already sent this exact request: process know result
				xhrSuccess( xhr_responses[cache_key], context );				
				return;
			}

			xhr_requests[form_prefix] = $.ajax({
				type:		'POST',
				url:		wpo_wcnlpc.ajaxurl+'?action=wpo_wcnlpc_api_request',
				data:		data,
				dataType:	'json',
				context:    context,
				timeout:	wpo_wcnlpc.xhr_timeout, // timeout, 8000ms by default 
				beforeSend : function()    {           
					if( typeof xhr_requests[this.form_prefix] != 'undefined' ) {
						xhr_requests[this.form_prefix].abort();
					}
					// remove classes indicating validation success
					$( '#'+this.form_prefix+'street_name_field, #'+this.form_prefix+'city_field' ).removeClass( 'wcnlpc-validated wcnlpc-not-found' );
				},
				success:	function( data, textStatus, jqXHR ) {
					xhrSuccess( data, this );
					xhr_responses[cache_key] = data;
					
					// show response error for admin users
					if ( wpo_wcnlpc.current_user_is_admin && false === data.success ) {
						let notice        = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><ul class="woocommerce-error" role="alert"><li><strong>'+wpo_wcnlpc.response_error_labels.message+':</strong> '+data.data.message+'</li><li><strong>'+wpo_wcnlpc.response_error_labels.error_code+':</strong> '+data.data.error_code+'</li></ul></div>';
						let checkout_form = $( 'body' ).find( 'form.checkout' );
						checkout_form.closest( '.woocommerce' ).find( '.woocommerce-NoticeGroup' ).remove();
						$( notice ).prependTo( checkout_form );
					}
				},
				error:		function( jqXHR, textStatus, errorThrown ) {
					if (textStatus != 'abort') {
						// no result (timeout or error) but still renable street & city fields
						if (wpo_wcnlpc.street_city_visibility == 'show' || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
							unblock_fields( '#'+this.form_prefix+'street_name, #'+this.form_prefix+'city' );
						}
						// MyParcel Delivery Options compatibility
						trigger_myparcel(this.form_prefix);
							
						// enable manual override
						if (wpo_wcnlpc.street_city_visibility == 'hide'  || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
							enable_fields(this.form_prefix);
						}						
					}
				}
			});
		};

		function xhrSuccess( response, context ) {
			// we have a result: unblock street & city fields (+remove spinner)
			if ( wpo_wcnlpc.street_city_visibility == 'show' || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
				unblock_fields( '#'+context.form_prefix+'street_name, #'+context.form_prefix+'city' );
			}
			// disable manual override
			if ( wpo_wcnlpc.street_city_visibility == 'hide'  || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
				disable_fields( context.form_prefix );
			}

			if ( ! response ) {
				return;  // nonce check failed
			}

			// skip if disabled meanwhile
			if ( $( context.form_parent ).hasClass( 'wpo_wcnlpc_disabled' ) ) {
				// set postcode & house number to validated
				$( context.form_parent ).find( '#billing_postcode_field, #shipping_postcode_field, #billing_house_number_field, #shipping_house_number_field' )
					.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' )
					.addClass( 'woocommerce-validated' );
				return;
			}

			if ( typeof response.success != 'undefined' && response.success === true ) {
				// remove error and set fields to validated
				$( context.form_parent ).find( '.wcnlpc-error, .wcnlpc-manual' ).remove();
				$( '#'+context.form_prefix+'postcode_field, #'+context.form_prefix+'house_number_field' )
					.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' )
					.addClass( 'woocommerce-validated' );

				$( '#'+context.form_prefix+'street_name' ).val(response.data.street);
				// trigger change event for validation
				$( '#'+context.form_prefix+'street_name' ).trigger( 'keydown' ).trigger( 'change' ); // WC doesn't respond to the change event if there was no keydown
				$( '#'+context.form_prefix+'city' ).val( response.data.city );
				$( '#'+context.form_prefix+'city' ).trigger( 'keydown' ).trigger( 'change' );

				// add classes indicating validation success
				$( '#'+context.form_prefix+'street_name_field, #'+context.form_prefix+'city_field' ).addClass( 'wcnlpc-validated' );

				// write resulting address if fields hidden
				if (wpo_wcnlpc.street_city_visibility == 'hide') {
					if ( $( context.form_parent ).find( '.wcnlpc-address' ).length < 1 ) {
						let $position_before = $( '#'+context.form_prefix+'street_name' ).closest( '.form-row' );
						let $address_text    = $( '<p class="wcnlpc-address" style="clear:both;"></p>' ).data( 'position_before', $position_before.attr( 'id' ) )
						$position_before.before( $address_text );
					}
					let address1 = getAddressOneData( context.form_prefix ); 
					$( context.form_parent ).find('.wcnlpc-address').html( address1+', '+response.data.city );
				}

				// MyParcel Delivery Options compatibility
				trigger_myparcel( context.form_prefix );
				
			} else if ( typeof response.success != 'undefined' && response.success === false ) {
				$( context.form_parent ).find( '.wcnlpc-address' ).remove();
				$( context.form_parent ).find( '.wcnlpc-error' ).html('');
				switch( response.data.error_code ) {
					case 'Postcode_Invalid':
						$( '#'+context.form_prefix+'postcode_field' ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						$( '#'+context.form_prefix+'postcode_field' ).parent().find( '.wcnlpc-error' ).html( response.data.message );
						break;
					case 'Number_Invalid':
						$( '#'+context.form_prefix+'house_number_field' ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						$( '#'+context.form_prefix+'postcode_field' ).parent().find( '.wcnlpc-error' ).html( response.data.message );
						break;
					case 'Address_Not_Found':
						$( '#'+context.form_prefix+'postcode_field' ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						$( '#'+context.form_prefix+'house_number_field' ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						// $( '#'+context.form_prefix+'postcode_field' ).parent().find( '.wcnlpc-error' ).html( response.data.message );
						break;
					case 'Connection_Error':
					case 'undefined':
						// No API response. Enable manual but don't mark fields red (we don't know if it's wrong)
						$( '#'+context.form_prefix+'postcode_field, #'+context.form_prefix+'house_number_field' )
							.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' )
							.addClass( 'woocommerce-validated' );
						break;
					default:
						$( '#'+context.form_prefix+'postcode_field' ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						$( '#'+context.form_prefix+'house_number_field' ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						$( '#'+context.form_prefix+'postcode_field' ).parent().find( '.wcnlpc-error' ).html( response.data.message );
						break;
				}

				// add classes indicating validation error
				$( '#'+context.form_prefix+'street_name_field, #'+context.form_prefix+'city_field' ).addClass( 'wcnlpc-not-found' );

				// MyParcel Delivery Options compatibility
				trigger_myparcel( context.form_prefix );
				
				// Allow manual override
				if ( response.data.error_code != 'Postcode_Invalid' && response.data.error_code != 'Number_Invalid') {
					if ( $( context.form_parent ).find( '.wcnlpc-manual' ).length < 1 ) {
						let $position_before = $( '#'+context.form_prefix+'street_name' ).closest( '.form-row' );
						let $manual_text     = $( '<p class="wcnlpc-manual" style="clear:both"></p>' ).data( 'position_before', $position_before.attr( 'id' ) )
						$position_before.before( $manual_text );
					}
					$( context.form_parent ).find( '.wcnlpc-manual ').html( wpo_wcnlpc.manual );
					enable_fields(context.form_prefix);
				} else {
					$( context.form_parent ).find( '.wcnlpc-manual' ).remove();
					if (wpo_wcnlpc.street_city_visibility == 'hide'  || wpo_wcnlpc.street_city_visibility == 'readonly' ) {
						disable_fields(context.form_prefix);
					}
				}
				
			} else {
				// unknown error
				$( context.form_parent ).find( '.wcnlpc-address' ).remove();
				$( context.form_parent ).find( '.wcnlpc-error' ).html( '' );
				$( '#'+context.form_prefix+'postcode_field, #'+context.form_prefix+'house_number_field' )
					.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' )
					.addClass( 'woocommerce-validated' );
				// $( form_row ).removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
				// $( context.form_parent ).find( '.wcnlpc-error' ).html( 'Ongeldige postcode / huisnummer combinatie' );

			}
			// allow other scripts to hook in
			$( 'body' ).trigger( 'wpo_wcnlpc_fields_updated' );
		}

		// block checkout fields
		function block_fields( selectors ) {
			// selectors = selectors.join(',');
			if ( wpo_wcnlpc.street_city_visibility == 'show' ) {
				$( selectors ).attr('readonly', true);
			}
			$( selectors ).addClass('wcnlpc_spinner');
		}
		function unblock_fields( selectors ) {
			// selectors = selectors.join(',');
			if ( wpo_wcnlpc.street_city_visibility == 'show' ) {
				$( selectors ).attr('readonly', false);
			}
			$( selectors ).removeClass('wcnlpc_spinner');
		}

		// Enable manual address entry
		function enable_fields( form_prefix ) {
			// disable postcode checker
			// thisform.addClass('wpo_wcnlpc_disabled');

			// show street name & city fields
			$('#'+form_prefix+'street_name_field, #'+form_prefix+'city_field')
				.show()
				.find('input')
					.val('')
					.attr('readonly', false);
			
			// set postcode & house number to validated
			// $('#'+form_prefix+'postcode_field, #'+form_prefix+'house_number_field')
			// 	.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' )
			// 	.addClass( 'woocommerce-validated' );
		}

		// Disable manual address entry
		function disable_fields( form_prefix ) {
			// enable postcode checker
			// thisform.removeClass('wpo_wcnlpc_disabled');

			// show street name & city fields
			$('#'+form_prefix+'street_name_field, #'+form_prefix+'city_field')
				.find('input')
					.attr('readonly', true);
			
			if (wpo_wcnlpc.street_city_visibility == 'hide') {
				$('#'+form_prefix+'street_name_field, #'+form_prefix+'city_field')
					.hide();
			}
		}

		// Recombine street name, house number and suffix
		function recombinePcFields( event ) {
			let form_prefix = ( event.target.id.indexOf( 'billing' ) > -1 ) ? 'billing_' : 'shipping_';
			let address1 = getAddressOneData( form_prefix );
			$( '#' + form_prefix + 'address_1' ).val( address1 );
		}

		function getAddressOneData( form_prefix ) {
			let street_name         = $( '#' + form_prefix + 'street_name' ).val();
			let house_number        = $( '#' + form_prefix + 'house_number' ).val();
			let house_number_suffix = $( '#' + form_prefix + 'house_number_suffix' ).val();

			let address1 = street_name + ' ' + house_number ;
			if ( house_number_suffix ) {
				address1 = address1 + '-' + house_number_suffix;
			}
			return address1;
		}

		function trigger_myparcel(form_prefix) {
			window.myparcel_checkout_updating = false; // update done
			if ( typeof window.update_myparcel_settings !== "undefined" ) {
				window.update_myparcel_settings();
			} else if ( ( typeof wc_myparcel_frontend !== "undefined" && typeof wc_myparcel_frontend.isUsingSplitAddressFields !== "undefined" )
				||  ( typeof wcmp_display_settings !== "undefined" && typeof wcmp_display_settings.isUsingSplitAddressFields !== "undefined" ) ) {
				// MyParcel 3.0.5+
				$( '#'+form_prefix+'address_1' ).trigger("input");
			} else if ( typeof MyParcelFrontend !== "undefined" ) {
				if ( MyParcelFrontend.hasSplitAddressFields() ) {
					// MyParcel 4.20.2+
					MyParcelFrontend.updateAddress();
				}
			}
		}
});
