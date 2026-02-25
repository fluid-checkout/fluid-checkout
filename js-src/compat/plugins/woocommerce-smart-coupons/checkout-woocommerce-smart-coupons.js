/**
 * Enhances checkout for WooCommerce Smart Coupons (by StoreApps).
 *
 * Background:
 * - WooCommerce Smart Coupons injects checkout AJAX handlers
 *   (see: woocommerce-smart-coupons/includes/class-wc-sc-display-coupons.php)
 *   which add notices before the checkout form.
 * - Fluid Checkout instead renders notices in a dedicated wrapper:
 *   .fc-checkout-notices (see: fluid-checkout/inc/checkout-steps.php).
 *
 * Purpose:
 * This script intercepts coupon apply/remove actions,
 * ensuring that all notices are properly displayed
 * within the Fluid Checkout notices wrapper,
 * maintaining compatibility and a seamless checkout UX.
 *
 * DEPENDS ON:
 * - jQuery
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCSmartCouponsCheckout = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		preferredNoticesWrapperSelector: '.fc-checkout-notices',
		fallbackNoticesWrapperSelector: '.woocommerce-notices-wrapper:first-child, form.woocommerce-checkout',

		applyButtonSelector: '.apply_coupons_credits',
		removeLinkSelector: 'a.woocommerce-remove-coupon',
		innerNoticesWrapperSelector: '.woocommerce-notices-wrapper',
		codeTextSelector: '.code',

		applyNonce: '', // Defined at initialization
		removeNonce: '', // Defined at initialization
	};



	/**
	 * METHODS
	 */



	/**
	 * Get the wrapper where notices should be rendered.
	 *
	 * Falls back from Fluid Checkout wrapper to WooCommerce's
	 * default notices wrapper and finally to the checkout form.
	 *
	 * @return  jQuery  The notices wrapper element.
	 */
	var getNoticesWrapper = function() {
		// Get notices wrapper
		var noticesWrapper = document.querySelector( _settings.preferredNoticesWrapperSelector ) || document.querySelector( _settings.fallbackNoticesWrapperSelector );

		// Bail if no notices wrapper found
		if ( ! noticesWrapper ) { return null; }

		// Return notices wrapper
		return noticesWrapper;
	};



	/**
	 * Insert notices HTML into the determined wrapper.
	 *
	 * Accepts raw markup or full response payload from Smart Coupons.
	 *
	 * @param   string  message  Raw markup or full response payload.
	 */
	var insertNotices = function( message ) {
		// Get notices wrapper
		var noticesWrapper = getNoticesWrapper();

		// Bail if notices wrapper is not available
		if ( ! noticesWrapper ) { return; }

		// Create temporary div to parse message
		var tempDiv = document.createElement('div');
		tempDiv.innerHTML = message;

		// Get inner notices
		var innerNotices = tempDiv.querySelector(_settings.innerNoticesWrapperSelector);
		var noticesHtml = innerNotices ? innerNotices.innerHTML : message;

		// Insert notices HTML into notices wrapper at the top of the form
		if ( noticesWrapper.tagName && noticesWrapper.tagName.toLowerCase() === 'form' ) {
			noticesWrapper.insertAdjacentHTML( 'afterbegin', noticesHtml );
		}
		// Otherwise, empty the wrapper and append notices HTML
		else {
			noticesWrapper.innerHTML = '';
			noticesWrapper.insertAdjacentHTML( 'beforeend', noticesHtml );
		}
	};



	/**
	 * Apply a Smart Coupon when the "apply coupon/credits" button is clicked.
	 * Sends an AJAX request using Smart Coupons endpoints and updates checkout.
	 *
	 * @param   Element  button  The apply button element.
	 */
	var applySmartCouponForButton = function( button ) {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }
		
		// Bail if button is not available
		if ( ! button ) { return; }

		// Newer versions of Smart Coupons changed how coupon data is stored.
		// Try multiple fallbacks so this works across versions.
		var couponCode =
			button.getAttribute('data-coupon_code') ||
			button.getAttribute('data-coupon') ||
			button.getAttribute('name');
		
		// Maybe get coupon code from code text selector
		if ( ! couponCode ) {
			couponCode = button.querySelector( _settings.codeTextSelector ) ? button.querySelector( _settings.codeTextSelector ).textContent.trim() : '';
		}
	
		// Bail if no coupon code value found
		if ( ! couponCode ) { return; }

		// Define apply URL
		var applyUrl = fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_sc_apply_coupon' );

		// Send AJAX request
		$.ajax( {
			type: 'POST',
			url: applyUrl,
			data: {
				coupon_code: couponCode,
				reference_id: button.getAttribute( 'data-reference_id' ) || '',
				security: _settings.applyNonce
			},
			dataType: 'json',
			success: function( response ) {
				// Bail if response or message is not available
				if ( ! response || ! response.message ) { return; }

				// Insert notices
				insertNotices( response.message );

				// Trigger checkout update
				$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
			},
			error: function() {}
		} );
	};



	/**
	 * Remove a Smart Coupon when its "remove" link is clicked.
	 *
	 * Sends an AJAX request using Smart Coupons endpoints and updates checkout.
	 *
	 * @param   jQuery  $link  The remove link element.
	 */
	var removeSmartCouponForLink = function( link ) {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }
		
		// Bail if link is not available
		if ( ! link ) { return; }

		// Get coupon code
		var couponCode = link.getAttribute( 'data-coupon' );

		// Bail if no coupon code
		if ( ! couponCode ) { return; }

		// Define remove URL
		var removeUrl = fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_sc_remove_coupon' );

		// Send AJAX request
		$.ajax( {
			type: 'POST',
			url: removeUrl,
			data: {
				coupon_code: couponCode,
				reference_id: link.getAttribute( 'data-reference_id' ) || '',
				security: _settings.removeNonce
			},
			dataType: 'json',
			success: function( response ) {
				// Bail if response or message is not available
				if ( ! response || ! response.message ) { return; }

				// Insert notices
				insertNotices( response.message );

				// Trigger checkout update
				$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
			},
			error: function() {}
		} );
	};



	/**
	 * Handle document clicks and route to the appropriate function.
	 *
	 * Uses capture phase so we can intercept before Smart Coupons handlers.
	 *
	 * @param   Event  e  The click event.
	 */
	var handleClick = function( e ) {
		var target = e.target;

		// Bail if target is not available or not a valid element
		if ( ! target || ! target.closest ) { return; }

		// APPLY COUPON
		if ( target.closest( _settings.applyButtonSelector ) ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			applySmartCouponForButton( target.closest( _settings.applyButtonSelector ) );
		}
		// REMOVE COUPON
		else if ( target.closest( _settings.removeLinkSelector ) ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			removeSmartCouponForLink( target.closest( _settings.removeLinkSelector ) );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 * 
	 * @param   object  options  The options object.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners (capture phase to intercept before Smart Coupons)
		document.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
