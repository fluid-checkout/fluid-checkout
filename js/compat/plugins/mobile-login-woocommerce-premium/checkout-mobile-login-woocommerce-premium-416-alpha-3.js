/**
 * Checkout scripts for: OTP Login/Signup Woocommerce Premium (by XootiX).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutMobileLoginWoocommercePremium = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		phoneFieldSelector: '.xoo-ml-phone-input',
		countryCodeFieldSelector: '.xoo-ml-phone-cc',
		verificationStatusFieldSelector: '.validate-mobile-login-woo',
		inlineVerifyButtonSelector: '.xoo-ml-inline-verify',
		loginModalButtonSelector: 'a[data-flyout-target="[data-flyout-checkout-login]"]',
		OTPLoginFormSelector: '.xoo-lwo-form',
		defaultLoginFormSelector: '.woocommerce-form-login',

		verifiedClass: 'verified',
	};



	/**
	 * METHODS
	 */



	/**
	 * Get phone number.
	 */
	var getPhoneNumber = function() {
		var phoneNumber = '';
		
		// Get phone number field
		var phoneField = document.querySelector( _settings.phoneFieldSelector );

		// Bail if phone number is not set
		if ( ! phoneField || ! phoneField.value ) { return phoneNumber; }

		// Get phone number and process it the same way as in the plugin
		phoneNumber = phoneField.value.toString().trim();

		return phoneNumber;
	}



	/**
	 * Maybe mark phone number as verified.
	 */
	var maybeMarkAsVerified = function() {
		// Get inline verify button
		var inlineVerifyButton = document.querySelector( _settings.inlineVerifyButtonSelector );

		// Bail if verify button is not found
		if ( ! inlineVerifyButton ) { return; }

		// Get phone number and country code
		var phoneNumber = getPhoneNumber();

		// Get hidden field with verification status
		var verificationStatusField = document.querySelector( _settings.verificationStatusFieldSelector );

		// Maybe add or move verification status indicator as how it is done in the plugin
		if ( phoneNumber && verificationStatusField && verificationStatusField.value ) {
			inlineVerifyButton.innerHTML = _settings.strings.verified;
			inlineVerifyButton.classList.add( _settings.verifiedClass );
		} else {
			inlineVerifyButton.innerHTML = _settings.strings.verify;
			inlineVerifyButton.classList.remove( _settings.verifiedClass );
		}
	}



	/**
	 * Maybe show OTP login form.
	 * This function is used to fix the plugin's function 'adjustPositions' hiding the OTP login form when it's not visible on page load.
	 */
	var maybeShowOTPLoginForm = function() {
		// Get forms
		var OTPLoginForm = document.querySelector( _settings.OTPLoginFormSelector );
		var defaultLoginForm = document.querySelector( _settings.defaultLoginFormSelector );

		// Bail if any of the forms is visible to prevent breaking plugin's form toggle buttons
		if ( 'none' !== OTPLoginForm.style.display || 'none' !== defaultLoginForm.style.display ) { return; }

		// Show OTP login form
		OTPLoginForm.style.display = 'block';
	}



	/**
	 * Trigger update checkout.
	 */
	var triggerCheckoutUpdate = function() {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle form field input event and route to the appropriate function.
	 */
	var handleInput = function( e ) {
		// PHONE NUMBER FIELD
		if ( e.target.matches( _settings.phoneFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
		// PHONE COUNTRY CODE FIELD
		if ( e.target.matches( _settings.countryCodeFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	}

	/**
	 * Handle form field change event and route to the appropriate function.
	 */
	var handleChange = function( e ) {
		// PHONE COUNTRY CODE FIELD
		if ( e.target.matches( _settings.countryCodeFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	}

	/**
	 * Handle click event and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// LOGIN MODAL BUTTON
		if ( e.target.matches( _settings.loginModalButtonSelector ) ) {
			maybeShowOTPLoginForm();
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Maybe update field at initialization
		maybeMarkAsVerified();

		// Add event listeners
		window.addEventListener( 'input', handleInput );
		window.addEventListener( 'change', handleChange );
		window.addEventListener( 'click', handleClick );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeMarkAsVerified );
			$( document.body ).on( 'change.select2', handleChange );

			// OTP verification success
			$( document.body ).on( 'xoo_ml_on_otp_success', triggerCheckoutUpdate );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
