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
console.log( '[FC SmartCoupons Debug] checkout-woocommerce-smart-coupons compat script file loaded.' );

jQuery( function( $ ) {
	if ( typeof fcSmartCoupons === 'undefined' ) {
		if ( window && window.console && console.warn ) {
			console.warn( '[FC SmartCoupons Debug] fcSmartCoupons is undefined, aborting Smart Coupons compatibility script.' );
		}
		return;
	}

	/**
	 * DEBUG LOGGER
	 *
	 * Logs are always enabled for now to help debugging
	 * Smart Coupons compatibility issues.
	 */
	var logDebug = function() {
		if ( ! window || ! window.console || ! console.log ) { return; }

		var args = Array.prototype.slice.call( arguments );
		args.unshift( '[FC SmartCoupons Debug]' );
		console.log.apply( console, args );
	};

	logDebug( 'Script initialized.', {
		fcSmartCoupons: fcSmartCoupons,
		hasApplyButtons: !! $( '.apply_coupons_credits' ).length
	} );

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

		logDebug( 'getNoticesWrapper resolved.', {
			isForm: $noticesWrapper.is( 'form' ),
			selector: $noticesWrapper.selector,
			length: $noticesWrapper.length
		} );

		return $noticesWrapper;
	};

	/**
	 * Insert notices HTML into the determined wrapper.
	 *
	 * Accepts raw markup or full response payload from Smart Coupons.
	 */
	var insertNotices = function( message ) {
		var $noticesWrapper = getNoticesWrapper();
		if ( ! $noticesWrapper.length ) {
			logDebug( 'insertNotices: no wrapper found, message dropped.', message );
			return;
		}

		logDebug( 'insertNotices: raw message.', message );

		var $response = $( '<div>' ).html( message );
		var $innerNotices = $response.find( '.woocommerce-notices-wrapper' );
		var noticesHtml = $innerNotices.length ? $innerNotices.html() : message;

		logDebug( 'insertNotices: parsed content.', {
			hasInnerWrapper: !! $innerNotices.length,
			targetIsForm: $noticesWrapper.is( 'form' )
		} );

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
		// Newer versions of Smart Coupons changed how coupon data is stored.
		// Try multiple fallbacks so this works across versions.
		var couponCode = $button.data( 'coupon_code' )
			|| $button.data( 'coupon' )
			|| $button.attr( 'name' )
			|| $button.find( '.code' ).text().trim();

		logDebug( 'applySmartCouponForButton called.', {
			button: $button.get( 0 ),
			couponCode: couponCode,
			referenceId: $button.data( 'reference_id' ),
			ajaxUrl: fcSmartCoupons.applyUrl
		} );

		if ( ! couponCode ) {
			logDebug( 'applySmartCouponForButton: no coupon code found on button (checked data-coupon_code, data-coupon, name, and .code text), aborting.' );
			return;
		}

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
				logDebug( 'applySmartCouponForButton: AJAX success.', response );

				if ( ! response || ! response.message ) {
					logDebug( 'applySmartCouponForButton: missing response.message, nothing to insert.' );
					return;
				}

				insertNotices( response.message );
				logDebug( 'applySmartCouponForButton: triggering update_checkout.' );
				$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				logDebug( 'applySmartCouponForButton: AJAX error.', {
					textStatus: textStatus,
					errorThrown: errorThrown,
					responseText: jqXHR && jqXHR.responseText
				} );
			},
			complete: function() {
				logDebug( 'applySmartCouponForButton: AJAX complete, restoring button opacity.' );
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

		logDebug( 'removeSmartCouponForLink called.', {
			link: $link.get( 0 ),
			couponCode: couponCode,
			referenceId: $link.data( 'reference_id' ),
			ajaxUrl: fcSmartCoupons.removeUrl
		} );

		if ( ! couponCode ) {
			logDebug( 'removeSmartCouponForLink: no coupon data attribute on link, aborting.' );
			return;
		}

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
				logDebug( 'removeSmartCouponForLink: AJAX success.', response );

				if ( ! response || ! response.message ) {
					logDebug( 'removeSmartCouponForLink: missing response.message, nothing to insert.' );
					return;
				}

				insertNotices( response.message );
				logDebug( 'removeSmartCouponForLink: triggering update_checkout.' );
				$( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
			},
			error: function( jqXHR, textStatus, errorThrown ) {
				logDebug( 'removeSmartCouponForLink: AJAX error.', {
					textStatus: textStatus,
					errorThrown: errorThrown,
					responseText: jqXHR && jqXHR.responseText
				} );
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

		logDebug( 'captureHandler fired for click.', {
			target: target,
			targetClassName: target && target.className
		} );

		if ( ! target || ! target.closest ) {
			logDebug( 'captureHandler: target has no closest method, aborting.' );
			return;
		}

		var button = target.closest( '.apply_coupons_credits' );

		logDebug( 'captureHandler: closest(".apply_coupons_credits") result.', {
			button: button
		} );

		if ( ! button ) {
			return;
		}

		e.preventDefault();
		e.stopImmediatePropagation();

		logDebug( 'captureHandler: intercepted click on apply button, calling applySmartCouponForButton.' );
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

		logDebug( 'remove coupon click handler fired.', {
			target: target,
			targetClassName: target && target.className
		} );

		if ( ! target || ! target.closest ) {
			logDebug( 'remove coupon handler: target has no closest method, aborting.' );
			return;
		}

		var link = target.closest( 'a.woocommerce-remove-coupon' );

		logDebug( 'remove coupon handler: closest("a.woocommerce-remove-coupon") result.', {
			link: link
		} );

		if ( ! link ) {
			return;
		}

		e.preventDefault();
		e.stopImmediatePropagation();

		logDebug( 'remove coupon handler: intercepted click on remove link, calling removeSmartCouponForLink.' );
		removeSmartCouponForLink( $( link ) );
	}, true );
} );
