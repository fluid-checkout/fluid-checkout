var wcev_is_init = true;
var wcev_current_business_type; //business || consumer
var wcev_wcmca_field_prefix = "wcmca_";
var wcev_user_requested_invoice = true;
var wcev_current_country_code;
jQuery(document).ready(function()
{
	var wcmca_billing_country = typeof wcmca_ajax_url !== 'undefined' ? "#wcmca_billing_country" : "";
	var wcmca_billing_request_eu_vat = typeof wcmca_ajax_url !== 'undefined' ? "#billing_request_eu_vat" : "";
	
	wcev_user_requested_invoice = wcev.show_eu_vat_field_only_when_the_user_requests_an_invoice == 'true' ? false : true;
	
	jQuery(document).on('change','#billing_country',wcev_on_new_billing_country_selection);
	jQuery(document).on('change','#billing_request_eu_vat',wcev_show_ev_vat_field_on_request_checkobox_click);
	jQuery(document).on('change','#billing_business_consumer_selector',wcev_on_business_type_selection);
	
	//WCMCA - Why it was used? if(typeof wcmca_ajax_url !== 'undefined')
	{
		jQuery(document).on('change','#wcmca_billing_country',wcev_on_new_wcmca_billing_country_selection);
		jQuery(document).on('change','#wcmca_billing_request_eu_vat',wcev_show_wcmca_ev_vat_field_on_request_checkobox_click);
		jQuery(document).on('change','#wcmca_billing_business_consumer_selector',wcev_on_wcmca_business_type_selection);
		jQuery(document).on('click', '#wcmca_add_new_address_button_billing, .wcmca_edit_address_button', wcev_wcmca_show_fields_according_to_the_loaded_business_type);
	}
	
	wcev_current_business_type = wcev.consumer_selector_active == 'true' ? 'consumer' : 'business';
	
	// CHANGE: Perform initialization on `updated_checkout` event.
	wcev_init();
	jQuery( 'body' ).on( 'updated_checkout', wcev_init );
});
// CHANGE: Move field initialization to a separate function.
function wcev_init() 
{
	if(jQuery('#billing_business_consumer_selector').length != 0)
		// CHANGE: Call the function directly instead of triggering change.
		wcev_on_business_type_selection({ currentTarget: jQuery('#billing_business_consumer_selector') }, false);
	else 
		wcev_on_new_billing_country_selection(true);
	jQuery('.wcev_disable_field').attr('tabindex', -1);

	// CHANGE: If `billing_request_eu_vat` checkbox exists, show the EU VAT field based on its state.
	var billing_request_eu_vat = jQuery('#billing_request_eu_vat');
	if (billing_request_eu_vat.length > 0) {
		wcev_show_ev_vat_field_on_request_checkobox_click(true);
	}
}
function wcev_wcmca_show_fields_according_to_the_loaded_business_type(event)
{
	if(jQuery('#'+wcev_wcmca_field_prefix+'billing_business_consumer_selector').length == 0)
		return;
	wcev_current_business_type = jQuery('#'+wcev_wcmca_field_prefix+'billing_business_consumer_selector').val();
	var country_code = wcev_get_current_selected_billing_country(wcev_wcmca_field_prefix);
	wcev_current_country_code = country_code;
	wcev_show_eu_vat_fields(wcev_is_eu(country_code), null, wcev_wcmca_field_prefix);
}
// CHANGE: Add `reset_request_invoice_checkbox` parameter to the function.
function wcev_on_business_type_selection(event, reset_request_invoice_checkbox = true)
{
	if(wcev_current_business_type == jQuery(event.currentTarget).val() && !wcev_is_init)
	{
		wcev_is_init = false;
		return ;
	}
	
	wcev_current_business_type = jQuery(event.currentTarget).val();
	var country_code = wcev_get_current_selected_billing_country("");
	wcev_current_country_code = country_code;
	
	// CHANGE: Add condition to reset the request invoice checkbox.
	if (reset_request_invoice_checkbox) {
		wcev_reset_request_invoice_checkbox("");
	}

	if(wcev.show_eu_vat_field_only_when_the_user_requests_an_invoice == 'true')
		wcev_show_eu_vat_fields(wcev_is_eu(country_code), '#billing_request_eu_vat_field', "");
	else	
		wcev_show_eu_vat_fields(wcev_is_eu(country_code), null, "");
}
function wcev_on_wcmca_business_type_selection(event)
{
	wcev_current_business_type = jQuery(event.currentTarget).val();
	var country_code = wcev_get_current_selected_billing_country(wcev_wcmca_field_prefix);
	wcev_current_country_code = country_code;
	
	wcev_reset_request_invoice_checkbox(wcev_wcmca_field_prefix);
	if(wcev.show_eu_vat_field_only_when_the_user_requests_an_invoice == 'true')
		wcev_show_eu_vat_fields(wcev_is_eu(country_code), '#'+wcev_wcmca_field_prefix+'billing_request_eu_vat_field', wcev_wcmca_field_prefix);
	else	
		wcev_show_eu_vat_fields(wcev_is_eu(country_code), null, wcev_wcmca_field_prefix);
}
function wcev_on_new_wcmca_billing_country_selection(event)
{
	wcev_on_new_billing_country_selection(null)
}
function wcev_show_wcmca_ev_vat_field_on_request_checkobox_click(event)
{
	wcev_show_ev_vat_field_on_request_checkobox_click(null);
}
function wcev_reset_request_invoice_checkbox(prefix)
{
	if(jQuery('#'+prefix+'billing_request_eu_vat').length != 0)
	{
		wcev_user_requested_invoice = false;
		jQuery('#'+prefix+'billing_request_eu_vat').prop('checked',false);
	}
}

