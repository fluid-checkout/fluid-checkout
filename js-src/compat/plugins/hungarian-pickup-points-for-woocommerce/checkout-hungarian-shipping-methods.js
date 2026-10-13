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
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeUpdateShippingAddressFieldsVisibility );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
