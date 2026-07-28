/* global wc_checkout_params */
jQuery( function ( $ ) {
	// wc_checkout_params is required to continue, ensure the object exists
	if ( typeof wc_checkout_params === 'undefined' ) {
		return false;
	}

	$.blockUI.defaults.overlayCSS.cursor = 'default';

	// CHANGE: Add global flags to dynamically indicate whether some operations can be performed
	window.can_update_checkout = true;
	window.can_prevent_unload = true;
	window.can_update_payment_methods = true;

	// CHANGE: Add flag indicate whether Fluid Checkout can prevent users from leaving the page when there is unsaved data
	var _updateBeforeUnload = false;

	// CHANGE: Add default settings object
	var _settings = {
		checkoutFormSelector:                         'form.checkout',
		orderPayFormSelector:                         '#order_review',

		formRowSelector:                              '.form-row',
		checkoutPlaceOrderSelector:                   '#place_order, .fc-place-order-button',
		checkoutTermsSelector:                        '.fc-terms-checkbox',
		checkoutUpdateFieldsSelector:                 '.address-field input.input-text, .update_totals_on_change input.input-text',
		checkoutLoadingInputSelector:                 '.loading_indicator_on_change input.input-text',
		focusedFieldSkipFragmentReplaceSelector:      'input[type="text"], input[type="color"], input[type="date"], input[type="datetime"], input[type="datetime-local"], input[type="email"], input[type="file"], input[type="image"], input[type="month"], input[type="number"], input[type="password"], input[type="search"], input[type="tel"], input[type="time"], input[type="url"], input[type="week"], select, textarea, .fc-select2-field',
		phoneFieldSelector:                           'input[type="tel"], [data-phone-field], input.js-phone-field, .js-phone-field input',
		loginButtonSelector:                          '.fc-contact-login__action',
		emailFieldSelector:                           'form.woocommerce-checkout input[name="billing_email"]',
		usernameFieldSelector:                        '.fc-login-form__inner input[name="username"]',

		sameAsCheckboxSelector:                       '#billing_same_as_shipping, #shipping_same_as_billing',
		sameAsCheckboxAvailableSelector:              '#billing_same_as_shipping_available, #shipping_same_as_billing_available',
		addressFieldsSectionSelector:                 '.woocommerce-billing-fields, .woocommerce-shipping-fields',
		addressFieldsSectionSelectorTemplate:         '.woocommerce-###FIELD_GROUP###-fields',
		addressFieldsWrapperSelector:                 '#woocommerce-billing-fields__field-wrapper, #woocommerce-shipping-fields__field-wrapper',
		addressFieldsToMirrorSelector:                'select, input, textarea',

		loadingClass:                                 'fc-loading',
		checkoutBlockUISelector:                      '.woocommerce-checkout-payment, .fc-shipping-method__packages',

		checkoutPlaceOrderApplyLoadingClass:          'yes',
		checkoutUpdateBeforeUnload:                   'yes',
		checkoutUpdateOnVisibilityChange:             'yes',
	};
	// CHANGE: END - Add default settings object



	/**
	 * Create the API object passed to custom place order button render callbacks.
	 * This is checkout-specific and includes form validation.
	 *
	 * @return {Object} API object with validate and submit methods
	 */
	function createCheckoutPlaceOrderApi() {
		// CHANGE: Bail if custom place order button API is not available
		if ( ! window.wc || ! window.wc.customPlaceOrderButton ) { return; }

		var $form = window.wc.customPlaceOrderButton.__getForm();

		return {
			/**
			 * Validate the checkout form.
			 * This gets a little tricky.
			 * The existing checkout.js does NOT have a "validate everything before submit" function - it's not needed.
			 * Validation is done:
			 *  - Field-by-field via `validate_field` on blur/change
			 *  - Server-side on form submission (errors are returned in AJAX response).
			 * This function tries to mimic client-side validation, but WooCommerce's real validation happens server-side.
			 *
			 * @return {Promise<{hasError: boolean}>} Promise resolving to validation result
			 */
			validate: function () {
				return new Promise( function ( resolve ) {
					var hasError = false;

					// On a "normal" shortcode checkout page, the page validates this server-side only (not via validate_field).
					// We do client-side validation here for a better UX with custom place order buttons.
					// Clearing any stale invalid state before re-validating, to ensure a clean slate.
					var $termsCheckbox = $form.find( 'input[name="terms"]:visible' );
					if ( $termsCheckbox.length ) {
						$termsCheckbox.closest( '.form-row' ).removeClass( 'woocommerce-invalid' );
					}

					// Trigger field-level validation (which adds `.woocommerce-invalid` to invalid fields)
					$form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' );

					// Check for validation errors (from validate_field handler)
					if ( $form.find( '.woocommerce-invalid' ).length > 0 ) {
						hasError = true;
					}

					// Check required fields (adds .woocommerce-invalid if not already set)
					$form.find( '.validate-required:visible' ).each( function () {
						var $field = $( this );
						var $input = $field.find( 'input.input-text, select, input:checkbox' );

						if ( $input.length === 0 ) {
							return;
						}

						var isEmpty;
						if ( $input.is( ':checkbox' ) ) {
							isEmpty = ! $input.is( ':checked' );
						} else {
							isEmpty = $input.val() === '' || $input.val() === null;
						}

						if ( isEmpty ) {
							hasError = true;
							$field.addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						}
					} );

					// Check terms checkbox - this is our client-side validation for better UX
					// (WC Core only validates terms server-side)
					if ( $termsCheckbox.length && ! $termsCheckbox.is( ':checked' ) ) {
						hasError = true;
						$termsCheckbox.closest( '.form-row' ).addClass( 'woocommerce-invalid' );
					}

					// Scroll to the first invalid field in DOM order
					if ( hasError ) {
						var $firstInvalidField = $form.find( '.woocommerce-invalid' ).first();
						if ( $firstInvalidField.length ) {
							$( 'html, body' ).animate(
								{
									scrollTop: $firstInvalidField.offset().top - 100,
								},
								500
							);
						}
					}

					resolve( { hasError: hasError } );
				} );
			},

			/**
			 * Submit the checkout form.
			 * Triggers the same logic as clicking the default place order button.
			 */
			submit: function () {
				$form.trigger( 'submit' );
			},
		};
	}

	// Clean up custom place order button before checkout update destroys the DOM.
	// After the update, init_payment_methods() will trigger payment method selection,
	// which will call render() again for the active gateway.
	$( document.body ).on( 'update_checkout', function () {
		// CHANGE: Bail if custom place order button API is not available
		if ( ! window.wc || ! window.wc.customPlaceOrderButton ) { return; }

		window.wc.customPlaceOrderButton.__cleanup();
	} );

	// When a gateway registers after a page load, render its button if it's selected.
	$( document.body ).on( 'wc_custom_place_order_button_registered', function ( e, gatewayId ) {
		// CHANGE: Bail if custom place order button API is not available
		if ( ! window.wc || ! window.wc.customPlaceOrderButton ) { return; }

		window.wc.customPlaceOrderButton.__maybeShow( gatewayId, createCheckoutPlaceOrderApi() );
	} );

	var wc_checkout_form = {
		updateTimer: false,
		dirtyInput: false,
		selectedPaymentMethod: false,
		xhr: false,
		// CHANGE: Use checkout form and order pay form selectors from settings
		$order_review: $( _settings.orderPayFormSelector ),
		$checkout_form: $( _settings.checkoutFormSelector ),
		init: function () {
			// CHANGE: Maybe merge default settings object with values from the server settings object
			if ( window.fcSettings ) {
				_settings = FCUtils.extendObject( true, _settings, window.fcSettings.checkout );
			}

			// CHANGE: Update checkout form and order pay form element variables, as the selector might have changed on initialization
			wc_checkout_form.$checkout_form = $( _settings.checkoutFormSelector );
			wc_checkout_form.$order_review = $( _settings.orderPayFormSelector );

			$( document.body ).on( 'update_checkout', this.update_checkout );
			$( document.body ).on( 'init_checkout', this.init_checkout );

			// CHANGE: Add event handlers to autocomplete attributes
			$( document.body ).on( 'init_checkout', this.set_autocomplete_attribute_from_data );
			$( document.body ).on( 'updated_checkout', this.set_autocomplete_attribute_from_data );

			// Payment methods
			this.$checkout_form.on(
				'click',
				'input[name="payment_method"]',
				this.payment_method_selected
			);

			if ( $( document.body ).hasClass( 'woocommerce-order-pay' ) ) {
				this.$order_review.on(
					'click',
					'input[name="payment_method"]',
					this.payment_method_selected
				);
				this.$order_review.on( 'submit', this.submitOrder );
				this.$order_review.attr( 'novalidate', 'novalidate' );

				// Initialize the custom place order button for the "order-pay" page
				var $orderPayMethod = this.$order_review.find( 'input[name="payment_method"]:checked' );
				if ( $orderPayMethod.length ) {
					// CHANGE: Check if custom place order button API is available before using it
					if ( window.wc && window.wc.customPlaceOrderButton ) {
						window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit( $orderPayMethod.val() );
					}

					$orderPayMethod.trigger( 'click' );
				}
			}

			// Prevent HTML5 validation which can conflict.
			this.$checkout_form.attr( 'novalidate', 'novalidate' );

			// Form submission
			this.$checkout_form.on( 'submit', this.submit );

			// CHANGE: Maybe disable inline validation from default WooCommerce checkout script when Fluid Checkout inline validation is enabled
			if ( ! window.CheckoutValidation ) {
				// Inline validation
				this.$checkout_form.on(
					'input validate change focusout',
					'.input-text, select, input:checkbox',
					this.validate_field
				);
			}
			// CHANGE: END - Maybe disable inline validation from default WooCommerce checkout script when Fluid Checkout inline validation is enabled

			// Manual trigger
			this.$checkout_form.on( 'update', this.trigger_update_checkout );

			// CHANGE: Add event listener for `removed_from_cart` event
			$( document.body ).on( 'removed_from_cart', this.trigger_update_checkout );

			// Inputs/selects which update totals
			// CHANGE: Removed selector `#ship-to-different-address input`
			this.$checkout_form.on( 'change', 'select.shipping_method, input[name^="shipping_method"], .update_totals_on_change select, .update_totals_on_change input[type="radio"], .update_totals_on_change input[type="checkbox"], .update_totals_on_change input:not([type="hidden"])', this.trigger_update_checkout ); // eslint-disable-line max-len
			this.$checkout_form.on( 'change', '.address-field select', this.input_changed );

			this.$checkout_form.on(
				'change',
				// CHANGE: Move CSS selector for fields that trigger update checkout when value is changed
				_settings.checkoutUpdateFieldsSelector,
				this.maybe_input_changed
			);
			this.$checkout_form.on(
				'keydown',
				// CHANGE: Move CSS selector for fields that trigger update checkout when value is changed
				_settings.checkoutUpdateFieldsSelector,
				this.queue_update_checkout
			);

			// Handle blur on address_1 fields when autocomplete provider is available
			this.$checkout_form.on(
				'blur',
				'#billing_address_1, #shipping_address_1',
				this.address_field_blur
			);

			// Address fields
			// CHANGE: Removed shipping to different address checkout `change` listener

			// Trigger events
			// CHANGE: Removed shipping to different address checkout `change` trigger
			this.init_payment_methods();

			// CHANGE: Update checkout totals to save data to session when user switches tabs, apps, goes to homescreen, etc.
			document.addEventListener( 'visibilitychange', FCUtils.debounce( this.maybe_update_checkout_visibility_change, 50 ) );

			// CHANGE: Maybe prevent leaving the page if there are unsaved changes, and trigger `update_checkout` to save the data.
			if ( 'yes' === _settings.checkoutUpdateBeforeUnload ) {
				this.$checkout_form.on( 'change, input', 'select, input, textarea', this.maybe_prevent_unload );
			}

			// CHANGE: Update checkout when "same as address" checkboxes state changes
			this.$checkout_form.on( 'change', _settings.sameAsCheckboxSelector, this.same_as_address_checkbox_changed );

			// CHANGE: Trigger reinitialization functions after checkout is updated
			$( document.body ).on( 'updated_checkout', this.maybe_reinitialize_collapsible_blocks );
			$( document.body ).on( 'updated_checkout', this.maybe_reinitialize_flyout_blocks );
			$( document.body ).on( 'updated_checkout', this.maybe_init_enhanced_dropdowns );

			// CHANGE: Enhance dropdown fields on initialization
			this.maybe_init_enhanced_dropdowns();

			// CHANGE: Add event listener to sync terms checkbox state
			$( document.body ).on( 'change', _settings.checkoutTermsSelector, this.terms_checked_changed );

			// Update on page load
			if ( wc_checkout_params.is_checkout === '1' ) {
				$( document.body ).trigger( 'init_checkout' );
			}
			if ( wc_checkout_params.option_guest_checkout === 'yes' ) {
				// CHANGE: Use native `change` event instead jQuery to handle create account checkbox toggle
				document.addEventListener( 'change', this.toggle_create_account, true );
			}

			// CHANGE: Add handler for login form modal initialization
			document.addEventListener( 'click', this.maybe_copy_email_to_login_form, true );
		},
		// CHANGE: Enhance dropdown fields
		maybe_init_enhanced_dropdowns: function() {
			if ( ! window.FCEnhancedSelect ) { return; }

			FCEnhancedSelect.enhanceFields();
		},
		// CHANGE: END - Enhance dropdown fields
		// CHANGE: Check if the "same as" address checkbox option is checked
		is_same_as_address_option_checked: function( fieldGroup ) {
			// Bail if field group is not valid
			if ( 'billing' !== fieldGroup && 'shipping' !== fieldGroup ) { return false; }

			// Get fields section
			var fieldsSectionSelector = _settings.addressFieldsSectionSelectorTemplate.replace( '###FIELD_GROUP###', fieldGroup );
			var fieldsSection = document.querySelector( fieldsSectionSelector );

			// Bail if field section was not found
			if ( ! fieldsSection ) { return false; }

			// Get field that determines whether "same as" checkbox is available
			var isCheckboxAvailableField = fieldsSection.querySelector( _settings.sameAsCheckboxAvailableSelector );

			// Bail if checkbox availability field was found, but the checkbox is not available
			if ( isCheckboxAvailableField && '1' !== isCheckboxAvailableField.value ) { return false; }

			// Get the checkbox field
			var sameAsCheckbox = fieldsSection.querySelector( _settings.sameAsCheckboxSelector );

			// Bail if "same as" checkbox was not found
			if ( ! sameAsCheckbox ) { return false; }

			// Get the checkbox field type
			var checkboxFieldType = sameAsCheckbox.getAttribute( 'type' );

			// Initialize return value
			var isChecked = false;

			// Test whether the checkbox is checked based on its field type
			switch ( checkboxFieldType ) {
				case 'hidden':
					isChecked = '1' === sameAsCheckbox.value;
					break;
				default:
					isChecked = sameAsCheckbox.checked;
					break;
			}

			return isChecked;
		},
		// CHANGE: END - Check if the "same as" address checkbox option is checked
		// CHANGE: Update checkout when "billing same as shipping" checked state changes
		same_as_address_checkbox_changed: function( e ) {
			// Initialize variables
			var fieldsSection = e.target.closest( _settings.addressFieldsSectionSelector );
			var fieldsWrapper = fieldsSection ? fieldsSection.querySelector( _settings.addressFieldsWrapperSelector ) : null;
			var sameAsCheckbox = fieldsSection ? fieldsSection.querySelector( _settings.sameAsCheckboxSelector ) : null;
			var fieldGroup = sameAsCheckbox && 0 === sameAsCheckbox.id.indexOf( 'billing' ) ? 'billing' : 'shipping';

			// Check whether required elements are available
			if ( window.CollapsibleBlock && sameAsCheckbox && fieldsWrapper ) {
				// Toggle state
				if ( ! wc_checkout_form.is_same_as_address_option_checked( fieldGroup ) ) {
					// Expand fields wrapper
					CollapsibleBlock.expand( fieldsWrapper );

					// Show section as loading
					$( fieldsWrapper ).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				}
				else {
					// Collapse fields wrapper
					CollapsibleBlock.collapse( fieldsWrapper );
				}
			}

			// Trigger update checkout
			$( document.body ).trigger( 'update_checkout' );
		},
		// CHANGE: END - Update checkout when "billing same as shipping" checked state changes
		// CHANGE: Mirror the address field value to the other address group (copy billing to shipping, or shipping to billing)
		mirror_address_field_value: function( field ) {
			// Bail if not a valid field
			if ( ! field || ! field.matches( _settings.addressFieldsToMirrorSelector ) ) { return; }

			// Get field key
			var fieldKey = field.getAttribute( 'name' );

			// Bail if not address field
			if ( ! fieldKey || ( 0 !== fieldKey.indexOf( 'billing_' ) && 0 !== fieldKey.indexOf( 'shipping_' ) ) ) { return; }

			// Get field groups
			var fieldGroup = 0 === fieldKey.indexOf( 'billing_' ) ? 'billing' : 'shipping';
			var mirrorFieldGroup = 'billing' === fieldGroup ? 'shipping' : 'billing';
			var mirrorFieldGroupElement = document.querySelector( '.woocommerce-' + mirrorFieldGroup + '-fields__field-wrapper' );

			// Bail if no mirror field group element
			if ( ! mirrorFieldGroupElement ) { return; }

			// Get checkbox state
			var isSameAsAddressChecked = wc_checkout_form.is_same_as_address_option_checked( mirrorFieldGroup );

			// Bail if "same as" checkbox is not checked
			if ( ! isSameAsAddressChecked ) { return; }

			// Get mirror field
			var mirrorFieldKey = fieldKey.replace( fieldGroup + '_', mirrorFieldGroup + '_' );
			var mirrorField = mirrorFieldGroupElement.querySelector( '[name="' + mirrorFieldKey + '"]' );

			// Bail if no mirror field
			if ( ! mirrorField ) { return; }

			// Mirror field value
			mirrorField.value = field.value;
		},
		// CHANGE: END - Mirror the address field value to the other address group (copy billing to shipping, or shipping to billing)
		// CHANGE: Mirror all address field values between billing and shipping fields when "same as address" checkbox is checked
		maybe_mirror_all_address_field_values: function() {
			// Get checkbox field
			var sameAsCheckbox = document.querySelector( _settings.sameAsCheckboxSelector );

			// Bail if checkbox was not found
			if ( ! sameAsCheckbox ) { return; }

			// Get the field groups based on the available checkbox element
			var targetFieldGroup = 0 === sameAsCheckbox.id.indexOf( 'billing' ) ? 'billing' : 'shipping';
			var originFieldGroup = 'billing' === targetFieldGroup ? 'shipping' : 'billing';

			// Get the checkbox state
			var isSameAsAddressChecked = wc_checkout_form.is_same_as_address_option_checked( targetFieldGroup );

			// Bail if "same as" checkbox is not checked
			if ( ! isSameAsAddressChecked ) { return; }

			// Get origin section and wrapper elements
			var originFieldsSectionSelector = _settings.addressFieldsSectionSelectorTemplate.replace( '###FIELD_GROUP###', originFieldGroup );
			var originFieldsSection = document.querySelector( originFieldsSectionSelector );
			var originFieldsWrapper = originFieldsSection ? originFieldsSection.querySelector( _settings.addressFieldsWrapperSelector ) : null;

			// Bail if origin fields section and wrapper were not found
			if ( ! originFieldsSection || ! originFieldsWrapper ) { return; }

			// Bail if no origin field group element
			if ( ! originFieldsWrapper ) { return; }

			// Get origin address fields
			var originAddressFields = originFieldsWrapper.querySelectorAll( _settings.addressFieldsToMirrorSelector );

			// Iterate over address fields and mirror their values
			for ( var i = 0; i < originAddressFields.length; i++ ) {
				var originField = originAddressFields[ i ];
				wc_checkout_form.mirror_address_field_value( originField );
			}
		},
		// CHANGE: END - Mirror all address field values between billing and shipping fields when "same as address" checkbox is checked
		// CHANGE: Reinitialize collapsible blocks after checkout update
		maybe_reinitialize_collapsible_blocks: function() {
			// Bail if collapsible blocks are not available
			if ( ! window.CollapsibleBlock ) { return; }

			// Try to initialize collapsible blocks if not yet initialized
			CollapsibleBlock.init( window.fcSettings ? fcSettings.collapsibleBlock : null );

			var collapsibleBlocks = document.querySelectorAll( '[data-collapsible]' );
			for ( var i = 0; i < collapsibleBlocks.length; i++ ) {
				var collapsibleBlock = collapsibleBlocks[i];

				// Maybe initialize the collapsible block
				if ( ! CollapsibleBlock.getInstance( collapsibleBlock ) ) {
					CollapsibleBlock.initializeElement( collapsibleBlock );
				}
			}
		},
		// CHANGE: END - Reinitialize collapsible blocks after checkout update
		// CHANGE: Reinitialize flyout blocks after checkout update
		maybe_reinitialize_flyout_blocks: function() {
			// Bail if flyout blocks are not available
			if ( ! window.FlyoutBlock ) { return; }

			FlyoutBlock.initTriggers();
		},
		// CHANGE: END - Reinitialize flyout blocks after checkout update
		// CHANGE: Update checkout when page gets hidden or visible again
		maybe_update_checkout_visibility_change: function() {
			// Bail if update on visibility change is disabled
			if ( 'yes' !== _settings.checkoutUpdateOnVisibilityChange ) { return; }

			// Trigger update if visibility state is changed to `hidden` or `visible`
			if ( 'hidden' == document.visibilityState || 'visible' == document.visibilityState ) {
				$( document.body ).trigger( 'update_checkout', { refresh_payment_methods: false } );
			}
		},
		// CHANGE: END - Update checkout when page gets hidden or visible again
		// CHANGE: Add event listener to prompt user that they might lose data when closing tab or leaving the current page after they change some values in the checkout form.
		maybe_prevent_unload: function( e ) {
			// Ignore some fields
			if ( e && e.target.closest( '.payment_box, input#createaccount' ) ) { return; }

			// Bail if already set to update before unload, that is the event listener has been already added.
			if ( _updateBeforeUnload ) { return; }

			// Create local named function to prevent user from leaving the page.
			// This is needed to be able to remove the event listener when it is no longer needed.
			// E.g when the user has updated the checkout totals and the flag is set to `false`.
			var preventUnload = function( e ) {
				// Bail if cannot prevent user from leaving the page
				if ( ! window.can_prevent_unload ) { return; }

				// Bail if cannot update before unload
				if ( ! _updateBeforeUnload ) { return; }

				// Prompt user if there is unsaved data
				e.preventDefault();
				e.returnValue = '';

				// Proceed to update the checkout totals if the user cancel the event
				$( document.body ).trigger( 'update_checkout' );

				// Reset flag to update on `beforeunload`
				_updateBeforeUnload = false;
				window.removeEventListener( 'beforeunload', preventUnload );
			};

			// Add event listener to prevent user from leaving the page, and set the flag to update on `beforeunload`
			window.addEventListener( 'beforeunload', preventUnload );
			_updateBeforeUnload = true;
		},
		// CHANGE: END - Add event listener to prompt user that they might lose data when closing tab or leaving the current page after they change some values in the checkout form.
		init_payment_methods: function () {
			var $payment_methods = $( '.woocommerce-checkout' ).find(
				'input[name="payment_method"]'
			);

			// If there is one method, we can hide the radio input
			if ( 1 === $payment_methods.length ) {
				$payment_methods.eq( 0 ).hide();
			}

			// If there was a previously selected method, check that one.
			if ( wc_checkout_form.selectedPaymentMethod ) {
				$( '#' + wc_checkout_form.selectedPaymentMethod ).prop(
					'checked',
					true
				);
			}

			// If there are none selected, select the first.
			if ( 0 === $payment_methods.filter( ':checked' ).length ) {
				$payment_methods.eq( 0 ).prop( 'checked', true );
			}

			// Get name of new selected method.
			var checkedPaymentMethod = $payment_methods
				.filter( ':checked' )
				.eq( 0 )
				.prop( 'id' );

			if ( $payment_methods.length > 1 ) {
				// Hide open descriptions.
				$( 'div.payment_box:not(".' + checkedPaymentMethod + '")' )
					.filter( ':visible' )
					.slideUp( 0 );
			}

			// Check if initially selected gateway has custom place order button (via server-side flag)
			// This hides the default button immediately to prevent flash while the gateway JS loads
			var $selectedMethod = $payment_methods.filter( ':checked' ).eq( 0 );
			// CHANGE: Check if custom place order button API is available before using it
			if ( window.wc && window.wc.customPlaceOrderButton && $selectedMethod.length ) {
				window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit( $selectedMethod.val() );
			}

			// Trigger click event for selected method
			$payment_methods.filter( ':checked' ).eq( 0 ).trigger( 'click' );
		},
		get_payment_method: function () {
			return wc_checkout_form.$checkout_form
				.find( 'input[name="payment_method"]:checked' )
				.val();
		},
		payment_method_selected: function ( e ) {
			e.stopPropagation();

			if ( $( '.payment_methods input.input-radio' ).length > 1 ) {
				var target_payment_box = $(
						'div.payment_box.' + $( this ).attr( 'ID' )
					),
					is_checked = $( this ).is( ':checked' );

				if ( is_checked && ! target_payment_box.is( ':visible' ) ) {
					$( 'div.payment_box' ).filter( ':visible' ).slideUp( 230 );

					if ( is_checked ) {
						target_payment_box.slideDown( 230 );
					}
				}
			} else {
				$( 'div.payment_box' ).show();
			}

			if ( $( this ).data( 'order_button_text' ) ) {
				// CHANGE: replaced the place order button css selector with an extended custom selector
				$( _settings.checkoutPlaceOrderSelector ).text(
					$( this ).data( 'order_button_text' )
				);
			} else {
				// CHANGE: replaced the place order button css selector with an extended custom selector
				$( _settings.checkoutPlaceOrderSelector ).text( $( _settings.checkoutPlaceOrderSelector ).data( 'value' ) );
			}

			var selectedPaymentMethod = $(
				'.woocommerce-checkout input[name="payment_method"]:checked'
			).attr( 'id' );

			if (
				selectedPaymentMethod !== wc_checkout_form.selectedPaymentMethod
			) {
				$( document.body ).trigger( 'payment_method_selected' );
			}

			// CHANGE: Add body class for selected payment method
			document.body.classList.remove( 'has-payment-method-selected--' + wc_checkout_form.selectedPaymentMethod );
			document.body.classList.add( 'has-payment-method-selected--' + selectedPaymentMethod );

			// Handle custom place order button
			// CHANGE: Check if custom place order button API is available before using it
			if ( window.wc && window.wc.customPlaceOrderButton ) {
				var gatewayId = $( this ).val();
				window.wc.customPlaceOrderButton.__maybeShow( gatewayId, createCheckoutPlaceOrderApi() );
			}
			// CHANGE: END - Check if custom place order button API is available before using it

			wc_checkout_form.selectedPaymentMethod = selectedPaymentMethod;
		},
		// CHANGE: Use collapsible block instead of jQuery to show/hide accoung fields section
		toggle_create_account: function ( e ) {
			// Bail if not target element
			if ( ! e.target || ! e.target.matches( 'input#createaccount' ) ) { return; }

			// Bail if collapsible block not available
			if ( ! window.CollapsibleBlock ) { return; }

			var checkbox = document.querySelector( 'input#createaccount' );
			var createAccountBlock = document.querySelector( 'div.create-account' );

			// Toggle state
			if ( checkbox.checked ) {
				CollapsibleBlock.expand( createAccountBlock );
			}
			else {
				CollapsibleBlock.collapse( createAccountBlock );
			}
		},
		// CHANGE: END - Use collapsible block instead of jQuery to show/hide accoung fields section
		// CHANGE: Add function to copy email field value to the login form username field
		maybe_copy_email_to_login_form: function( e ) {
			// CHANGE: Use collapsible block instead of jQuery to show/hide accoung fields section
			// Bail if not target element
			if ( ! e.target || ! e.target.closest( _settings.loginButtonSelector ) ) { return; }

			var billingEmailField = document.querySelector( _settings.emailFieldSelector );
			var usernameField = document.querySelector( _settings.usernameFieldSelector );

			// Maybe copy email to username field
			if ( billingEmailField && usernameField ) {
				usernameField.value = billingEmailField.value;
			}
		},
		// CHANGE: END - Add function to copy email field value to the login form username field
		init_checkout: function () {
			$( document.body ).trigger( 'update_checkout' );
		},
		/**
		 * Check if an address_1 field has an active autocomplete provider and should skip checkout updates
		 * @param {Event} e - The event object
		 * @return {boolean} true if updates should be skipped, false otherwise
		 */
		should_skip_address_update: function ( e ) {
			var target = e.target || e.srcElement;
			if (
				target &&
				( target.id === 'billing_address_1' ||
					target.id === 'shipping_address_1' )
			) {
				// Skip if we're manipulating the DOM for autocomplete
				if (
					target.getAttribute( 'data-autocomplete-manipulating' ) ===
					'true'
				) {
					return true;
				}

				var type = target.id.replace( '_address_1', '' );

				// Check if window.wc.addressAutocomplete exists and has an active provider
				if (
					window.wc &&
					window.wc.addressAutocomplete &&
					window.wc.addressAutocomplete.activeProvider
				) {
					if (
						window.wc.addressAutocomplete.activeProvider[ type ]
					) {
						return true;
					}
				}
			}
			return false;
		},
		/**
		 * Check if an address_1 field has an active autocomplete provider and should trigger checkout updates on blur
		 * @param {Event} e - The event object
		 * @return {boolean} true if updates should be triggered, false otherwise
		 */
		should_trigger_address_blur_update: function ( e ) {
			var target = e.target || e.srcElement;
			if (
				target &&
				( target.id === 'billing_address_1' ||
					target.id === 'shipping_address_1' )
			) {
				// Skip if we're manipulating the DOM for autocomplete
				if (
					target.getAttribute( 'data-autocomplete-manipulating' ) ===
					'true'
				) {
					return false;
				}

				var type = target.id.replace( '_address_1', '' );

				// Check if window.wc.addressAutocomplete exists and has an active provider
				if (
					window.wc &&
					window.wc.addressAutocomplete &&
					window.wc.addressAutocomplete.activeProvider
				) {
					if (
						window.wc.addressAutocomplete.activeProvider[ type ]
					) {
						return true;
					}
				}
			}
			return false;
		},
		maybe_input_changed: function ( e ) {
			if ( wc_checkout_form.should_skip_address_update( e ) ) {
				return;
			}

			if ( wc_checkout_form.dirtyInput ) {
				wc_checkout_form.input_changed( e );
			}
		},
		// CHANGE: Add function to set the autocomplete attribute values form data attributes. This fixes issue with lost user data when refreshing the page while using the Firefox Browser.
		set_autocomplete_attribute_from_data: function( e ) {
			var $fields = $( _settings.checkoutFormSelector ).find( 'input, select, textarea' );
			$fields.each( function() {
				if ( $( this ).attr( 'data-autocomplete' ) ) {
					$( this ).attr( 'autocomplete', $( this ).attr( 'data-autocomplete' ) );
				}
			} );
		},
		// CHANGE: END - Add function to set the autocomplete attribute values form data attributes. This fixes issue with lost user data when refreshing the page while using the Firefox Browser.
		// CHANGE: Add function to sync the terms checkbox state
		terms_checked_changed: function( e ) {
			var termsCheckBoxChecked = $( e.target ).prop( 'checked' );
			$( _settings.checkoutTermsSelector ).prop( 'checked', termsCheckBoxChecked );
		},
		// CHANGE: END - Add function to sync the terms checkbox state
		// CHANGE: Maybe add loading class to the form row
		maybe_set_form_row_loading: function( e ) {
			if ( e.target && e.target.matches( _settings.checkoutLoadingInputSelector ) ) {
				var formRow = e.target.closest( _settings.formRowSelector );
				if ( formRow ) {
					formRow.classList.add( _settings.loadingClass );
				}
			}
		},
		// CHANGE: END - Maybe add loading class to the form row
		// CHANGE: Add function to remove loading classes from elements after updating the checkout fragments
		maybe_stop_form_row_loading_indicators: function() {
			var maybeLoadingFields = document.querySelectorAll( _settings.checkoutLoadingInputSelector );
			for ( var i = 0; i < maybeLoadingFields.length; i++ ) {
				var input = maybeLoadingFields[ i ];
				var formRow = input.closest( _settings.formRowSelector );
				if ( formRow ) {
					formRow.classList.remove( _settings.loadingClass );
				}
			}
		},
		// CHANGE: END - Add function to remove loading classes from elements after updating the checkout fragments
		input_changed: function ( e ) {
			if ( wc_checkout_form.should_skip_address_update( e ) ) {
				return;
			}

			wc_checkout_form.dirtyInput = e.target;
			wc_checkout_form.maybe_update_checkout();

			// CHANGE: Maybe add loading class to the form row
			wc_checkout_form.maybe_set_form_row_loading( e );
		},
		address_field_blur: function ( e ) {
			if ( wc_checkout_form.should_trigger_address_blur_update( e ) ) {
				wc_checkout_form.dirtyInput = e.target;
				wc_checkout_form.maybe_update_checkout();
			}
		},
		queue_update_checkout: function ( e ) {
			// CHANGE: Add key detection to use the `event.key` property which is more reliable than `event.keyCode` or `event.which`.
			var code = e.key;

			// CHANGE: Also skip `update_checkout` when pressing other controls keys such as "Shift", "Control", "Command", "Alt" and "Arrows"
			if ( FCUtils.keyboardKeys.TAB === code || FCUtils.keyboardKeys.SHIFT === code || FCUtils.keyboardKeys.CONTROL === code || FCUtils.keyboardKeys.ALT === code || FCUtils.keyboardKeys.COMMAND_OR_WINDOWS === code || FCUtils.keyboardKeys.ARROW_LEFT === code || FCUtils.keyboardKeys.ARROW_RIGHT === code || FCUtils.keyboardKeys.ARROW_UP === code || FCUtils.keyboardKeys.ARROW_DOWN === code ) {
				return true;
			}

			// Check if we're in an address_1 field with an active provider
			var target = e.target || e.srcElement;
			if (
				target &&
				( target.id === 'billing_address_1' ||
					target.id === 'shipping_address_1' )
			) {
				// Check if an autocomplete provider is available for this field
				var type = target.id.replace( '_address_1', '' );

				// Check if window.wc.addressAutocomplete exists and has an active provider
				if (
					window.wc &&
					window.wc.addressAutocomplete &&
					window.wc.addressAutocomplete.activeProvider
				) {
					if (
						window.wc.addressAutocomplete.activeProvider[ type ]
					) {
						// Provider is available - don't queue updates while typing
						// Updates will be triggered on blur instead
						return true;
					}
				}
			}

			wc_checkout_form.dirtyInput = this;
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout(
				wc_checkout_form.maybe_update_checkout,
				'1000'
			);

			// CHANGE: Maybe add loading class to the form row
			wc_checkout_form.maybe_set_form_row_loading( e );
		},
		trigger_update_checkout: function ( event ) {
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.dirtyInput = false;
			$( document.body ).trigger( 'update_checkout', {
				current_target: event ? event.currentTarget : null,
			} );
		},
		maybe_update_checkout: function () {
			var update_totals = true;

			if ( $( wc_checkout_form.dirtyInput ).length ) {
				var $required_inputs = $( wc_checkout_form.dirtyInput )
					.closest( 'div' )
					.find( '.address-field.validate-required' );

				if ( $required_inputs.length ) {
					$required_inputs.each( function () {
						if (
							$( this ).find( 'input.input-text' ).val() === ''
						) {
							update_totals = false;
						}
					} );
				}
			}
			if ( update_totals ) {
				wc_checkout_form.trigger_update_checkout();
			}
		},
		// CHANGE: Removed shipping to different address checkout `change` listener handler function `ship_to_different_address`
		reset_update_checkout_timer: function () {
			clearTimeout( wc_checkout_form.updateTimer );
		},
		is_valid_json: function ( raw_json ) {
			try {
				var json = JSON.parse( raw_json );

				return json && 'object' === typeof json;
			} catch ( e ) {
				return false;
			}
		},
		validate_field: function ( e ) {
			var $this = $( this ),
				$parent = $this.closest( '.form-row' ),
				validated = true,
				validate_required = $parent.is( '.validate-required' ),
				validate_email = $parent.is( '.validate-email' ),
				validate_phone = $parent.is( '.validate-phone' ),
				pattern = '',
				event_type = e.type;

			if ( 'input' === event_type ) {
				$this
					.removeAttr( 'aria-invalid' )
					.removeAttr( 'aria-describedby' );
				$parent.find( '.checkout-inline-error-message' ).remove();
				$parent.removeClass(
					// eslint-disable-next-line max-len
					'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-validated'
				);
			}

			if (
				'validate' === event_type ||
				'change' === event_type ||
				'focusout' === event_type
			) {
				if ( validate_required ) {
					if (
						( 'checkbox' === $this.attr( 'type' ) &&
							! $this.is( ':checked' ) ) ||
						$this.val() === ''
					) {
						$this.attr( 'aria-invalid', 'true' );
						$parent
							.removeClass( 'woocommerce-validated' )
							.addClass(
								'woocommerce-invalid woocommerce-invalid-required-field'
							);
						validated = false;
					}
				}

				if ( validate_email ) {
					if ( $this.val() ) {
						/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
						pattern = new RegExp(
							// eslint-disable-next-line max-len
							/^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[0-9a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i
						); // eslint-disable-line max-len

						if ( ! pattern.test( $this.val() ) ) {
							$this.attr( 'aria-invalid', 'true' );
							$parent
								.removeClass( 'woocommerce-validated' )
								.addClass(
									'woocommerce-invalid woocommerce-invalid-email'
								); // eslint-disable-line max-len
							validated = false;
						}
					}
				}

				if ( validate_phone ) {
					pattern = new RegExp( /[\s\#0-9_\-\+\/\(\)\.]/g );

					if ( 0 < $this.val().replace( pattern, '' ).length ) {
						$this.attr( 'aria-invalid', 'true' );
						$parent
							.removeClass( 'woocommerce-validated' )
							.addClass(
								'woocommerce-invalid woocommerce-invalid-phone'
							);
						validated = false;
					}
				}

				if ( validated ) {
					$this
						.removeAttr( 'aria-invalid' )
						.removeAttr( 'aria-describedby' );
					$parent.find( '.checkout-inline-error-message' ).remove();
					$parent
						.removeClass(
							'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone'
						)
						.addClass( 'woocommerce-validated' ); // eslint-disable-line max-len
				}
			}
		},
		update_checkout: function ( event, args ) {
			// CHANGE: Mirror all field values
			wc_checkout_form.maybe_mirror_all_address_field_values();

			// Small timeout to prevent multiple requests when several fields update at the same time
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout(
				wc_checkout_form.update_checkout_action,
				'5',
				args
			);
		},
		update_checkout_action: function ( args ) {
			// CHANGE: Check flag that allows or block updating the checkout
			if ( ! window.can_update_checkout ) { return; }

			if ( wc_checkout_form.xhr ) {
				wc_checkout_form.xhr.abort();
			}

			// CHANGE: Use checkout form selector from settings
			if ( $( _settings.checkoutFormSelector ).length === 0 ) {
				return;
			}

			args =
				typeof args !== 'undefined'
					? args
					: {
							update_shipping_method: true,
					  };

			var country = $( '#billing_country' ).val(),
				state = $( '#billing_state' ).val(),
				postcode = $( ':input#billing_postcode' ).val(),
				city = $( '#billing_city' ).val(),
				address = $( ':input#billing_address_1' ).val(),
				address_2 = $( ':input#billing_address_2' ).val(),
				// CHANGE: Always get shipping address values from shipping fields
				s_country = $( '#shipping_country' ).val(),
				s_state = $( '#shipping_state' ).val(),
				s_postcode = $( ':input#shipping_postcode' ).val(),
				s_city = $( '#shipping_city' ).val(),
				s_address = $( ':input#shipping_address_1' ).val(),
				s_address_2 = $( ':input#shipping_address_2' ).val(),
				// CHANGE: END - Always get shipping address values from shipping fields
				$required_inputs = $( wc_checkout_form.$checkout_form ).find(
					'.address-field.validate-required:visible'
				),
				has_full_address = true;

			if ( $required_inputs.length ) {
				$required_inputs.each( function () {
					if ( $( this ).find( ':input' ).val() === '' ) {
						has_full_address = false;
					}
				} );
			}

			// CHANGE: Removed if handling of different shipping address checkbox, always get shipping address values from shipping fields (see above)

			var data = {
				security: wc_checkout_params.update_order_review_nonce,
				payment_method: wc_checkout_form.get_payment_method(),
				country: country,
				state: state,
				postcode: postcode,
				city: city,
				address: address,
				address_2: address_2,
				s_country: s_country,
				s_state: s_state,
				s_postcode: s_postcode,
				s_city: s_city,
				s_address: s_address,
				s_address_2: s_address_2,
				has_full_address: has_full_address,
				post_data: $( 'form.checkout' ).serialize(),
			};

			if ( false !== args.update_shipping_method ) {
				var shipping_methods = {};

				$(
					// eslint-disable-next-line max-len
					'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]'
				).each( function () {
					shipping_methods[ $( this ).data( 'index' ) ] =
						$( this ).val();
				} );

				data.shipping_method = shipping_methods;
			}

			// CHANGE: Define selector for blocking UI of sections as a variable, also block the shipping methods section when updating
			var blockui_selector = _settings.checkoutBlockUISelector;

			// CHANGE: Add flag to indicate whether payment methods fragment should be refreshed
			if ( ( undefined !== window.can_update_payment_methods && true !== window.can_update_payment_methods ) || false === args.refresh_payment_methods ) {
				blockui_selector = blockui_selector.replace( '.woocommerce-checkout-payment, ', '' );
				data.refresh_payment_methods = 'false';
			}

			// CHANGE: Use selector from settings to Block UI elements
			$( blockui_selector ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			// CHANGE: Set body class for processing checkout update
			document.body.classList.add( 'fc-processing-update' );

			wc_checkout_form.xhr = $.ajax( {
				type: 'POST',
				url: wc_checkout_params.wc_ajax_url
					.toString()
					.replace( '%%endpoint%%', 'update_order_review' ),
				data: data,
				success: function ( data ) {
					// Reload the page if requested
					if ( data && true === data.reload ) {
						window.location.reload();
						return;
					}

					// CHANGE: Set global flag for processing checkout update
					window.processing_checkout_update = true;

					// Remove any notices added previously
					$( '.woocommerce-NoticeGroup-updateOrderReview' ).remove();

					// CHANGE: replaced the terms checkbox css selector
					var termsCheckBoxChecked = $( _settings.checkoutTermsSelector ).prop( 'checked' );

					// Save payment details to a temporary object
					var paymentDetails = {};
					$( '.payment_box :input' ).each( function () {
						var ID = $( this ).attr( 'id' );

						if ( ID ) {
							if (
								$.inArray( $( this ).attr( 'type' ), [
									'checkbox',
									'radio',
								] ) !== -1
							) {
								paymentDetails[ ID ] =
									$( this ).prop( 'checked' );
							} else {
								paymentDetails[ ID ] = $( this ).val();
							}
						}
					});

					// CHANGE: Set variables for current focused element
					FCUtils.setCurrentFocusedElementGlobalVariables();

					// Always update the fragments
					if ( data && data.fragments ) {
						// CHANGE: Trigger custom event before fragments are replaced.
						$( document.body ).trigger( 'fc_checkout_fragments_replace_before', [ data ] );

						// CHANGE: Try to remove intl-tel-input components from existing fields before replacing fragments
						if ( window.intlTelInput ) {
							// Get intl-tel-input object
							var intlTelInputObject = window.intlTelInputGlobals || window.intlTelInput;

							// Get all phone fields
							var allPhoneFields = document.querySelectorAll( _settings.phoneFieldSelector );
							for ( var i = 0; i < allPhoneFields.length; i++ ) {
								var field = allPhoneFields[i];
								var phoneField = intlTelInputObject.getInstance( field );
								if ( phoneField ) {
									var preservedValue = phoneField.getNumber();
									phoneField.destroy();
									field.value = preservedValue;
								}
							}
						}
						// CHANGE: END - Try to remove intl-tel-input components from existing fields before replacing fragments

						$.each( data.fragments, function ( key, value ) {
							// CHANGE: Declare local variables needed for some checks before replacing the fragment
							var fragmentToReplace = document.querySelector( key );
							var replaceFragment = true;

							// CHANGE: Maybe set to skip fragment with the focus within it. This avoids unexpected closing of mobile keyboard and lost of focus when updating fragments.
							if ( fragmentToReplace && window.fcCurrentFocusedElement.closest( key ) && window.fcCurrentFocusedElement.closest( _settings.focusedFieldSkipFragmentReplaceSelector ) ) {
								replaceFragment = false;
							}

							// CHANGE: Allow fragments to be replaced every time even when their contents are equal the existing elements in the DOM, this overseeds the check for focus within the fragment.
							if ( value && -1 !== value.toString().indexOf( 'fc-fragment-always-replace' ) ) {
								replaceFragment = true;
							}

							// CHANGE: Check whether fragments should be replaced
							if ( replaceFragment && ( ! wc_checkout_form.fragments || wc_checkout_form.fragments[ key ] !== value ) ) {
								// CHANGE: Log replaced fragment to console if debug mode is enabled.
								if ( 'yes' === fcSettings.debugMode ) {
									console.log( 'Replacing fragment: ' + key );
								}
								$( key ).replaceWith( value );
							}
							// CHANGE: Log skipped fragment to console if debug mode is enabled.
							else if ( 'yes' === fcSettings.debugMode ) {
								console.log( 'Skipping fragment: ' + key );
							}

							$( key ).unblock();
						} );
						wc_checkout_form.fragments = data.fragments;

						// CHANGE: Trigger custom event after fragments are replaced.
						$( document.body ).trigger( 'fc_checkout_fragments_replace_after', [ data ] );
					}

					// CHANGE: Unblock remaining blocked fragments after updating.
					$( _settings.checkoutBlockUISelector ).unblock();

					// CHANGE: Unset body class for processing checkout update
					document.body.classList.remove( 'fc-processing-update' );

					// CHANGE: Re-set focus to the element with focus previously to updating fragments
					FCUtils.maybeRefocusElement( window.fcCurrentFocusedElement, window.fcCurrentFocusedElementValue );

					// Recheck the terms and conditions box, if needed
					if ( termsCheckBoxChecked ) {
						// CHANGE: replaced the terms checkbox css selector
						$( _settings.checkoutTermsSelector ).prop( 'checked', true );
					}

					// Fill in the payment details if possible without overwriting data if set.
					if ( ! $.isEmptyObject( paymentDetails ) ) {
						$( '.payment_box :input' ).each( function () {
							var ID = $( this ).attr( 'id' );
							if ( ID ) {
								if (
									$.inArray( $( this ).attr( 'type' ), [
										'checkbox',
										'radio',
									] ) !== -1
								) {
									$( this )
										.prop( 'checked', paymentDetails[ ID ] )
										.trigger( 'change' );
								} else if (
									$.inArray( $( this ).attr( 'type' ), [
										'select',
									] ) !== -1
								) {
									$( this )
										.val( paymentDetails[ ID ] )
										.trigger( 'change' );
								} else if (
									null !== $( this ).val() &&
									0 === $( this ).val().length
								) {
									$( this )
										.val( paymentDetails[ ID ] )
										.trigger( 'change' );
								}
							}
						} );
					}

					// Check for error
					if ( data && 'failure' === data.result ) {
						// CHANGE: Use checkout form selector from settings
						var $form = $( _settings.checkoutFormSelector );

						// Remove notices from all sources
						$(
							'.woocommerce-error, .woocommerce-message, .is-error, .is-success'
						).remove();

						// Add new errors returned by this event
						if ( data.messages ) {
							$form.prepend(
								'<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview">' +
									data.messages +
									'</div>'
							); // eslint-disable-line max-len
						} else {
							$form.prepend( data );
						}

						// Lose focus for all fields
						$form
							.find( '.input-text, select, input:checkbox' )
							.trigger( 'validate' )
							.trigger( 'blur' );

						wc_checkout_form.scroll_to_notices();
					}

					// Re-init methods
					wc_checkout_form.init_payment_methods();

					// If there is no errors and the checkout update was triggered by changing the shipping method, focus its radio input.
					if (
						data &&
						'success' === data.result &&
						args.current_target &&
						args.current_target.id.indexOf( 'shipping_method' ) !==
							-1
					) {
						document
							.getElementById( args.current_target.id )
							.focus();
					}

					// CHANGE: Unset global flag for processing checkout update
					window.processing_checkout_update = false;

					// CHANGE: Set to not prompt user before leaving the page
					_updateBeforeUnload = false;

					// CHANGE: Unset current focused element and value
					setTimeout( function() {
						FCUtils.unsetCurrentFocusedElementGlobalVariables();
					}, 60 );

					// CHANGE: Maybe remove loading class from form rows when completing the ajax request
					wc_checkout_form.maybe_stop_form_row_loading_indicators();

					// Fire updated_checkout event.
					$( document.body ).trigger( 'updated_checkout', [ data ] );
				},
				// CHANGE: Maybe remove loading class from form rows when completing the ajax request
				error: function() {
					wc_checkout_form.maybe_stop_form_row_loading_indicators();
				}
				// CHANGE: END - Maybe remove loading class from form rows when completing the ajax request
			} );
		},
		handleUnloadEvent: function ( e ) {
			// Modern browsers have their own standard generic messages that they will display.
			// Confirm, alert, prompt or custom message are not allowed during the unload event
			// Browsers will display their own standard messages

			// Check if the browser is Internet Explorer
			if (
				navigator.userAgent.indexOf( 'MSIE' ) !== -1 ||
				!! document.documentMode
			) {
				// IE handles unload events differently than modern browsers
				e.preventDefault();
				return undefined;
			}

			return true;
		},
		attachUnloadEventsOnSubmit: function () {
			$( window ).on( 'beforeunload', this.handleUnloadEvent );
		},
		detachUnloadEventsOnSubmit: function () {
			$( window ).off( 'beforeunload', this.handleUnloadEvent );
		},
		blockOnSubmit: function ( $form ) {
			var isBlocked = $form.data( 'blockUI.isBlocked' );

			if ( 1 !== isBlocked ) {
				$form.block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					},
				} );
			}
		},
		submitOrder: function () {
			wc_checkout_form.blockOnSubmit( $( this ) );
		},
		submit: function () {
			wc_checkout_form.reset_update_checkout_timer();
			var $form = $( this );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			// CHANGE: Reset flag to update on `beforeunload`
			_updateBeforeUnload = false;

			// CHANGE: Mirror all field values
			wc_checkout_form.maybe_mirror_all_address_field_values();

			// Trigger a handler to let gateways manipulate the checkout if needed
			if (
				$form.triggerHandler( 'checkout_place_order', [
					wc_checkout_form,
				] ) !== false &&
				$form.triggerHandler(
					'checkout_place_order_' +
						wc_checkout_form.get_payment_method(),
					[ wc_checkout_form ]
				) !== false
			) {
				$form.addClass( 'processing' );

				// CHANGE: Disable place order button
				var currentFocusedElement = document.activeElement;
				$( _settings.checkoutPlaceOrderSelector ).attr( 'disabled', 'disabled' );
				$( _settings.checkoutPlaceOrderSelector ).addClass( 'disabled' );
				if ( 'yes' === _settings.checkoutPlaceOrderApplyLoadingClass ) {
					$( _settings.checkoutPlaceOrderSelector ).addClass( _settings.loadingClass );
				}
				// END - Disable place order button

				// CHANGE: Block checkout update requests
				window.can_update_checkout = false;

				wc_checkout_form.blockOnSubmit( $form );

				// Attach event to block reloading the page when the form has been submitted
				wc_checkout_form.attachUnloadEventsOnSubmit();

				// ajaxSetup is global, but we use it to ensure JSON is valid once returned.
				$.ajaxSetup( {
					dataFilter: function ( raw_response, dataType ) {
						// We only want to work with JSON
						if ( 'json' !== dataType ) {
							return raw_response;
						}

						if ( wc_checkout_form.is_valid_json( raw_response ) ) {
							return raw_response;
						} else {
							// Attempt to fix the malformed JSON
							var maybe_valid_json =
								raw_response.match( /{"result.*}/ );

							if ( null === maybe_valid_json ) {
								console.log(
									'Unable to fix malformed JSON #1'
								);
							} else if (
								wc_checkout_form.is_valid_json(
									maybe_valid_json[ 0 ]
								)
							) {
								console.log(
									'Fixed malformed JSON. Original:'
								);
								console.log( raw_response );
								raw_response = maybe_valid_json[ 0 ];
							} else {
								console.log(
									'Unable to fix malformed JSON #2'
								);
							}
						}

						return raw_response;
					},
				} );

				$.ajax( {
					type: 'POST',
					url: wc_checkout_params.checkout_url,
					data: $form.serialize(),
					dataType: 'json',
					success: function ( result ) {
						// Detach the unload handler that prevents a reload / redirect
						wc_checkout_form.detachUnloadEventsOnSubmit();

						// CHANGE: Set to not prompt user before leaving the page
						_updateBeforeUnload = false;

						// CHANGE: Use event like trigger to allow other scripts to stop the code execution at this point
						var eventResult = $( document.body ).triggerHandler( 'fc_checkout_request_place_order_success', [ result, wc_checkout_form ] );
						if ( eventResult === false ) {
							// CHANGE: Unblock checkout update requests
							window.can_update_checkout = true;

							// CHANGE: Unblock the place order button
							if ( 'yes' === _settings.checkoutPlaceOrderApplyLoadingClass ) {
								$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.loadingClass );
							}
							$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
							$( _settings.checkoutPlaceOrderSelector ).removeClass( 'disabled' );
							FCUtils.maybeRefocusElement( currentFocusedElement );
							// END - Unblock the place order button

							return; // Exit the function if the event returned value is false
						}

						$( '.checkout-inline-error-message' ).remove();

						try {
							if (
								'success' === result.result &&
								$form.triggerHandler(
									'checkout_place_order_success',
									[ result, wc_checkout_form ]
								) !== false
							) {
								if (
									-1 ===
										result.redirect.indexOf( 'https://' ) ||
									-1 === result.redirect.indexOf( 'http://' )
								) {
									window.location = result.redirect;
								} else {
									window.location = decodeURI(
										result.redirect
									);
								}
							} else if ( 'failure' === result.result ) {
								throw 'Result failure';
							} else {
								throw 'Invalid response';
							}
						} catch ( err ) {
							// Reload page
							if ( true === result.reload ) {
								window.location.reload();
								return;
							}

							// CHANGE: Unblock checkout update requests
							window.can_update_checkout = true;

							// CHANGE: Unblock the place order button
							if ( 'yes' === _settings.checkoutPlaceOrderApplyLoadingClass ) {
								$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.loadingClass );
							}
							$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
							$( _settings.checkoutPlaceOrderSelector ).removeClass( 'disabled' );
							FCUtils.maybeRefocusElement( currentFocusedElement );
							// END - Unblock the place order button

							// Trigger update in case we need a fresh nonce
							if ( true === result.refresh ) {
								$( document.body ).trigger( 'update_checkout' );
							}

							// Add new errors
							if ( result.messages ) {
								var $msgs = $( result.messages )
									// The error notice template (plugins/woocommerce/templates/notices/error.php)
									// adds the role="alert" to a list HTML element. This becomes a problem in this context
									// because screen readers won't read the list content correctly if its role is not "list".
									.removeAttr( 'role' )
									.attr( 'tabindex', '-1' );
								var $msgsWithLink =
									wc_checkout_form.wrapMessagesInsideLink(
										$msgs
									);
								var $msgsWrapper = $(
									'<div role="alert"></div>'
								).append( $msgsWithLink );

								wc_checkout_form.submit_error(
									$msgsWrapper.prop( 'outerHTML' )
								);
								wc_checkout_form.show_inline_errors( $msgs );
							} else {
								wc_checkout_form.submit_error(
									'<div class="woocommerce-error">' +
										wc_checkout_params.i18n_checkout_error +
										'</div>'
								); // eslint-disable-line max-len
							}
						}
					},
					error: function ( jqXHR, textStatus, errorThrown ) {
						// CHANGE: Unblock checkout update requests
						window.can_update_checkout = true;

						// CHANGE: Unblock the place order button
						if ( 'yes' === _settings.checkoutPlaceOrderApplyLoadingClass ) {
							$( _settings.checkoutPlaceOrderSelector ).removeClass( _settings.loadingClass );
						}
						$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
						$( _settings.checkoutPlaceOrderSelector ).removeClass( 'disabled' );
						FCUtils.maybeRefocusElement( currentFocusedElement );
						// END - Unblock the place order button

						// Detach the unload handler that prevents a reload / redirect
						wc_checkout_form.detachUnloadEventsOnSubmit();

						// This is just a technical error fallback. i18_checkout_error is expected to be always defined and localized.
						var errorMessage = errorThrown;

						if (
							typeof wc_checkout_params === 'object' &&
							wc_checkout_params !== null &&
							wc_checkout_params.hasOwnProperty(
								'i18n_checkout_error'
							) &&
							typeof wc_checkout_params.i18n_checkout_error ===
								'string' &&
							wc_checkout_params.i18n_checkout_error.trim() !== ''
						) {
							errorMessage =
								wc_checkout_params.i18n_checkout_error;
						}

						wc_checkout_form.submit_error(
							'<div class="woocommerce-error">' +
								errorMessage +
								'</div>'
						);
					},
				} );
			}

			return false;
		},
		submit_error: function ( error_message ) {
			$(
				'.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message, .is-error, .is-success'
			).remove();
			wc_checkout_form.$checkout_form.prepend(
				'<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
					error_message +
					'</div>'
			); // eslint-disable-line max-len
			wc_checkout_form.$checkout_form
				.removeClass( 'processing' )
				.unblock();

			// CHANGE: Unblock the place order button
			$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
			$( _settings.checkoutPlaceOrderSelector ).removeClass( 'disabled' );
			// END - Unblock the place order button

			wc_checkout_form.$checkout_form
				.find( '.input-text, select, input:checkbox' )
				.trigger( 'validate' )
				.trigger( 'blur' );
			wc_checkout_form.scroll_to_notices();
			wc_checkout_form.$checkout_form
				.find(
					'.woocommerce-error[tabindex="-1"], .wc-block-components-notice-banner.is-error[tabindex="-1"]'
				)
				.focus();
			$( document.body ).trigger( 'checkout_error', [ error_message ] );
		},
		wrapMessagesInsideLink: function ( $msgs ) {
			$msgs.find( 'li[data-id]' ).each( function () {
				const $this = $( this );
				const dataId = $this.attr( 'data-id' );
				if ( dataId ) {
					const $link = $( '<a>', {
						href: '#' + dataId,
						html: $this.html(),
					} );
					$this.empty().append( $link );
				}
			} );

			return $msgs;
		},
		show_inline_errors: function ( $messages ) {
			$messages.find( 'li[data-id]' ).each( function () {
				const $this = $( this );
				const dataId = $this.attr( 'data-id' );
				const $field = $( '#' + dataId );

				if ( $field.length === 1 ) {
					const descriptionId = dataId + '_description';
					const msg = $this.text().trim();
					const $formRow = $field.closest( '.form-row' );

					const errorMessage = document.createElement( 'p' );
					errorMessage.id = descriptionId;
					errorMessage.className = 'checkout-inline-error-message';
					errorMessage.textContent = msg;

					if ( $formRow && errorMessage.textContent.length > 0 ) {
						$formRow.append( errorMessage );
					}

					$field.attr( 'aria-describedby', descriptionId );
					$field.attr( 'aria-invalid', 'true' );
				}
			} );
		},
		scroll_to_notices: function () {
			var scrollElement = $(
				'.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout'
			);

			if ( ! scrollElement.length ) {
				// CHANGE: Use checkout form selector from settings
				scrollElement = $( _settings.checkoutFormSelector );
			}
			$.scroll_to_notices( scrollElement );
		},
	};

	var wc_checkout_coupons = {
		init: function () {
			$( document.body ).on(
				'click',
				'a.showcoupon',
				this.show_coupon_form
			);
			$( document.body ).on(
				'click',
				'.woocommerce-remove-coupon',
				this.remove_coupon
			);
			$( document.body ).on(
				'keydown',
				'.woocommerce-remove-coupon',
				this.on_keydown_remove_coupon
			);
			$( document.body ).on(
				'change input',
				'#coupon_code',
				this.remove_coupon_error
			);
			$( 'form.checkout_coupon' )
				.hide()
				.on( 'submit', this.submit.bind( this ) );
		},
		show_coupon_form: function () {
			var $showcoupon = $( this );

			$( '.checkout_coupon' ).slideToggle( 400, function () {
				var $coupon_form = $( this );

				if ( $coupon_form.is( ':visible' ) ) {
					$showcoupon.attr( 'aria-expanded', 'true' );
					$coupon_form.find( ':input:eq(0)' ).trigger( 'focus' );
				} else {
					$showcoupon.attr( 'aria-expanded', 'false' );
				}
			} );
			return false;
		},
		show_coupon_error: function ( html_element, $target ) {
			if ( $target.length === 0 ) {
				return;
			}

			this.remove_coupon_error();

			var msg = $( $.parseHTML( html_element ) ).text().trim();

			if ( msg === '' ) {
				return;
			}

			$target
				.find( '#coupon_code' )
				.focus()
				.addClass( 'has-error' )
				.attr( 'aria-invalid', 'true' )
				.attr( 'aria-describedby', 'coupon-error-notice' );

			$( '<span>', {
				class: 'coupon-error-notice',
				id: 'coupon-error-notice',
				role: 'alert',
				text: msg,
			} ).appendTo( $target );
		},
		remove_coupon_error: function () {
			var $coupon_field = $( '#coupon_code' );

			if ( $coupon_field.length === 0 ) {
				return;
			}

			$coupon_field
				.removeClass( 'has-error' )
				.removeAttr( 'aria-invalid' )
				.removeAttr( 'aria-describedby' )
				.next( '.coupon-error-notice' )
				.remove();
		},

		clear_coupon_input: function () {
			const $coupon_field = $( '#coupon_code' );
			$coupon_field
				.val( '' )
				.removeClass( 'has-error' )
				.removeAttr( 'aria-invalid' )
				.removeAttr( 'aria-describedby' )
				.next( '.coupon-error-notice' )
				.remove();
		},
		submit: function ( evt ) {
			var $form = $( evt.currentTarget );
			var $coupon_field = $form.find( '#coupon_code' );
			var self = this;

			self.remove_coupon_error();

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			$form.addClass( 'processing' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			var data = {
				security: wc_checkout_params.apply_coupon_nonce,
				coupon_code: $form.find( 'input[name="coupon_code"]' ).val(),
				billing_email: wc_checkout_form.$checkout_form
					.find( 'input[name="billing_email"]' )
					.val(),
			};

			$.ajax( {
				type: 'POST',
				url: wc_checkout_params.wc_ajax_url
					.toString()
					.replace( '%%endpoint%%', 'apply_coupon' ),
				data: data,
				success: function ( response ) {
					$(
						'.woocommerce-error, .woocommerce-message, .is-error, .is-success, .checkout-inline-error-message'
					).remove();
					$form.removeClass( 'processing' ).unblock();

					if ( response ) {
						// We only want to show coupon notices if they are no errors.
						// Coupon errors are shown under the input.
						if (
							response.indexOf( 'woocommerce-error' ) === -1 &&
							response.indexOf( 'is-error' ) === -1
						) {
							$form.slideUp( 400, function () {
								$( 'a.showcoupon' ).attr(
									'aria-expanded',
									'false'
								);
								$form.before( response );
							} );
							self.clear_coupon_input();
						} else {
							self.show_coupon_error(
								response,
								$coupon_field.parent()
							);
						}

						$( document.body ).trigger(
							'applied_coupon_in_checkout',
							[ data.coupon_code ]
						);
						$( document.body ).trigger( 'update_checkout', {
							update_shipping_method: false,
						} );
					}
				},
				dataType: 'html',
			} );

			return false;
		},
		remove_coupon: function ( e ) {
			// CHANGE: Bail when Fluid Checkout integrated coupon code feature is enabled
			if ( _settings.checkoutCoupons && 'yes' === _settings.checkoutCoupons.isEnabled ) { return; }

			e.preventDefault();

			var container = $( this ).parents(
					'.woocommerce-checkout-review-order'
				),
				coupon = $( this ).data( 'coupon' );

			container.addClass( 'processing' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			var data = {
				security: wc_checkout_params.remove_coupon_nonce,
				coupon: coupon,
			};

			$.ajax( {
				type: 'POST',
				url: wc_checkout_params.wc_ajax_url
					.toString()
					.replace( '%%endpoint%%', 'remove_coupon' ),
				data: data,
				success: function ( code ) {
					$(
						'.woocommerce-error, .woocommerce-message, .is-error, .is-success'
					).remove();
					container.removeClass( 'processing' ).unblock();

					if ( code ) {
						$( 'form.woocommerce-checkout' ).before( code );

						$( document.body ).trigger(
							'removed_coupon_in_checkout',
							[ data.coupon ]
						);
						$( document.body ).trigger( 'update_checkout', {
							update_shipping_method: false,
						} );

						// Remove coupon code from coupon field
						wc_checkout_coupons.clear_coupon_input();
						$( 'form.checkout_coupon' ).slideUp( 400, function () {
							$( 'a.showcoupon' ).attr(
								'aria-expanded',
								'false'
							);
						} );
					}
				},
				error: function ( jqXHR ) {
					if ( wc_checkout_params.debug_mode ) {
						/* jshint devel: true */
						console.log( jqXHR.responseText );
					}
				},
				dataType: 'html',
			} );
		},
		/**
		 * Handle when pressing the Space key on the remove coupon link.
		 * This is necessary because the link got the role="button" attribute
		 * and needs to act like a button.
		 *
		 * @param {Object} e The JQuery event
		 */
		on_keydown_remove_coupon: function ( e ) {
			if ( e.key === ' ' ) {
				e.preventDefault();
				$( this ).trigger( 'click' );
			}
		},
	};

	var wc_checkout_login_form = {
		init: function () {
			$( document.body ).on(
				'click',
				'a.showlogin',
				this.show_login_form
			);
		},
		show_login_form: function () {
			var $form = $( 'form.login, form.woocommerce-form--login' );
			if ( $form.is( ':visible' ) ) {
				// If already visible, hide it.
				$form.slideToggle( {
					duration: 400,
				} );
			} else {
				// If not visible, show it and then scroll
				$form.slideToggle( {
					duration: 400,
					complete: function () {
						if ( $form.is( ':visible' ) ) {
							$( 'html, body' ).animate(
								{
									scrollTop: $form.offset().top - 50,
								},
								300
							);
						}
					},
				} );
			}
			return false;
		},
	};

	var wc_terms_toggle = {
		init: function () {
			$( document.body ).on(
				'click',
				'a.woocommerce-terms-and-conditions-link',
				this.toggle_terms
			);
		},

		toggle_terms: function () {
			if ( $( '.woocommerce-terms-and-conditions' ).length ) {
				$( '.woocommerce-terms-and-conditions' ).slideToggle(
					function () {
						var link_toggle = $(
							'.woocommerce-terms-and-conditions-link'
						);

						if (
							$( '.woocommerce-terms-and-conditions' ).is(
								':visible'
							)
						) {
							link_toggle.addClass(
								'woocommerce-terms-and-conditions-link--open'
							);
							link_toggle.removeClass(
								'woocommerce-terms-and-conditions-link--closed'
							);
						} else {
							link_toggle.removeClass(
								'woocommerce-terms-and-conditions-link--open'
							);
							link_toggle.addClass(
								'woocommerce-terms-and-conditions-link--closed'
							);
						}
					}
				);

				return false;
			}
		},
	};

	wc_checkout_form.init();
	wc_checkout_coupons.init();
	wc_checkout_login_form.init();
	wc_terms_toggle.init();
} );
