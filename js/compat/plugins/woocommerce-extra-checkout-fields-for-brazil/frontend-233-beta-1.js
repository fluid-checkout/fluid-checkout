/* global wcbcf_public_params */
/*jshint devel: true */
jQuery( function( $ ) {

	/**
	 * Frontend actions
	 */
	var wc_ecfb_frontend = {

		/**
		 * Initialize frontend actions
		 */
		init: function() {
			if ( '0' === wcbcf_public_params.sort_state_country ) {
				$( document.body ).on( 'country_to_state_changing', this.country_to_state_changing );
			}

			if ( '0' !== wcbcf_public_params.person_type ) {
				this.person_type_fields();
			}

			if ( 'yes' === wcbcf_public_params.maskedinput ) {
				$( document.body ).on( 'change', '#billing_country', function() {
					if ( 'BR' === $( this ).val() ) {
						wc_ecfb_frontend.maskBilling();
					} else {
						wc_ecfb_frontend.unmaskBilling();
					}
				});

				$( document.body ).on( 'change', '#shipping_country', function() {
					if ( 'BR' === $( this ).val() ) {
						wc_ecfb_frontend.maskShipping();
					} else {
						wc_ecfb_frontend.unmaskShipping();
					}
				});

				this.maskGeneral();
			}

			if ( 'yes' === wcbcf_public_params.mailcheck ) {
				this.emailCheck();
			}

			// Check if select2 exists.
			if ( $().select2 ) {
				$( '.wc-ecfb-select' ).select2();
			}
			if ($('#billing_country').val() === 'BR' ) {
				wc_ecfb_frontend.maskBilling();
			}
			// CHANGE: Also mask shipping when initializing
			if ($('#shipping_country').val() === 'BR' ) {
				wc_ecfb_frontend.maskShipping();
			}

			// CHANGE: Apply billing country changes to fields on initialization
			$( document.body ).on( 'updated_checkout', wc_ecfb_frontend.billing_country_change );
		},

		/**
		 * Country to state changing.
		 * Fix the fields order.
		 */
		country_to_state_changing: function() {
			// Billing.
			$( '#billing_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			$( '#billing_postcode_field' ).insertAfter( '#billing_country_field' );

			// Shipping.
			if ( $( '#shipping_state_field' ).length ) {
				$( '#shipping_state_field label' ).html( wcbcf_public_params.state + ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
				$( '#shipping_postcode_field' ).insertAfter( '#shipping_country_field' );
			}
		},

		// CHANGE: Move billing country change handler function to a named function that can be reused
		billing_country_change: function () {
			// CHANGE: Get value from the field element directly, instead of using reference from `this` keyword
			var current = $( '#billing_country' ).val();

			if ( 'BR' === current ) {
				$( '.person-type-field label .required' ).remove();
				$( '.person-type-field' ).addClass( 'validate-required' );
				$( '.person-type-field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			} else {
				$( '.person-type-field' ).removeClass( 'validate-required' );
				$( '.person-type-field label .required' ).remove();
			}

			// CHANGE: Do not trigger field `change` event after updating the field attributes
			// to prevent an infinite loop where the `change` event triggers the `update_checkout` event,
			// which in turn, would run this code again and trigger the field `change` event, and so on.
		},

		person_type_fields: function() {
			// Required fields.
			if ( 'no' === wcbcf_public_params.only_brazil ) {
				$( '.person-type-field label .required' ).remove();
				$( '.person-type-field' ).addClass( 'validate-required' );
				$( '.person-type-field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
			} else {
				// CHANGE: Use billing country change handler from named function
				$( '#billing_country' ).on( 'change', wc_ecfb_frontend.billing_country_change );
			}

			if ( '1' === wcbcf_public_params.person_type ) {
				$( '#billing_persontype' ).on( 'change', function () {
					var current = $( this ).val();

					$( '#billing_cpf_field' ).hide();
					$( '#billing_rg_field' ).hide();
					$( '#billing_company_field' ).hide();
					$( '#billing_cnpj_field' ).hide();
					$( '#billing_ie_field' ).hide();

					if ( '1' === current ) {
						$( '#billing_cpf_field' ).show();
						$( '#billing_rg_field' ).show();
					}

					if ( '2' === current ) {
						$( '#billing_company_field' ).show();
						$( '#billing_cnpj_field' ).show();
						$( '#billing_ie_field' ).show();

						// CHANGE: Maybe make company field `required` on the frontend in cases which
						// the company field is validated as required on the server-side.
						var currentCountry = $( '#billing_country' ).val();
						$( '#billing_company_field label .optional' ).remove();
						$( '#billing_company_field label .required' ).remove();
						if ( 'no' === wcbcf_public_params.only_brazil || 'BR' === currentCountry ) {
							$( '#billing_company_field' ).addClass( 'validate-required' );
							$( '#billing_company_field label' ).append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );
						}
						else {
							$( '#billing_company_field' ).removeClass( 'validate-required' );
							$( '#billing_company_field label' ).append( '<span class="optional">(' + wcbcf_public_params.optional + ')</span>' );

							// Maybe clear Fluid Checkout inline validation results for the company field
							if ( window.CheckoutValidation ) {
								CheckoutValidation.clearValidationResults( document.querySelector( '#billing_company' ), document.querySelector( '#billing_company_field' ) );
							}
						}
					}
				}).change();
			}
		},

		maskBilling: function() {
			wc_ecfb_frontend.maskPhone( '#billing_phone, #billing_cellphone' );
			$( '#billing_birthdate' ).mask( '00/00/0000' );
			$( '#billing_postcode' ).mask( '00000-000' );
			$( '#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode' ).attr( 'type', 'tel' );
		},

		unmaskBilling: function() {
			$( '#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode' ).unmask().attr( 'type', 'text' );
		},

		maskShipping: function() {
			// CHANGE: Add mask to shipping phone if field exists
			wc_ecfb_frontend.maskPhone( '#shipping_phone' );
			$( '#shipping_postcode' ).mask( '00000-000' ).attr( 'type', 'tel' );
		},

		unmaskShipping: function() {
			// CHANGE: Remove mask from shipping phone if field exists
			$( '#shipping_postcode', '#shipping_phone' ).unmask().attr( 'type', 'text' );
		},

		maskGeneral: function() {
			$( '#billing_cpf, #credit-card-cpf' ).mask( '000.000.000-00' );
			$( '#billing_cnpj' ).mask( '00.000.000/0000-00' );
			wc_ecfb_frontend.maskPhone( '#credit-card-phone' );
		},

		maskPhone: function(selector) {
			var $element = $(selector),
					MaskBehavior = function(val) {
						return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
					},
					maskOptions = {
						onKeyPress: function(val, e, field, options) {
							field.mask(MaskBehavior.apply({}, arguments), options);
						}
					};

			$element.mask(MaskBehavior, maskOptions);
		},

		emailCheck: function() {
			if ( $( '#wcbcf-mailsuggest' ).length < 1 ) {
				$( '#billing_email' ).after( '<div id="wcbcf-mailsuggest"></div>' );
			}

			$( '#billing_email' ).on( 'blur', function () {
				$( '#wcbcf-mailsuggest' ).html( '' );
				$( this ).mailcheck({
					suggested: function( element, suggestion ) {
						$( '#wcbcf-mailsuggest' ).html( 'Você quis dizer: ' + suggestion.full + '?' );
					}
				});
			});

			$( '#wcbcf-mailsuggest' ).css({
				color: '#c00',
				fontSize: 'small'
			});
		}
	};

	wc_ecfb_frontend.init();

	// CHANGE: Also run initialization after every checkout update as sections with affected fields might have have been replaced
	$( document.body ).on( 'updated_checkout', function() { wc_ecfb_frontend.init() } );
});
