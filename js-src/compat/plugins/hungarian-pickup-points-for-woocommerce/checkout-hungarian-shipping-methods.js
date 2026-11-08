/**
 * Checkout scripts for: Hungarian Pickup Points & Shipping Labels for WooCommerce (by Viszt Péter).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutHungarianShippingMethods = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		selectedShippingMethodOptionSelector: 'input[type="radio"][name^="shipping_method"]:checked',
		shipToDifferentAddressCheckboxSelector: '#ship-to-different-address-checkbox',
		mapTriggerSelector: '#vp-woo-pont-show-map, .vp-woo-pont-show-map',
		billingCountryFieldSelector: '#billing_country',
		shippingCountryFieldSelector: '#shipping_country',
		hungarianShippingMethodID: 'vp_pont',
	};



	/**
	 * METHODS
	 */



	/**
	 * Maybe update the shipping address fields visibility based on the selected shipping method.
	 */
	var maybeUpdateShippingAddressFieldsVisibility = function() {
		// Bail if plugin function is not available
		if ( 'undefined' === typeof vp_woo_pont_frontend || 'function' !== typeof vp_woo_pont_frontend.show_and_hide_shipping_address ) { return; }

		var isHungarianShippingMethod = false;
		var selectedShippingMethodOption = document.querySelector( _settings.selectedShippingMethodOptionSelector );
		var shipToDifferentAddressCheckbox = document.querySelector( _settings.shipToDifferentAddressCheckboxSelector );

		// Check if the target shipping method is selected
		if ( selectedShippingMethodOption && selectedShippingMethodOption.value ) {
			isHungarianShippingMethod = selectedShippingMethodOption.value.includes( _settings.hungarianShippingMethodID );
		}

		// Show or hide the shipping address section, which also unchecks "Ship to a different address" when hiding it
		vp_woo_pont_frontend.show_and_hide_shipping_address( isHungarianShippingMethod );

		// Maybe check "Ship to a different address" again, as Fluid Checkout requires it for other shipping methods
		if ( shipToDifferentAddressCheckbox && ! isHungarianShippingMethod ) {
			shipToDifferentAddressCheckbox.checked = true;
		}
	};



	/**
	 * Maybe use the shipping country for the pickup point map, as the plugin loads points for the billing country first.
	 * Uses a jQuery listener on `document.body`, so it runs before the plugin's listener on `document` and also receives its jQuery-triggered click.
	 */
	var maybeUseShippingCountryForMap = function() {
		var billingCountryField = document.querySelector( _settings.billingCountryFieldSelector );
		var shippingCountryField = document.querySelector( _settings.shippingCountryFieldSelector );

		// Bail if country fields are not available
		if ( ! billingCountryField || ! shippingCountryField ) { return; }

		// Get billing and shipping countries
		var billingCountry = billingCountryField.value;
		var shippingCountry = shippingCountryField.value;

		// Bail if shipping country is not set
		if ( ! shippingCountry ) { return; }

		// Bail if billing country already matches the shipping country
		if ( billingCountry === shippingCountry ) { return; }

		// Use the shipping country while the plugin reads the country fields
		billingCountryField.value = shippingCountry;

		// Restore the billing country after the plugin has read it
		requestAnimationFrame( function() {
			billingCountryField.value = billingCountry;
		} );
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeUpdateShippingAddressFieldsVisibility );
			$( document.body ).on( 'click', _settings.mapTriggerSelector, maybeUseShippingCountryForMap );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
