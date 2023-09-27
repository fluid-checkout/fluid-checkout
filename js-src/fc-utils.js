/**
 * Utility resources shared across the scripts of Fluid Checkout.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCUtils = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _publicMethods = { }
	var _settings = {
		select2FormRowSelector:                '.form-row.fc-select2-field',
		select2FocusElementSelector:           '.select2-selection, input[type="text"]',
	}



	/**
	 * PROPERTIES
	 */



	/**
	 * Mapping of keyboard keys based on and comparable with `event.key` values.
	 */
	_publicMethods.keyboardKeys = {
		ESC: 'Escape',
		ENTER: 'Enter',
		SPACE: ' ',
		TAB: 'Tab',
		CAPS: 'CapsLock',
		SHIFT: 'Shift',
		FUNCTION: 'Fn',
		CONTROL: 'Control',
		COMMAND_OR_WINDOWS: 'Meta', // This is the `Windows` logo key, or the `Command` or `⌘` key on Mac keyboards.
		ALT: 'Alt',
		ARROW_LEFT: 'ArrowLeft',
		ARROW_RIGHT: 'ArrowRight',
		ARROW_UP: 'ArrowUp',
		ARROW_DOWN: 'ArrowDown',
	}



	/**
	 * Determine which `animationend` event is supported.
	 */
	_publicMethods.animationEndEvent = window.whichAnimationEnd ? window.whichAnimationEnd() : 'animationend';




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
	_publicMethods.extendObject = function () {
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
						extended[prop] = _publicMethods.extendObject(extended[prop], obj[prop]);
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
	 * Returns a function, that, as long as it continues to be invoked, will not
	 * be triggered. The function will be called after it stops being called for
	 * N milliseconds. If `immediate` is passed, trigger the function on the
	 * leading edge, instead of the trailing.
	 *
	 * @param   {[type]}  func       Function to be executed.
	 * @param   {[type]}  wait       Wait time in milliseconds.
	 * @param   {[type]}  immediate  Trigger the function on the leading edge.
	 *
	 * @return  function              Function to be executed, incapsulated in a timed function.
	 */
	_publicMethods.debounce = function ( func, wait, immediate ) {
		var timeout;

		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply( context, args );
			};

			var callNow = immediate && !timeout;
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );

			if ( callNow ) func.apply( context, args );
		};
	};





	/**
	 * Maybe set focus back to the element that was focused before an update.
	 * 
	 * @param  {HTMLElement}  currentFocusedElement  The element that was currently focused before an update.
	 * @param  {mixed}        currentValue           The value of the element in focus before an update.
	 */
	_publicMethods.maybeRefocusElement = function( currentFocusedElement, currentValue ) {
		// Bail if no element to focus
		if ( null === currentFocusedElement ) { return; }

		// Bail if focus is set to the document body
		if ( document.body === currentFocusedElement ) { return; }

		requestAnimationFrame( function() {
			var elementToFocus;

			// Try findind the `select2` focusable element
			if ( currentFocusedElement.closest( _settings.select2FormRowSelector ) ) {
				var formRow = currentFocusedElement.closest( _settings.select2FormRowSelector );
				elementToFocus = formRow.querySelector( _settings.select2FocusElementSelector );
			}
			// Try findind the the current focused element after updating updated element by ID
			else if ( currentFocusedElement.id ) {
				elementToFocus = document.getElementById( currentFocusedElement.id );
			}
			// Try findind the updated element by name attribute
			else if ( currentFocusedElement.getAttribute( 'name' ) ) {
				var nameAttr = currentFocusedElement.getAttribute( 'name' );
				elementToFocus = document.querySelector( '[name="'+nameAttr+'"]' );
			}

			// Try setting focus if element is found
			if ( elementToFocus ) {
				elementToFocus.focus();

				// Try to set current value to the focused element
				if ( undefined !== currentValue && null !== currentValue && currentValue !== elementToFocus.value ) {
					elementToFocus.value = currentValue;
				}

				// Set keyboard track position back to that previously to update
				setTimeout( function(){
					// Try to set the same track position
					if( null !== elementToFocus.selectionStart && null !== elementToFocus.selectionEnd ) {
						if ( currentFocusedElement.selectionStart && currentFocusedElement.selectionEnd ) {
							elementToFocus.selectionStart = currentFocusedElement.selectionStart;
							elementToFocus.selectionEnd = currentFocusedElement.selectionEnd;
						}
						// Otherwise try set the track position to the end of the field
						// @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/setSelectionRange
						// @see https://html.spec.whatwg.org/multipage/input.html#concept-input-apply
						else {
							elementToFocus.selectionStart = elementToFocus.selectionEnd = 999999;
						}
					}
				}, 0 );
			}
		} );
	};





	/**
	 * Expose public APIs.
	 */
	return _publicMethods;

});
