/* global fcSmartCoupons */
// Source behavior in WooCommerce Smart Coupons:
// - woocommerce-smart-coupons/includes/class-wc-sc-display-coupons.php
//   injects checkout AJAX handlers that insert notices before the form.
// Fluid Checkout wrapper:
// - fluid-checkout/inc/checkout-steps.php renders .fc-checkout-notices.
// This script intercepts apply/remove actions to insert notices in that wrapper.
jQuery( function( $ ) {
	if ( typeof fcSmartCoupons === 'undefined' ) { return; }

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

	var captureHandler = function( e ) {
		var target = e.target;
		if ( ! target || ! target.closest ) { return; }

		var button = target.closest( '.apply_coupons_credits' );
		if ( ! button ) { return; }

		e.preventDefault();
		e.stopImmediatePropagation();

		applySmartCouponForButton( $( button ) );
	};

	// Capture phase runs before plugin handlers and allows us to prevent
	// Smart Coupons from injecting notices into the wrong place.
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
