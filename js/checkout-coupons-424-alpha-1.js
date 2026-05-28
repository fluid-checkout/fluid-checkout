/**
 * Manage checkout steps state.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutCoupons = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass:                             'has-fc-checkout-coupons',

		uiProcessingClass:                     'processing',
		loadingClass:                          'fc-loading',

		sectionWrapperSelector:                '.fc-step__substep, .fc-sidebar .coupon-code-form, .fc-cart-coupon-code-form, .fc-discount-section',
		generalNoticesSelector:                '.woocommerce-notices-wrapper',
		messagesWrapperSelector:               '.fc-coupon-code-messages',
		errorMessagesWrapperSelector:          '.fc-coupon-code-messages .woocommerce-error',
		errorMessagesWrapperSingleSelector:    '.fc-coupon-code-messages .woocommerce-error li[data-coupon="###COUPON_CODE###"], .fc-coupon-code-messages .is-error[data-coupon="###COUPON_CODE###"]',

		useGeneralNoticesSection:              'no',
		suppressSuccessMessages:               'yes',

		couponAddedSectionSelector:            '.fc-step__substep-text-content--coupon-codes',
		couponSectionSelector:                 '.fc-coupon-code-section',
		couponFieldSelector:                   'input[name="coupon_code"]',
		addCouponButtonSelector:               '.fc-coupon-code__apply',
		removeCouponButtonSelector:            '.woocommerce-remove-coupon',
		errorMessageDismissButtonSelector:     '.fc-coupon-code-message-dismiss',

		appliedCouponCodeSelectorTemplate:     '.fc-coupon-codes__coupon.coupon-###COUPON_CODE###, tr.cart-discount.coupon-###COUPON_CODE###',

		couponCodeAttribute:                   'data-coupon',
		referenceIdAttribute:                  'data-reference-id',
		expansibleCouponSectionKeyAttribute:   'data-section-key',

		expansibleCouponContentSelector:       '.fc-expansible-form-section__content',
		expansibleCouponToggleSelector:        '.fc-expansible-form-section__toggle--###SECTION_KEY###',
		expansibleCouponToggleButtonSelector:  '.expansible-section__toggle-plus--###SECTION_KEY###',

		section_key_placeholder:               '###SECTION_KEY###',

		couponAnimationEndEvent:               'transitionend',
		couponAnimationProperty:               'background-color',
		couponAnimationClass:                  'background-highlight-success',
		couponAnimationName:                   'background-highlight-success',
	}
	var _restoreMessagesMatrix = [];
	var _recentlyAddedCouponCodes = [];



	/**
	 * METHODS
	 */



	/**
	 * Add notices in the coupon code section.
	 *
	 * @param   HTML         content           HTML content to be displayed.
	 * @param   HTMLElement  referenceElement  The element which was used to trigger adding the coupon.
	 */
	var showNotices = function( content, referenceElement ) {
		// Try to get messages wrapper from the coupon code section
		var messagesWrapper;
		var sectionWrapper = referenceElement ? referenceElement.closest( _settings.sectionWrapperSelector ) : null;
		if ( sectionWrapper ) {
			messagesWrapper = sectionWrapper.querySelector( _settings.messagesWrapperSelector );
		}
		else {
			messagesWrapper = document.querySelector( _settings.generalNoticesSelector );
		}

		// Otherwise try to get messages wrapper from the general notices section
		if ( 'yes' === _settings.useGeneralNoticesSection ) {
			messagesWrapper = document.querySelector( _settings.generalNoticesSelector );
		}

		// Add message
		if ( messagesWrapper ) {
			messagesWrapper.innerHTML = content;
		}
	}

	/**
	 * Clear notices from the coupon code section.
	 *
	 * @param   HTMLElement  referenceElement  The element which was used to trigger adding the coupon.
	 */
	var clearNotices = function( referenceElement ) {
		// Try to get messages wrapper from the coupon code section
		var messagesWrapper;
		var sectionWrapper = referenceElement ? referenceElement.closest( _settings.sectionWrapperSelector ) : null;
		if ( sectionWrapper ) {
			messagesWrapper = sectionWrapper.querySelector( _settings.messagesWrapperSelector );
		}
		else {
			messagesWrapper = document.querySelector( _settings.generalNoticesSelector );
		}
		
		// Clear messages wrapper
		if ( messagesWrapper ) {
			messagesWrapper.innerHTML = '';
		}
	}

	/**
	 * Remove all notices from general notices section.
	 */
	var clearGeneralNotices = function() {
		var generalNotices = document.querySelector( _settings.generalNoticesSelector );
		if ( generalNotices ) {
			generalNotices.innerHTML = '';
		}
	}



	/**
	 * Remove the error message from the coupon code message list.
	 *
	 * @param   string  couponCode  The coupon code associated with the message element.
	 */
	var dismissErrorMessage = function( couponCode ) {
		// Bail if coupon code was not provided
		if ( ! couponCode ) { return; }

		// Get coupon code message element
		var errorMessageSelector = _settings.errorMessagesWrapperSingleSelector.replace( /###COUPON_CODE###/g, couponCode );
		var errorMessageElement = document.querySelector( errorMessageSelector );

		// Maybe remove the error message element
		if ( errorMessageElement ) {
			errorMessageElement.parentElement.removeChild( errorMessageElement );
		}

		// Remove the error messages container if it's empty
		var errorMessagesWrapper = document.querySelector( _settings.errorMessagesWrapperSelector );
		if ( errorMessagesWrapper && 0 === errorMessagesWrapper.children.length ) {
			errorMessagesWrapper.parentElement.removeChild( errorMessagesWrapper );
		}
	}



	/**
	 * Play the animation for adding the coupon code.
	 * 
	 * @param   HTMLElement  appliedCouponCodeElement  The applied coupon code element.
	 * @param   string       couponCode         The coupon code.
	 */
	var playAddingCouponCodeAnimation = function( appliedCouponCodeElement, couponCode ) {
		// Prepare to remove animating class when transition ends
		appliedCouponCodeElement.addEventListener( FCUtils.animationEndEvent, function endRemovingItemAnimation( e ) {
			// Bail if not target animation ending
			if ( e.animationName !== _settings.couponAnimationName ) { return; }

			// Remove coupon code from the recently added list
			var index = _recentlyAddedCouponCodes.indexOf( couponCode );
			if ( -1 < index ) {
				_recentlyAddedCouponCodes.splice( index, 1 );
			}

			// Remove animation class
			appliedCouponCodeElement.classList.remove( _settings.couponAnimationClass );

			// Remove event listener
			appliedCouponCodeElement.removeEventListener( FCUtils.animationEndEvent, endRemovingItemAnimation );
		} );

		appliedCouponCodeElement.classList.add( _settings.couponAnimationClass );
	}

	/**
	 * Maybe add animation to the newly added coupon codes.
	 */
	var maybeAddNewCouponCodeAnimation = function() {
		// Bail if no new coupon codes have been added
		if ( !_recentlyAddedCouponCodes || 0 === _recentlyAddedCouponCodes.length ) { return; }

		// Iterate through the recently added coupon codes
		for ( var i = 0; i < _recentlyAddedCouponCodes.length; i++ ) {
			var couponCode = _recentlyAddedCouponCodes[ i ];
			var appliedCouponCodeElements = document.querySelectorAll( _settings.appliedCouponCodeSelectorTemplate.replace( /###COUPON_CODE###/g, couponCode ) );

			// Skip if no coupon code elements were found
			if ( ! appliedCouponCodeElements ) { continue; }

			// Highlight each applied coupon code element
			for ( var j = 0; j < appliedCouponCodeElements.length; j++ ) {
				var appliedCouponCodeElement = appliedCouponCodeElements[ j ];
				playAddingCouponCodeAnimation( appliedCouponCodeElement, couponCode );
			}
		}
	}



	/**
	 * Block the coupon code buttons.
	 */
	var blockCouponCodeButtons = function() {
		// Get coupon code buttons
		var couponCodeButtons = document.querySelectorAll( _settings.addCouponButtonSelector );

		// Bail if no coupon code buttons were found
		if ( ! couponCodeButtons ) { return; }

		// Iterate through the coupon code buttons
		for ( var i = 0; i < couponCodeButtons.length; i++ ) {
			// Get coupon code button
			var couponCodeButton = couponCodeButtons[ i ];
			
			// Set button loading state
			couponCodeButton.classList.add( _settings.loadingClass );
			couponCodeButton.setAttribute( 'disabled', 'disabled' );
		}
	}

	/**
	 * Unblock coupon code buttons.
	 */
	var unblockCouponCodeButtons = function() {
		// Get coupon code buttons
		var couponCodeButtons = document.querySelectorAll( _settings.addCouponButtonSelector );

		// Bail if no coupon code buttons were found
		if ( ! couponCodeButtons ) { return; }

		// Iterate through the coupon code buttons
		for ( var i = 0; i < couponCodeButtons.length; i++ ) {
			// Get coupon code button
			var couponCodeButton = couponCodeButtons[ i ];

			// Remove loading state
			couponCodeButton.classList.remove( _settings.loadingClass );
			couponCodeButton.removeAttribute( 'disabled' );
		}
	}


	/**
	 * Process adding a coupon code to the cart from the add coupon button.
	 *
	 * @param   HTMLElement  referenceElement  The element which was used to trigger adding the coupon.
	 */
	var processAddCoupon = function( referenceElement ) {
		var couponCode = '';

		// Try to get coupon code from attributes
		if ( referenceElement && referenceElement.hasAttribute( _settings.couponCodeAttribute ) ) {
			couponCode = referenceElement.getAttribute( _settings.couponCodeAttribute );
		}

		// Try to get coupon code from coupon field
		if ( ! couponCode || '' == couponCode ) {
			var couponSection = referenceElement.closest( _settings.couponSectionSelector );
			var couponField = couponSection.querySelector( _settings.couponFieldSelector );

			// Bail if coupon code field was not found
			if ( ! couponField ) { return; }

			// Focus on add coupon button and bail if coupon field is empty
			if ( '' === couponField.value.trim() ) {
				couponField.value = ''; // Clear space chars if any
				couponField.focus();
				return;
			}

			couponCode = couponField.value;
		}

		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		_publicMethods.addCouponCode( couponCode, referenceElement );
	}
	
	/**
	 * Process removing a coupon code to the cart from the add coupon button.
	 *
	 * @param   HTMLElement  referenceElement  The remove coupon button element.
	 */
	var processRemoveCoupon = function( referenceElement ) {
		// Bail if add coupon button not provided
		if ( ! referenceElement ) { return; }

		var couponCode = '';

		// Try to get coupon code from button attributes
		if ( referenceElement.hasAttribute( _settings.couponCodeAttribute ) ) {
			couponCode = referenceElement.getAttribute( _settings.couponCodeAttribute );
		}

		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		_publicMethods.removeCouponCode( couponCode, referenceElement );
	}

	/**
	 * Run processes before sending request to update cart fragments.
	 */
	var processBeforeFragmentsUpdate = function() {
		// Bail if using general notices section
		if ( 'yes' === _settings.useGeneralNoticesSection ) { return; }

		// Build matrix of coupon code error messages and their positions
		var errorMessages = document.querySelectorAll( _settings.errorMessagesWrapperSelector );
		_restoreMessagesMatrix = [];
		for ( var i = 0; i < errorMessages.length; i++ ) {
			var errorMessage = errorMessages[ i ];
			var position = Array.prototype.indexOf.call( errorMessage.parentElement.children, errorMessage );
			_restoreMessagesMatrix.push( [ position, errorMessage ] );
		}
	}

	/**
	 * Run processes after cart fragments are replaced on the page.
	 */
	var processAfterFragmentsReplaced = function() {
		// Bail if using general notices section
		if ( 'yes' === _settings.useGeneralNoticesSection ) { return; }

		// Get coupon code messages wrapper element
		var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );

		// Bail if messages wrapper is not available
		if ( ! messagesWrapper ) { return; }

		// Maybe restore error messages
		for ( var j = 0; j < _restoreMessagesMatrix.length; j++ ) {
			var restoreMessage = _restoreMessagesMatrix[ j ];
			var position = restoreMessage[ 0 ];
			var errorMessage = restoreMessage[ 1 ];
			var refereceMessage = messagesWrapper.children[ position ];
			var couponCode = errorMessage.getAttribute( _settings.couponCodeAttribute );

			// Skip "restore" message if it already exists for the same coupon code
			var errorMessageCouponCodeSelector = _settings.errorMessagesWrapperSelector + '[' + _settings.couponCodeAttribute + '="' + couponCode + '"]';
			if ( document.querySelector( errorMessageCouponCodeSelector ) ) { continue; }

			// Restore error message
			messagesWrapper.insertBefore( errorMessage, refereceMessage );
		}
	}

	/**
	 * Process dismissing message for the coupon code associated with the dismiss button.
	 *
	 * @param   HTMLElement  dismissButton  The dismiss cart item button element.
	 */
	var processErrorMessageDismiss = function( dismissButton ) {
		var couponCode = dismissButton.getAttribute( _settings.couponCodeAttribute );
		dismissErrorMessage( couponCode );
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// ADD COUPON
		if ( e.target.closest( _settings.addCouponButtonSelector ) ) {
			e.preventDefault();
			var addCouponButton = e.target.closest( _settings.addCouponButtonSelector );
			processAddCoupon( addCouponButton );
		}
		// REMOVE COUPON
		else if ( e.target.closest( _settings.removeCouponButtonSelector ) ) {
			e.preventDefault();
			var removeCouponButton = e.target.closest( _settings.removeCouponButtonSelector );
			processRemoveCoupon( removeCouponButton );
		}
		// ERROR MESSAGE DISMISS
		else if ( e.target.closest( _settings.errorMessageDismissButtonSelector ) ) {
			e.preventDefault();
			processErrorMessageDismiss( e.target.closest( _settings.errorMessageDismissButtonSelector ) );
		}
	};

	/**
	 * Handle keypress event.
	 */
	 var handleKeyDown = function( e ) {
		// ENTER on input fields
		if ( FCUtils.keyboardKeys.ENTER === e.key && e.target.closest( _settings.couponFieldSelector ) ) {
			// Prevents submitting form
			e.preventDefault();

			// ADD COUPON
			processAddCoupon( e.target.closest( _settings.couponFieldSelector ) );
		}
	};



	/**
	 * Use the same method that WooCommerce uses to block other parts of the checkout form while updating.
	 * The UI is unblocked by the WooCommerce `checkout.js` script (which is replaced with a modified version but keeps the same behavior)
	 * using the checkout fragment selector, then unblocking after the checkout update is completed.
	 *
	 * @param   HTMLElement  element  Element to block the UI and show the loading indicator.
	 */
	_publicMethods.blockUI = function( element ) {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Bail if element is invalid
		if ( ! element ) { return; }

		$( element ).addClass( _settings.uiProcessingClass ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		} );
	}

	/**
	 * Unblock UI element to be used again.
	 *
	 * @param   HTMLElement  element  Element to get the UI unblocked.
	 *
	 * @see  blockUI
	 */
	_publicMethods.unblockUI = function( element ) {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		$( element ).removeClass( _settings.uiProcessingClass ).unblock();
	}



	/**
	 * Add a coupon code to the cart.
	 *
	 * @param   string  couponCode  The coupon code to be added to the cart.
	 */
	_publicMethods.addCouponCode = function( couponCode, referenceElement ) {
		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		// Maybe clear general notices
		if ( 'yes' === _settings.useGeneralNoticesSection ) {
			clearGeneralNotices();
		}

		// Get reference id and save to reference element
		var reference_id = Math.floor( Math.random() * 1000 );
		if ( referenceElement ) {
			referenceElement.setAttribute( _settings.referenceIdAttribute, reference_id );
		}

		// Get coupon added section
		var couponAddedSection = document.querySelector( _settings.couponAddedSectionSelector );

		// Maybe block coupon added section
		if ( couponAddedSection && couponAddedSection.children.length > 0 ) {
			_publicMethods.blockUI( couponAddedSection );
		}

		// Maybe block coupon code buttons
		blockCouponCodeButtons();

		// Add security nonce
		var data = {
			security: _settings.addCouponCodeNonce,
			coupon_code: couponCode,
			reference_id: reference_id,
		}

		$.ajax( {
			type:         'POST',
			url:          fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_add_coupon_code' ),
			data:         data,
			dataType:     'json',
			success:      function( response ) {
				// Get back the reference id
				var referenceElement;
				if ( response.reference_id ) {
					referenceElement = document.querySelector( '[' + _settings.referenceIdAttribute + '="' + response.reference_id + '"]' );
				}

				// Maybe process success
				if ( response.result && 'success' === response.result ) {
					// Clear notices
					clearNotices( referenceElement );

					// Maybe add messages
					if ( response.message && 'yes' !== _settings.suppressSuccessMessages ) {
						showNotices( response.message, referenceElement );
					}

					// Maybe remove coupon code value from the field
					var couponField = document.querySelector( _settings.couponFieldSelector );
					if ( couponField ) {
						couponField.value = '';
					}

					// Maybe add coupon code to the list of recently added coupon codes
					if ( -1 === _recentlyAddedCouponCodes.indexOf( couponCode ) ) {
						_recentlyAddedCouponCodes.push( couponCode );
					}

					// Maybe close the coupon code field section
					if ( window.CollapsibleBlock ) {
						// Get expansible content section
						var expansibleCouponContent = referenceElement.closest( _settings.expansibleCouponContentSelector );

						// Maybe collapse/expanse sections after adding coupon
						if ( expansibleCouponContent ) {
							// Collapse the coupon code field content section
							CollapsibleBlock.collapse( expansibleCouponContent );

							// Get section key and toggle elements
							var section_key = expansibleCouponContent.getAttribute( _settings.expansibleCouponSectionKeyAttribute );
							var expansibleCouponToggle = document.querySelector( _settings.expansibleCouponToggleSelector.replace( _settings.section_key_placeholder, section_key ) );
							var expansibleCouponToggleButton = document.querySelector( _settings.expansibleCouponToggleButtonSelector.replace( _settings.section_key_placeholder, section_key ) );

							// Maybe expand coupon code toggle section
							if ( expansibleCouponToggle ) {
								CollapsibleBlock.expand( expansibleCouponToggle );
							}

							// Maybe focus back to the add coupon code button
							if ( expansibleCouponToggleButton ) {
								expansibleCouponToggleButton.focus();
							}
						}
					}

					$( document.body ).trigger( 'wc_fragment_refresh' );
					$( document.body ).trigger( 'applied_coupon_in_checkout', [ data.coupon_code ] );
					$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
				}
				// Maybe process error
				else if ( response.result && 'error' === response.result ) {
					// Display new messages
					showNotices( response.message, referenceElement );

					// Trigger error event
					$( document.body ).trigger( 'checkout_error' , [ response.message ] );

					// Unblock coupon section
					var couponAddedSection = document.querySelector( _settings.couponAddedSectionSelector );
					if ( couponAddedSection ) {
						_publicMethods.unblockUI( couponAddedSection );
					}
				}
			},
			complete: function() {
				// Unblock coupon code buttons
				unblockCouponCodeButtons();
			}
		} );
	};



	/**
	 * Remove coupon from the cart.
	 *
	 * @param   string  couponCode  The coupon code to be removed from the cart.
	 */
	_publicMethods.removeCouponCode = function( couponCode, referenceElement ) {
		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		// Maybe clear general notices
		if ( 'yes' === _settings.useGeneralNoticesSection ) {
			clearGeneralNotices();
		}

		// Get reference id and save to reference element
		var reference_id = Math.floor( Math.random() * 1000 );
		if ( referenceElement ) {
			referenceElement.setAttribute( _settings.referenceIdAttribute, reference_id );
		}

		// Block coupon section
		var couponAddedSection = document.querySelector( _settings.couponAddedSectionSelector );
		_publicMethods.blockUI( couponAddedSection );

		// Add security nonce
		var data = {
			security: _settings.removeCouponCodeNonce,
			coupon_code: couponCode,
			reference_id: reference_id,
		}

		$.ajax({
			type:         'POST',
			url:          fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_remove_coupon_code' ),
			data:         data,
			dataType:     'json',
			success:      function( response ) {
				// Get back the reference id
				var referenceElement;
				if ( response.reference_id ) {
					referenceElement = document.querySelector( '[' + _settings.referenceIdAttribute + '="' + response.reference_id + '"]' );
				}

				// Maybe process success
				if ( response.result && 'success' === response.result ) {
					// Clear notices
					clearNotices( referenceElement );

					// Maybe add messages
					if ( response.message && 'yes' !== _settings.suppressSuccessMessages ) {
						showNotices( response.message, referenceElement );
					}

					$( document.body ).trigger( 'wc_fragment_refresh' );
					$( document.body ).trigger( 'removed_coupon_in_checkout', [ data.coupon_code ] );
					$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
				}
				// Maybe process error
				else if ( response.result && 'error' === response.result ) {
					// Display new messages
					showNotices( response.message, referenceElement );

					// Unblock coupon section
					var couponAddedSection = document.querySelector( _settings.couponAddedSectionSelector );
					if ( couponAddedSection ) {
						_publicMethods.unblockUI( couponAddedSection );
					}
				}
			}
		});
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			// Coupon code messages restore
			$( document.body ).on( 'wc_fragment_refresh update_checkout', processBeforeFragmentsUpdate );
			$( document.body ).on( 'wc_fragments_refreshed updated_checkout', processAfterFragmentsReplaced );

			// New coupon code animation
			$( document.body ).on( 'wc_fragments_refreshed updated_checkout', maybeAddNewCouponCodeAnimation );
		}

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
