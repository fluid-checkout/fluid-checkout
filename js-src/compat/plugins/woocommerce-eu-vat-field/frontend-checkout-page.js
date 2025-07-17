let wcev_default_border_color = "";
let wcev_default_label_color = "";
jQuery(document).ready(function()
{
	// Replace "change" event with "keydown" for quicker validation.
	jQuery(document).on('keydown','#billing_eu_vat',wcev_validate_field_and_remove_tax);
	jQuery(document).on('keydown','#billing_it_sid_pec, #billing_it_codice_fiscale, #billing_es_nif_nie', wcev_force_validation);
	
	//For now it has been disabled because it is not possible to recognize the cause validation "failure" (invalit vat, invalid sid, any other field validation fail...)
	//jQuery(document).on('updated_checkout', wcev_vat_field_visual_validation_feedback); //triggered after "update_order_review" event has been handled by the "woocommerce\assets\frontend\js\checkout.js .> update_checkout_action:" script
	
	wcev_default_label_color = jQuery('label').css('color');
	wcev_default_border_color = jQuery('#billing_eu_vat').css('border-color');
	
	// CHANGE: Remove `selectWoo` method call on the `billing_business_consumer_selector` field as the field re-initalization is handled on `update_checkout` event.
	
	// CHANGE: Remove `wcev_reset_fields` function call to prevent overriding the checkbox state from session.
});
function wcev_force_validation(event)
{
	jQuery( 'body' ).trigger( 'update_checkout' );
}
function wcev_vat_field_visual_validation_feedback(event, data)
{
	if(data.result == 'success')
	{
		jQuery('#billing_eu_vat').css({'border-color': '#69bf29'});
		jQuery('#billing_eu_vat_field label').css({'color': wcev_default_label_color});
	}
	else 
	{
		jQuery('#billing_eu_vat').css({'border-color': '#a00'});
		jQuery('#billing_eu_vat_field label').css({'color': '#a00'});
	}
}
function wcev_reset_fields()
{
	if(jQuery('#billing_request_eu_vat').length > 0)
		jQuery('#billing_request_eu_vat').prop('checked', false);
}
function wcev_validate_field_and_remove_tax(event)
{
	//if(jQuery('#billing_eu_vat').val() != "")
		jQuery( 'body' ).trigger( 'update_checkout' );
		jQuery(document.body).on( 'updated_checkout', wcev_refresh_cart );
		
}
function wcev_refresh_cart(event)
{
	//js\frontend\cart-fragments.js
	if( typeof wc_cart_fragments_params !== 'undefined' )
		jQuery.ajax( {
						url: wc_cart_fragments_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ),
						type: 'POST',
						success: function( data ) 
						{
							if ( data && data.fragments ) 
							{
								jQuery.each( data.fragments, function( key, value ) 
								{
									jQuery( key ).replaceWith( value );
								});

								/*if ( wcev_supports_html5_storage()  ) 
								{
									sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( data.fragments ) );
									set_cart_hash( data.cart_hash );

									if ( data.cart_hash ) {
										set_cart_creation_timestamp();
									}
								}*/

								jQuery( document.body ).trigger( 'wc_fragments_refreshed' );
							}
						}
					} );
	else
		jQuery( document.body ).trigger( 'wc_fragment_refresh');
}
	
/*function wcev_supports_html5_storage() 
{
	var	supports_html5_storage;
   try {
		supports_html5_storage = ( 'sessionStorage' in window && window.sessionStorage !== null );
	} catch( err ) {
		supports_html5_storage = false;
	}
	
	return supports_html5_storage;
}*/