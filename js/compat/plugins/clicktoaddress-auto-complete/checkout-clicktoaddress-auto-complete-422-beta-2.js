/**
 * Checkout scripts for: Fetchify (by ClearCourse Business Services Limited t/a Fetchify).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutClickToAddressAutoComplete = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		fieldsToHideSelector: '[cc-c2a-hide="1"]',
		manualAddressButtonSelector: '.cc_c2a_manual',
		searchAddressButtonSelector: '.cc_c2a_hide',
		searchAddressInputSelector: '.cc_c2a_search_input',
		addressFormSelector: '.woocommerce-billing-fields, .woocommerce-shipping-fields',
		initIndicatorSelector: '#cc_c2a',
	};



	/**
	 * METHODS
	 */



	/**
	 * Maybe initialize plugin's postcode search field.
	 */
	var maybeInitializePostcodeSearchField = function() {
		// Bail if plugin functions are not available
		if ( 'undefined' === typeof window.cc_c2a_functions ) { return; }

		// Bail if postcode search is disabled
		if ( ! window.cc_c2a_config.postcode.enabled ) { return; }

		// Bail if plugin's function is not available
		if ( 'function' !== typeof window.cc_c2a_functions.postcode.add_postcode ) { return; }

		// Initialize postcode search field
		window.cc_c2a_functions.postcode.add_postcode();
	}



	/**
	 * Maybe initialize plugin's address auto-complete feature.
	 */
	var maybeInitializeAddressAutoComplete = function() {
		var initIndicator = document.querySelector( _settings.initIndicatorSelector );
		var searchInput = document.querySelector( _settings.searchAddressInputSelector );

		// Bail if plugin functions are not available
		if ( 'undefined' === typeof window.cc_c2a_functions ) { return; }

		// Bail if address auto-complete is disabled
		if ( ! window.cc_c2a_config.autocomplete.enabled ) { return; }

		// Bail if plugin's function is not available
		if ( 'function' !== typeof window.cc_c2a_functions.autocomplete.add_autocomplete ) { return; }

		// Maybe initialize address auto-complete feature
		if ( ! initIndicator ) {
			window.cc_c2a_functions.autocomplete.add_autocomplete();

			// Maybe reset visibility state for manual address input fields
			if ( searchInput && 'none' !== searchInput.closest( 'p' ).style.display ) {
				window.cc_c2a_fields_visible = 0;
			}
		}

		// Hide marked fields
		maybeHideMarkedFields();
	}



	/**
	 * Maybe hide fields marked fields.
	 */
	var maybeHideMarkedFields = function() {
		// Bail if fields should not be hidden
		if ( ! window.cc_c2a_config.autocomplete.hide_fields || window.cc_c2a_fields_visible ) { return; }

		// Hide marked fields
		hideMarkedFields();
	}

	/**
	 * Hide fields marked with "cc-c2a-hide" attribute.
	 */
	var hideMarkedFields = function() {
		// Get all fields with "cc-c2a-hide" attribute
		var fieldsToHide = document.querySelectorAll( _settings.fieldsToHideSelector );

		// Hide fields
		for ( var i = 0; i < fieldsToHide.length; i++ ) {
			fieldsToHide[i].style.display = 'none';
		}
	}



	/**
	 * Toggle manual address fields visibility.
	 */
	var toggleManualAddressFieldsVisibility = function( element ) {
		var searchButton = document.querySelector( _settings.searchAddressButtonSelector );
		var searchInput = document.querySelector( _settings.searchAddressInputSelector );
		var containers = querySelectorAll( _settings.addressFormSelector );

		// Bail if plugin functions are not available
		if ( 'undefined' === typeof window.cc_c2a_functions ) { return; }

		// Hide manual address button
		element.style.display = 'none';

		// Maybe show search button
		if ( searchButton ) {
			searchButton.style.display = 'block';
		}

		// Maybe hide search input container
		if ( searchInput ) {
			searchInput.closest( 'p' ).style.display = 'none';
		}

		// Maybe show address fields
		if ( 'function' === typeof window.cc_c2a_functions.autocomplete.show_fields ) {
			for ( var i = 0; i < containers.length; i++ ) {
				window.cc_c2a_functions.autocomplete.show_fields( containers[i], false );
			}
		}
	}



	/**
	 * Toggle search address fields visibility.
	 */
	var toggleSearchAddressFieldsVisibility = function( element ) {
		var manualButton = document.querySelector( _settings.manualAddressButtonSelector );
		var searchInput = document.querySelector( _settings.searchAddressInputSelector );

		// Set global variable as it is done in the plugin
		window.cc_c2a_fields_visible = 0;

		// Hide search button
		element.style.display = 'none';

		// Maybe show manual address button
		if ( manualButton ) {
			manualButton.style.display = 'block';
		}

		// Maybe show search input container
		if ( searchInput ) {
			searchInput.closest( 'p' ).style.display = 'block';
		}

		// Hide marked fields
		hideMarkedFields();
	}



	/**
	 * Handle captured `click` event and route to the appropriate functions.
	 */
	var handleClick = function( e ) {
		// MANUAL ADDRESS BUTTON
		if ( e.target.matches( _settings.manualAddressButtonSelector ) ) {
			e.preventDefault();
			toggleManualAddressFieldsVisibility( e.target );
		}
		// SEARCH ADDRESS BUTTON
		if ( e.target.matches( _settings.searchAddressButtonSelector ) ) {
			e.preventDefault();
			toggleSearchAddressFieldsVisibility( e.target );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Add event listeners
		document.addEventListener( 'click', handleClick, true );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeInitializePostcodeSearchField );
			$( document.body ).on( 'updated_checkout', maybeInitializeAddressAutoComplete );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
