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


	var _hasInitialized = false;	
	var _publicMethods = {};
	var _settings = {
		fieldsWrapperSelector: '#wfc-gift-options__field-wrapper',
		checkboxSelector: '#_wfc_has_gift_options',

		bodyClass: 'has-wfc-gift-options--active',
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
			CollapsibleBlock.expand( fieldsWrapper );
		}
		else {
			CollapsibleBlock.collapse( fieldsWrapper );
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
	 * Finish to initialize component and set related handlers.
	 */
	var finishInit = function() {
		// Add event listeners
		window.addEventListener( 'change', handleChange );

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	}



	/**
	 * Load required dependencies, initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		// Finish initialization, maybe load dependencies first
		if ( window.CollapsibleBlock ) {
			finishInit();
		}
		else if( window.RequireBundle ) {
			RequireBundle.require( [ 'collapsible-block' ], function() { finishInit(); } );
		}
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
