/**
 * File sticky-states.js.
 *
 * Implement sticky elements based on scroll position.
 */

 (function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
	  define([], factory(root));
	} else if ( typeof exports === 'object' ) {
	  module.exports = factory(root);
	} else {
	  root.StickyStates = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {
		managers: [],
	};
	var _settings = {};
	var _defaults = {
		elementSelector: '[data-sticky-states]',
		innerElementSelector: '[data-sticky-states-inner]',

		isEndPositionClass: 'is-end-position',
		isStickyClass: 'is-sticky',
		isStickyTopClass: 'is-sticky--top',
		isStickyBottomClass: 'is-sticky--bottom',
		isActivatedClass: 'is-activated',

		positionAttribute: 'data-sticky-position',
		thresholdAttribute: 'data-sticky-threshold',
		stickyRelativeToAttribute: 'data-sticky-relative-to',
		staticAtEndAttribute: 'data-sticky-static-at-end',
		containerAttribute: 'data-sticky-container',

		position: 'top', // Accepted values: `top`, `bottom`
		threshold: 0,
	};
	var _resizeObserver;



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
	 * Execute a function immediately and prevent execution of the same method again for the amount of time defined as the threshold.
	 *
	 * @param   function  fn          Function to be executed.
	 * @param   int       threshhold  Threshold time in milliseconds. Default 250ms.
	 * @param   object    scope       Scope of execution of the function.
	 *
	 * @return  function              Function to be executed, incapsulated in a timed function.
	 */
	var _throttle = function( fn, threshhold, scope ) {
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
			}
			else {
				last = now;
				fn.apply( context, args );
			}
		};
	}



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
	var _debounce = function ( func, wait, immediate ) {
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
	 * Get the offset position of the element recursively adding the offset position of parent offset elements until the `stopElement` (or the `body` element).
	 *
	 * @param   HTMLElement  element      Element to get the offset position for.
	 * @param   HTMLElement  stopElement  Parent offset element where to stop adding the offset position to the total offset top position of the element.
	 *
	 * @return  int                       Offset position of the element until the `stopElement` or the `body` element.
	 */
	var getOffsetTop = function( element, stopElement ) {
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
	 * Check if the element is considered visible. Does not consider the CSS property `visibility: hidden;`.
	 */
	var isVisible = function( element ) {
		return !!( element.offsetWidth || element.offsetHeight || element.getClientRects().length );
	}



	/**
	 * Check if the element is considered sticky.
	 */
	 _publicMethods.isStickyPosition = function( element ) {
		// Try checking for the sticky class
		if ( element.matches( '.' + _settings.isStickyClass ) ) { return true; }

		// Try checking for computed styles (slower)
		// Used for elements set to sticky position by other means other than using sticky-states
		var elementComputedPosition = window.getComputedStyle( element ).position;
		return elementComputedPosition == 'fixed' || elementComputedPosition == 'sticky';
	}



	/**
	 * Maybe change state of sticky elements.
	 */
	var maybeChangeState = function() {
		var currentScrollPosition = window.pageYOffset || document.body.scrollTop;

		// Iterate sticky elements
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			var isSticky = currentScrollPosition >= manager.settings.threshold;
			var relativeHeight = 0;
			var isEndThreshold = currentScrollPosition >= manager.settings.endThreshold;
			var isStaticAtEnd = manager.stickyElement.hasAttribute( manager.settings.staticAtEndAttribute );

			// Maybe set isSticky value based on the relative element
			if ( manager.relativeElement && isVisible( manager.relativeElement ) && _publicMethods.isStickyPosition( manager.relativeElement ) ) {
 				relativeHeight = manager.relativeElement.getBoundingClientRect().height;
				isSticky = currentScrollPosition >= ( manager.settings.threshold - relativeHeight );
				isEndThreshold = currentScrollPosition >= ( manager.settings.endThreshold - relativeHeight );
			}

			// Sticky
			if ( isSticky && ! isEndThreshold ) {
				var stickyWidth = window.getComputedStyle( manager.innerElement ).width;
				var containerHeight = window.getComputedStyle( manager.stickyElement ).height;
				manager.innerElement.style.top = relativeHeight > 0 ? relativeHeight + 'px' : '';
				manager.innerElement.style.width = stickyWidth; // variable already has unit `px`
				manager.stickyElement.style.height = containerHeight; // variable already has unit `px`
				manager.stickyElement.style.position = '';
				manager.stickyElement.classList.add( manager.settings.isStickyClass, ( manager.settings.position == 'top' ? manager.settings.isStickyTopClass : manager.settings.isStickyBottomClass ) );
				manager.stickyElement.classList.remove( manager.settings.isEndPositionClass );
			}
			// Absolute ( at end position )
			else if ( isEndThreshold && ! isStaticAtEnd ) {
				var containerHeight = parseInt( window.getComputedStyle( manager.containerElement ).height.replace( 'px' ) );
				var elementHeight = parseInt( window.getComputedStyle( manager.stickyElement ).height.replace( 'px' ) );
				var elementOffsetToContainer = getOffsetTop( manager.stickyElement ) - getOffsetTop( manager.containerElement );
				manager.innerElement.style.top = ( containerHeight - elementHeight - elementOffsetToContainer ) + 'px';
				manager.stickyElement.classList.remove( manager.settings.isStickyClass, manager.settings.isStickyTopClass, manager.settings.isStickyBottomClass );
				manager.stickyElement.classList.add( manager.settings.isEndPositionClass );
			}
			// Static
			else {
				manager.stickyElement.classList.remove( manager.settings.isStickyClass, manager.settings.isStickyTopClass, manager.settings.isStickyBottomClass, manager.settings.isEndPositionClass );
				manager.stickyElement.style.height = '';
				manager.innerElement.style.width = '';
				manager.innerElement.style.top = '';
			}
		}
	};



	/**
	 * Loop function to changes visibility of the variation switcher.
	 */
	var throttledChangeState = _throttle( maybeChangeState, 50 );
	var loop = function() {
		throttledChangeState();
		// Loop this function indefinitely
		window.requestAnimationFrame( loop );
	};



	/**
	 * Trigger recalculate threshold values when resizing.
	 */
	var resetStickyLimitsOnResize = function() {
		// Iterate managers
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			if ( manager ) {
				_publicMethods.resetStickyLimits( manager );
			}
		}
	};



	/**
	 * Recalculate threshold values.
	 */
	_publicMethods.resetStickyLimits = function( manager ) {
		var windowHeight = Math.max( document.documentElement.clientHeight, window.innerHeight || 0 );
		var thresholdAttrValue = manager.stickyElement.getAttribute( manager.settings.thresholdAttribute );
		var elementRect = manager.stickyElement.getBoundingClientRect();
		var elementOffset = getOffsetTop( manager.stickyElement );

		// Threshold
		manager.settings.threshold = isNaN( parseInt( thresholdAttrValue ) ) ? elementOffset : parseInt( thresholdAttrValue );

		// Calculate threshold for elements sticky to the bottom
		if ( manager.settings.position == 'bottom' ) {
			manager.settings.threshold = Math.max( manager.settings.threshold - windowHeight + elementRect.height, 0 );
		}

		// Maybe get relativeElement set via attribute
		var relativeElementSelector = manager.stickyElement.getAttribute( manager.settings.stickyRelativeToAttribute );
		if ( relativeElementSelector != null && relativeElementSelector != '' ) {
			// Try to find the relative sticky element in the page
			manager.relativeElement = document.querySelector( relativeElementSelector );
		}

		// Use the parent element as the container element
		manager.containerElement = manager.stickyElement.parentNode;

		// Maybe get containerElement set via attribute
		var containerSelector = manager.stickyElement.getAttribute( manager.settings.containerAttribute );
		if ( containerSelector != null && containerSelector != '' ) {

			// Try to find the containerElement in the element's hierarchy first
			manager.containerElement = manager.stickyElement.closest( containerSelector );

			// Try to find the containerElement on the entire document and set to the first found element that matches the selector
			if ( ! manager.containerElement ) {
				manager.containerElement = document.querySelector( containerSelector );
			}
		}

		// Maybe set endThreshold
		if ( manager.containerElement ) {
			var containerHeight = parseInt( window.getComputedStyle( manager.containerElement ).height.replace( 'px' ) );
			var elementHeight = parseInt( window.getComputedStyle( manager.stickyElement ).height.replace( 'px' ) );
			var elementOffsetToContainer = getOffsetTop( manager.stickyElement ) - getOffsetTop( manager.containerElement );

			// Set endThreshold to bottom of containerElement
			manager.settings.endThreshold = manager.settings.threshold + containerHeight - elementHeight - elementOffsetToContainer;

			// Maybe calculate endThreshold for elements sticky to the bottom
			if ( manager.settings.position == 'bottom' ) {
				var endThreshold = getOffsetTop( manager.stickyElement ) - windowHeight + elementRect.height;

				// Maybe set endThreshold to stop sticky state at the element's normal position
				if ( endThreshold > manager.settings.threshold ) {
					manager.settings.endThreshold = endThreshold;
				}
			}
		}
	}



	/**
	 * Get manager instance from an element.
	 *
	 * @param    HTMLElement   An element that is a `stickyElement`, `stickyInner` or `containerElement`.
	 */
	_publicMethods.getInstance = function ( element ) {
		var instance;
		// Try getting instance from the element
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			if ( manager.stickyElement == element ) { instance = manager; break; }
		}

		// Try getting instace from other types of elements
		if ( ! instance )  { instance = _publicMethods.getInstanceFromInner( element ); }

		return instance;
	}

	/**
	 * Get manager instance for innerElement.
	 */
	_publicMethods.getInstanceFromInner = function ( innerElement ) {
		var instance;
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			if ( manager.innerElement == innerElement ) { instance = manager; break; }
		}
		return instance;
	}



	/**
	 * Initialize an sticky element.
	 */
	_publicMethods.initializeElement = function( stickyElement ) {
		var manager = {};
		manager.settings = extend( _settings );

		// Get elements
		manager.stickyElement = stickyElement;
		manager.innerElement = manager.stickyElement.querySelector( manager.settings.innerElementSelector );

		var positionAttrValue = manager.stickyElement.getAttribute( manager.settings.positionAttribute );
		manager.settings.position = positionAttrValue == 'top' || positionAttrValue == 'bottom' ? positionAttrValue : _settings.position;

		// Calculate threshold values, recalculate when resize window
		_publicMethods.resetStickyLimits( manager );

		// Start observing resize of relevant elements
		if ( _resizeObserver ) {
			_resizeObserver.observe( manager.containerElement );
			_resizeObserver.observe( manager.stickyElement );
			_resizeObserver.observe( manager.innerElement );
		}

		// Set element as activated
		manager.isActivated = true;
		manager.stickyElement.classList.add( manager.settings.isActivatedClass );

		// Add manager to public methods
		_publicMethods.managers.push( manager );
	}



	/**
	 * Initialize.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge with general settings with options
		_settings = extend( _defaults, options );

		// Initialize resize observer
		if ( window.ResizeObserver ) {
			_resizeObserver = new ResizeObserver( _debounce( resetStickyLimitsOnResize, 50 ) );
		}

		// Initialize each sticky element
		var stickyElements = document.querySelectorAll( _settings.elementSelector );
		for ( var i = 0; i < stickyElements.length; i++ ) {
			_publicMethods.initializeElement( stickyElements[ i ] );
		}

		// Start handling sticky states
		requestAnimationFrame( loop );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
