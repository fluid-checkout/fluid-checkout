/**
 * Manage states and views for the flyout blocks.
 * 
 * File flyout-block.js.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FlyoutBlock = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {
		managers: [],
	};
	var _settings = { };
	var _defaults = {
		flyoutWrapperSelector: '[data-flyout]',
		flyoutContentSelector: '[data-flyout-content]',
		toggleButtonSelector: '[data-flyout-toggle]',
		openButtonSelector: '[data-flyout-open]',
		closeButtonSelector: '[data-flyout-close]',
		overlaySelector: '[data-flyout-overlay]',
		flyoutTogglePassActionSelector: '[data-flyout-pass-action], .js-flyout-pass-action',
		autoFocusSelector: '[data-autofocus]',

		flyoutToggleClassSelector: '.js-flyout-toggle',
		flyoutClassTogglePrefix: 'js-flyout-target-',
		
		idPrefix: 'flyout-block',
		headingsSelector: 'h1, h2, h3, h4, h5, h6, [role="heading"]',
		
		bodyHasFlyoutClass: 'has-flyout',
		bodyHasFlyoutOpenClass: 'has-flyout--open',
		isActivatedClass: 'is-activated',
		isOpenClass: 'is-open',
		openAnimationClass: 'fade-in-up',
		closeAnimationClass: 'fade-out-down',
		
		targetElementAttribute: 'data-flyout-target',
		openAnimationClassAttribute: 'data-flyout-open-animation-class',
		closeAnimationClassAttribute: 'data-flyout-close-animation-class',
		manualFocusAttribute: 'data-flyout-manual-focus',
		descriptionAttribute: 'data-flyout-description',
		autoFocusAttribute: 'data-autofocus',
		focusableElementsSelector: 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), select:not([disabled]), details, summary, iframe, object, embed, [contenteditable] [tabindex]:not([tabindex="-1"])',
		
		flyoutRoleAttribute: 'data-flyout-role',

		overlayTemplate: '<div class="flyout-overlay" data-flyout-overlay></div>',
	};
	var _states = {
		OPEN: 'open',
		CLOSED: 'closed',
	}
	var _key = {
		TAB: 'Tab',
		ENTER: 'Enter',
		ESC: 'Escape',
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
	 * Gets keyboard-focusable elements within a specified element
	 *
	 * @param   HTMLElement  element  The element to search within. Defaults to the `document` root element.
	 *
	 * @return  NodeList              All focusable elements withing the element passed in.
	 */
	var getFocusableElements = function( element ) {
		// Set element to `document` root if not passed in
		if ( ! element ) { element = document; }
		
		// Get elements that are keyboard-focusable, but might be `disabled`
		return element.querySelectorAll( _settings.focusableElementsSelector );
	}



	/**
	 * Get target element
	 */
	var getTargetElement = function( element ) {
		// Bail if element not valid
		if ( ! element ) return false;

		// Try get target element from attributes
		var targetSelector = element.getAttribute( _settings.targetElementAttribute );
		if ( targetSelector && targetSelector != '' ) {
			return document.querySelector( targetSelector );
		}

		// Return target element from parent nodes
		return element.closest( _settings.flyoutWrapperSelector );
	}



	/**
	 * Get target element
	 */
	var getTargetElementFromClass = function( element ) {
		if ( ! element ) { return false; }

		// Iterate classes
		for ( var i = 0; i < element.classList.length; i++ ) {
			var cssClass = element.classList[i];
			if ( cssClass.indexOf( _settings.flyoutClassTogglePrefix ) == 0 ) {
				// Get target id from class name
				var targetSelector = '#' + cssClass.replace( _settings.flyoutClassTogglePrefix, '' );
				return document.querySelector( targetSelector );
			}
		}
		
		return false;
	}


	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function ( e ) {
		// TOGGLE
		if ( e.target.closest( _settings.toggleButtonSelector ) ) {
			e.preventDefault();
			var button = e.target.closest( _settings.toggleButtonSelector );
			var element = getTargetElement( button );
			_publicMethods.toggle( element );
		}
		// TOGGLE (CSS CLASS)
		else if ( e.target.closest( _settings.flyoutToggleClassSelector ) ) {
			// Maybe prevent element's action
			if ( ! e.target.closest( _settings.flyoutTogglePassActionSelector ) ) {
				e.preventDefault();
			}

			var toggleElement = e.target.closest( _settings.flyoutToggleClassSelector );
			var element = getTargetElementFromClass( toggleElement );
			
			if ( element ) {
				_publicMethods.toggle( element );
			}
		}
		// OPEN
		else if ( e.target.closest( _settings.openButtonSelector ) ) {
			e.preventDefault();
			var button = e.target.closest( _settings.openButtonSelector );
			var element = getTargetElement( button );
			_publicMethods.open( element );
		}
		// CLOSE
		else if ( e.target.closest( _settings.closeButtonSelector ) ) {
			e.preventDefault();
			
			var button = e.target.closest( _settings.closeButtonSelector );
			var element = getTargetElement( button );
			_publicMethods.close( element );
		}
		// OVERLAY - Specific to a flyout block
		else if ( e.target.matches( _settings.flyoutWrapperSelector ) ) {
			var element = getTargetElement( e.target );
			_publicMethods.close( element );
		}
		// OVERLAY - General
		else if ( e.target.closest( _settings.overlaySelector ) ) {
			_publicMethods.closeAll();
		}
	}



	/**
	 * Handle keypress event.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// Close all flyout blocks if `ESC` was pressed
		if ( e.key == _key.ESC ) {
			_publicMethods.closeAll();
		}

		// ENTER on flyout trigger
		if ( ( e.key == _key.ENTER || e.key == _key.SPACE ) && e.target.closest( _settings.triggerSelectors ) ) {
			// Similate click
			handleClick( e );
		}
	};



	/**
	 * Set the `inert` property of the sibling elements.
	 *
	 * @param   HTMLElement  element  The element which to maintain the focus in, all siblings will be marked with the value of the param `inert` except this one.
	 * @param   bool         inert    Boolean value to set to the `inert` property, `true` will make siblings inert, `false` will release the inert state.
	 */
	var setSiblingsInert = function( element, inert ) {
		// Bail if element does not have a parentNode
		if ( ! element || ! element.parentNode ) { return; }

		// Release all elements in case of an invalid value for the `inert` param.
		if ( typeof inert !== 'boolean' ) { inert = false; }

		// Make all other elements `inert`
		var siblings = element.parentNode ? element.parentNode.childNodes : null;
		if ( siblings ) {
			Array.from( siblings ).forEach( function( child ) {
				if ( child != element ) { child.inert = inert; }
			} );
		}
	}


	/**
	 * Set all sibling elements on the passed element's tree with `inert` property.
	 *
	 * @param   HTMLElement  element  The element which to maintain the focus in, all siblings up the element's tree will be marked with the value of the param `inert` except this one.
	 * @param   bool         inert    Boolean value to set to the `inert` property, `true` will make siblings inert, `false` will release the inert state.
	 */
	var setTreeSiblingsInert = function( element, inert ) {
		// Bail if element does not have a parentNode
		if ( ! element || ! element.parentNode ) { return; }

		var targetElement = element;
		
		while ( targetElement.parentNode ) {
			setSiblingsInert( targetElement, inert );
			targetElement = targetElement.parentNode;
		}
	}



	/**
	 * Get current state of element
	 */
	_publicMethods.getState = function ( element ) {
		var manager = _publicMethods.getInstance( element );
		// Bail if manager invalid
		if ( ! manager ) return false;

		return manager.state;
	}



	/**
	 * Open element
	 */
	_publicMethods.open = function ( element ) {
		var manager = _publicMethods.getInstance( element );
		// Bail if manager invalid
		if ( ! manager ) return false;

		// Save element with focus
		manager.previousActiveElement = document.activeElement;

		// Set element open then play openning animation
		AnimateHelper.doThenAnimate( element, manager.settings.openAnimationClass, function() {
			// Set manager state
			manager.state = _states.OPEN;

			// Maybe save `hidden` attribute on the flyout element
			manager.wasHidden = manager.element.hasAttribute( 'hidden' );
			manager.element.removeAttribute( 'hidden' );

			// Set classes
			manager.element.classList.add( manager.settings.isOpenClass );
			document.body.classList.add( manager.settings.bodyHasFlyoutOpenClass, manager.settings.bodyHasFlyoutOpenClass + '-' + manager.element.id );

			// Set flyout content `role` attribute from data attributes
			var roleAttrValue = manager.element.getAttribute( manager.settings.flyoutRoleAttribute ) == 'alert' || manager.element.getAttribute( manager.settings.flyoutRoleAttribute ) == 'alertdialog' ? 'alertdialog' : 'dialog';
			manager.contentElement.setAttribute( 'role', roleAttrValue );

			// Set content element as focusable
			manager.contentElement.setAttribute( 'tabindex', 0 );

			// Maybe skip setting focus
			if ( ! manager.element.hasAttribute( manager.settings.manualFocusAttribute ) ) {
				// Set focus element as the dialog itself
				var focusElement = manager.contentElement;
				
				// Maybe set focus to child element marked as auto-focus
				var autofocusChild = manager.element.querySelector( manager.settings.autoFocusSelector );
				if ( autofocusChild ) {
					focusElement = autofocusChild;
				}
				// Maybe set focus to first focusable element
				else if ( manager.element.matches( manager.settings.autoFocusSelector ) ) {
					var focusableElements = Array.from( getFocusableElements( manager.element ) );
					focusableElements = focusableElements.filter( function( maybeFocusable ) { return ! maybeFocusable.matches( manager.settings.closeButtonSelector ); } );

					if ( focusableElements.length > 0 ) {
						focusElement = focusableElements[0];
					}
				}

				// Set focus
				focusElement.focus();
			}

			// Make all other elements `inert`
			setTreeSiblingsInert( manager.element, true );
		} );
	}


	
	/**
	 * Close element
	 */
	_publicMethods.close = function ( element ) {   
		var manager = _publicMethods.getInstance( element );
		// Bail if manager invalid
		if ( ! manager ) return false;

		// Play closing animation then set element closed
		AnimateHelper.animateThenDo( element, manager.settings.closeAnimationClass, function() {
			// Set manager state
			manager.state = _states.CLOSED;

			// Remove classes
			manager.element.classList.remove( manager.settings.isOpenClass );
			document.body.classList.remove( manager.settings.bodyHasFlyoutOpenClass + '-' + manager.element.id );

			// Remove flyout content `role` attribute
			manager.contentElement.removeAttribute( 'role' );

			// Set content element as not-focusable
			manager.contentElement.removeAttribute( 'tabindex' );

			// Maybe set `hidden` attribute again
			if ( manager.wasHidden ) {
				manager.element.setAttribute( 'hidden', '' );
			}
			manager.element.wasHidden = undefined;

			// Maybe remove body class for open elements
			if ( ! _publicMethods.hasAnyElementOpen() ) {
				document.body.classList.remove( manager.settings.bodyHasFlyoutOpenClass );
			}

			// Release all other elements, set `inert` to false
			setTreeSiblingsInert( manager.element, false );

			// Set focus back to the element previously with focus
			if ( manager.previousActiveElement ) {
				manager.previousActiveElement.focus();
			}
		} );
	}



	/**
	 * Close all instances
	 */
	_publicMethods.closeAll = function () {
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			var currentState = _publicMethods.getState( manager.element );
			if ( currentState != _states.CLOSED ) {
				_publicMethods.close( manager.element );
			}
		}
	}



	/**
	 * Toggle element state
	 */
	_publicMethods.toggle = function ( element ) {
		var currentState = _publicMethods.getState( element );
		if ( currentState == _states.CLOSED ) {
			_publicMethods.open( element );
		}
		else {
			_publicMethods.close( element );
		}
	}



	/**
	 * Get manager instance for element
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
	 * Get manager instance for element
	 */
	_publicMethods.hasAnyElementOpen = function () {
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var manager = _publicMethods.managers[i];
			if ( manager.state == _states.OPEN ) { return true; }
		}
		
		return false;
	}



	/**
	 * Initialize a trigger element.
	 */
	_publicMethods.initializeTrigger = function( trigger ) {
		// Enable the trigger element
		trigger.removeAttribute( 'disabled' );
		trigger.removeAttribute( 'aria-hidden' );
		
		// Add the element to the natural tab order
		trigger.setAttribute( 'tabindex', '0' );

		// Set trigger role to `button`
		if ( trigger.tagName.toUpperCase() != 'BUTTON' ) {
			trigger.setAttribute( 'role', 'button' );
		}

		// Maybe move selector to the target attribute
		var triggerHref = trigger.getAttribute( 'href' );
		if ( triggerHref != undefined && triggerHref != '' && triggerHref != '#' && triggerHref.indexOf( '#' ) == 0 ) {
			// Move selector to the target attribute
			var targetElement = document.querySelector( triggerHref );
			if ( targetElement ) {
				trigger.setAttribute( _settings.targetElementAttribute, triggerHref );
			}
		}

		// Remove the `href` attribute if the element has a flyout target
		if ( trigger.getAttribute( _settings.targetElementAttribute ) ) {
			trigger.removeAttribute( 'href' );
		}
	}
	


	/**
	 * Initialize an element
	 */
	_publicMethods.initializeElement = function( element ) {
		var manager = {};
		manager.element = element;
		manager.settings = extend( _settings );
		manager.state = _states.CLOSED;

		// Maybe create element ID
		if ( ! manager.element.id || manager.element.id == '' ) {
			manager.element.id = manager.settings.idPrefix + '-' + _publicMethods.managers.length;
		}

		// Get the content element
		manager.contentElement = manager.element.querySelector( manager.settings.flyoutContentSelector );
		
		// Try get open/close animation classes from attributes
		var openAnimationAttrValue = manager.element.getAttribute( manager.settings.openAnimationClassAttribute );
		var closeAnimationAttrValue = manager.element.getAttribute( manager.settings.closeAnimationClassAttribute );
		manager.settings.openAnimationClass = openAnimationAttrValue && openAnimationAttrValue != '' ? openAnimationAttrValue : _settings.openAnimationClass;
		manager.settings.closeAnimationClass = closeAnimationAttrValue && closeAnimationAttrValue != '' ? closeAnimationAttrValue : _settings.closeAnimationClass;

		// Set flyout accessible name
		var ariaLabelValue = manager.element.getAttribute( 'aria-label' );
		var ariaLabelledbyValue = manager.element.getAttribute( 'aria-labelledby' );
		if ( ( ! ariaLabelValue || ariaLabelValue == '' ) && ( ! ariaLabelledbyValue || ariaLabelledbyValue == '' ) ) {
			var firstHeading = manager.contentElement.querySelector( manager.settings.headingsSelector );
			
			// Set `aria-labelled` If a heading element exists inside the flyout content element
			if ( firstHeading ) {
				var headingId = firstHeading.id != undefined && firstHeading.id != '' ? firstHeading.id : manager.element.id + '-heading';
				firstHeading.id = headingId;
				manager.contentElement.setAttribute( 'aria-labelledby', headingId );
			}
		}

		// Set flyout accessible description
		var ariaDescribedbyValue = manager.element.getAttribute( 'aria-describedby' );
		if ( ! ariaDescribedbyValue || ariaDescribedbyValue == '' ) {
			var descriptionElement = manager.contentElement.querySelector( '[' + manager.settings.descriptionAttribute + ']' );
			
			// If a description element exists inside the flyout content element
			if ( descriptionElement ) {
				var descriptionId = descriptionElement.id != undefined && descriptionElement.id != '' ? descriptionElement.id : manager.element.id + '-description';
				descriptionElement.id = descriptionId;
				manager.contentElement.setAttribute( 'aria-describedby', descriptionId );
			}
		}
		
		// Set element as activated
		manager.isActivated = true;
		manager.element.classList.add( manager.settings.isActivatedClass );
		
		// Add manager to public methods
		_publicMethods.managers.push( manager );
	}



	/**
	 * Finish Initialize
	 */
	var finishInit = function( options ) {
		// Merge with general settings with options
		_settings = extend( _defaults, options );

		// Set dynamic settings value
		_settings.triggerSelectors = _settings.toggleButtonSelector + ', ' + _settings.openButtonSelector + ', ' + _settings.closeButtonSelector;

		// Iterate elements
		var elements = document.querySelectorAll( _settings.flyoutWrapperSelector );
		for ( var i = 0; i < elements.length; i++ ) {
			_publicMethods.initializeElement( elements[ i ] );
		}

		// Iterate trigger elements
		var triggers = document.querySelectorAll( _settings.triggerSelectors );
		for ( var i = 0; i < triggers.length; i++ ) {
			_publicMethods.initializeTrigger( triggers[ i ] );
		}

		// Add flyout overlay
		var overlayElement = document.createElement('div');
		overlayElement.innerHTML = _settings.overlayTemplate.trim();
		document.body.appendChild( overlayElement.childNodes[0] );

		// Add event listeners
		document.addEventListener( 'click', handleClick );
		document.addEventListener( 'keydown', handleKeyDown, true );
		
		// Add body class
		document.body.classList.add( _settings.bodyHasFlyoutClass );

		_hasInitialized = true;
	}

	

	/**
	 * Initialize Script.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Finish initialization, maybe load dependencies first (AnimateHelper, and Inert Polyfill)
		if ( window.AnimateHelper && Element.prototype.hasOwnProperty( 'inert' ) ) {
			finishInit( options );
		}
		else if( window.RequireBundle ) {
			RequireBundle.require( [ 'animate-helper', 'polyfill-inert' ], function() { finishInit( options ); } );
		}
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
