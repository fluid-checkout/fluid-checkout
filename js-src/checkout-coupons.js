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
		bodyClass: 'has-fc-checkout-coupons',

		uiProcessingClass: 'processing',

		messagesWrapperSelector: '.fc-coupon-code-messages',
		couponAddedSectionSelector: '.fc-step__substep-text-content--coupon-codes',
		couponFieldSelector: 'input[name="coupon_code"]',
		addCouponButtonSelector: '.fc-coupon-code__apply',
		removeCouponButtonSelector: '.woocommerce-remove-coupon',
		couponCodeAttribute: 'data-coupon',

		expansibleCouponToggleSelector: '.fc-expansible-form-section__toggle--coupon_code',
		expansibleCouponContentSelector: '.fc-expansible-form-section__content--coupon_code',
		expansibleCouponToggleButtonSelector: '.expansible-section__toggle-plus--coupon_code',

	}
	var _key = {
		ENTER: 'Enter',
		SPACE: ' ',
	}



	/**
	 * METHODS
	 */



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



	/**
	 * Add notices in the coupon code section.
	 *
	 * @param   HTML  content  HTML content to be displayed.
	 */
	var showNotices = function( content ) {
		var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );
		messagesWrapper.innerHTML = content;
	}

	/**
	 * Remove all notices from the coupon code section.
	 *
	 * @param   HTML  content  HTML content to be displayed.
	 */
	var clearNotices = function() {
		var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );
		messagesWrapper.innerHTML = '';
	}



	/**
	 * Process adding a coupon code to the cart from the add coupon button.
	 *
	 * @param   HTMLElement  addCouponButton  The cart item element.
	 */
	var processAddCoupon = function( addCouponButton ) {
		var couponCode = '';

		// Try to get coupon code from button attributes
		if ( addCouponButton && addCouponButton.hasAttribute( _settings.couponCodeAttribute ) ) {
			couponCode = addCouponButton.getAttribute( _settings.couponCodeAttribute );
		}

		// Try to get coupon code from coupon field
		if ( ! couponCode || '' == couponCode ) {
			var couponField = document.querySelector( _settings.couponFieldSelector );

			// Bail if coupon code field was not found
			if ( ! couponField ) { return; }

			couponCode = couponField.value;
		}

		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		_publicMethods.addCouponCode( couponCode );
	}
	
	/**
	 * Process adding a coupon code to the cart from the add coupon button.
	 *
	 * @param   HTMLElement  removeCouponButton  The cart item element.
	 */
	var processRemoveCoupon = function( removeCouponButton ) {
		// Bail if add coupon button not provided
		if ( ! removeCouponButton ) { return; }

		var couponCode = '';

		// Try to get coupon code from button attributes
		if ( removeCouponButton.hasAttribute( _settings.couponCodeAttribute ) ) {
			couponCode = removeCouponButton.getAttribute( _settings.couponCodeAttribute );
		}

		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		_publicMethods.removeCouponCode( couponCode );
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
		
	};

	/**
	 * Handle keypress event.
	 */
	 var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// ENTER on input fields
		if ( e.key == _key.ENTER && e.target.closest( _settings.couponFieldSelector ) ) {
			// Prevents submitting form
			e.preventDefault();

			// ADD COUPON
			processAddCoupon();
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
		$( element ).removeClass( _settings.uiProcessingClass ).unblock();
	}



	/**
	 * Add a coupon code to the cart.
	 *
	 * @param   string  couponCode  The coupon code to be added to the cart.
	 */
	_publicMethods.addCouponCode = function( couponCode ) {
		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		clearNotices();

		// Block coupon section
		var couponAddedSection = document.querySelector( _settings.couponAddedSectionSelector );
		if ( couponAddedSection ) {
			_publicMethods.blockUI( couponAddedSection );
		}

		// Add security nonce
		var data = {
			security: fcSettings.checkoutCoupons.addCouponCodeNonce,
			coupon_code: couponCode,
		}

		$.ajax({
			type:		'POST',
			url:		fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_add_coupon_code' ),
			data:		data,
			dataType:   'json',
			success:	function( response ) {

				if ( response.result && 'success' === response.result ) {
					// Maybe add messages
					if ( response.message ) {
						showNotices( response.message );
					}

					// Maybe remove coupon code value from the field
					var couponField = document.querySelector( _settings.couponFieldSelector );
					if ( couponField ) {
						couponField.value = '';
					}

					// Maybe close the coupon code field section
					if ( window.CollapsibleBlock ) {

						var expansibleCouponToggle = document.querySelector( _settings.expansibleCouponToggleSelector );
						var expansibleCouponContent = document.querySelector( _settings.expansibleCouponContentSelector );
						var expansibleCouponToggleButton = document.querySelector( _settings.expansibleCouponToggleButtonSelector );

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
					
					$( document.body ).trigger( 'wc_fragment_refresh' );
					$( document.body ).trigger( 'applied_coupon_in_checkout', [ data.coupon_code ] );
					$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
				}
				else if ( response.result && 'error' === response.result ) {
					// Display new messages
					showNotices( response.message );

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
	 * Remove coupon from the cart.
	 *
	 * @param   string  couponCode  The coupon code to be removed from the cart.
	 */
	_publicMethods.removeCouponCode = function( couponCode ) {
		// Bail if coupon code was not provided
		if ( ! couponCode || '' == couponCode ) { return; }

		clearNotices();

		// Block coupon section
		var couponAddedSection = document.querySelector( _settings.couponAddedSectionSelector );
		if ( couponAddedSection ) {
			_publicMethods.blockUI( couponAddedSection );
		}

		// Add security nonce
		var data = {
			security: fcSettings.checkoutCoupons.removeCouponCodeNonce,
			coupon_code: couponCode,
		}

		$.ajax({
			type:		'POST',
			url:		fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_remove_coupon_code' ),
			data:		data,
			dataType:   'json',
			success:	function( response ) {

				console.log( response );

				if ( response.result && 'success' === response.result ) {
					// Maybe add messages
					if ( response.message ) {
						showNotices( response.message );
					}
					
					$( document.body ).trigger( 'wc_fragment_refresh' );
					$( document.body ).trigger( 'removed_coupon_in_checkout', [ data.coupon_code ] );
					$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
				}
				else if ( response.result && 'error' === response.result ) {
					// Display new messages
					showNotices( response.message );

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
	 * Finish to initialize component and set related handlers.
	 */
	var finishInit = function() {
		// Add event listeners
		window.addEventListener( 'click', handleClick );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// // Add jQuery event listeners
		// if ( _hasJQuery ) {
		// 	$( document.body ).on( 'updated_checkout', maybeChangeSubstepState );
		// 	$( document.body ).on( 'updated_checkout', maybeRemoveFragmentsLoadingClass );
		// }

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = extend( _settings, options );

		// Finish initialization, maybe load dependencies first
		if ( window.CollapsibleBlock ) {
			finishInit();
		}
		else if( window.RequireBundle ) {
			RequireBundle.require( [ 'collapsible-block' ], function() { finishInit(); } );
		}
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
