jQuery(document).ready(function() {

	// CHANGE: Add parameters to define if should use transition
	var toggle_fields = function( show, use_transition ) {
		// CHANGE: Define duration for the fade effects
		var duration = use_transition ? 400 : 0;

		if ( show ) {
			// CHANGE: Change duration to hide/show fields with or without transition
			jQuery('#billing_company_field').fadeIn( duration );
			jQuery('#billing_company_wi_id_field').fadeIn( duration );
			jQuery('#billing_company_wi_vat_field').fadeIn( duration );
			jQuery('#billing_company_wi_tax_field').fadeIn( duration );
		}
		else {
			// CHANGE: Change duration to hide/show fields with or without transition
			jQuery('#billing_company_field').fadeOut( duration );
			jQuery('#billing_company_wi_id_field').fadeOut( duration );
			jQuery('#billing_company_wi_vat_field').fadeOut( duration );
			jQuery('#billing_company_wi_tax_field').fadeOut( duration );
		}
	}

	// CHANGE: Extract function to be used for captured events
	var trigger_toggle_fields = function() {
		// CHANGE: Get the event name
		var event_name = arguments[ 0 ].type;

		// CHANGE: Define if should use transition
		var use_transition = event_name === 'change';

		//CHANGE: Get the checkbox field directly
		var $field = jQuery( '#wi_as_company' );
		
		// CHANGE: Use variable for the checkbox and pass the transition parameter
		toggle_fields( $field.is(':checked'), use_transition );
	}

	// CHANGE: Ask WooCommerce to recalculate the order totals so the reverse-charge VAT exemption
	// is reflected in the summary when the business status or VAT # changes. Without this the
	// update_checkout AJAX (which re-applies the exemption server-side) never fires for these fields.
	// CHANGE: Rename refresh_totals to trigger_update_checkout and use captured/delegated events
	var trigger_update_checkout = function() {
		jQuery( document.body ).trigger( 'update_checkout' );
	}

	// CHANGE: Use captured events instead of attaching directly to the element
	jQuery( document.body ).on( 'change', '#wi_as_company', trigger_toggle_fields );

	// CHANGE: Add event to manage checkout fragments update
	jQuery( document.body ).on( 'change', '#wi_as_company', trigger_update_checkout );
	// CHANGE: Delegated so it keeps working after WooCommerce refreshes the checkout. 'change' fires on blur,
	// avoiding a recalculation (and VIES lookup) on every keystroke.
	jQuery( document.body ).on( 'change', '#billing_company_wi_vat', trigger_update_checkout );
	jQuery( document.body ).on( 'updated_checkout', trigger_toggle_fields );

	toggle_fields(jQuery('#wi_as_company').is(':checked'))
});
