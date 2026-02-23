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
 * - fcSmartCoupons (localized by PHP)
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.fcSmartCouponsCheckoutSettings = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		checkoutNoticesSelector: '.fc-checkout-notices',
		woocommerceNoticesWrapperSelector: '.woocommerce-notices-wrapper:first',
		checkoutFormSelector: 'form.woocommerce-checkout',

		applyButtonSelector: '.apply_coupons_credits',
		removeLinkSelector: 'a.woocommerce-remove-coupon',
		innerNoticesWrapperSelector: '.woocommerce-notices-wrapper',
		codeTextSelector: '.code',
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
		var $noticesWrapper = $( _settings.checkoutNoticesSelector );
		if ( ! $noticesWrapper.length ) {
			$noticesWrapper = $( _settings.woocommerceNoticesWrapperSelector );
		}
		if ( ! $noticesWrapper.length ) {
			$noticesWrapper = $( _settings.checkoutFormSelector );
		}
		return $noticesWrapper;
	};



	/**
	 * Insert notices HTML into the determined wrapper.
	 *
	 * Accepts raw markup or full response payload from Smart Coupons.
	 *
	 * @param   string  message  Raw markup or full response payload.
	 */
	var insertNotices = function( message ) {
		var $noticesWrapper = getNoticesWrapper();

		// Bail if notices wrapper is not available
		if ( ! $noticesWrapper.length ) { return; }

		var $response = $( '<div>' ).html( message );
		var $innerNotices = $response.find( _settings.innerNoticesWrapperSelector );
		var noticesHtml = $innerNotices.length ? $innerNotices.html() : message;

		if ( $noticesWrapper.is( 'form' ) ) {
			$noticesWrapper.prepend( noticesHtml );
		} else {
			$noticesWrapper.empty().append( noticesHtml );
		}
	};



	/**
	 * Apply a Smart Coupon when the "apply coupon/credits" button is clicked.
	 *
	 * Sends an AJAX request using Smart Coupons endpoints and updates checkout.
	 *
	 * @param   jQuery  $button  The apply button element.
	 */
	var applySmartCouponForButton = function( $button ) {
		// Newer versions of Smart Coupons changed how coupon data is stored.
		// Try multiple fallbacks so this works across versions.
		var couponCode = $button.data( 'coupon_code' )
			|| $button.data( 'coupon' )
			|| $button.attr( 'name' )
			|| $button.find( _settings.codeTextSelector ).text().trim();

		// Bail if no coupon code
		if ( ! couponCode ) { return; }

		$.ajax( {
			type: 'POST',
			url: root.fcSmartCoupons.applyUrl,
			data: {
				coupon_code: couponCode,
				reference_id: $button.data( 'reference_id' ) || '',
				security: root.fcSmartCoupons.applyNonce
			},
			dataType: 'json',
			success: function( response ) {
				// Bail if response or message is not available
				if ( ! response || ! response.message ) { return; }

				insertNotices( response.message );
				if ( _hasJQuery ) {
					$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
				}
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
	var removeSmartCouponForLink = function( $link ) {
		var couponCode = $link.data( 'coupon' );

		// Bail if no coupon code
		if ( ! couponCode ) { return; }

		$.ajax( {
			type: 'POST',
			url: root.fcSmartCoupons.removeUrl,
			data: {
				coupon_code: couponCode,
				reference_id: $link.data( 'reference_id' ) || '',
				security: root.fcSmartCoupons.removeNonce
			},
			dataType: 'json',
			success: function( response ) {
				// Bail if response or message is not available
				if ( ! response || ! response.message ) { return; }

				insertNotices( response.message );
				if ( _hasJQuery ) {
					$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
				}
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
			applySmartCouponForButton( $( target.closest( _settings.applyButtonSelector ) ) );
		}
		// REMOVE COUPON
		else if ( target.closest( _settings.removeLinkSelector ) ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			removeSmartCouponForLink( $( target.closest( _settings.removeLinkSelector ) ) );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Bail if fcSmartCoupons config is not available
		if ( typeof root.fcSmartCoupons === 'undefined' ) { return; }

		// Add event listeners (capture phase to intercept before Smart Coupons)
		document.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
