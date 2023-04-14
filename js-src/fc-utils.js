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



	/**
	 * PROPERTIES
	 */



	/**
	 * Mapping of keyboard keys based on and comparable with `event.key` values.
	 */
	_publicMethods.keyboardKeys = {
		ENTER: 'Enter',
		SPACE: ' ',
		TAB: 'Tab',
		SHIFT: 'Shift',
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
	 * Expose public APIs.
	 */
	return _publicMethods;

});
