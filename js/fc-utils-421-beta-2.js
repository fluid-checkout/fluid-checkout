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
		focusableElementsSelector:             'a[role="button"], a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), select:not([disabled]), details, summary, iframe, object, embed, [contenteditable] [tabindex]:not([tabindex="-1"])',

		scrollOffsetSelector:                  '.fc-checkout-header',
		scrollBehavior:                        'smooth',
		scrollOffset:                          0,
		scrollDelay:                           50,

		breakpoints: {
			mobile: { minWidth: 0, maxWidth: 549 },
			phablet: { minWidth: 550, maxWidth: 749 },
			tablet: { minWidth: 750, maxWidth: 999 },
			desktop: { minWidth: 1000, maxWidth: 1279 },
			desktopMedium: { minWidth: 1280, maxWidth: 1499 },
			desktopLarge: { minWidth: 1500, maxWidth: 1999 },
			desktopExtraLarge: { minWidth: 2000, maxWidth: 1000000 },
		},

		select2FormRowSelector:                '.form-row.fc-select2-field',
		select2FocusElementSelector:           '.select2-selection, input[type="text"]',
		select2OptionsSelector:                '.select2-results__options',
		select2SelectionSelector:              '.select2-selection__rendered',
		
		tomSelectFormRowSelector:              '.form-row.fc-select2-field',
		tomSelectKeepingClosedClass:           'keeping-closed',
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



	/**
	 * Set the variables that track the current focused element and its value.
	 * 
	 * @param  {Boolean}  setToRelativeSelect2  Whether to set to relative `select2` field element.
	 */
	var getCurrentFocusedElementGlobalVariables = function( setToRelativeSelect2 ) {
		// Set defaults
		if ( true !== setToRelativeSelect2 ) {
			setToRelativeSelect2 = false;
		}

		// Set current focused element and value
		var currentfocusedElement = document.activeElement;

		// Maybe set to relative `select2` field element,
		// if the focus is current on a `select2` field option.
		var select2Options = currentfocusedElement.closest( _settings.select2OptionsSelector );
		if ( setToRelativeSelect2 && select2Options ) {
			var select2ElementId = select2Options.getAttribute( 'id' ).replace( '-results', '-container' );
			currentfocusedElement = document.getElementById( select2ElementId );
		}

		// Maybe set to form row for `select2` fields
		var currentFocusedFormRow = currentfocusedElement.closest( _settings.select2FormRowSelector );
		if ( currentFocusedFormRow && currentFocusedFormRow.querySelector( _settings.select2SelectionSelector ) ) {
			// Remove focus from current element as it will be replaced
			// This fixes an issue where `select2` fields would not work properly
			// after checkout is updated while focus is on a `select2` field
			if ( currentfocusedElement ) { currentfocusedElement.blur(); }

			currentfocusedElement = currentFocusedFormRow;
		}

		return currentfocusedElement;
	}



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
	 * @param   {[type]}  func        Function to be executed.
	 * @param   {[type]}  wait        Wait time in milliseconds.
	 * @param   {[type]}  immediate   Trigger the function on the leading edge.
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
	 * Return a function, that, when invoked, will only be triggered at most once
	 * during a given window of time. Normally, the throttled function will run
	 * as much as it can, without ever going more than once per `wait` duration;
	 * 
	 * @param   function  fn          Function to be executed.
	 * @param   int       threshhold  Wait time in milliseconds.
	 * @param   object    scope       Scope of the function to be executed.
	 * 
	 * @return  function              Function to be executed, incapsulated in a timed function.
	 */
	_publicMethods.throttle = function( fn, threshhold, scope ) {
		threshhold || (threshhold = 250);
		var last,
		deferTimer;
		return function () {
			var context = scope || this;
		
			var now = +new Date,
				args = arguments;
			if ( last && now < last + threshhold ) {
				// hold on to it
				clearTimeout( deferTimer );
				deferTimer = setTimeout( function () {
					last = now;
					fn.apply( context, args );
				}, threshhold );
			} else {
				last = now;
				fn.apply( context, args );
			}
		};
	}



	/**
	 * Check if the element is considered visible. Does not consider the CSS property `visibility: hidden;`.
	 */
	_publicMethods.isElementVisible = function( element ) {
		return !!( element.offsetWidth || element.offsetHeight || element.getClientRects().length );
	}



	/**
	 * Get the current breakpoints matched based on the window width.
	 */
	_publicMethods.getCurrentBreakpoints = function() {
		// Define variables
		var windowWidth = window.innerWidth;
		var breakpointSelectors = Object.entries( _settings.breakpoints );
		var currentBreakpoints = [];

		// Iterate through all breakpoints
		for ( var i = 0; i < breakpointSelectors.length; i++ ) {
			// Get variables
			var breakpoint = breakpointSelectors[ i ][ 0 ];
			var values = breakpointSelectors[ i ][ 1 ];

			// Check whether window width is within the breakpoint max width bounds,
			// that is, exclude breakpoints which are min width is larger than the
			// actual window width.
			if ( values.minWidth <= windowWidth ) {
				// Add breakpoint to the current breakpoints
				currentBreakpoints.push( breakpoint );
			}
		}

		// Return list with the current breakpoint and smaller breakpoints
		return currentBreakpoints;
	}

	/**
	 * Check if the current breakpoint matches the specified breakpoint or is smaller than the target breakpoint.
	 * 
	 * @param   string   targetBreakpoint  The breakpoint to check against. Accepted values are: `mobile`, `phablet`, `tablet`, `desktop`, `desktopMedium`, `desktopLarge` and `desktopExtraLarge`.
	 */
	_publicMethods.isCurrentBreakpointOrSmaller = function( targetBreakpoint ) {
		// Get variables
		var currentBreakpoints = _publicMethods.getCurrentBreakpoints();
		var targetBreakpointIndex = currentBreakpoints.indexOf( targetBreakpoint );
		var higherstBreakpointIndex = currentBreakpoints.length - 1;

		// Return `true` if highest breakpoint matches the target breakpoint
		if ( targetBreakpointIndex == higherstBreakpointIndex ) {
			return true;
		}

		// Iterate through current breakpoints
		for ( var i = 0; i < currentBreakpoints.length; i++ ) {
			// Check whether higherst breakpoint is higher than target
			// If so, return `false`.
			if ( -1 !== targetBreakpointIndex && i > targetBreakpointIndex ) {
				return false;
			}
		}

		// Otherwise it is smaller, then return `true`.
		return true;
	}



	/**
	 * Gets keyboard-focusable elements within a specified element
	 *
	 * @param   HTMLElement  element  The element to search within. Defaults to the `document` root element.
	 *
	 * @return  NodeList              All focusable elements withing the element passed in.
	 */
	_publicMethods.getFocusableElements = function( element ) {
		// Set element to `document` root if not passed in
		if ( ! element ) { element = document; }

		// Get elements that are keyboard-focusable, but might be `disabled`
		return element.querySelectorAll( _settings.focusableElementsSelector );
	}

	/**
	 * Set the variables that track the current focused element and its value.
	 */
	_publicMethods.setCurrentFocusedElementGlobalVariables = function() {
		// Set current focused element and value
		window.fcCurrentFocusedElement = getCurrentFocusedElementGlobalVariables();
		window.fcCurrentFocusedElementValue = window.fcCurrentFocusedElement.value;
		window.fcCurrentFocusedElementReopenDropdown = false;

		// Maybe set to reopen dropdown after refocusing
		// Maybe close TomSelect dropdown when refocusing
		if ( fcCurrentFocusedElement.id.includes( '-ts-control' ) ) {
			// Get related select field
			var formRow = fcCurrentFocusedElement.closest( _settings.tomSelectFormRowSelector );
			var selectField = formRow && formRow.querySelector( 'select' );

			// Maybe set to reopen dropdown after refocusing
			if ( selectField && selectField.tomselect && selectField.tomselect.isOpen ) {
				window.fcCurrentFocusedElementReopenDropdown = true;
			}
		}
	}

	/**
	 * Set the variables that track the current focused element and its value.
	 */
	_publicMethods.maybeSetCurrentFocusedElementGlobalVariablesRelativeSelect2 = function() {
		// Set current focused element and value,
		// and retrieve relative `select2` field element if focus is on a `select2` field option.
		var currentFocusedSelect2Element = getCurrentFocusedElementGlobalVariables( true );

		// Maybe set current focused element to relative `select2` field element
		if ( currentFocusedSelect2Element ) {
			window.fcCurrentFocusedElement = currentFocusedSelect2Element;
			window.fcCurrentFocusedElementValue = window.fcCurrentFocusedElement.value;
		}
	}

	/**
	 * Unset the variables that track the current focused element and its value.
	 */
	_publicMethods.unsetCurrentFocusedElementGlobalVariables = function() {
		window.fcCurrentFocusedElement = null;
		window.fcCurrentFocusedElementValue = null;
	}

	/**
	 * Maybe set focus back to the element that was focused before an update.
	 * 
	 * @param  {HTMLElement}  currentFocusedElement  The element that was currently focused before an update.
	 * @param  {mixed}        currentValue           The value of the element in focus before an update.
	 */
	_publicMethods.maybeRefocusElement = function( currentFocusedElement, currentValue ) {
		// Bail if no element to focus
		if ( null == currentFocusedElement ) { return; }

		// Bail if focus is set to the document body
		if ( document.body === currentFocusedElement ) { return; }

		requestAnimationFrame( function() {
			var elementToFocus;

			// Try findind the `select2` focusable element
			if ( currentFocusedElement.closest( _settings.select2FormRowSelector ) ) {
				var formRow = currentFocusedElement.closest( _settings.select2FormRowSelector );
				elementToFocus = formRow.querySelector( _settings.select2FocusElementSelector );
			}
			// Try findind the updated element by id
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
				// Get related select field
				var formRow = elementToFocus.closest( _settings.tomSelectFormRowSelector );
				var selectField = formRow && formRow.querySelector( 'select' );

				// Maybe set class for keeping dropdown closed
				if ( ! window.fcCurrentFocusedElementReopenDropdown && formRow ) {
					formRow.classList.add( _settings.tomSelectKeepingClosedClass );
				}

				// Set focus to element
				elementToFocus.focus();

				// Try to set current value to the focused element
				if ( undefined !== currentValue && null !== currentValue && currentValue !== elementToFocus.value ) {
					elementToFocus.value = currentValue;
				}

				// Set keyboard track position back to that previously to update
				setTimeout( function(){
					// Try to set the same track position
					if ( elementToFocus && 'selectionStart' in elementToFocus && 'selectionEnd' in elementToFocus && undefined !== elementToFocus.selectionStart && undefined !== elementToFocus.selectionEnd && null !== elementToFocus.selectionStart && null !== elementToFocus.selectionEnd ) {
						if ( currentFocusedElement && 'selectionStart' in currentFocusedElement && 'selectionEnd' in currentFocusedElement && undefined !== currentFocusedElement.selectionStart && undefined !== currentFocusedElement.selectionEnd && null !== currentFocusedElement.selectionStart && null !== currentFocusedElement.selectionEnd ) {
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

					// Maybe close TomSelect dropdown when refocusing
					if ( ! window.fcCurrentFocusedElementReopenDropdown && elementToFocus.id.includes( '-ts-control' ) ) {
						// Maybe close TomSelect dropdown
						if ( selectField && selectField.tomselect ) {
							selectField.tomselect.close();
						}
					}

					// Remove TomSelect class for keeping dropdown closed
					if ( formRow ) {
						formRow.classList.remove( _settings.tomSelectKeepingClosedClass );
					}
				}, 0 );
			}
		} );
	};



	/**
	 * Get the offset position of the element recursively adding the offset position of parent elements until the `stopElement` (or the `body` element).
	 *
	 * @param   HTMLElement  element      Element to get the offset position for.
	 * @param   HTMLElement  stopElement  Parent element where to stop adding the offset position to the total offset top position of the element.
	 *
	 * @return  int                       Offset position of the element until the `stopElement` or the `body` element.
	 */
	_publicMethods.getOffsetTop = function( element, stopElement ) {
		var offsetTop = 0;

		while( element ) {
			// Reached the stopElement
			if ( stopElement && stopElement == element ) {
				break;
			}

			offsetTop += element.offsetTop;
			element = element.offsetParent;
		}
		
		return offsetTop;
	}

	/**
	 * Get the scroll offset position for the sticky elements.
	 * 
	 * @param   string       includeOffsetSelector  (optional) Selector for the elements to include in the offset position. Pass `0` or `false` to skip this parameter.
	 * @param   int          addedOffsetAmount      (optional) Added offset amount to the scroll position.
	 */
	_publicMethods.getStickyElementsOffset = function( includeOffsetSelector, addedOffsetAmount ) {
		var stickyElementsOffset = 0;

		// Maybe add height of the progress bar to scroll position
		try {
			var offsetItemsList = null !== includeOffsetSelector && undefined !== includeOffsetSelector ? document.querySelectorAll( includeOffsetSelector ) : [];
			if ( offsetItemsList.length > 0 ) {
				var offsetItem = offsetItemsList[ 0 ];
				var height = offsetItem.getBoundingClientRect().height;
				stickyElementsOffset += height;
			}
		}
		catch ( error ) {
			// Do nothing
		}

		// Maybe add sticky elements height to scroll position
		if ( window.StickyStates ) {
			var maybeStickyElements = document.querySelectorAll( _settings.scrollOffsetSelector );
			if ( maybeStickyElements && maybeStickyElements.length > 0 ) {
				for ( var i = 0; i < maybeStickyElements.length; i++ ) {
					var stickyElement = maybeStickyElements[i];
					if ( StickyStates.isStickyPosition( stickyElement ) ) {
						var height = stickyElement.getBoundingClientRect().height;
						stickyElementsOffset += height;
					}
				}
			}
		}

		// Maybe add arbitrary offset amount
		if ( ! isNaN( addedOffsetAmount ) ) {
			stickyElementsOffset += addedOffsetAmount;
		}

		return stickyElementsOffset;
	}

	/**
	 * Change scroll position to top of the element after the sticky elements.
	 *
	 * @param   HTMLElement  element                The element of to scroll to.
	 * @param   string       includeOffsetSelector  (optional) Selector for the elements to include in the offset position. Pass `0` or `false` to skip this parameter.
	 * @param   int          addedOffsetAmount      (optional) Added offset amount to the scroll position.
	 */
	_publicMethods.scrollToElement = function( element, includeOffsetSelector, addedOffsetAmount ) {
		// Bail if step element not provided
		if ( ! element ) { return; }

		// Get the offset position of the element
		var stickyElementsOffset = _publicMethods.getStickyElementsOffset( includeOffsetSelector, addedOffsetAmount );
		var elementOffset = _publicMethods.getOffsetTop( element ) + ( _settings.scrollOffset * -1 ) + ( stickyElementsOffset * -1 );

		// Scroll to the element, considering its offset position.
		window.setTimeout( function() {
			window.scrollTo( {
				top: elementOffset,
				behavior: _settings.scrollBehavior,
			} );
		}, _settings.scrollDelay );
	}



	/**
	 * Expose public APIs.
	 */
	return _publicMethods;

});
