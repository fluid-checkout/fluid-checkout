/**
 * Checkout Script
 * 
 * Replaces the original WooCommerce `checkout.js`.
 */

/* global wc_checkout_params */
jQuery( function( $ ) {

	// wc_checkout_params is required to continue, ensure the object exists
	if ( typeof wc_checkout_params === 'undefined' ) {
		return false;
	}

	// CHANGE: Create flag to allow or block updating the checkout
	window.can_update_checkout = true;

	$.blockUI.defaults.overlayCSS.cursor = 'default';

	// CHANGE: Add flag to up prevent users from leaving the page when there is unsaved data
	var _updateBeforeUnload = false;

	// CHANGE: Add default settings object
	var _settings = {
		checkoutPlaceOrderSelector: '#place_order, .fc-place-order-button',
		checkoutTermsSelector: '.fc-terms-checkbox',
		checkoutUpdateFieldsSelector: '.address-field input.input-text, .update_totals_on_change input.input-text',
		checkoutUpdateBeforeUnload: 'yes',
	};

	// CHANGE: Add auxiliar function to merge objects
	/*!
	* Merge two or more objects together.
	* (c) 2017 Chris Ferdinandi, MIT License, https://gomakethings.com
	* @param   {Boolean}  deep     If true, do a deep (or recursive) merge [optional]
	* @param   {Object}   objects  The objects to merge together
	* @returns {Object}            Merged values of defaults and options
	*/
	var extend = function () {
		// Variables
		var extended = {};
		var deep = false;
		var i = 0;

		// Check if a deep merge
		if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
			deep = arguments[0];
			i++;
		}

		// Merge the object into the extended object
		var merge = function (obj) {
			for (var prop in obj) {
				if (obj.hasOwnProperty(prop)) {
					// If property is an object, merge properties
					if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
						extended[prop] = extend(extended[prop], obj[prop]);
					} else {
						extended[prop] = obj[prop];
					}
				}
			}
		};

		// Loop through each object and conduct a merge
		for (; i < arguments.length; i++) {
			var obj = arguments[i];
			merge(obj);
		}

		return extended;
    };

	var wc_checkout_form = {
		updateTimer: false,
		dirtyInput: false,
		selectedPaymentMethod: false,
		xhr: false,
		$order_review: $( '#order_review' ),
		$checkout_form: $( 'form.checkout' ),
		init: function() {
			// CHANGE: Merge default settings object with values from the server settings object
			_settings = extend( true, _settings, window.fcSettings );

			$( document.body ).on( 'update_checkout', this.update_checkout );
			$( document.body ).on( 'init_checkout', this.init_checkout );

			// Payment methods
			this.$checkout_form.on( 'click', 'input[name="payment_method"]', this.payment_method_selected );

			if ( $( document.body ).hasClass( 'woocommerce-order-pay' ) ) {
				this.$order_review.on( 'click', 'input[name="payment_method"]', this.payment_method_selected );
				this.$order_review.on( 'submit', this.submitOrder );
				this.$order_review.attr( 'novalidate', 'novalidate' );
			}

			// Prevent HTML5 validation which can conflict.
			this.$checkout_form.attr( 'novalidate', 'novalidate' );

			// Form submission
			this.$checkout_form.on( 'submit', this.submit );

			// Inline validation
			this.$checkout_form.on( 'input validate change', '.input-text, select, input:checkbox', this.validate_field );

			// Manual trigger
			this.$checkout_form.on( 'update', this.trigger_update_checkout );

			// Inputs/selects which update totals
			// CHANGE: Removed selector `#ship-to-different-address input`
			this.$checkout_form.on( 'change', 'select.shipping_method, input[name^="shipping_method"], .update_totals_on_change select, .update_totals_on_change input[type="radio"], .update_totals_on_change input[type="checkbox"]', this.trigger_update_checkout ); // eslint-disable-line max-len
			this.$checkout_form.on( 'change', '.address-field select', this.input_changed );
			
			// CHANGE: Move CSS selector for fields that trigger update checkout when value is changed
			this.$checkout_form.on( 'change', _settings.checkoutUpdateFieldsSelector, this.maybe_input_changed ); // eslint-disable-line max-len
			this.$checkout_form.on( 'keydown', _settings.checkoutUpdateFieldsSelector, this.queue_update_checkout ); // eslint-disable-line max-len

			// Address fields
			// CHANGE: Removed shipping to different address checkout `change` listener

			// CHANGE: Update checkout totals to save data to session when user switches tabs, apps, goes to homescreen, etc.
			document.addEventListener( 'visibilitychange', this.maybe_update_checkout_visibility_change );

			// CHANGE: Maybe prevent leaving the page if there are unsaved changes, and trigger `update_checkout` to save the data.
			if ( 'yes' === _settings.checkoutUpdateBeforeUnload ) {
				this.$checkout_form.on( 'change, input', 'select, input, textarea', this.maybe_prevent_unload );
			}

			// CHANGE: Update checkout when "billing same as shipping" checked state changes
			this.$checkout_form.on( 'change', '#billing_same_as_shipping', this.billing_same_shipping_changed );
			$( document.body ).on( 'updated_checkout', this.maybe_reinitialize_collapsible_blocks );

			// CHANGE: Add event listener to sync terms checkbox state
			this.$checkout_form.on( 'change', _settings.checkoutTermsSelector, this.terms_checked_changed );

			// Trigger events
			// CHANGE: Removed shipping to different address checkout `change` trigger
			this.init_payment_methods();

			// Update on page load
			if ( wc_checkout_params.is_checkout === '1' ) {
				$( document.body ).trigger( 'init_checkout' );
			}
			if ( wc_checkout_params.option_guest_checkout === 'yes' ) {
				$( 'input#createaccount' ).on( 'change', this.toggle_create_account ).trigger( 'change' );
			}
		},
		// CHANGE: Update checkout when "billing same as shipping" checked state changes
		billing_same_shipping_changed: function( e ) {
			if ( window.CollapsibleBlock ) {
				var checkbox = document.querySelector( '#billing_same_as_shipping' );
				var fieldsWrapper = document.querySelector( '#woocommerce-billing-fields__field-wrapper' );

				// Toggle state
				if ( ! checkbox.checked ) {
					CollapsibleBlock.expand( fieldsWrapper );
				}
				else {
					CollapsibleBlock.collapse( fieldsWrapper );
				}
			}

			$( document.body ).trigger( 'update_checkout' );
		},
		// CHANGE: Reinitialize billing fields collapsible block after checkout update
		maybe_reinitialize_collapsible_blocks: function() {
			if ( window.CollapsibleBlock ) {
				var collapsibleBlocks = document.querySelectorAll( '[data-collapsible]' );
				for ( var i = 0; i < collapsibleBlocks.length; i++ ) {
					var collapsibleBlock = collapsibleBlocks[i];
					
					// Maybe initialize the collapsible block
					if ( ! CollapsibleBlock.getInstance( collapsibleBlock ) ) {
						CollapsibleBlock.initializeElement( collapsibleBlock );
					}
				}
			}
		},
		// CHANGE: Update checkout when page gets hidden or visible again
		maybe_update_checkout_visibility_change: function() {
			if ( 'hidden' == document.visibilityState || 'visible' == document.visibilityState ) {
				$( document.body ).trigger( 'update_checkout' );
			}
		},
		// CHANGE: Prompt user that they might lose data when closing tab or leaving the current page after they change some values in the checkout form
		maybe_prevent_unload: function( e ) {
			// Ignore some fields
			if ( e && e.target.closest( '.payment_box, input#createaccount' ) ) { return; }

			if ( ! _updateBeforeUnload ) {

				var preventUnload = function( e ) {
					// Prompt user if there is unsaved data
					if ( _updateBeforeUnload ) {
						e.preventDefault();
						e.returnValue = '';
	
						// Proceed to update the checkout totals if the user cancel the event
						$( document.body ).trigger( 'update_checkout' );

						// Reset flag to update on `beforeunload`
						_updateBeforeUnload = false;
						window.removeEventListener( 'beforeunload', preventUnload );
					}
				};

				window.addEventListener( 'beforeunload', preventUnload );
				_updateBeforeUnload = true;
			}
		},
		init_payment_methods: function() {
			var $payment_methods = $( '.woocommerce-checkout' ).find( 'input[name="payment_method"]' );

			// If there is one method, we can hide the radio input
			if ( 1 === $payment_methods.length ) {
				$payment_methods.eq(0).hide();
			}

			// If there was a previously selected method, check that one.
			if ( wc_checkout_form.selectedPaymentMethod ) {
				$( '#' + wc_checkout_form.selectedPaymentMethod ).prop( 'checked', true );
			}

			// If there are none selected, select the first.
			if ( 0 === $payment_methods.filter( ':checked' ).length ) {
				$payment_methods.eq(0).prop( 'checked', true );
			}

			// Get name of new selected method.
			var checkedPaymentMethod = $payment_methods.filter( ':checked' ).eq(0).prop( 'id' );

			if ( $payment_methods.length > 1 ) {
				// Hide open descriptions.
				$( 'div.payment_box:not(".' + checkedPaymentMethod + '")' ).filter( ':visible' ).slideUp( 0 );
			}

			// Trigger click event for selected method
			$payment_methods.filter( ':checked' ).eq(0).trigger( 'click' );
		},
		get_payment_method: function() {
			return wc_checkout_form.$checkout_form.find( 'input[name="payment_method"]:checked' ).val();
		},
		payment_method_selected: function( e ) {
			e.stopPropagation();

			if ( $( '.payment_methods input.input-radio' ).length > 1 ) {
				var target_payment_box = $( 'div.payment_box.' + $( this ).attr( 'ID' ) ),
					is_checked         = $( this ).is( ':checked' );

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
				$( _settings.checkoutPlaceOrderSelector ).text( $( this ).data( 'order_button_text' ) );
			} else {
				// CHANGE: replaced the place order button css selector with an extended custom selector
				$( _settings.checkoutPlaceOrderSelector ).text( $( _settings.checkoutPlaceOrderSelector ).data( 'value' ) );
			}

			var selectedPaymentMethod = $( '.woocommerce-checkout input[name="payment_method"]:checked' ).attr( 'id' );

			if ( selectedPaymentMethod !== wc_checkout_form.selectedPaymentMethod ) {
				$( document.body ).trigger( 'payment_method_selected' );
			}

			// CHANGE: Add body class for selected payment method
			document.body.classList.remove( 'has-payment-method-selected--' + wc_checkout_form.selectedPaymentMethod );
			document.body.classList.add( 'has-payment-method-selected--' + selectedPaymentMethod );

			wc_checkout_form.selectedPaymentMethod = selectedPaymentMethod;
		},
		toggle_create_account: function() {
			$( 'div.create-account' ).hide();

			if ( $( this ).is( ':checked' ) ) {
				// Ensure password is not pre-populated.
				$( '#account_password' ).val( '' ).trigger( 'change' );
				$( 'div.create-account' ).slideDown();
			}
		},
		init_checkout: function() {
			$( document.body ).trigger( 'update_checkout' );
		},
		maybe_input_changed: function( e ) {
			if ( wc_checkout_form.dirtyInput ) {
				wc_checkout_form.input_changed( e );
			}
		},
		// CHANGE: Add function to sync the terms checkbox state
		terms_checked_changed: function( e ) {
			var termsCheckBoxChecked = $( e.target ).prop( 'checked' );
			$( _settings.checkoutTermsSelector ).prop( 'checked', termsCheckBoxChecked );
		},
		input_changed: function( e ) {
			wc_checkout_form.dirtyInput = e.target;
			wc_checkout_form.maybe_update_checkout();
		},
		queue_update_checkout: function( e ) {
			var code = e.keyCode || e.which || 0;

			// CHANGE: Also skip `update_checkout` when pressing other controls keys such as "Shift", "Control", "Command", "Alt" and "Arrows"
			if ( code === 9 || code === 16 || code === 17 || code === 18 || code === 91 || code === 92 || code === 37 || code === 38 || code === 39 || code === 40 ) {
				return true;
			}

			wc_checkout_form.dirtyInput = this;
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout( wc_checkout_form.maybe_update_checkout, '1000' );
		},
		trigger_update_checkout: function() {
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.dirtyInput = false;
			$( document.body ).trigger( 'update_checkout' );
		},
		maybe_update_checkout: function() {
			var update_totals = true;

			if ( $( wc_checkout_form.dirtyInput ).length ) {
				var $required_inputs = $( wc_checkout_form.dirtyInput ).closest( 'div' ).find( '.address-field.validate-required' );

				if ( $required_inputs.length ) {
					$required_inputs.each( function() {
						if ( $( this ).find( 'input.input-text' ).val() === '' ) {
							update_totals = false;
						}
					});
				}
			}
			if ( update_totals ) {
				wc_checkout_form.trigger_update_checkout();
			}
		},
        // CHANGE: Removed shipping to different address checkout `change` listener handler function
		reset_update_checkout_timer: function() {
			clearTimeout( wc_checkout_form.updateTimer );
		},
		is_valid_json: function( raw_json ) {
			try {
				var json = JSON.parse( raw_json );

				return ( json && 'object' === typeof json );
			} catch ( e ) {
				return false;
			}
		},
		validate_field: function( e ) {
			var $this             = $( this ),
				$parent           = $this.closest( '.form-row' ),
				validated         = true,
				validate_required = $parent.is( '.validate-required' ),
				validate_email    = $parent.is( '.validate-email' ),
				validate_phone    = $parent.is( '.validate-phone' ),
				pattern           = '',
				event_type        = e.type;

			if ( 'input' === event_type ) {
				$parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-validated' ); // eslint-disable-line max-len
			}

			if ( 'validate' === event_type || 'change' === event_type ) {

				if ( validate_required ) {
					if ( 'checkbox' === $this.attr( 'type' ) && ! $this.is( ':checked' ) ) {
						$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						validated = false;
					} else if ( $this.val() === '' ) {
						$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
						validated = false;
					}
				}

				if ( validate_email ) {
					if ( $this.val() ) {
						/* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
						pattern = new RegExp( /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[0-9a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i ); // eslint-disable-line max-len

						if ( ! pattern.test( $this.val() ) ) {
							$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-email woocommerce-invalid-phone' ); // eslint-disable-line max-len
							validated = false;
						}
					}
				}

				if ( validate_phone ) {
					pattern = new RegExp( /[\s\#0-9_\-\+\/\(\)\.]/g );

					if ( 0 < $this.val().replace( pattern, '' ).length ) {
						$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-phone' );
						validated = false;
					}
				}

				if ( validated ) {
					$parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone' ).addClass( 'woocommerce-validated' ); // eslint-disable-line max-len
				}
			}
		},
		update_checkout: function( event, args ) {
			// Small timeout to prevent multiple requests when several fields update at the same time
			wc_checkout_form.reset_update_checkout_timer();
			wc_checkout_form.updateTimer = setTimeout( wc_checkout_form.update_checkout_action, '5', args );
		},
		// CHANGE: Add function to re-focus and keep value of the element that was previously focused before submitting an ajax request
		maybe_refocus_element: function( currentFocusedElement, currentValue ) {
			// Bail if no element to focus
			if ( null === currentFocusedElement ) { return; }

			requestAnimationFrame( function() {
				var elementToFocus;

				// Try findind the the current focused element after updating updated element by ID
				if ( currentFocusedElement.id ) {
					elementToFocus = document.getElementById( currentFocusedElement.id );
				}
				// Try findind the updated element by classes
				else if ( currentFocusedElement.getAttribute( 'name' ) ) {
					var nameAttr = currentFocusedElement.getAttribute( 'name' );
					elementToFocus = document.querySelector( '[name="'+nameAttr+'"]' );
				}
				// Try findind the `select2` focusable element
				else if ( currentFocusedElement.closest( '.form-row' ) ) {
					var formRow = currentFocusedElement.closest( '.form-row' );
					if ( formRow.id ) {
						elementToFocus = document.querySelector( '.form-row[id="'+formRow.id+'"] .select2-selection' );
					}
				}

				// Try setting focus if element is found
				if ( elementToFocus ) {
					elementToFocus.focus();

					// Try to set current value to the focused element
					if ( null !== currentValue && currentValue !== elementToFocus.value ) {
						elementToFocus.value = currentValue;
					}
					
					// Set keyboard track position back to that previously to update
					setTimeout( function(){
						if ( currentFocusedElement.selectionStart && currentFocusedElement.selectionEnd ) {
							elementToFocus.selectionStart = currentFocusedElement.selectionStart;
							elementToFocus.selectionEnd = currentFocusedElement.selectionEnd;
						}
						else if( elementToFocus.selectionStart && elementToFocus.selectionEnd ) {
							elementToFocus.selectionStart = elementToFocus.selectionEnd = Number.MAX_SAFE_INTEGER || 10000;
						}
					}, 0 );
				}
			} );
		},
		update_checkout_action: function( args ) {
			// CHANGE: Check flag that allows or block updating the checkout
			if ( ! window.can_update_checkout ) { return; }

			if ( wc_checkout_form.xhr ) {
				wc_checkout_form.xhr.abort();
			}

			if ( $( 'form.checkout' ).length === 0 ) {
				return;
			}

			args = typeof args !== 'undefined' ? args : {
				update_shipping_method: true
			};

			var country			 = $( '#billing_country' ).val(),
				state			 = $( '#billing_state' ).val(),
				postcode		 = $( ':input#billing_postcode' ).val(),
				city			 = $( '#billing_city' ).val(),
				address			 = $( ':input#billing_address_1' ).val(),
				address_2		 = $( ':input#billing_address_2' ).val(),
				// CHANGE: Always get shipping address values from shipping fields
				s_country		 = $( '#shipping_country' ).val(),
				s_state			 = $( '#shipping_state' ).val(),
				s_postcode		 = $( ':input#shipping_postcode' ).val(),
				s_city			 = $( '#shipping_city' ).val(),
				s_address		 = $( ':input#shipping_address_1' ).val(),
				s_address_2		 = $( ':input#shipping_address_2' ).val(),
				// END - CHANGE: Always get shipping address values from shipping fields
				$required_inputs = $( wc_checkout_form.$checkout_form ).find( '.address-field.validate-required:visible' ),
				has_full_address = true;

			if ( $required_inputs.length ) {
				$required_inputs.each( function() {
					if ( $( this ).find( ':input' ).val() === '' ) {
						has_full_address = false;
					}
				});
			}

            // CHANGE: Removed if handling of different shipping address checkbox, always get shipping address values from shipping fields (see above)

			var data = {
				security        : wc_checkout_params.update_order_review_nonce,
				payment_method  : wc_checkout_form.get_payment_method(),
				country         : country,
				state           : state,
				postcode        : postcode,
				city            : city,
				address         : address,
				address_2       : address_2,
				s_country       : s_country,
				s_state         : s_state,
				s_postcode      : s_postcode,
				s_city          : s_city,
				s_address       : s_address,
				s_address_2     : s_address_2,
				has_full_address: has_full_address,
				post_data       : $( 'form.checkout' ).serialize()
			};

			if ( false !== args.update_shipping_method ) {
				var shipping_methods = {};

				// eslint-disable-next-line max-len
				$( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
					shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
				} );

				data.shipping_method = shipping_methods;
			}

			// CHANGE: Also block the shipping methods section when updating
			$( '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table .fc-shipping-method__packages' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			wc_checkout_form.xhr = $.ajax({
				type:		'POST',
				url:		wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'update_order_review' ),
				data:		data,
				success:	function( data ) {

					// Reload the page if requested
					if ( data && true === data.reload ) {
						window.location.reload();
						return;
					}

					// Remove any notices added previously
					$( '.woocommerce-NoticeGroup-updateOrderReview' ).remove();

					// CHANGE: replaced the terms checkbox css selector
					var termsCheckBoxChecked = $( _settings.checkoutTermsSelector ).prop( 'checked' );

					// Save payment details to a temporary object
					var paymentDetails = {};
					$( '.payment_box :input' ).each( function() {
						var ID = $( this ).attr( 'id' );

						if ( ID ) {
							if ( $.inArray( $( this ).attr( 'type' ), [ 'checkbox', 'radio' ] ) !== -1 ) {
								paymentDetails[ ID ] = $( this ).prop( 'checked' );
							} else {
								paymentDetails[ ID ] = $( this ).val();
							}
						}
					});

					// CHANGE: Get current element with focus, will reset after updating the fragments
					var currentFocusedElement = document.activeElement;
					var currentValue = document.activeElement.value;
					
					// Always update the fragments
					if ( data && data.fragments ) {
						// CHANGE: Try to remove select2 components from existing fields before replacing fragments
						$( 'select.country_select, select.state_select' ).each( function() {
							var field = $( this );
							if ( field && field.selectWoo && 'function' === typeof field.selectWoo ) {
								field.selectWoo( 'destroy' );
							}
						});

						$.each( data.fragments, function ( key, value ) {
							if ( ! wc_checkout_form.fragments || wc_checkout_form.fragments[ key ] !== value ) {
								$( key ).replaceWith( value );
							}
							$( key ).unblock();
						} );
						wc_checkout_form.fragments = data.fragments;
					}

					// CHANGE: Re-set focus to the element with focus previously to updating fragments
					wc_checkout_form.maybe_refocus_element( currentFocusedElement, currentValue );

					// Recheck the terms and conditions box, if needed
					if ( termsCheckBoxChecked ) {
						// CHANGE: replaced the terms checkbox css selector
						$( _settings.checkoutTermsSelector ).prop( 'checked', true );
					}

					// Fill in the payment details if possible without overwriting data if set.
					if ( ! $.isEmptyObject( paymentDetails ) ) {
						$( '.payment_box :input' ).each( function() {
							var ID = $( this ).attr( 'id' );
							if ( ID ) {
								if ( $.inArray( $( this ).attr( 'type' ), [ 'checkbox', 'radio' ] ) !== -1 ) {
									$( this ).prop( 'checked', paymentDetails[ ID ] ).trigger( 'change' );
								} else if ( $.inArray( $( this ).attr( 'type' ), [ 'select' ] ) !== -1 ) {
									$( this ).val( paymentDetails[ ID ] ).trigger( 'change' );
								} else if ( null !== $( this ).val() && 0 === $( this ).val().length ) {
									$( this ).val( paymentDetails[ ID ] ).trigger( 'change' );
								}
							}
						});
					}

					// Check for error
					if ( data && 'failure' === data.result ) {

						var $form = $( 'form.checkout' );

						// Remove notices from all sources
						$( '.woocommerce-error, .woocommerce-message' ).remove();

						// Add new errors returned by this event
						if ( data.messages ) {
							$form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview">' + data.messages + '</div>' ); // eslint-disable-line max-len
						} else {
							$form.prepend( data );
						}

						// Lose focus for all fields
						$form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).trigger( 'blur' );

						wc_checkout_form.scroll_to_notices();
					}

					// Re-init methods
					wc_checkout_form.init_payment_methods();

					// CHANGE: Set to not prompt user before leaving the page
					_updateBeforeUnload = false;

					// Fire updated_checkout event.
					$( document.body ).trigger( 'updated_checkout', [ data ] );
				}

			});
		},
		handleUnloadEvent: function( e ) {
			// Modern browsers have their own standard generic messages that they will display.
			// Confirm, alert, prompt or custom message are not allowed during the unload event
			// Browsers will display their own standard messages

			// Check if the browser is Internet Explorer
			if((navigator.userAgent.indexOf('MSIE') !== -1 ) || (!!document.documentMode)) {
				// IE handles unload events differently than modern browsers
				e.preventDefault();
				return undefined;
			}

			return true;
		},
		attachUnloadEventsOnSubmit: function() {
			$( window ).on('beforeunload', this.handleUnloadEvent);
		},
		detachUnloadEventsOnSubmit: function() {
			$( window ).off('beforeunload', this.handleUnloadEvent);
		},
		blockOnSubmit: function( $form ) {
			var isBlocked = $form.data( 'blockUI.isBlocked' );

			if ( 1 !== isBlocked ) {
				$form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			}
		},
		submitOrder: function() {
			wc_checkout_form.blockOnSubmit( $( this ) );
		},
		submit: function() {
			wc_checkout_form.reset_update_checkout_timer();
			var $form = $( this );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			// CHANGE: Reset flag to update on `beforeunload`
			_updateBeforeUnload = false;

			// Trigger a handler to let gateways manipulate the checkout if needed
			// eslint-disable-next-line max-len
			if ( $form.triggerHandler( 'checkout_place_order' ) !== false && $form.triggerHandler( 'checkout_place_order_' + wc_checkout_form.get_payment_method() ) !== false ) {

				$form.addClass( 'processing' );

				// CHANGE: Disable place order button
				var currentFocusedElement = document.activeElement;
				$( _settings.checkoutPlaceOrderSelector ).attr( 'disabled', 'disabled' );
				$( _settings.checkoutPlaceOrderSelector ).addClass( 'disabled' );
				// END - Disable place order button

				wc_checkout_form.blockOnSubmit( $form );

				// Attach event to block reloading the page when the form has been submitted
				wc_checkout_form.attachUnloadEventsOnSubmit();

				// ajaxSetup is global, but we use it to ensure JSON is valid once returned.
				$.ajaxSetup( {
					dataFilter: function( raw_response, dataType ) {
						// We only want to work with JSON
						if ( 'json' !== dataType ) {
							return raw_response;
						}

						if ( wc_checkout_form.is_valid_json( raw_response ) ) {
							return raw_response;
						} else {
							// Attempt to fix the malformed JSON
							var maybe_valid_json = raw_response.match( /{"result.*}/ );

							if ( null === maybe_valid_json ) {
								console.log( 'Unable to fix malformed JSON' );
							} else if ( wc_checkout_form.is_valid_json( maybe_valid_json[0] ) ) {
								console.log( 'Fixed malformed JSON. Original:' );
								console.log( raw_response );
								raw_response = maybe_valid_json[0];
							} else {
								console.log( 'Unable to fix malformed JSON' );
							}
						}

						return raw_response;
					}
				} );

				$.ajax({
					type:		'POST',
					url:		wc_checkout_params.checkout_url,
					data:		$form.serialize(),
					dataType:   'json',
					success:	function( result ) {
						// Detach the unload handler that prevents a reload / redirect
						wc_checkout_form.detachUnloadEventsOnSubmit();

						try {
							if ( 'success' === result.result && $form.triggerHandler( 'checkout_place_order_success', result ) !== false ) {
								if ( -1 === result.redirect.indexOf( 'https://' ) || -1 === result.redirect.indexOf( 'http://' ) ) {
									window.location = result.redirect;
								} else {
									window.location = decodeURI( result.redirect );
								}
							} else if ( 'failure' === result.result ) {
								throw 'Result failure';
							} else {
								throw 'Invalid response';
							}
						} catch( err ) {
							// Reload page
							if ( true === result.reload ) {
								window.location.reload();
								return;
							}

							// CHANGE: Unblock the place order button
							$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
							$( _settings.checkoutPlaceOrderSelector ).removeClass( 'disabled' );
							wc_checkout_form.maybe_refocus_element( currentFocusedElement );
							// END - Unblock the place order button

							// Trigger update in case we need a fresh nonce
							if ( true === result.refresh ) {
								$( document.body ).trigger( 'update_checkout' );
							}

							// Add new errors
							if ( result.messages ) {
								wc_checkout_form.submit_error( result.messages );
							} else {
								wc_checkout_form.submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' ); // eslint-disable-line max-len
							}
						}
					},
					error:	function( jqXHR, textStatus, errorThrown ) {
						// Detach the unload handler that prevents a reload / redirect
						wc_checkout_form.detachUnloadEventsOnSubmit();

						wc_checkout_form.submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' );
					}
				});
			}

			return false;
		},
		submit_error: function( error_message ) {
			$( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();
			wc_checkout_form.$checkout_form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>' ); // eslint-disable-line max-len
			wc_checkout_form.$checkout_form.removeClass( 'processing' ).unblock();
			// CHANGE: Unblock the place order button
			$( _settings.checkoutPlaceOrderSelector ).removeAttr( 'disabled' );
			$( _settings.checkoutPlaceOrderSelector ).removeClass( 'disabled' );
			// END - Unblock the place order button
			wc_checkout_form.$checkout_form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).trigger( 'blur' );
			wc_checkout_form.scroll_to_notices();
			$( document.body ).trigger( 'checkout_error' , [ error_message ] );
		},
		scroll_to_notices: function() {
			var scrollElement           = $( '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout' );

			if ( ! scrollElement.length ) {
				scrollElement = $( 'form.checkout' );
			}
			$.scroll_to_notices( scrollElement );
		}
	};

	var wc_checkout_coupons = {
		init: function() {
			$( document.body ).on( 'click', 'a.showcoupon', this.show_coupon_form );
			$( document.body ).on( 'click', '.woocommerce-remove-coupon', this.remove_coupon );
			$( 'form.checkout_coupon' ).hide().on( 'submit', this.submit );
			// CHANGE: Added event listeners for apply coupon via ajax
			$( document.body ).on( 'click', '[data-apply-coupon-button]', this.apply_coupon );
			wc_checkout_form.$checkout_form.on( 'keydown', 'input[name="coupon_code"]', this.maybe_apply_coupon_keydown );
		},
		show_coupon_form: function() {
			$( '.checkout_coupon' ).slideToggle( 400, function() {
				$( '.checkout_coupon' ).find( ':input:eq(0)' ).trigger( 'focus' );
			});
			return false;
		},
		submit: function() {
			var $form = $( this );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			$form.addClass( 'processing' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				security:		wc_checkout_params.apply_coupon_nonce,
				coupon_code:	$form.find( 'input[name="coupon_code"]' ).val()
			};

			$.ajax({
				type:		'POST',
				url:		wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' ),
				data:		data,
				success:	function( code ) {
					$( '.woocommerce-error, .woocommerce-message' ).remove();
					$form.removeClass( 'processing' ).unblock();

					if ( code ) {
						$form.before( code );
						$form.slideUp();

						$( document.body ).trigger( 'applied_coupon_in_checkout', [ data.coupon_code ] );
						$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
					}
				},
				dataType: 'html'
			});

			return false;
		},
		remove_coupon: function( e ) {
			e.preventDefault();

			// CHANGE: Change container element to be the coupon code item if Fluid Checkout coupons feature is enabled
			var isFluidCheckoutCouponsEnabled = $( this ).parents( '.fc-coupon-codes__coupon' ).length > 0;
			var container = isFluidCheckoutCouponsEnabled ? $( this ).parents( '.fc-coupon-codes__coupon' ) : $( this ).parents( '.woocommerce-checkout-review-order' ),
				coupon    = $( this ).data( 'coupon' );

			container.addClass( 'processing' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				security: wc_checkout_params.remove_coupon_nonce,
				coupon:   coupon
			};

			// CHANGE: Remove existing messages previously to sending request
			if ( isFluidCheckoutCouponsEnabled ) {
				$( '.woocommerce-error, .woocommerce-message' ).remove();
			}

			$.ajax({
				type:    'POST',
				url:     wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'remove_coupon' ),
				data:    data,
				success: function( code ) {
					// CHANGE: Unblock container and remove messages if using native WooCommerce coupons feature
					if ( ! isFluidCheckoutCouponsEnabled ) {
						$( '.woocommerce-error, .woocommerce-message' ).remove();
						container.removeClass( 'processing' ).unblock();
					}

					if ( code ) {
						// CHANGE: Get the checkout substep elements
						var $substep_title = $( '.fc-step__substep[data-substep-id="coupon_codes"] .fc-step__substep-title' );
						var $substep = $( '.fc-step__substep[data-substep-id="coupon_codes"]' );

						// CHANGE: Maybe display coupon code messages in the coupon code step instead of the top of the page
						if ( isFluidCheckoutCouponsEnabled && $substep_title.length ) {
							$( code ).insertAfter( $substep_title );
						}
						else if ( isFluidCheckoutCouponsEnabled && $substep.length > 0 ) {
							$substep.prepend( code );
						}
						else {
							$( 'form.woocommerce-checkout' ).before( code );
						}

						$( document.body ).trigger( 'removed_coupon_in_checkout', [ data.coupon ] );
						$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );

						// Remove coupon code from coupon field
						$( 'form.checkout_coupon' ).find( 'input[name="coupon_code"]' ).val( '' );

						// CHANGE: Remove coupon code from coupon field on the checkout form
						$( 'form.woocommerce-checkout' ).find( 'input[name="coupon_code"]' ).val( '' );
					}
				},
				error: function ( jqXHR ) {
					if ( wc_checkout_params.debug_mode ) {
						/* jshint devel: true */
						console.log( jqXHR.responseText );
					}
				},
				dataType: 'html'
			});
		},
		// CHANGE: Add function to apply coupon via ajax from the checkout form
		apply_coupon: function( e ) {
			e.preventDefault();

			var coupon_code    = $( 'form.woocommerce-checkout' ).find( 'input[name="coupon_code"]' ).val();
			var coupon_field   = $( 'form.woocommerce-checkout' ).find( 'input[name="coupon_code"]' );
			var apply_button   = $( 'form.woocommerce-checkout' ).find( '[data-apply-coupon-button]' );

			var data = {
				security: wc_checkout_params.apply_coupon_nonce,
				coupon_code:   coupon_code
			};

			// Display loading/processing indication
			var container = $( this ).parents( '.fc-expansible-form-section__content--coupon_code' );
			container.addClass( 'processing' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			// Disable coupon and button
			coupon_field.prop( 'disabled', true );
			apply_button.prop( 'disabled', true );

			$.ajax({
				type:    'POST',
				url:     wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'apply_coupon' ),
				data:    data,
				success: function( code ) {
					$( '.woocommerce-error, .woocommerce-message' ).remove();
					
					// Remove loading/processing indication and unblock coupon field and button
					container.removeClass( 'processing' ).unblock();
					coupon_field.prop( 'disabled', false );
					apply_button.prop( 'disabled', false );

					if ( code ) {
						// Get the checkout substep elements
						var $substep_title = $( '.fc-step__substep[data-substep-id="coupon_codes"] .fc-step__substep-title' );
						var $substep = $( '.fc-step__substep[data-substep-id="coupon_codes"]' );

						// Display response (`code`) as a message
						if ( $substep_title.length > 0 ) {
							$( code ).insertAfter( $substep_title );
						}
						else {
							$substep.prepend( code );
						}

						$( document.body ).trigger( 'applied_coupon_in_checkout', [ data.coupon_code ] );
						$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );

						// Remove coupon code from coupon field
						$( 'form.checkout_coupon' ).find( 'input[name="coupon_code"]' ).val( '' );

						// CHANGE: Remove coupon code from coupon field on the checkout form
						$( 'form.woocommerce-checkout' ).find( 'input[name="coupon_code"]' ).val( '' );

						// Close the coupon code field section
						if ( window.CollapsibleBlock ) {

							var expansibleCouponToggle = document.querySelector( 'form.woocommerce-checkout .fc-expansible-form-section__toggle--coupon_code' );
							var expansibleCouponContent = document.querySelector( 'form.woocommerce-checkout .fc-expansible-form-section__content--coupon_code' );
							var expansibleCouponToggleButton = document.querySelector( 'form.woocommerce-checkout .expansible-section__toggle-plus--coupon_code' );

							if ( expansibleCouponToggle && expansibleCouponContent ) {
								// Change expanded/collapsed states for the fields and text blocks
								CollapsibleBlock.collapse( expansibleCouponContent );
								CollapsibleBlock.expand( expansibleCouponToggle );

								// Focus back to the add coupon code
								if ( expansibleCouponToggleButton ) {
									expansibleCouponToggleButton.focus();
								}
							}
						}
					}
				},
				error: function ( jqXHR ) {
					if ( wc_checkout_params.debug_mode ) {
						/* jshint devel: true */
						console.log( jqXHR.responseText );
					}
				},
				dataType: 'html'
			});
		},
		// CHANGE: Added function to handle `ENTER` key on the coupon code field
		maybe_apply_coupon_keydown: function( e ) {
			var code = e.keyCode || e.which || 0;

			if ( $( e.target ).is( 'form.woocommerce-checkout input[name="coupon_code"]' ) && code === 13 ) {
				e.preventDefault();
				wc_checkout_coupons.apply_coupon( e );
			}
		},
	};

	var wc_checkout_login_form = {
		init: function() {
			$( document.body ).on( 'click', 'a.showlogin', this.show_login_form );
		},
		show_login_form: function() {
			$( 'form.login, form.woocommerce-form--login' ).slideToggle();
			return false;
		}
	};

	var wc_terms_toggle = {
		init: function() {
			$( document.body ).on( 'click', 'a.woocommerce-terms-and-conditions-link', this.toggle_terms );
		},

		toggle_terms: function() {
			if ( $( '.woocommerce-terms-and-conditions' ).length ) {
				$( '.woocommerce-terms-and-conditions' ).slideToggle( function() {
					var link_toggle = $( '.woocommerce-terms-and-conditions-link' );

					if ( $( '.woocommerce-terms-and-conditions' ).is( ':visible' ) ) {
						link_toggle.addClass( 'woocommerce-terms-and-conditions-link--open' );
						link_toggle.removeClass( 'woocommerce-terms-and-conditions-link--closed' );
					} else {
						link_toggle.removeClass( 'woocommerce-terms-and-conditions-link--open' );
						link_toggle.addClass( 'woocommerce-terms-and-conditions-link--closed' );
					}
				} );

				return false;
			}
		}
	};

	wc_checkout_form.init();
	wc_checkout_coupons.init();
	wc_checkout_login_form.init();
	wc_terms_toggle.init();
});
