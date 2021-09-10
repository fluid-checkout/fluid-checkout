/**
 * Manage checkout steps state.
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
		root.CheckoutLocalPickup = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass: 'has-fc-checkout-local-pickup',

        shippingMethodPackageSelector: '.shipping-method__package',
        chosenShippingMethodSelector: 'input.shipping_method:checked',
        shippingAddressSubstepSelector: '.fc-step__substep[data-substep-id="shipping_address"]',

        localPickupSelectedClass: 'has-local-pickup-selected', 
        hideEditButtonClass: 'hide-edit-button',
        
        localPickupIdPattern: 'local_pickup',
	}



	/**
	 * METHODS
	 */



	/*!
	* Merge two or more objects together.
	* (c) 2017 Chris Ferdinandi, MIT License, https://gomakethings.com
	* @param   {Boolean}  deep     If true, do a deep (or recursive) merge [optional]
	* @param   {Object}   objects  The objects to merge together
	* @returns {Object}            Merged values of defaults and options
	*/
	var extend = function () {
		// Variables
		var extended = {};
		var deep = false;
		var i = 0;

		// Check if a deep merge
		if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
			deep = arguments[0];
			i++;
		}

		// Merge the object into the extended object
		var merge = function (obj) {
			for (var prop in obj) {
				if (obj.hasOwnProperty(prop)) {
					// If property is an object, merge properties
					if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
						extended[prop] = extend(extended[prop], obj[prop]);
					} else {
						extended[prop] = obj[prop];
					}
				}
			}
		};

		// Loop through each object and conduct a merge
		for (; i < arguments.length; i++) {
			var obj = arguments[i];
			merge(obj);
		}

		return extended;
    };



	/**
	 * Maybe change visibility status of the shipping address edit buttons when shipping method is `local_pickup`.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 * @param   Array  data   The updated checkout data.
	 */
	var maybeChangeShippingAddressEditButtonVisibility = function( _event, data ) {
		var $packages = $( _settings.shippingMethodPackageSelector );
		if ( $packages.length ) {
			$packages.each( function() {

				var $chosen_method = $( this ).find( _settings.chosenShippingMethodSelector );
				var $shipping_address_substep = $( _settings.shippingAddressSubstepSelector );
				var is_local_pickup = $chosen_method.val().startsWith( _settings.localPickupIdPattern );
				
				// TODO: Manage multiple shipping packages
				if( is_local_pickup ) {
					$shipping_address_substep.addClass( [ _settings.localPickupSelectedClass, _settings.hideEditButtonClass ] );
				}
				else {
					$shipping_address_substep.removeClass( [ _settings.localPickupSelectedClass, _settings.hideEditButtonClass ] );
				}

			} );
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = extend( _settings, options );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeChangeShippingAddressEditButtonVisibility );
		}

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