function wcev_on_new_billing_country_selection(event)
{	
	var wcma_field_prefix = event == null ? wcev_wcmca_field_prefix : "";
	var country_code = wcev_get_current_selected_billing_country(wcma_field_prefix);
	wcev_current_country_code = country_code;
	
	//reset
	if(wcev.is_checkout == 'true')
	{
		if(!wcev_is_eu(country_code))
			wcev_reset_request_invoice_checkbox(wcma_field_prefix);
		
	}
	
	if(wcev.show_eu_vat_field_only_when_the_user_requests_an_invoice == 'true' && !jQuery('#'+wcma_field_prefix+'billing_request_eu_vat').prop('checked'))
	{
		//hides eu vat field
		wcev_show_eu_vat_fields(wcev_is_eu(country_code), '#'+wcma_field_prefix+'billing_request_eu_vat_field', wcma_field_prefix);
	}
	else	
		wcev_show_eu_vat_fields(wcev_is_eu(country_code), null, wcma_field_prefix);
	
	//init
	if(wcev_is_init || event == true)
	{
		wcev_is_init = false;
		wcev_show_ev_vat_field_on_request_checkobox_click(event);
	}
}
function wcev_show_ev_vat_field_on_request_checkobox_click(event)
{
	var wcma_field_prefix = event == null ? wcev_wcmca_field_prefix : "";
	if(wcev.show_require_invoice_option == 'false')
		return;
	
	wcev_user_requested_invoice = jQuery('#'+wcma_field_prefix+'billing_request_eu_vat').prop('checked')/*  ? true : false */;
	if(wcev.show_eu_vat_field_only_when_the_user_requests_an_invoice == 'true')
		wcev_show_eu_vat_fields(wcev_user_requested_invoice, '#'+wcma_field_prefix+'billing_eu_vat_field', wcma_field_prefix);
	wcev_assign_required(wcma_field_prefix);
}
function wcev_show_eu_vat_fields(show, css_selectors, wcma_field_prefix)
{
	let show_only_invoice_checkbox = css_selectors === '#'+wcma_field_prefix+'billing_request_eu_vat_field' ? true : false;
	let selectors = typeof css_selectors === 'undefined' || css_selectors == null ? '#'+wcma_field_prefix+'billing_request_eu_vat_field, #'+wcma_field_prefix+'billing_eu_vat_field' : css_selectors;
	let sub_selectors = selectors.replace(/_field/g, ''); //??
	const vat_field_can_be_rendered = show && 
									wcev_current_business_type == 'business' && 
									(show_only_invoice_checkbox || wcev_user_requested_invoice || wcev.show_require_invoice_option == 'false' || wcev.show_eu_vat_field_only_when_the_user_requests_an_invoice == 'false');
	
	//Example: italy sid/pec field
	selectors = wcev_manage_additional_country_fields(selectors, vat_field_can_be_rendered, wcma_field_prefix);
		
	if(css_selectors == '#'+wcma_field_prefix+'billing_request_eu_vat_field')
	{
		jQuery('#'+wcma_field_prefix+'billing_eu_vat_field').hide();
	}
	
	if(wcev.show_company_name_field_only_when_the_user_requests_an_invoice == 'true')
	{
		
		jQuery('#'+wcma_field_prefix+'billing_company_field').hide();
		if(!show_only_invoice_checkbox)
			selectors += ', #'+wcma_field_prefix+'billing_company_field';
	}
	
	if(vat_field_can_be_rendered)
	{ 
		jQuery(selectors).show();
		jQuery(sub_selectors).prop('disabled', false);
		jQuery(sub_selectors).removeProp('disabled'); 
	}
	else
	{ 
		jQuery(selectors).hide();
		jQuery(sub_selectors).prop('disabled', true); //?? avoid data to be sent when posting the form?
	}
	
	//Force the display of the "Request invoice" option even for consumer
	if(show_only_invoice_checkbox && wcev.always_show_require_invoice_option == 'true')
	{
		jQuery('#'+wcma_field_prefix+'billing_request_eu_vat_field').show();
		jQuery(sub_selectors).prop('disabled', false);
		jQuery(sub_selectors).removeProp('disabled'); 
	}
	
	//Consumer/business selector managment 
	if(wcev.show_company_name_field_only_when_the_user_requests_an_invoice != 'true')
		switch(wcev_current_business_type)
		{
			case 'consumer': jQuery('#'+wcma_field_prefix+'billing_company_field').hide(); break;
			case 'business': jQuery('#'+wcma_field_prefix+'billing_company_field').show(); break;
		}
	
	//UI
	wcev_assign_required(wcma_field_prefix);
	
	// CHANGE: Remove 'update_checkout' event trigger to avoid endless loop.
}
function wcev_manage_additional_country_fields(selectors, vat_field_can_be_rendered, wcma_field_prefix)
{
	//In case of "Italy", if the plugin is going extra fields like SDI and Codice fiscale fields
	if(wcev_current_country_code == 'IT')
	{
		//SDI field visible only for business
		selectors = selectors.includes("billing_eu_vat_field") ?  selectors+", #"+wcma_field_prefix+"billing_it_sid_pec_field": selectors;
		
		//Codice fiscale field visible for both consumer and business users
		jQuery('#'+wcma_field_prefix+'billing_it_codice_fiscale_field').show(); 
	}
	else //if selected country is not italy or the vat is not showed, the field is always hidden
	{
		jQuery('#'+wcma_field_prefix+'billing_it_sid_pec_field, #'+wcma_field_prefix+'billing_it_codice_fiscale_field').hide(); 
	}
	
	//Greece
	if(wcev_current_country_code == 'GR')
	{
		selectors = selectors.includes("billing_eu_vat_field") ?  selectors+", #"+wcma_field_prefix+"billing_gr_tax_office_field, #"+wcma_field_prefix+"billing_gr_business_activity_field": selectors;
	}
	else //if selected country is not italy or the vat is not showed, the field is always hidden
	{
		jQuery('#'+wcma_field_prefix+'billing_gr_tax_office_field, #'+wcma_field_prefix+'billing_gr_business_activity_field').hide(); 
	}
	
	//Spain
	if(wcev_current_country_code == 'ES')
	{
		jQuery('#'+wcma_field_prefix+'billing_es_nif_nie_field').show(); 
	}
	else 
	{
		jQuery('#'+wcma_field_prefix+'billing_es_nif_nie_field').hide(); 
	}
	
	//Slovakia
	if(wcev_current_country_code == 'SK')
	{
		//Company ID field visible only for business
		selectors = selectors.includes("billing_eu_vat_field") ?  selectors+", #"+wcma_field_prefix+"billing_sk_company_id_field, #"+wcma_field_prefix+"billing_sk_company_dic_field": selectors;
	}
	else 
	{
		jQuery('#'+wcma_field_prefix+'billing_sk_company_id_field, #'+wcma_field_prefix+'billing_sk_company_dic_field').hide(); 
	}
	//Czech Republic
	if(wcev_current_country_code == 'CZ')
	{
		//Company ID field visible only for business
		selectors = selectors.includes("billing_eu_vat_field") ?  selectors+", #"+wcma_field_prefix+"billing_cz_company_id_field": selectors;
	}
	else 
	{
		jQuery('#'+wcma_field_prefix+'billing_cz_company_id_field').hide(); 
	}
	
	return selectors;
}
//Common
function wcev_is_eu(country_code)
{
	//CH, HR, RS ?
	if(wcev.always_show_the_field == 'true')
		return true;
	
	var eu_countrycodes = wcev.eu_country_codes;
	/* [
			'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL', 'GR',
					'ES', 'FI', 'FR', 'GB', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV',
					'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
		];  */
		
	if(wcev.eu_countries_to_exclude.length != 0)
	{
		for(var i = 0; i < wcev.eu_countries_to_exclude.length; i++)
		{
			var index = eu_countrycodes.indexOf(wcev.eu_countries_to_exclude[i]);
			//console.log(wcev.eu_countries_to_exclude[i]);
			if (index >= 0) 
			  eu_countrycodes.splice( index, 1 );
		}
	}	

	return jQuery.inArray(country_code, eu_countrycodes) == -1 ? false : true;
}
function wcev_assign_required(wcma_field_prefix)
{
	var fields = ['billing_company_field', 'billing_eu_vat_field' ]
	var is_required = false;
	for(var i=0; i<fields.length; i++)
	{
		if(fields[i] == 'billing_company_field')
		{
			is_required = wcev.billing_company_is_required == 'true';
		}
		else 
			is_required = wcev.is_required == 'true' || (wcev.show_require_invoice_option == 'true'  && wcev.requested_invoice_not_required == 'false' && jQuery('#'+wcma_field_prefix+'billing_request_eu_vat').prop('checked'));;
		
		//if not visible, will not be required 
		is_required = jQuery("#"+wcma_field_prefix+fields[i]).is(":visible") ? is_required : false;
		
		
		if(is_required)
		{
				jQuery("#"+wcma_field_prefix+fields[i]).addClass('validate-required validate-wcev-eu-vat');
				jQuery("#"+wcma_field_prefix+fields[i]).removeClass('woocommerce-validated');
				jQuery("#"+wcma_field_prefix+fields[i]+" label .required").remove(); //To be sure that just one * is present
				jQuery("#"+wcma_field_prefix+fields[i]+" label").append('<abbr class="required" title="required">*</abbr>');
				jQuery("#"+wcma_field_prefix+fields[i]+" label span.optional").remove();
		}
		else
		{
			jQuery("#"+wcma_field_prefix+fields[i]).removeClass('validate-required validate-wcev-eu-vat');
			jQuery("#"+wcma_field_prefix+fields[i]).addClass('woocommerce-validated');
			jQuery("#"+wcma_field_prefix+fields[i]+" label .required").remove();
		}
	}
}
function wcev_get_current_selected_billing_country(wcma_field_prefix)
{
	return jQuery("#"+wcma_field_prefix+"billing_country").val();
}