/**
 * Show or hide billing address fields.
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
		root.CheckoutBillingSameShipping = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasJQuery = ( $ != null );
	var _hasInitialized = false;	
	var _publicMethods = {};
	var _settings = {
		fieldsWrapperSelector: '#woocommerce-billing-fields__field-wrapper',
		checkboxSelector: '#billing_same_as_shipping',
	}

	
  
	/**
	 * METHODS
	 */
	

	 /**
	 * Toggle fields visibility.
	 */
	var toggleFieldsVisibility = function( checkbox ) {
		var fieldsWrapper = document.querySelector( _settings.fieldsWrapperSelector );

		// Toggle state
		if ( ! checkbox.checked ) {
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

			// Update the checkout
			$( document.body ).trigger( 'update_checkout' );
		}
	};



	/**
	 * Initialize select2 components for address fields after `updated_checkout`.
	 */
	var maybeReinitializeCollapsibleBlock = function() {
		var fieldsWrapper = document.querySelector( _settings.fieldsWrapperSelector );

		// Maybe initialize collapsible-block for the element
		if ( ! CollapsibleBlock.getInstance( fieldsWrapper ) ) {
			CollapsibleBlock.initializeElement( fieldsWrapper );
		}
	}



	/**
	 * Finish to initialize component and set related handlers.
	 */
	var finishInit = function() {
		// Add event listeners
		window.addEventListener( 'change', handleChange );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeReinitializeCollapsibleBlock );
		}

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
