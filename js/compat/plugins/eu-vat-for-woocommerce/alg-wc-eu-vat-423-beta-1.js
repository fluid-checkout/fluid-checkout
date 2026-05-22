/**
 * alg-wc-eu-vat.js
 *
 * @version 1.6.1
 * @since   1.0.0
 * @author  WPFactory
 * @todo    [dev] replace `billing_eu_vat_number` and `billing_eu_vat_number_field` with `alg_wc_eu_vat_get_field_id()`
 * @todo    [dev] customizable event for `billing_company` (currently `input`; could be e.g. `change`)
 */

jQuery( function( $ ) {

	// CHANGE: Add settings object with selectors
	var _settings = {
		vat_input_selector: 'input[name="billing_eu_vat_number"]',
		billing_company_selector: 'input[name="billing_company"]',
		billing_country_selector: 'select[name="billing_country"]',
		shipping_country_selector: 'select[name="shipping_country"]',
		ship_different_address_selector: '#ship-to-different-address',
		vat_paragraph_selector: 'p[id="billing_eu_vat_number_field"]',
		vat_input_label_selector: 'label[for="billing_eu_vat_number"]',
		vat_input_customer_choice_selector: 'input[name="billing_eu_vat_number_customer_decide"]',
		vat_input_belgium_compatibility_selector: 'input[name="billing_eu_vat_number_belgium_compatibility"]',
		progress_text_selector: 'div[id="alg_wc_eu_vat_progress"]',
	}

	// Setup before functions
	var input_timer;                                                      // timer identifier
	var input_timer_company_require;                                      // timer identifier (company require)
	var input_timer_company_load;                                      // timer identifier (company require)
	var input_timer_company;                                              // timer identifier (company)
	var done_input_interval = 1000;                                       // time in ms
	// CHANGE: Remove variables for elements that should be dynamically captured
	var var_belgium_compatibility = 'no';
	// CHANGE: Remove variables for elements that should be dynamically captured

	// CHANGE: Extract function to add the progress text element
	var add_progress_text_element = function() {
		// Bail if progress text already exists
		if ( $( _settings.progress_text_selector ).length > 0 ) { return; }

		// Add progress text
		if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
			// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
			$( _settings.vat_paragraph_selector ).append( '<div id="alg_wc_eu_vat_progress"></div>' );
		}
	}
	add_progress_text_element();
	// CHANGE: Use captured event handlers to allow for dynamic DOM changes
	$( 'body' ).on( 'updated_checkout', add_progress_text_element );
	
	if ( 'yes_for_company' == alg_wc_eu_vat_ajax_object.is_required ) {
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		$( _settings.billing_company_selector ).blur(function(){
		  // vat_input_billing_country.change();
		  is_company_name_not_empty();
		  // $( 'body' ).trigger( 'update_checkout' );
		});
		
		// CHANGE: Use captured event handlers to allow for dynamic DOM changes
		$( 'body' ).on( 'input', _settings.billing_company_selector, function() {
			clearTimeout( input_timer_company_require );
			input_timer_company_require = setTimeout( alg_wc_eu_vat_require_on_company_fill, done_input_interval );
		});
		
		clearTimeout( input_timer_company_load );
		input_timer_company_load = setTimeout( alg_wc_eu_vat_require_on_company_fill, done_input_interval );
	}

	// Initial validate
	alg_wc_eu_vat_validate_vat();

	// On input, start the countdown
	// CHANGE: Use captured event handlers to allow for dynamic DOM changes
	$( 'body' ).on( 'input', _settings.vat_input_selector, function() {
		clearTimeout( input_timer );
		input_timer = setTimeout( alg_wc_eu_vat_validate_vat, done_input_interval );
	} );
	

	// On country change - re-validate
	// CHANGE: Use captured event handlers to allow for dynamic DOM changes
	$( 'body' ).on( 'change', _settings.billing_country_selector, alg_wc_eu_vat_validate_vat );
	$( 'body' ).on( 'change', _settings.shipping_country_selector, alg_wc_eu_vat_validate_vat );
	$( 'body' ).on( 'click', _settings.ship_different_address_selector, alg_wc_eu_vat_validate_vat );

	// Company name - re-validate
	if ( alg_wc_eu_vat_ajax_object.do_check_company_name ) {
		// CHANGE: Use captured event handlers to allow for dynamic DOM changes
		$( 'body' ).on( 'input', _settings.billing_company_selector, function() {
			clearTimeout( input_timer_company );
			input_timer_company = setTimeout( alg_wc_eu_vat_validate_vat, done_input_interval );
		} );
	}
	

	// CHANGE: Use captured event handlers to allow for dynamic DOM changes
	$( 'body' ).on( 'change', _settings.vat_input_customer_choice_selector, function() {
		alg_wc_eu_vat_validate_vat();
	});
	
	// CHANGE: Use captured event handlers to allow for dynamic DOM changes
	$( 'body' ).on( 'change', _settings.vat_input_belgium_compatibility_selector, function() {
		alg_wc_eu_vat_validate_vat();
	});
	
	function alg_wc_eu_vat_require_on_company_fill() {
		// vat_input_billing_country.change();
		is_company_name_not_empty();
		// $( 'body' ).trigger( 'update_checkout' );
	}
	
	function is_company_name_not_empty(){
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		var _vat_paragraph = $( _settings.vat_paragraph_selector );
		var _vat_input = $( _settings.vat_input_selector );
		var _vat_input_label = $( _settings.vat_input_label_selector );
		
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		if('' != $( _settings.billing_company_selector ).val()){
			_vat_paragraph.removeClass( 'woocommerce-invalid' );
			_vat_paragraph.removeClass( 'woocommerce-validated' );
			_vat_paragraph.addClass( 'validate-required' );
			_vat_input.addClass('field-required');
			_vat_input_label.find("span.optional").remove();
			_vat_input_label.find("abbr").remove();
			_vat_input_label.append('<abbr class="required" title="required">*</abbr>');
			// vat_paragraph.show();
		}else{
			_vat_paragraph.removeClass( 'woocommerce-invalid' );
			_vat_paragraph.removeClass( 'woocommerce-validated' );
			_vat_paragraph.removeClass( 'validate-required' );
			_vat_input.removeClass('field-required');
			_vat_input_label.find("abbr").hide();
			_vat_input_label.find("span.optional").remove();
			_vat_input_label.append('<span class="optional">(optional)</span>');
			// vat_paragraph.hide();
		}
		// CHANGE: END - Dynamically get elements from page because it might have been replaced with AJAX.
	}
	/**
	 * alg_wc_eu_vat_validate_vat
	 *
	 * @version 2.9.8
	 * @since   1.0.0
	 */
	function alg_wc_eu_vat_validate_vat() {
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		var _vat_paragraph = $( _settings.vat_paragraph_selector );
		var _vat_input = $( _settings.vat_input_selector );
		var _vat_input_label = $( _settings.vat_input_label_selector );
		var _progress_text = $( _settings.progress_text_selector );
		
		if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
			if( 'yes' == alg_wc_eu_vat_ajax_object.hide_message_on_preserved_countries ){
				if(alg_wc_eu_vat_ajax_object.preserve_countries.length > 0){
					// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
					if(jQuery.inArray( $( _settings.billing_country_selector ).val(), alg_wc_eu_vat_ajax_object.preserve_countries ) >= 0){
						// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
						_progress_text.hide();
					}else{
						// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
						_progress_text.show();
					}
				}
			}
		}
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		if($( _settings.vat_input_customer_choice_selector ).length > 0){
			if ($( _settings.vat_input_customer_choice_selector ).is(':checked')) {
				_vat_paragraph.removeClass( 'woocommerce-invalid' );
				_vat_paragraph.removeClass( 'woocommerce-validated' );
				_vat_paragraph.removeClass( 'validate-required' );
				_vat_input.removeClass('field-required');
				_vat_input_label.find("abbr").hide();
				_vat_input_label.find("span.optional").remove();
				_vat_input_label.append('<span class="optional">(optional)</span>');
				_vat_paragraph.hide();
				return;
			}else{
				_vat_paragraph.removeClass( 'woocommerce-invalid' );
				_vat_paragraph.removeClass( 'woocommerce-validated' );
				_vat_paragraph.addClass( 'validate-required' );
				_vat_input.addClass('field-required');
				_vat_input_label.find("span.optional").remove();
				_vat_input_label.find("abbr").show();
				_vat_paragraph.show();
			}
		}
		// CHANGE: END - Dynamically get elements from page because it might have been replaced with AJAX.
		
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		if($( _settings.vat_input_belgium_compatibility_selector ).length > 0){
			if ($( _settings.vat_input_belgium_compatibility_selector ).is(':checked')) {
				var_belgium_compatibility = 'yes';
			}else{
				var_belgium_compatibility = 'no';
			}
		}
		
		_vat_paragraph.removeClass( 'woocommerce-invalid' );
		_vat_paragraph.removeClass( 'woocommerce-validated' );
		_vat_paragraph.removeClass( 'woocommerce-invalid-mismatch' );
		
		// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
		var vat_number_to_check = $( _settings.vat_input_selector ).val();
		
		if(vat_number_to_check === ''){
			vat_number_to_check = undefined;
		}
		if ( undefined != vat_number_to_check ) {
			// Validating EU VAT Number through AJAX call
			if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
				_progress_text.text( alg_wc_eu_vat_ajax_object.progress_text_validating );
				_progress_text.removeClass();
				_progress_text.addClass( 'alg-wc-eu-vat-validating' );
			}
			var data = {
				'action': 'alg_wc_eu_vat_validate_action',
				'alg_wc_eu_vat_to_check': vat_number_to_check,
				'alg_wc_eu_vat_belgium_compatibility': var_belgium_compatibility,
				// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
				'billing_country': $( _settings.billing_country_selector ).val(),
				'shipping_country': $( _settings.shipping_country_selector ).val(),
				'billing_company': $( _settings.billing_company_selector ).val(),
			};
			$.ajax( {
				type: "POST",
				url: alg_wc_eu_vat_ajax_object.ajax_url,
				data: data,
				success: function( response ) {
					// CHANGE: Dynamically get elements from page because it might have been replaced with AJAX.
					var _vat_paragraph = $( _settings.vat_paragraph_selector );
					var _progress_text = $( _settings.progress_text_selector );

					response = response.replace("</pre>", "");
					response = response.trim();
					var splt = response.split("|");
					response = splt[0];
					
					if ( '1' == response ) {
						_vat_paragraph.addClass( 'woocommerce-validated' );
						if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
							_progress_text.text( alg_wc_eu_vat_ajax_object.progress_text_valid );
							_progress_text.removeClass();
							_progress_text.addClass( 'alg-wc-eu-vat-valid' );
						}
					} else if ( '0' == response ) {
						_vat_paragraph.addClass( 'woocommerce-invalid' );
						if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
							_progress_text.text( alg_wc_eu_vat_ajax_object.progress_text_not_valid );
							_progress_text.removeClass();
							_progress_text.addClass( 'alg-wc-eu-vat-not-valid' );
						}
					} else if ( '4' == response ) {
						_vat_paragraph.addClass( 'woocommerce-invalid' );
						if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
							_progress_text.text( alg_wc_eu_vat_ajax_object.text_shipping_billing_countries );
							_progress_text.removeClass();
							_progress_text.addClass( 'alg-wc-eu-vat-not-valid-billing-country' );
						}
					} else if ( '5' == response ) {
						var com = splt[1];
						_vat_paragraph.addClass( 'woocommerce-invalid' );
						_vat_paragraph.addClass( 'woocommerce-invalid-mismatch' );
						if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
							_progress_text.text( alg_wc_eu_vat_ajax_object.company_name_mismatch.replace("%company_name%", com) );
							_progress_text.removeClass();
							_progress_text.addClass( 'alg-wc-eu-vat-not-valid-company-mismatch' );
						}
					} else if ( '6' == response ) {
						_vat_paragraph.removeClass( 'woocommerce-invalid' );
						_vat_paragraph.removeClass( 'woocommerce-validated' );
						if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
							_progress_text.text( alg_wc_eu_vat_ajax_object.progress_text_validation_failed );
							_progress_text.removeClass();
							_progress_text.addClass( 'alg-wc-eu-vat-validation-failed' );
						}
					} else {
						_vat_paragraph.addClass( 'woocommerce-invalid' );
						if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
							_progress_text.text( alg_wc_eu_vat_ajax_object.progress_text_validation_failed );
							_progress_text.removeClass();
							_progress_text.addClass( 'alg-wc-eu-vat-validation-failed' );
						}
					}
					$( 'body' ).trigger( 'update_checkout' );
				},
			} );
		} else {
			// VAT input is empty
			if ( 'yes' == alg_wc_eu_vat_ajax_object.add_progress_text ) {
				_progress_text.text( '' );
			}
			if ( _vat_paragraph.hasClass( 'validate-required' ) ) {
				// Required
				_vat_paragraph.addClass( 'woocommerce-invalid' );
			} else {
				// Not required
				_vat_paragraph.addClass( 'woocommerce-validated' );
			}
			$( 'body' ).trigger( 'update_checkout' );
		}
	};
} );
