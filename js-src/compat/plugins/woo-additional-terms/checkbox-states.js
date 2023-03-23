/**
 * Save additional terms checkboxes states between when updating checkout fragments.
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.WooAdditionalTermsCheckboxStates = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		additionalTermsCheckboxSelector: '.woocommerce-terms-and-conditions-wrapper.woo-additional-terms input[type="checkbox"]',
	};
	var _checkboxStates = {};




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
	 * Save the additional checkout states to a local variable.
	 */
	var saveCheckboxesStates = function() {
		// Clear checkboxes states
		_checkboxStates = {};

		// Get all additional terms checkboxes
		var checkboxes = document.querySelectorAll( _settings.additionalTermsCheckboxSelector );
		
		// Iterate and save additional terms checkboxes states
		for ( var i = 0; i < checkboxes.length; i++ ) {
			var checkbox = checkboxes[ i ];
			_checkboxStates[ checkbox.name ] = checkbox.checked;
		}
	}

	/**
	 * Restore the additional checkboxes states to a local variable.
	 */
	var restoreCheckboxesStates = function() {
		// Get all additional terms checkboxes
		var checkboxes = document.querySelectorAll( _settings.additionalTermsCheckboxSelector );
		
		// Iterate and restore additional terms checkboxes states
		for ( var i = 0; i < checkboxes.length; i++ ) {
			var checkbox = checkboxes[ i ];
			if ( _checkboxStates[ checkbox.name ] ) {
				checkbox.checked = _checkboxStates[ checkbox.name ];
			}
		}
	}



	/**
	 * Initialize script.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Set jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'fc_checkout_fragments_replace_before', saveCheckboxesStates );
			$( document.body ).on( 'fc_checkout_fragments_replace_after', restoreCheckboxesStates );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
