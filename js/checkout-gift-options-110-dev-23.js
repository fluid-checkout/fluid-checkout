/**
 * Show or hide gift options fields.
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
		root.CheckoutGiftOptions = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';



	var $ = jQuery;
	
	var _publicMethods = {};
	var _settings = {
		fieldsWrapperSelector: '#woocommerce-gift-options__field-wrapper',
		checkboxSelector: '#_wfc_has_gift_options',

		bodyClass: 'has-wfc-gift-options--active',
		activeClass: 'active',
	}

	
  
	/**
	 * METHODS
	 */
	

	 /**
	 * Toggle fields visibility.
	 */
	var toggleFieldsVisibility = function( checkbox ) {
		var fieldsWrapper = document.querySelector( _settings.fieldsWrapperSelector );

		if ( checkbox.checked ) {
			fieldsWrapper.classList.add( _settings.activeClass );
		}
		else {
			fieldsWrapper.classList.remove( _settings.activeClass );
		}
	};

	
	
	/**
	 * Handle captured `change` event and route to the appropriate function.
	 */
	var handleChange = function( e ) {
		if ( e.target.matches( _settings.checkboxSelector ) ) {
			toggleFieldsVisibility( e.target );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Add event listeners
		window.addEventListener( 'change', handleChange );

		// Add init class
		document.body.classList.add( _settings.bodyClass );
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
