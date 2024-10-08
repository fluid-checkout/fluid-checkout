/* global bmwPublicParams */
jQuery(function ($) {
	/**
	 * Frontend actions
	 */
	const bmwFrontEnd = {
		/**
		 * Initialize frontend actions
		 */
		init() {
			if ('0' !== bmwPublicParams.person_type) {
				// CHANGE: Fix refence to function in main object
				bmwFrontEnd.person_type_fields();
			}

			if ('yes' === bmwPublicParams.maskedinput) {
				$(document.body).on('change', '#billing_country', function () {
					if ('BR' === $(this).val()) {
						bmwFrontEnd.maskBilling();
					} else {
						bmwFrontEnd.unmaskBilling();
					}
				});

				$(document.body).on('change', '#shipping_country', function () {
					if ('BR' === $(this).val()) {
						bmwFrontEnd.maskShipping();
					} else {
						bmwFrontEnd.unmaskShipping();
					}
				});

				if ('BR' === $('#billing_country').val()) {
					bmwFrontEnd.maskBilling();
				}

				if ('BR' === $('#shipping_country').val()) {
					bmwFrontEnd.maskShipping();
				}

				// CHANGE: Fix refence to function in main object
				bmwFrontEnd.maskGeneral();
			}

			// CHANGE: Remove code of the Mailcheck feature from Brazilian Market plugin as it is already provided by Fluid Checkout

			// Check if select2 exists.
			if ($().select2) {
				$('.wc-ecfb-select').select2();
			}
		},

		person_type_fields() {
			/**
			 * Control person type fields
			 *
			 * @param {string}  personType
			 * @param {boolean} checkCountry
			 */
			const handleFields = function (personType, checkCountry = false) {
				let country = 'BR';

				if (checkCountry) {
					country = $('#billing_country').val();
				}

				$('.person-type-field')
					.hide()
					.removeClass(
						'validate-required is-active woocommerce-validated'
					);
				$('#billing_persontype_field').show().addClass('is-active');

				if ('1' === personType) {
					if ('BR' === country) {
						$('#billing_cpf_field')
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
						$('#billing_rg_field')
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
					} else {
						$('#billing_cpf_field').show().addClass('is-active');
						$('#billing_rg_field').show().addClass('is-active');
					}
				}

				if ('2' === personType) {
					if ('BR' === country) {
						$('#billing_company_field label .optional').remove();
						$('#billing_company_field')
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
						$('#billing_cnpj_field')
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
						$('#billing_ie_field')
							.addClass(
								'validate-required is-active woocommerce-validated'
							)
							.show();
					} else {
						$('#billing_company_field')
							.addClass('is-active')
							.show();
						$('#billing_cnpj_field').addClass('is-active').show();
						$('#billing_ie_field').addClass('is-active').show();
					}
				}

				if ('BR' === country) {
					$('.person-type-field label .required').remove();
					$('.person-type-field label').append(
						' <abbr class="required" title="' +
							bmwPublicParams.required +
							'">*</abbr>'
					);
				}
			};

			/**
			 * Maybe run handle fields
			 *
			 * @param {boolean} checkCountry
			 * @return {void}
			 */
			const maybeRunHandleFields = function (checkCountry = false) {
				if ('1' === bmwPublicParams.person_type) {
					$('#billing_persontype')
						.on('change', function () {
							const personType = $(this).val();

							handleFields(personType, checkCountry);
						})
						// CHANGE: Do not trigger `change` event for the field, as it might cause other plugins to behave errorneusly

					// CHANGE: Run the function `handleFields` directly, to handle the field changes without triggering the `change` event remove above
					handleFields( '1', checkCountry );
				}
			};

			// Required fields.
			if ('no' === bmwPublicParams.only_brazil) {
				$('.person-type-field label .required').remove();
				$('.person-type-field label').append(
					' <abbr class="required" title="' +
						bmwPublicParams.required +
						'">*</abbr>'
				);

				maybeRunHandleFields();
			} else {
				// CHANGE: Extract anonymous function to a named function that can be reused
				var updateBillingPersonFieldArgs = function () {
					$('.person-type-field').removeClass(
						'validate-required is-active woocommerce-validated'
					);
					$('.person-type-field label .required').remove();
					maybeRunHandleFields(true);

					// CHANGE: Get field directly as referencing it with `this` do not work in this context
					const current = $('#billing_country').val();

						if ('BR' === current) {
							$('.person-type-field label .required').remove();
							$('.person-type-field').addClass(
								'validate-required'
							);
							$('.person-type-field label').append(
								' <abbr class="required" title="' +
									bmwPublicParams.required +
									'">*</abbr>'
							);
						} else {
							$('.person-type-field').removeClass(
								'validate-required is-active woocommerce-validated'
							);
							$('.person-type-field label .required').remove();
						}
				};
				// CHANGE: Replace `change` event trigger with calling the function directly
				$('#billing_country').on('change', updateBillingPersonFieldArgs);
				updateBillingPersonFieldArgs();
			}

			if ('1' === bmwPublicParams.person_type) {
				// CHANGE: Extract anonymous function to a named function that can be reused
				var updateBillingFieldsArgs = function () {
					// CHANGE: Get field directly as referencing it with `this` do not work in this context
					const current = $('#billing_persontype').val();

					$('#billing_cpf_field').hide();
					$('#billing_rg_field').hide();
					$('#billing_company_field').hide();
					$('#billing_cnpj_field').hide();
					$('#billing_ie_field').hide();

					if ('1' === current) {
						$('#billing_cpf_field').show();
						$('#billing_rg_field').show();
					}

					if ('2' === current) {
						$('#billing_company_field').show();
						$('#billing_cnpj_field').show();
						$('#billing_ie_field').show();
					}
				};
				// CHANGE: Replace `change` event trigger with calling the function directly
				$('#billing_persontype').on( 'change', updateBillingFieldsArgs );
				updateBillingFieldsArgs();
			}
		},

		maskBilling() {
			// CHANGE: Maybe do not mask phone fields
			if ( 'yes' === bmwPublicParams.maskedinput_phone ) {
				bmwFrontEnd.maskPhone('#billing_phone, #billing_cellphone');
			}

			$('#billing_birthdate').mask('00/00/0000');
			$('#billing_postcode').mask('00000-000');
			$(
				'#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode'
			).attr('type', 'tel');
		},

		unmaskBilling() {
			$(
				'#billing_phone, #billing_cellphone, #billing_birthdate, #billing_postcode'
			)
				.unmask()
				.attr('type', 'text');
		},

		maskShipping() {
			// CHANGE: Maybe mask shipping phone field
			if ( 'yes' === bmwPublicParams.maskedinput_phone ) {
				bmwFrontEnd.maskPhone( '#shipping_phone' );
			}

			$('#shipping_postcode').mask('00000-000').attr('type', 'tel');
		},

		unmaskShipping() {
			$('#shipping_postcode').unmask().attr('type', 'text');

			// CHANGE: Unmask shipping phone field, but do not change type attribute
			$( '#shipping_phone' ).unmask();
		},

		maskGeneral() {
			$('#billing_cpf, #credit-card-cpf').mask('000.000.000-00');
			$('#billing_cnpj').mask('00.000.000/0000-00');
			bmwFrontEnd.maskPhone('#credit-card-phone');
		},

		maskPhone(selector) {
			const $element = $(selector),
				MaskBehavior = function (val) {
					return val.replace(/\D/g, '').length === 11
						? '(00) 00000-0000'
						: '(00) 0000-00009';
				},
				maskOptions = {
					onKeyPress(val, e, field, options) {
						field.mask(MaskBehavior.apply({}, arguments), options);
					},
				};

			$element.mask(MaskBehavior, maskOptions);
		},

		// CHANGE: Remove code of the Mailcheck feature from Brazilian Market plugin as it is already provided by Fluid Checkout
	};

	bmwFrontEnd.init();

	// CHANGE: Also run initialization after every checkout update as sections with affected fields might have have been replaced
	$( document.body ).on( 'updated_checkout', bmwFrontEnd.init );
});
