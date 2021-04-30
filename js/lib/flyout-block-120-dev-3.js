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
		flyoutElementSelector: '[data-flyout-content]',
		toggleButtonSelector: '[data-flyout-toggle]',
		openButtonSelector: '[data-flyout-open]',
		closeButtonSelector: '[data-flyout-close]',
		overlaySelector: '[data-flyout-overlay]',
		flyoutTogglePassActionSelector: '[data-flyout-pass-action], .js-flyout-pass-action',

		flyoutToggleClassSelector: '.js-flyout-toggle',
		flyoutClassTogglePrefix: 'js-flyout-target-',
		
		idPrefix: 'flyout-block',
		
		bodyHasFlyoutClass: 'has-flyout',
		bodyHasFlyoutOpenClass: 'has-flyout--open',
		isActivatedClass: 'is-activated',
		isOpenClass: 'is-open',
		openAnimationClass: 'slide-in-up',
		closeAnimationClass: 'slide-out-down',
		
		targetElementAttribute: 'data-flyout-target',
		openAnimationClassAttribute: 'data-flyout-open-animation-class',
		closeAnimationClassAttribute: 'data-flyout-close-animation-class',

		overlayTemplate: '<div class="flyout-overlay" data-flyout-overlay></div>',
	};
	var _states = {
		OPEN: 'open',
		CLOSED: 'closed',
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
	var handleKeyDown = function(e) {
		if ( e.keyCode == 27 || e.which == 27 || e.key == 'Escape' || e.key == 'Esc' ) {
			_publicMethods.closeAll();
		}
	};
	


	/**
	 * Finish Initialize
	 */
	var finishInit = function( options ) {
		// Merge with general settings with options
		_settings = extend( _defaults, options );

		// Iterate elements
		var elements = document.querySelectorAll( _settings.flyoutWrapperSelector );
		for ( var i = 0; i < elements.length; i++ ) {
			_publicMethods.initializeElement( elements[ i ] );
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

		// Set element open then play openning animation
		AnimateHelper.doThenAnimate( element, manager.settings.openAnimationClass, function() {
			// Set manager state
			manager.state = _states.OPEN;

			// Set classes
			manager.element.classList.add( manager.settings.isOpenClass );
			document.body.classList.add( manager.settings.bodyHasFlyoutOpenClass );
			document.body.classList.add( manager.settings.bodyHasFlyoutOpenClass + '-' + manager.element.id );
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
		AnimateHelper.doThenAnimate( element, manager.settings.closeAnimationClass, function() {
			// Set manager state
			manager.state = _states.CLOSED;

			// Remove classes
			manager.element.classList.remove( manager.settings.isOpenClass );
			document.body.classList.remove( manager.settings.bodyHasFlyoutOpenClass + '-' + manager.element.id );

			// Maybe remove body class for open elements
			if ( ! _publicMethods.hasAnyElementOpen() ) {
				document.body.classList.remove( manager.settings.bodyHasFlyoutOpenClass );
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
		
		// Try get open/close animation classes from attributes
		var openAnimationAttrValue = manager.element.getAttribute( manager.settings.openAnimationClassAttribute );
		var closeAnimationAttrValue = manager.element.getAttribute( manager.settings.closeAnimationClassAttribute );
		manager.settings.openAnimationClass = openAnimationAttrValue && openAnimationAttrValue != '' ? openAnimationAttrValue : _settings.openAnimationClass;
		manager.settings.closeAnimationClass = closeAnimationAttrValue && closeAnimationAttrValue != '' ? closeAnimationAttrValue : _settings.closeAnimationClass;
		
		// Set element as activated
		manager.isActivated = true;
		manager.element.classList.add( manager.settings.isActivatedClass );
		
		// Add manager to public methods
		_publicMethods.managers.push( manager );
	}

	

	/**
	 * Initialize Script
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Finish initialization, maybe load dependencies first
		if ( window.AnimateHelper ) {
			finishInit( options );
		}
		else if( window.RequireBundle ) {
			RequireBundle.require( [ 'animate-helper' ], function() { finishInit( options ); } );
		}
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
