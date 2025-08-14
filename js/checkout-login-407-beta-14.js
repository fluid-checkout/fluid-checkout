/**
 * Manage AJAX login on the checkout page.
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
		root.CheckoutLogin = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		uiProcessingClass: 'processing',

		messagesWrapperSelector: '.fc-login-messages',

		loginFormSelector: '.woocommerce-form-login',
		usernameFieldSelector: '.woocommerce-form-login input[name="username"]',
		passwordFieldSelector: '.woocommerce-form-login input[name="password"]',
		rememberMeFieldSelector: '.woocommerce-form-login input[name="rememberme"]',
		loginButtonSelector: '.fc-login-button .woocommerce-form-login__submit',

		messagesWrapperTemplate: '<ul class="woocommerce-error"><li>###MESSAGE###</li></ul>',
	}



	/**
	 * METHODS
	 */



	/**
	 * Add notices to the login form.
	 *
	 * @param   HTML  content  HTML content to be displayed.
	 */
	var showNotices = function( content ) {
		// Bail if content is empty
		if ( ! content ) { return; }

		// Get messages wrapper from the login form
		var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );

		// Bail if messages wrapper is not available
		if ( ! messagesWrapper ) { return; }

		// Add error notice
		messagesWrapper.innerHTML = _settings.messagesWrapperTemplate.replace( '###MESSAGE###', content );
	}

	/**
	 * Remove all notices from the login form.
	 *
	 * @param   HTML  content  HTML content to be displayed.
	 */
	var clearNotices = function() {
		var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );
		if ( messagesWrapper ) {
			messagesWrapper.innerHTML = '';
		}
	}



	/**
	 * Log user in.
	 */
	var checkoutLogin = function() {
		// Clear old notices
		clearNotices();

		var loginForm = document.querySelector( _settings.loginFormSelector );
		var usernameField = document.querySelector( _settings.usernameFieldSelector );
		var passwordField = document.querySelector( _settings.passwordFieldSelector );
		var rememberMeField = document.querySelector( _settings.rememberMeFieldSelector );

		// Bail if required elements are not available
		if ( ! loginForm || ! usernameField || ! passwordField || ! rememberMeField ) { return; }

		// Block login form
		_publicMethods.blockUI( loginForm );

		// Add security nonce
		var data = {
			security: _settings.checkoutLoginNonce,
			username: usernameField.value,
			password: passwordField.value,
			rememberme: rememberMeField.checked,
		}

		$.ajax({
			type:		'POST',
			url:		fcSettings.wcAjaxUrl.replace( '%%endpoint%%', 'fc_checkout_login' ),
			data:		data,
			dataType:   'json',
			success:	function( response ) {

				// Maybe process success
				if ( response.result && 'success' === response.result ) {
					// Reload the page
					window.location.reload()
				}
				// Maybe process error
				else if ( response.result && 'error' === response.result ) {
					// Display new messages
					showNotices( response.message );

					// Unblock login form
					var loginForm = document.querySelector( _settings.loginFormSelector );
					if ( loginForm ) {
						_publicMethods.unblockUI( loginForm );
					}
				}

			}

		});
	};



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// LOGIN BUTTTON
		if ( e.target.closest( _settings.loginButtonSelector ) ) {
			e.preventDefault();
			checkoutLogin();
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
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
