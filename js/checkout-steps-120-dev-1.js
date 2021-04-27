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
		root.CheckoutSteps = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		bodyClass: 'has-wfc-checkout-steps',
		bodyClassActiveStepPattern: 'wfc-checkout-step--active-{ID}',

		substepSelector: '.wfc-step__substep',
		substepFieldsSelector: '.wfc-step__substep-fields',
		substepTextSelector: '.wfc-step__substep-text',
		substepEditButtonSelector: '[data-step-edit]',
		substepSaveButtonSelector: '[data-step-save]',
		substepEditingClass: 'is-editing',
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
	 * Expand the substep fields for edition, and collapse the substep values in text format.
	 *
	 * @param   HTMLElement  substepElement  Substep element to change the state of.
	 */
	var expandSubstepEdit = function( substepElement ) {
		// Bail if editButton not valid
		if ( ! substepElement ) { return; }

		var substepFieldsElement = substepElement.querySelector( _settings.substepFieldsSelector );
		var substepTextElement = substepElement.querySelector( _settings.substepTextSelector );

		// Change expanded/collapsed states for the fields and text blocks
		CollapsibleBlock.expand( substepFieldsElement );
		CollapsibleBlock.collapse( substepTextElement );

		// Add editing class to the substep element
		substepElement.classList.add( _settings.substepEditingClass );
	}

	/**
	 * Collapse the substep fields, and expand the substep values in text format for review.
	 *
	 * @param   HTMLElement  substepElement  Substep element to change the state of.
	 */
	 var collapseSubstepEdit = function( substepElement ) {
		// Bail if editButton not valid
		if ( ! substepElement ) { return; }

		var substepFieldsElement = substepElement.querySelector( _settings.substepFieldsSelector );
		var substepTextElement = substepElement.querySelector( _settings.substepTextSelector );

		// Change expanded/collapsed states for the fields and text blocks
		CollapsibleBlock.collapse( substepFieldsElement );
		CollapsibleBlock.expand( substepTextElement );

		// Remove editing class from the substep element
		substepElement.classList.remove( _settings.substepEditingClass );
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// EDIT SUBSTEP
		if ( e.target.closest( _settings.substepEditButtonSelector ) ) {
			var editButton = e.target.closest( _settings.substepEditButtonSelector );
			var substepElement = editButton.closest( _settings.substepSelector );
			expandSubstepEdit( substepElement );
		}
		// SAVE SUBSTEP
		else if ( e.target.closest( _settings.substepSaveButtonSelector ) ) {
			var saveButton = e.target.closest( _settings.substepSaveButtonSelector );
			var substepElement = saveButton.closest( _settings.substepSelector );
			// TODO: CHANGE FUNCTION CALL TO VALIDATE SUBSTEP BEFORE COLLAPSING
			collapseSubstepEdit( substepElement );
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = extend( _settings, options );

		// TODO: Check if CollapsibleBlocks is loaded before finishing the initialization, and try to load it if not available
		
		// Add event listeners
		window.addEventListener( 'click', handleClick );

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
