/**
 * Checkout scripts for: Germanized for WooCommerce (by vendidero).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutWooCommerceGermanized = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		uiProcessingClass: 'processing',
		managedByPickupLocationClass: 'wc-shiptastic-managed-by-pickup-location',
		locationNoticeClass: 'wc-shiptastic-managed-by-pickup-location-notice',

		removePickupLocationButtonSelector: '.pickup-location-remove',
		currentPickupLocationInputSelector: '#current_pickup_location',
		customerNumberFieldSelector: '#pickup_location_customer_number',
		fieldContainerSelector: '.woocommerce-input-wrapper',
		fieldRowSelector: '.form-row',

		prefferedDeliveryTypeFieldSelector: '.dhl-preferred-service-content .dhl-preferred-location-types input',
		prefferedDeliveryLocationFieldSelector : '.dhl-preferred-service-data > input',

		shippingAddressPrefix: '#shipping_',
	};


	/**
	 * METHODS
	 */



	/**
	 * Get a list of address fields that are set for value replacement.
	 */
	var getAddressReplacementFields = function() {
		// Bail if plugin function is not available
		if ( 'undefined' === typeof window.shiptastic.shipments_pickup_locations.getPickupLocation ) { return; }

		// Get current pickup location input
		var currentPickupLocationInput = document.querySelector( _settings.currentPickupLocationInputSelector );

		// Bail if current pickup location is not set
		if ( ! currentPickupLocationInput.value ) { return; }

		// Get value replacements
		var currentLocation = window.shiptastic.shipments_pickup_locations.getPickupLocation( currentPickupLocationInput.value );
		var replacemnts = Object.keys( currentLocation.address_replacements );

		// Add shipping address prefix to each field (replace the current field name)
		var replacmentFields = [];
		for ( var i = 0; i < replacemnts.length; i++ ) {
			replacmentFields.push( _settings.shippingAddressPrefix + replacemnts[ i ] );
		}

		return replacmentFields;
	}



	/**
	 * Maybe add "Managed by pickup location" notice to the field.
	 */
	var maybeAddFieldNotice = function() {
		// Bail if notice element from the plugin is not available
		if ( 'undefined' === typeof window.shiptastic.shipments_pickup_locations.params.i18n_managed_by_pickup_location ) { return; }

		// Get replacment fields
		var replacmentFields = getAddressReplacementFields();

		// Bail if no fields are set for replacement
		if ( ! replacmentFields || ! replacmentFields.length ) { return; }

		// Get current pickup location input
		var currentPickupLocationInput = document.querySelector( _settings.currentPickupLocationInputSelector );

		// Bail if current pickup location is not set
		if ( ! currentPickupLocationInput.value ) { return; }

		// Iterate through fields to add notice
		for ( var i = 0; i < replacmentFields.length; i++ ) {
			var field = document.querySelector( replacmentFields[ i ] );

			// Skip if field is not available
			if ( ! field ) { continue; }

			// Find parent form row
			var fieldRow = field.closest( _settings.fieldRowSelector );

			// If row exists, add class from the plugin add notice element
			if ( fieldRow ) {
				fieldRow.classList.add( _settings.managedByPickupLocationClass );

				// Skip if notice already exists
				if ( fieldRow.querySelector( '.' + _settings.locationNoticeClass ) ) { continue; }

				// Find field label
				var fieldLabel = fieldRow.querySelector( 'label' );

				// Skip if label is not available
				if ( ! fieldLabel ) { continue; }

				// Add notice element after the field label
				var notice = document.createElement( 'span' );
				notice.className = _settings.locationNoticeClass;
				notice.innerHTML = window.shiptastic.shipments_pickup_locations.params.i18n_managed_by_pickup_location;
				fieldLabel.parentNode.insertBefore( notice, fieldLabel.nextSibling );
			}
			// Otherwise, add class to the field itself
			else {
				field.classList.add( _settings.managedByPickupLocationClass );
			}
		}
	}



	/**
	 * Maybe block shipping address fields.
	 */
	var maybeBlockShippingAddressFields = function() {
		// Get replacment fields
		var replacmentFields = getAddressReplacementFields();

		// Bail if no fields are set for replacement
		if ( ! replacmentFields || ! replacmentFields.length ) { return; }

		// Iterate through fields to block
		for ( var i = 0; i < replacmentFields.length; i++ ) {
			var field = document.querySelector( replacmentFields[ i ] );

			// Skip if field is not available
			if ( ! field ) { continue; }
			
			// Find field container
			var fieldContainer = field.closest( _settings.fieldContainerSelector );
			if ( fieldContainer ) {
				// Block the field container
				_publicMethods.blockUI( fieldContainer );
			}
		}
	}



	/**
	 * Trigger update checkout.
	 */
	var triggerCheckoutUpdate = function() {
		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// REMOVE PICKUP LOCATION BUTTON
		if ( e.target.matches( _settings.removePickupLocationButtonSelector ) ) {
			maybeBlockShippingAddressFields();
			triggerCheckoutUpdate();
		}
	};

	/**
	 * Handle keypress event event and route to the appropriate functions.
	 */
	var handleKeyDown = function( e ) {
		// CUSTOMER NUMBER FIELD
		if ( e.target.matches( _settings.customerNumberFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	}

	/**
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// PREFERRED DELIVERY SECTION RADIO FIELDS
		if ( e.target.matches( _settings.prefferedDeliveryTypeFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
		// PREFERRED DELIVERY LOCATION FIELDS
		else if ( e.target.matches( _settings.prefferedDeliveryLocationFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	}



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
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		document.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'keydown', handleKeyDown );
		document.addEventListener( 'change', handleChange );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeAddFieldNotice );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
