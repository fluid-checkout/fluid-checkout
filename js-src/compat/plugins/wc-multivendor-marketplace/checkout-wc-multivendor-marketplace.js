/**
 * Checkout scripts for: WCFM - WooCommerce Multivendor Marketplace (by WC Lovers).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutWCMultivendorMarketplace = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		selectedShippingMethodSelector: 'input[name^="shipping_method"]:checked',
		deliveryLocationFieldRowSelector: '#wcfmmp_user_location_field',
		deliveryLocationMapSelector: '#wcfmmp-user-locaton-map',
		localPickupMethodPrefix: 'local_pickup',
		isHiddenClass: 'fc-hidden',
	};



	/**
	 * METHODS
	 */



	/**
	 * Check whether local pickup is selected for every shipping package.
	 *
	 * @return  {Boolean}  True when every shipping package has a local pickup method selected.
	 */
	var isLocalPickupSelectedForAllPackages = function() {
		var selectedMethods = document.querySelectorAll( _settings.selectedShippingMethodSelector );

		// Bail if no shipping method is selected
		if ( ! selectedMethods.length ) { return false; }

		// Iterate selected shipping methods
		for ( var i = 0; i < selectedMethods.length; i++ ) {
			// Return `false` when a package does not use local pickup
			if ( 0 !== selectedMethods[ i ].value.indexOf( _settings.localPickupMethodPrefix ) ) { return false; }
		}

		return true;
	};



	/**
	 * Maybe hide the delivery location field and map when every shipping package uses local pickup.
	 * Required as the shipping address fields are not replaced while being edited, so the field keeps its required state from the page load.
	 */
	var maybeChangeDeliveryLocationVisibility = function() {
		var isLocalPickup = isLocalPickupSelectedForAllPackages();
		var fieldRow = document.querySelector( _settings.deliveryLocationFieldRowSelector );
		var map = document.querySelector( _settings.deliveryLocationMapSelector );

		// Maybe change the delivery location field visibility
		if ( fieldRow ) {
			fieldRow.classList.toggle( _settings.isHiddenClass, isLocalPickup );
		}

		// Maybe change the map visibility
		if ( map ) {
			map.classList.toggle( _settings.isHiddenClass, isLocalPickup );
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
			$( document.body ).on( 'updated_checkout', maybeChangeDeliveryLocationVisibility );
		}

		// Set the initial delivery location visibility
		maybeChangeDeliveryLocationVisibility();

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
