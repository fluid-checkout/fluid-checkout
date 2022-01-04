/**
 * File slider.js.
 *
 * Implement interactive mobile and desktop slider
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
	  define([], factory(root));
	} else if ( typeof exports === 'object' ) {
	  module.exports = factory(root);
	} else {
	  root.CollapsibleBlock = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {
		managers: [],
		states: {
			COLLAPSED: 'collapsed',
			FIRST_EXPANDED: 'first-expanded',
			EXPANDED: 'expanded',
		},
	};
	var _settings = { };
	var _defaults = {
		bodyClass: 'has-collapsible-block',

		elementSelector: '[data-collapsible]',
		contentElementSelector: '[data-collapsible-content]',
		contentInnerSelector: '.collapsible-content__inner',
		handlerSelector: '[data-collapsible-handler]',
		handlerMultiTargetSelector: '[data-collapsible-targets]',

		autoFocusSelector: '[data-autofocus]',
		focusableElementsSelector: 'a[href], a[role="button"]:not([disabled]), button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), select:not([disabled]), details, summary, iframe, object, embed, [contenteditable] [tabindex]:not([tabindex="-1"])',
		selectContentsOnFocus: true,

		isCollapsedClass: 'is-collapsed',
		isExpandedClass: 'is-expanded',
		isActivatedClass: 'is-activated',
		cssTransition: 'height .15s linear',

		targetAttribute: 'aria-controls',
		multiTargetAttribute: 'data-collapsible-targets',
		maxHeightAttribute: 'data-collapsible-max-height',
		createHandlerAttribute: 'data-collapsible-create-handler',
		changeStateOnResizeAttribute: 'data-collapsible-change-state-resize',

		initialState: _publicMethods.states.FIRST_EXPANDED,
		initialStateAttribute: 'data-collapsible-initial-state',

		idPrefix: 'collapsible',
		createHandler: false,
		maxHeight: 0,
		handlerTemplate: '<a href="#collapsible" role="button" data-collapsible-handler>Read more</a>',
		contentInnerTemplate: '<div class="collapsible-content__inner"></div>',
	};
	var _key = {
		ENTER: 'Enter',
		SPACE: ' ',
	}



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
	 * Provide a crossbrowser way to determine which
	 * transitionend event is supported by the current browser.
	 *
	 * Based on the work of:
	 * Jonathan Suh - https://jonsuh.com/blog/detect-the-end-of-css-animations-and-transitions-with-javascript/
	 * David Walsh - https://davidwalsh.name/css-animation-callback
	 *
	 * @return  {String}  The transitionend event name
	 */
	var getTransitionEndEvent = function() {
		var t;
		var el = document.createElement('fakeelement');
		var transitions = {
			'transition':'transitionend',
			'OTransition':'oTransitionEnd',
			'MozTransition':'transitionend',
			'WebkitTransition':'webkitTransitionEnd'
		}

		for( t in transitions ){
			if( el.style[t] !== undefined ){
				return transitions[t];
			}
		}

		return 'transitionend';
	};



	/**
	 * Trigger a reflow, flushing the CSS changes.
	 *
	 * @param   HTMLElement  element  Element to get the computed height value.
	 *
	 * @see https://gist.github.com/paulirish/5d52fb081b3570c81e3a
	 */
	var reflow = function( element ) {
		// Set element as the body when not provided
		if ( ! element ) {
			element = document.body;
		}

		element.offsetHeight;
	}



	/**
	 * Check if the element is considered visible. Does not consider the CSS property `visibility: hidden;`.
	 */
	 var isVisible = function( element ) {
		return !!( element.offsetWidth || element.offsetHeight || element.getClientRects().length );
	}



	/**
	 * Gets keyboard-focusable elements within a specified element
	 *
	 * @param   HTMLElement  element  The element to search within. Defaults to the `document` root element.
	 *
	 * @return  NodeList              All focusable elements withing the element passed in.
	 */
	 var getFocusableElements = function( element ) {
		// Set element to `document` root if not passed in
		if ( ! element ) { element = document; }

		// Get elements that are keyboard-focusable
		return element.querySelectorAll( _settings.focusableElementsSelector );
	}



	/**
	 * Handle toggle events for handlers with multiple targets.
	 *
	 * @param   HTMLElement  handlerElement  Handler element.
	 */
	var handleMultipleTargets = function( handlerElement ) {
		// Bail if handler element is not valid
		if ( ! handlerElement ) { return; }

		// Get target ids
		var multiTargetIds = handlerElement.getAttribute( _settings.multiTargetAttribute );
		var targetIds = multiTargetIds.split( ',' );

		// Iterate targetIds
		for ( var i = 0; i < targetIds.length; i++) {
			var targetId = targetIds[i];

			// Get target element
			var targetElement = document.querySelector( '#' + targetId.trim() );
			if ( targetElement ) {
				// Get the collapsbile element
				var element = targetElement.closest( _settings.elementSelector );

				// Maybe toggle collapsbile element state
				if ( element ) {
					_publicMethods.toggleState( element );
				}
			}
		}
	}



	/**
	 * Handle toggle events for handlers with single targets.
	 *
	 * @param   HTMLElement  handlerElement  Handler element.
	 */
	var handleSingleTarget = function( handlerElement ) {
		// Bail if handler element is not valid
		if ( ! handlerElement ) { return; }

		// Get target element
		var targetElement = document.querySelector( '#' + handlerElement.getAttribute( _settings.targetAttribute ) );

		// Get target element from the handler element
		if ( ! targetElement ) {
			targetElement = handlerElement;
		}

		// Get the collapsbile element
		var element = targetElement.closest( _settings.elementSelector );

		// Maybe toggle collapsbile element state
		if ( element ) {
			_publicMethods.toggleState( element );
		}
	}



	/**
	 * Route click events
	 */
	var handleClick = function( e ) {
		if ( e.target.closest( _settings.handlerSelector ) && e.target.closest( _settings.handlerMultiTargetSelector ) ) {
			e.preventDefault();
			var handlerElement = e.target.closest( _settings.handlerMultiTargetSelector );
			handleMultipleTargets( handlerElement );
		}
		else if ( e.target.closest( _settings.handlerSelector ) ) {
			e.preventDefault();
			var handlerElement = e.target.closest( _settings.handlerSelector );
			handleSingleTarget( handlerElement );
		}
	}



	/**
	 * Handle keypress event.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// ENTER or SPACE on handler element
		if ( ( e.key == _key.ENTER || e.key == _key.SPACE ) && e.target.closest( _settings.handlerSelector ) ) {
			// Similate click
			handleClick( e );
		}
	};



	/**
	 * Create handler element
	 */
	var createHandlerElement = function( manager ) {
		var element = manager.element;
		var contentElement = manager.contentElement;
		var handler = document.createElement('div');
		handler.innerHTML = manager.settings.handlerTemplate.trim();
		manager.handlerElement = handler.childNodes[0];
		manager.handlerElement.setAttribute( manager.settings.targetAttribute, contentElement.id );

		element.insertBefore( handler.childNodes[0], contentElement.nextSibling );
	}



	/**
	 * Create content inner element
	 */
	var maybeCreateContentInnerElement = function( manager ) {
		// Bail if content inner element already exists
		if ( manager.contentElement.querySelector( manager.settings.contentInnerSelector ) ) { return; }

		var contentElement = manager.contentElement;
		var newContentPlaceholder = document.createElement('div');
		newContentPlaceholder.innerHTML = manager.settings.contentInnerTemplate.trim();
		var contentInner = newContentPlaceholder.childNodes[0];

		// Move content to new content inner element
		contentInner.innerHTML = contentElement.innerHTML;
		contentElement.innerHTML = newContentPlaceholder.innerHTML;
	}



	/**
	 * Get the element's computed `height` even when hidden or collapsed.
	 *
	 * @param   HTMLElement  element  Element to get the computed height value.
	 *
	 * @return  Number                The computed height value of the element.
	 */
	var getComputedHeight = function( element ) {
		// Get original element style values
		var originalPosition = element.style.position;
		var originalDisplay = element.style.display;
		var originalVisibility = element.style.visibility;
		var originalTransition = element.style.transition;
		var originalHeight = element.style.height;

		// Set element styles prior to getting its height
		element.style.position = 'absolute';
		element.style.display = 'block';
		element.style.visibility = 'hidden';
		element.style.transition = 'none';
		element.style.height = '';

		// Get the element's natural height
		var computedHeight = element.scrollHeight;

		// Set element styles back to original values
		element.style.position = originalPosition;
		element.style.display = originalDisplay;
		element.style.visibility = originalVisibility;
		element.style.transition = originalTransition;
		element.style.height = originalHeight;

		return computedHeight;
	}



	/**
	 * Get the element's current used `height` space, even in the middle of a transition.
	 *
	 * @param   HTMLElement  element  Element to get the current height value.
	 *
	 * @return  Number                The current height value of the element.
	 */
	var getCurrentHeight = function( element ) {
		return element.getBoundingClientRect().height;
	}



	/**
	 * Set the height of the content element.
	 *
	 * @param   HTMLElement  element         Collapsible block content element.
	 * @param   Number       size            New height value for the content element in pixels. The string `px` will be added to the value before setting it to the element's style property.
	 * @param   Boolean      withTransition  Whether to use transitions between states.
	 */
	var setHeight = function( element, size, withTransition ) {
		// Set default value for withTransition
		withTransition = withTransition === false ? false : true;

		// Remove element's transition
		var originalTransition;
		if ( ! withTransition ) {
			originalTransition = element.style.transition;
			element.style.transition = 'none';
		}

		// Set the element's new height
		element.style.height = size + 'px';

		// Restore element's transition
		if ( ! withTransition ) {
			// Trigger a reflow, flushing the CSS changes
			reflow( element );

			// Set element styles back to original values
			element.style.transition = originalTransition;
		}
	}



	/**
	 * Resize element
	 */
	var maybeChangeStateOnResize = function( manager ) {
		// TODO: REFACTOR THIS FUNCTION TO BE MORE EFFICIENT
		// Reset collapsed state
		_publicMethods.expand( manager.element );

		requestAnimationFrame( function() {
			// Maybe collapse
			if ( getComputedHeight( manager.contentElement ) > manager.settings.maxHeight ) {
				_publicMethods.collapse( manager.element );
			}
		} );
	}



	/**
	 * Syncronize `aria-expanded` attribute for every handler of the collapsible-block on the page
	 *
	 * @param   mixed  HTMLElement  The content element of the collapsible block
	 */
	var syncAriaExpanded = function ( element, expanded ) {
		// Bail if `element` or `expanded` are invalid
		if ( ! element && typeof expanded !== 'boolean' ) { return; }

		var handlers = document.querySelectorAll( '[' + _settings.targetAttribute + '=' + element.id + ']' );
		for ( var i = 0; i < handlers.length; i++ ) {
			var handler = handlers[ i ];
			handler.setAttribute( 'aria-expanded', expanded );
		}
	}



	/**
	 * Finish the change to the "expanded" state.
	 *
	 * @param   mixed  element  The content element of the collapsible block as a HTMLElement, or an Event dispatched on that element.
	 */
	var finishExpand = function ( element ) {
		// Bail if element is invalid
		if ( ! element ) { return; }

		// Maybe bail when handling a transition event but not for the right property
		if ( 'propertyName' in element && element.propertyName !== 'height' ) return;

		// Get target element from property, usually passed in an event object
		if ( 'target' in element && element.target ) {
			element = element.target;
		}

		var manager = _publicMethods.getInstance( element.closest( _settings.elementSelector ) );

		// Remove content element properties when transition is complete
		element.style.height = '';
		element.style.overflow = '';

		// Syncronize `aria-expanded` for every handler on the page
		syncAriaExpanded( element, true );

		if ( manager && manager.isActivated === true ) {
			var focusElement = null;

			// Maybe set focus to the child element marked as auto-focus that is visible, skipping those in nested collapsible blocks
			var autofocusChildren = element.querySelectorAll( _settings.autoFocusSelector );
			if ( ! focusElement && autofocusChildren ) {
				for ( var i = 0; i < autofocusChildren.length; i++ ) {
					var autofocusChild = autofocusChildren[i];

					if ( autofocusChild.closest( _settings.contentElementSelector ) === element && isVisible( autofocusChild ) ) {
						focusElement = autofocusChild;
					}
				}
			}

			// Maybe set focusElement to the first focusable element that is visible
			if ( ! focusElement && element.matches( _settings.autoFocusSelector ) ) {
				var focusableElements = Array.from( getFocusableElements( element ) );

				for ( var i = 0; i < focusableElements.length; i++ ) {
					var focusableElement = focusableElements[i];
					if ( isVisible( focusableElement ) ) {
						focusElement = focusableElement;
						break;
					}
				}
			}

			// Set focus to focusElement
			if ( focusElement ) {
				focusElement.focus();
				if ( _settings.selectContentsOnFocus && 'select' in focusElement ) {
					focusElement.select();
				}
			}
		}

		// Remove the event handler so it runs only once
		element.removeEventListener( getTransitionEndEvent(), finishExpand );
	}


	/**
	 * Finish the change to the "collapsed" state.
	 *
	 * @param   mixed  element  The content element of the collapsible block as a HTMLElement, or an Event dispatched on that element.
	 */
	var finishCollapse = function ( element ) {
		// Bail if element is invalid
		if ( ! element ) { return; }

		// Maybe bail when handling a transition event but not for the right property
		if ( 'propertyName' in element && element.propertyName !== 'height' ) return;

		// Get target element from property, usually passed in an event object
		if ( 'target' in element && element.target ) {
			element = element.target;
		}

		// Hide the element from the screen and from the accessibility tree
		element.style.display = 'none';

		// Syncronize `aria-expanded` for every handler on the page
		syncAriaExpanded( element, false );

		// Remove the event handler so it runs only once
		element.removeEventListener( getTransitionEndEvent(), finishCollapse );
	}



	/**
	 * Get slider manager instance from slider element.
	 *
	 * @param   HTMLElement  element  Collapsible block main element.
	 *
	 * @return  Object                Collapsible block `manager` instance.
	 */
	_publicMethods.getInstance = function ( element ) {
		var instance;

		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			if ( manager.element == element ) { instance = manager; break; }
		}

		return instance;
	}



	/**
	 * Collapse element.
	 *
	 * @param   HTMLElement  element         Collapsible block main element.
	 * @param   Boolean      withTransition  Whether to use transitions between states.
	 */
	_publicMethods.collapse = function( element, withTransition ) {
		var manager = _publicMethods.getInstance( element );

		// Bail if manager not found
		// TODO: Maybe try to initialize collapsible and manager on the fly
		if ( ! manager ) { return; }

		// Set default value for withTransition
		withTransition = withTransition === false ? false : true;

		// Update element's state to `collapsed`
		manager.element.classList.add( manager.settings.isCollapsedClass );
		manager.element.classList.remove( manager.settings.isExpandedClass );

		// Remove `finishExpand` event listener to prevent block from expanding at the end of the transition
		manager.contentElement.removeEventListener( getTransitionEndEvent(), finishExpand );

		// Set content element to hide overflowing content
		manager.contentElement.style.overflow = 'hidden';

		// Set height of the element to the current height value
		// Without knowing the value of `height` property the browser can't calculate the steps of the `height` values
		// related to the transition time and therefore won't be able to display the transition.
		setHeight( manager.contentElement, getCurrentHeight( manager.contentElement ), false );

		// Set event listener to finish the "collapse" state change
		if ( withTransition ) {
			manager.contentElement.addEventListener( getTransitionEndEvent(), finishCollapse );
		}

		// Trigger a reflow, flushing the CSS changes
		reflow( element );

		// Set height of the element to the `collapsed` state
		setHeight( manager.contentElement, manager.settings.maxHeight, withTransition );

		// Make sure to finish the "collapse" state change when transitions are not used
		if ( ! withTransition ) {
			finishCollapse( manager.contentElement );
		}
	}



	/**
	 * Expand element.
	 *
	 * @param   HTMLElement  element         Collapsible block main element.
	 * @param   Boolean      withTransition  Whether to use transitions between states.
	 */
	_publicMethods.expand = function( element, withTransition ) {
		// Get element manager
		var manager = _publicMethods.getInstance( element );

		// Bail if manager not found
		// TODO: Maybe try to initialize collapsible and manager on the fly
		if ( ! manager ) { return; }

		// Set default value for withTransition
		withTransition = withTransition === false ? false : true;

		// Show the element again on the screen and add it back to the accessibility tree
		manager.contentElement.style.display = '';

		// Remove `finishCollapse` event listener to prevent block from collapsing at the end of the transition
		manager.contentElement.removeEventListener( getTransitionEndEvent(), finishCollapse );

		// Set height of the element to the current height value
		setHeight( manager.contentElement, getCurrentHeight( manager.contentElement ), false );

		// Set event listener to finish the "expand" state change
		if ( withTransition ) {
			manager.contentElement.addEventListener( getTransitionEndEvent(), finishExpand );
		}

		// Expand element to its content height
		requestAnimationFrame( function() {
			var computedHeight = getComputedHeight( manager.contentElement );

			// Trigger a reflow, flushing the CSS changes
			reflow( element );

			// Set height of the element to the `expanded` state
			setHeight( manager.contentElement, computedHeight, withTransition );

			// Update element's state to `expanded`
			manager.element.classList.add( manager.settings.isExpandedClass );
			manager.element.classList.remove( manager.settings.isCollapsedClass );

			// Make sure to finish the "expand" state change when transitions are not used
			if ( ! withTransition ) {
				finishExpand( manager.contentElement );
			}
		} );
	}



	/**
	 * Toggle between collapsed/expanded states of the element.
	 *
	 * @param   HTMLElement  element         Collapsible block main element.
	 * @param   Boolean      withTransition  Whether to use transitions between states.
	 */
	_publicMethods.toggleState = function( element, withTransition ) {
		var manager = _publicMethods.getInstance( element );

		// Bail if manager not found
		if ( ! manager ) { return; }

		// Toggle state
		if ( element.classList.contains( manager.settings.isCollapsedClass ) ) {
			_publicMethods.expand( element, withTransition );
		}
		else {
			_publicMethods.collapse( element, withTransition );
		}
	}



	/**
	 * /**
	 * Get current state of the collapsible block.
	 *
	 * @param   HTMLElement  element         Collapsible block main element.
	 *
	 * @return  string                       Either `collapsed` or `expanded`. Can be compared to the constants present in `CollapsibleBlock.states`.
	 */
	_publicMethods.getState = function( element ) {
		var manager = _publicMethods.getInstance( element );

		// Bail if manager not found
		if ( ! manager ) { return false; }

		// Get current state
		var currentState = _publicMethods.states.EXPANDED;
		if ( element.classList.contains( manager.settings.isCollapsedClass ) ) {
			currentState = _publicMethods.states.COLLAPSED;
		}

		return currentState;
	}



	/**
	 * Initialize a handler element.
	 */
	_publicMethods.initializeHandler = function( handler ) {
		// Enable the handler element
		handler.removeAttribute( 'disabled' );
		handler.removeAttribute( 'aria-hidden' );

		// Add the element to the natural tab order
		handler.setAttribute( 'tabindex', '0' );

		// Set handler role to `button`
		if ( handler.tagName.toUpperCase() != 'BUTTON' ) {
			handler.setAttribute( 'role', 'button' );
		}

		// Get target attributes
		var targetId = handler.getAttribute( _settings.targetAttribute );
		var multiTargetIds = handler.getAttribute( _settings.multiTargetAttribute );

		// Maybe get target element id from attributes or parent elements
		if ( ( ! targetId || targetId == '' ) && ( ! multiTargetIds || multiTargetIds == '' ) ) {
			var parentCollapsible = handler.closest( _settings.elementSelector );

			// Check if collapsbile blocks is also the content element
			if ( parentCollapsible && parentCollapsible.matches( _settings.contentElementSelector ) ) {
				targetId = parentCollapsible.id;
			}
			// Else, try to get content element from the collapsible block
			else if ( parentCollapsible && parentCollapsible.querySelector( _settings.contentElementSelector ) ) {
				var contentElement = parentCollapsible.querySelector( _settings.contentElementSelector );
				targetId = contentElement.id;
			}

			// Maybe set target attribute
			if ( targetId && targetId != '' ) {
				handler.setAttribute( _settings.targetAttribute, targetId );
			}
		}

		// Remove the `href` attribute
		handler.removeAttribute( 'href' );
	}



	/**
	 * Initialize an element.
	 *
	 * @param   HTMLElement  element  Collapsible block main element.
	 */
	_publicMethods.initializeElement = function( element ) {
		var manager = {};
		_publicMethods.managers.push( manager );
		manager.element = element;
		// TODO: Refactor to remove `manager.settings` as it will always be a copy of the high-level `_settings` variable, with more properties that can be added directly to the `manager` variable.
		manager.settings = extend( _settings );

		// Get content element
		manager.contentElement = manager.element.matches( _settings.contentElementSelector ) ? manager.element : manager.element.querySelector( manager.settings.contentElementSelector );
		if ( ! manager.contentElement ) {
			manager.contentElement = manager.element;
		}

		// Maybe create element ID
		if ( manager.element.id == '' ) {
			manager.element.id = manager.settings.idPrefix + '_' + _publicMethods.managers.length;
		}

		// Maybe create contentElement ID
		if ( manager.contentElement.id == '' ) {
			manager.contentElement.id = manager.element.id + '__content';
		}

		// Get maxHeight from attributes
		var maxHeightAttribute = manager.contentElement.getAttribute( manager.settings.maxHeightAttribute );
		manager.settings.maxHeight = maxHeightAttribute && maxHeightAttribute != '' ? parseInt( maxHeightAttribute ) : manager.settings.maxHeight;

		// Get createHandler from attributes
		var createHandler = manager.element.getAttribute( manager.settings.createHandlerAttribute );
		manager.settings.createHandler = createHandler == 'true' || createHandler == 'false' ? Boolean( createHandler ) : manager.settings.createHandler;
		if ( manager.settings.createHandler ) {
			createHandlerElement( manager );
		}

		// Maybe create content inner element
		maybeCreateContentInnerElement( manager );

		// Set initial state at element initialization
		var initialStateAttribute = manager.contentElement.getAttribute( manager.settings.initialStateAttribute );
		var initialState = initialStateAttribute ? initialStateAttribute : manager.settings.initialState;
		var index = Array.prototype.indexOf.call( manager.element.parentNode.children, manager.element );
		if ( initialState == _publicMethods.states.EXPANDED || ( initialState == _publicMethods.states.FIRST_EXPANDED && index == 0 ) ) {
			_publicMethods.expand( manager.element, false );
		}
		else {
			_publicMethods.collapse( manager.element, false );
		}

		// Maybe change state on resize
		var changeStateOnResizeAttribute = manager.element.getAttribute( manager.settings.changeStateOnResizeAttribute );
		manager.settings.changeStateOnResize = changeStateOnResizeAttribute && changeStateOnResizeAttribute != '' ? Boolean( changeStateOnResizeAttribute ) : false;
		if ( manager.settings.changeStateOnResize ) {
			maybeChangeStateOnResize( manager );

			// TODO: Maybe move event handler to a single listener
			window.addEventListener( 'resize', function() { maybeChangeStateOnResize( manager ); } );
		}

		// Set css transition property
		var computedTransition = window.getComputedStyle( manager.contentElement ).transition;
		var cssTransition = computedTransition != '' ? computedTransition + ', ' + manager.settings.cssTransition : manager.settings.cssTransition;
		manager.contentElement.style.transition = cssTransition;

		// Set element as activated
		requestAnimationFrame( function(){
			manager.isActivated = true;
			manager.element.classList.add( manager.settings.isActivatedClass );
		} );
	}



	/**
	 * Initialize.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge with general settings with options
		_settings = extend( _defaults, options );

		// Initialize collapsible elements
		var elements = document.querySelectorAll( _settings.elementSelector );
		for ( var i = 0; i < elements.length; i++ ) {
			_publicMethods.initializeElement( elements[ i ] );
		}

		// Initialize handler elements
		var handlers = document.querySelectorAll( _settings.handlerSelector );
		for ( var i = 0; i < handlers.length; i++ ) {
			_publicMethods.initializeHandler( handlers[ i ] );
		}

		// Trigger a reflow, flushing the CSS changes
		reflow();

		// Syncronize `aria-expanded` for every handler on the page
		for ( var i = 0; i < elements.length; i++ ) {
			var element = elements[ i ];
			var contentElement = element.matches( _settings.contentElementSelector ) ? element : element.querySelector( _settings.contentElementSelector );
			syncAriaExpanded( contentElement, _publicMethods.getState( element ) == _publicMethods.states.EXPANDED );
		}

		// Add event listeners
		document.addEventListener( 'click', handleClick );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// Set body class
		document.body.classList.add( _settings.bodyClass );

		// Set as initialized
		requestAnimationFrame( function() {
			_hasInitialized = true;
		} );
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
