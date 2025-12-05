( function( $ ) {
	'use strict';

	var fcItalianAddOn = {
		namespace: '.fcItalianAddOn',
		init: function() {
			if ( ! fcItalianAddOn.canInit() ) {
				return;
			}
			fcItalianAddOn.bindFields();
			fcItalianAddOn.refreshState();
		},
		canInit: function() {
			return typeof wcpdf_IT_billing_customer_type_change === 'function' && typeof wcpdf_IT_check_required === 'function';
		},
		bindFields: function() {
			fcItalianAddOn.bind( '#billing_customer_type', wcpdf_IT_billing_customer_type_change );
			fcItalianAddOn.bind( '#billing_country', wcpdf_IT_check_required );
		},
		bind: function( selector, callback ) {
			var $field = $( selector );
			if ( ! $field.length || typeof callback !== 'function' ) {
				return;
			}
			$field.off( fcItalianAddOn.namespace ).on( 'change' + fcItalianAddOn.namespace, callback );
		},
		refreshState: function() {
			var helpers = [
				wcpdf_IT_billing_customer_type_change,
				wcpdf_IT_check_required,
				wcpdf_IT_check_cf2,
				wcpdf_IT_check_PEC,
				wcpdf_IT_check_billing_company,
				wcpdf_IT_check_visible_required_fields,
				wcpdf_IT_billing_invoice_type_change
			];
			helpers.forEach( function( helper ) {
				if ( typeof helper === 'function' ) {
					helper();
				}
			} );
		}
	};

	$( function() {
		fcItalianAddOn.init();
		$( 'body' ).on( 'updated_checkout', fcItalianAddOn.init );
	} );

} )( jQuery );

