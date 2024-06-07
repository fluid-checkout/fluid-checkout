/**
 * Sync FC color mode with theme's
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
		root.BlocksyColorModeSwitch = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		switchColorModeNonce: '', // Value updated during runtime
	};



	/**
	 * METHODS
	 */



	/**
	 * Get cookie value by name.
	 * 
	 *  @param  {String}  cname  Name of the cookie.
	 */
	var getCookie = function( cname ) {
		// Append "=" to the cookie name
		let name = cname + "=";

		// Get all cookies in an array by splitting them by ";"
		let cookies = document.cookie.split( ';' );

		// Loop through the cookies array
		for ( let i = 0; i < cookies.length; i++ ) {
			// Get the current cookie
			let cookie = cookies[i];

			// Remove any leading spaces
			while ( cookie.charAt( 0 ) == ' ' ) {
				cookie = cookie.substring( 1 );
			}

			// If the cookie is found, return the value of the cookie
			if ( cookie.indexOf( name ) == 0 ) {
				return cookie.substring( name.length, cookie.length );
			}
		}

		return "";
	}



	/**
	 * Maybe swith color mode.
	 */
	var maybeSwitchColorMode = function() {
		// Wait until the color mode is updated
		setTimeout( () => {
			let colorMode = getCookie( 'blocksy_current_theme' );

			// Add body class inidicating dark mode
			if ( 'dark' === colorMode && ! $( document.body ).hasClass( 'has-fc-dark-mode' ) ) {
				$( document.body ).addClass( 'has-fc-dark-mode' );
			} else if ( 'light' === colorMode ) {
				$( document.body ).removeClass( 'has-fc-dark-mode' );
			}

			// CSS variables
			loadCssVariables( colorMode );
		}, 300 )
	}



	/**
	 * Load CSS variables for the currently active color mode.
	 */
	var loadCssVariables = function( colorMode ) {
		// Get data to send
		var data = FCUtils.extendObject( data, {
			color_mode: colorMode,
			security: _settings.switchColorModeNonce,
		} );

		// Add CSS variables using AJAX
		$.ajax({
			url: fcSettings.wcAjaxUrl.toString().replace( '%%endpoint%%', 'fc_switch_color_mode' ),
			type: 'POST',
			data: data,
			dataType: 'json',
			success: function( response ) {
				let cssVariables = response.variables;

				// Add the received variables to root
				Object.keys( cssVariables ).forEach(function( key ) {
					document.documentElement.style.setProperty( key, cssVariables[key] );
				});
			}
		});
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		if ( _hasJQuery ) {
			// Maybe change color mode when theme's color switch is clicked
			$( document.body ).on( 'click', '.ct-color-switch', maybeSwitchColorMode );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});