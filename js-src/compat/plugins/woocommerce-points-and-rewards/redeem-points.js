/**
 * Apply discounts for the plugin WooCommerce Points and Rewards.
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
		root.PointsRewardsRedeemPoints = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _isProcessing = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass: 'has-fc-points-rewards-redeem-points',

		substepSelector: '.fc-step__substep[data-substep-id="coupon_codes"]',
		redeemSectionSelector: '.wc_points_redeem_earn_points',
		redeemButtonSelector: '.wc_points_rewards_apply_discount',
		messagesSelector: '.woocommerce-error, .woocommerce-message',

		maxPointsFieldSelector: '[name="wc-points-rewards-max-points"]',
		pointsToApplyFieldSelector: 'input.wc_points_rewards_apply_discount_amount',
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



	var maybePromptPointsToRedeem = function() {
		// Bail if settings not available or partial redemption not enabled
		if ( ! window.fcSettings || ! fcSettings.woocommercePointsRewards || ! fcSettings.woocommercePointsRewards.partialRedemptionEnabled ) { return true; }
		
		// Get max points
		var max_points = document.querySelector( _settings.maxPointsFieldSelector );
		var pointsToApplyField = document.querySelector( _settings.pointsToApplyFieldSelector );
		
		// Bail if hidden fields for max amount of points or amount of points to redeem are not available
		if ( ! max_points || ! pointsToApplyField ) { return true; }

		var points = prompt( fcSettings.woocommercePointsRewards.pointsToRedeemMessage, max_points.value );
		if ( null != points && 0 != points ) {
			
			if ( points >= fcSettings.woocommercePointsRewards.minPointsToRedeem ) {
				// Should continue with redemption
				pointsToApplyField.value = points;
				return true;
			}
			else {
				alert( fcSettings.woocommercePointsRewards.lessThanMinPointsMessage );
			}
		}
		else {
			// User cancelled or value invalid
			return false;
		}
	}



	/**
	 * Sends the apply discount ajax request.
	 * 
	 * Adapted from the inline script added by the original plugin.
	 * 
	 * @see WC_Points_Rewards_Cart_Checkout::render_redeem_points_message()
	 */
	var submitPointsRedemption = function( e ) {
		// Bail if still processing a request for points redemption
		if ( _isProcessing ) { return; }

		// Maybe prompt how many points to redeem or cancel submit if dialog is cancelled by the user
		if ( ! maybePromptPointsToRedeem() ) { return; }

		// Get sections
		var $substep = $( _settings.substepSelector );
		var $section = $( _settings.redeemSectionSelector );

		// Set processing
		_isProcessing = true;
		$section.addClass( "processing" ).block({message: null, overlayCSS: {background: "#fff", opacity: 0.6}});

		// Remove existing messages
		var $messages = $substep.find( _settings.messagesSelector );
		$messages.remove();

		var data = {
			action: "wc_points_rewards_apply_discount",
			discount_amount: $("input.wc_points_rewards_apply_discount_amount").val(),
			security: ( woocommerce_params.apply_coupon_nonce ? woocommerce_params.apply_coupon_nonce : wc_checkout_params.apply_coupon_nonce )
		};

		$.ajax({
			type:     "POST",
			url:      woocommerce_params.ajax_url,
			data:     data,
			success:  function( response ) {
				if ( response ) {
					// Display response as a message
					$substep.prepend( response );

					$( "body" ).trigger( "update_checkout" );
				}
			},
			dataType: "html"
		});
	}



	/**
	 * Resets the processing flag state to `false`.
	 */
	var resetProcessingFlag = function () {
		_isProcessing = false;
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// NEXT STEP
		if ( e.target.closest( _settings.redeemButtonSelector ) ) {
			e.preventDefault();
			submitPointsRedemption( e );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {		
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = extend( _settings, options );

		// Add event listeners
		window.addEventListener( 'click', handleClick );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', resetProcessingFlag );
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
