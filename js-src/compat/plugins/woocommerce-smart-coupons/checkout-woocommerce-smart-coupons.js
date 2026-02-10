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
 */
	jQuery( function( $ ) {
		if ( typeof fcSmartCoupons === 'undefined' ) { return; }

		/**
		 * METHODS
		 *
		 * Helper functions for handling Smart Coupons notices and actions.
		 */

		/**
		 * Get the wrapper where notices should be rendered.
		 *
		 * Falls back from Fluid Checkout wrapper to WooCommerce's
		 * default notices wrapper and finally to the checkout form.
		 */
		var getNoticesWrapper = function() {
		var $noticesWrapper = $( '.fc-checkout-notices' );
		if ( ! $noticesWrapper.length ) {
			$noticesWrapper = $( '.woocommerce-notices-wrapper:first' );
		}
		if ( ! $noticesWrapper.length ) {
			$noticesWrapper = $( 'form.woocommerce-checkout' );
		}
		return $noticesWrapper;
	};

		/**
		 * Insert notices HTML into the determined wrapper.
		 *
		 * Accepts raw markup or full response payload from Smart Coupons.
		 */
		var insertNotices = function( message ) {
		var $noticesWrapper = getNoticesWrapper();
		if ( ! $noticesWrapper.length ) { return; }

		var $response = $( '<div>' ).html( message );
		var $innerNotices = $response.find( '.woocommerce-notices-wrapper' );
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
		 */
		var applySmartCouponForButton = function( $button ) {
		var couponCode = $button.data( 'coupon_code' );
		if ( ! couponCode ) { return; }

		$button.css( 'opacity', '0.5' );

		$.ajax( {
			type: 'POST',
			url: fcSmartCoupons.applyUrl,
			data: {
				coupon_code: couponCode,
				reference_id: $button.data( 'reference_id' ) || '',
				security: fcSmartCoupons.applyNonce
			},
			dataType: 'json',
			success: function( response ) {
				if ( ! response || ! response.message ) { return; }

				insertNotices( response.message );
				$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
			},
			complete: function() {
				$button.css( 'opacity', '' );
			}
		} );
	};

		/**
		 * Remove a Smart Coupon when its "remove" link is clicked.
		 *
		 * Sends an AJAX request using Smart Coupons endpoints and updates checkout.
		 */
		var removeSmartCouponForLink = function( $link ) {
		var couponCode = $link.data( 'coupon' );
		if ( ! couponCode ) { return; }

		$.ajax( {
			type: 'POST',
			url: fcSmartCoupons.removeUrl,
			data: {
				coupon_code: couponCode,
				reference_id: $link.data( 'reference_id' ) || '',
				security: fcSmartCoupons.removeNonce
			},
			dataType: 'json',
			success: function( response ) {
				if ( ! response || ! response.message ) { return; }

				insertNotices( response.message );
				$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
			}
		} );
	};

		/**
		 * Capture click events on Smart Coupons apply button.
		 *
		 * Uses capture phase so we can intercept before plugin handlers.
		 */
		var captureHandler = function( e ) {
		var target = e.target;
		if ( ! target || ! target.closest ) { return; }

		var button = target.closest( '.apply_coupons_credits' );
		if ( ! button ) { return; }

		e.preventDefault();
		e.stopImmediatePropagation();

		applySmartCouponForButton( $( button ) );
	};

		/**
		 * EVENT LISTENERS
		 *
		 * Attach global click listeners in capture phase to override
		 * Smart Coupons default behavior and keep notices in sync.
		 */
	document.addEventListener( 'click', captureHandler, true );

	document.addEventListener( 'click', function( e ) {
		var target = e.target;
		if ( ! target || ! target.closest ) { return; }

		var link = target.closest( 'a.woocommerce-remove-coupon' );
		if ( ! link ) { return; }

		e.preventDefault();
		e.stopImmediatePropagation();

		removeSmartCouponForLink( $( link ) );
	}, true );
} );
