/**
 * Checkout scripts for: Delivery & Pickup Date Time for WooCommerce (by CodeRockz).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutWooDelivery = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		dateFieldSelector: '#coderockz_woo_delivery_date_datepicker, #coderockz_woo_delivery_pickup_date_datepicker',
		timeFieldSelector: '#coderockz_woo_delivery_time_field, #coderockz_woo_delivery_pickup_time_field',
		deliveryDateHiddenFieldSelector: '#fc_coderockz_woo_delivery_date',
		deliveryTimeHiddenFieldSelector: '#fc_coderockz_woo_delivery_time',
		pickupDateHiddenFieldSelector: '#fc_coderockz_woo_pickup_date',
		pickupTimeHiddenFieldSelector: '#fc_coderockz_woo_pickup_time',
	};



	/**
	 * METHODS
	 */



	/**
	 * Maybe update datepicker fields with WC session values.
	 */
	var maybeUpdateDatepickerFields = function() {
		var datepickerFields = document.querySelectorAll( _settings.dateFieldSelector );

		// Bail if no fields found
		if ( ! datepickerFields.length ) { return; }

		// Update each Flatpickr field
		for ( var i = 0; i < datepickerFields.length; i++ ) {
			var flatPickerField = datepickerFields[ i ];
			var hiddenFieldSelector = flatPickerField.id.includes( 'pickup' ) ? _settings.pickupDateHiddenFieldSelector : _settings.deliveryDateHiddenFieldSelector;
			var hiddenDateField = document.querySelector(hiddenFieldSelector);

			// Get Flatpickr instance for the field if exists
			var flatPickerInstance = null;
			if ( flatPickerField._flatpickr ) {
				flatPickerInstance = flatPickerField._flatpickr;
			}

			// Update the Flatpickr field only if there's no value selected
			if ( flatPickerInstance && hiddenDateField && hiddenDateField.value && !flatPickerInstance.selectedDates.length ) {
				flatPickerInstance.setDate( hiddenDateField.value, false, 'Y-m-d' );
			}
		}
	}



	/**
	 * Myabe update time select fields with WC session values.
	 */
	var maybeUpdateTimeSelectFields = function() {
		var timeSelectFields = document.querySelectorAll( _settings.timeFieldSelector );

		// Bail if no fields found
		if ( ! timeSelectFields.length ) { return; }

		// Update each time select field
		for ( var i = 0; i < timeSelectFields.length; i++ ) {
			var timeSelectField = timeSelectFields[ i ];
			var hiddenFieldSelector = timeSelectField.id.includes( 'pickup' ) ? _settings.pickupTimeHiddenFieldSelector : _settings.deliveryTimeHiddenFieldSelector;
			var hiddenTimeField = document.querySelector( hiddenFieldSelector );

			// Bail if hidden field doesn't exist or has no value
			if ( ! hiddenTimeField || ! hiddenTimeField.value ) { continue; }

			// Bail if there's already a value selected
			if ( timeSelectField.value ) { continue; }

			// Loop through select field options to find one that matches the hidden field value and isn't disabled
			var validTimeOption = null;
			for ( var j = 0; j < timeSelectField.options.length; j++ ) {
				var option = timeSelectField.options[ j ];
				if ( option.value === hiddenTimeField.value && ! option.disabled ) {
					validTimeOption = option;
					break;
				}
			}

			// Update the select field
			if ( validTimeOption ) {
				timeSelectField.value = validTimeOption.value;
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
	 * Handle captured `change` event and route to the appropriate functions.
	 */
	var handleChange = function( e ) {
		// DATEPICKER FIELDS
		if ( e.target.matches( _settings.dateFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
		// TIME SELECT FIELDS
		else if ( e.target.matches( _settings.timeFieldSelector ) ) {
			triggerCheckoutUpdate();
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		maybeUpdateDatepickerFields();
		maybeUpdateTimeSelectFields();

		window.addEventListener( 'change', handleChange );

		if ( _hasJQuery ) {
			$( document.body ).on( 'change.select2', handleChange );
			$( document.body ).on( 'updated_checkout', maybeUpdateDatepickerFields );
			$( document.body ).on( 'updated_checkout', maybeUpdateTimeSelectFields );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
