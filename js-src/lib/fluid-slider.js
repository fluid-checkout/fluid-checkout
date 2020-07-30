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
	  root.FluidSlider = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';


	var _hasInitialized = false;
	var _transitionEndEvent = window.whichTransitionEnd ? window.whichTransitionEnd() : 'transitionend';
	var _publicMethods = {
		managers: [],
	};
	var _settings = { };
	var _defaults = {
		sliderWrapperSelector: '.slider-wrapper',
		sliderViewportSelector: '.slider-viewport',
		sliderContainerSelector: '.slider-container',
		sliderItemSelector: '.slider-item',
		isActivatedClass: 'is-activated',
		isDisabledClass: 'is-disabled',
		isResizingClass: 'is-resizing',
		isAnimatingClass: 'is-animating',
		isCurrentClass: 'is-current',
		
		slideSpacing: 0, // px
		slideSensitivity: 30, // % of slide item width
		touchEventsSensitivity: 25, // px
		
		slidesPerViewAttribute: 'data-slider-per-view',
		slidesPerView: {
			xs: { breakpointInitial: 0, breakpointFinal: 749, itemsPerView: 1 },
			md: { breakpointInitial: 750, breakpointFinal: 999, itemsPerView: 3 },
			ml: { breakpointInitial: 1000, breakpointFinal: 1199, itemsPerView: 4 },
			lg: { breakpointInitial: 1200, breakpointFinal: 1499, itemsPerView: 4 },
			xl: { breakpointInitial: 1500, breakpointFinal: 100000, itemsPerView: 4 }, // breakpointFinal can be any very high number
		},
		
		createNavigationButtons: false,
		createNavigationButtonsAttribute: 'data-slider-navigation-buttons',
		navigationButtonsSelector: '.slider-navigation',
		navigationPrevSelector: '.slider-navigation__prev',
		navigationNextSelector: '.slider-navigation__next',
		hasNavigationButtonsClass: 'has-navigation-buttons',
		navigationButtonsTemplate: '<div class="slider-navigation"><button class="slider-navigation__prev">Previous</button></div><button class="slider-navigation__next">Next</button></div>',
		
		createNavigationBullets: false,
		createNavigationBulletsAttribute: 'data-slider-navigation-bullets',
		navigationBulletsSelector: '.slider-navigation-bullets',
		navigationBulletItemSelector: '.slider-navigation-bullets__item',
		hasNavigationBulletsClass: 'has-navigation-bullets',
		navigationBulletsWrapperTemplate: '<div class="slider-navigation-bullets"></div>',
		navigationBulletItemTemplate: '<span class="slider-navigation-bullets__item"></span>',
	};



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
		for (; i < arguments.length; i++ ) {
		var obj = arguments[i];
		merge(obj);
		}

		return extended;

	};



	/**
	 * Get slider manager instance from slider element
	 */
	var getManager = function ( sliderViewport ) {
		var manager;
		for ( var i = 0; i < _publicMethods.managers.length; i++ ) {
			var managerItem = _publicMethods.managers[i];
			if ( managerItem.sliderViewport == sliderViewport ) { manager = managerItem; break; }
		}
		return manager;
	}



	/**
	 * Get number of slides visible at once
	 */
	var getSlidesPerView = function ( manager ) {
		var itemsPerView = 1;
		var windowWidth = window.innerWidth;
		var slidesPerView = _settings.slidesPerView;

		// Try get slides per view settings from html attributes
		var slidesPerViewAttr = manager.sliderWrapper.getAttribute( _settings.slidesPerViewAttribute );
		if ( slidesPerViewAttr ) {
			try {
				var parsedParams = JSON.parse( slidesPerViewAttr );
				slidesPerView = extend( _settings.slidesPerView, parsedParams );
			}
			catch (err) {
				console.log( 'Could not parse attribute "' + _settings.slidesPerViewAttribute + '" into JSON.');
			}
		}
		
		Object.entries( slidesPerView ).forEach( function ( values, i ) {
			if ( windowWidth >= values[1].breakpointInitial && windowWidth <= values[1].breakpointFinal ) {
				itemsPerView = values[1].itemsPerView;
			}
		});

		return itemsPerView;
	}



	/**
	 * Sanitize slide number to prevent moving to inexistent slides
	 */
	var getSanitizedSlideNumber = function ( manager, number ) {
		var maxActiveSlideAdjustment = getSlidesPerView( manager );
		if ( number > manager.slideCount - maxActiveSlideAdjustment ) { number = manager.slideCount - maxActiveSlideAdjustment; }
		return number < 0 ? 0 : number;
	}



	/**
	 * Calculate spacing between slide items
	 */
	var getSlidesSpacing = function( manager ) {
		// Return default if too few slides
		if ( manager.slides.length < 2 ) { return _settings.slideSpacing; }

		// Calculate space between slides
		var rightEdge = manager.slides.item(0).getBoundingClientRect().right;
		var leftEdge = manager.slides.item(1).getBoundingClientRect().left;
		var spacing = leftEdge - rightEdge;
		spacing = spacing > 0 ? spacing : _settings.slideSpacing;
		return spacing;
	}



	/**
	 * Get container position to display the slide by its number
	 */
	var getSlidePosition = function ( manager, number ) {
		return -( ( number * manager.slideWidth ) + ( number  * manager.slideSpacing ) );
	}



	var setNavigationButtonDisabledStatus = function( button, isDisabled ) {
		// Bail if button not valid
		if ( ! button ) { return; }
		
		if ( isDisabled ) {
			button.classList.add( _settings.isDisabledClass );
		}
		else {
			button.classList.remove( _settings.isDisabledClass );
		}
	}



	/**
	 * Set active slide and container element to new position
	 */
	var setActiveSlide = function( manager, number ) {
		// Set sanitized active slide number
		manager.activeSlide = getSanitizedSlideNumber( manager, number );

		// Prepare to remove animating class when transition ends
		manager.sliderViewport.addEventListener( _transitionEndEvent, function removeAnimatingClass( e ) {
			if ( e.propertyName == 'transform' ) {
				manager.sliderViewport.classList.remove( _settings.isAnimatingClass );
				manager.sliderViewport.removeEventListener( _transitionEndEvent, removeAnimatingClass );
			}
		} );

		// Apply animating class
		manager.sliderViewport.classList.add( _settings.isAnimatingClass );

		// Change container position
		var translateValue = 'translateX(' + getSlidePosition( manager, manager.activeSlide ) + 'px)';
		manager.sliderContainer.style.transform = translateValue;
		manager.sliderContainer.style.msTransform = translateValue; // Add support for IE9+

		// Set current bullet
		if ( manager.sliderNavigationBullets ) {
			var bullets = manager.sliderNavigationBullets.querySelectorAll( _settings.navigationBulletItemSelector );
			for ( var i = 0; i < bullets.length; i++ ) {
				var bullet = bullets[i];
				if ( i == manager.activeSlide ) {
					bullet.classList.add( _settings.isCurrentClass );
				}
				else {
					bullet.classList.remove( _settings.isCurrentClass );
				}
			}
		}

		// Set navigation buttons status
		if ( manager.sliderNavigation ) {
			setNavigationButtonDisabledStatus( manager.sliderNavigationPrev, manager.activeSlide <= 0 )
			setNavigationButtonDisabledStatus( manager.sliderNavigationNext, manager.activeSlide >= getSanitizedSlideNumber( manager, manager.slideCount ) );
		}
	}



	/**
	 * Remove sizing styles from slider element, container and items
	 */
	var removeSlideSizing = function ( manager ) {
		// Lock viewport while resizing
		manager.sliderViewport.classList.remove( _settings.isActivatedClass );
		manager.sliderViewport.classList.add( _settings.isResizingClass );
		manager.sliderViewport.style.height = manager.sliderViewport.getBoundingClientRect().height + 'px';

		// Remove container width
		manager.sliderContainer.style.width = '';

		// Remove slide width for each slide item
		for ( var slideIndex = 0; slideIndex < manager.slides.length; slideIndex++ ) {
			var slideItem = manager.slides[ slideIndex ];
			slideItem.style.width = '';
			slideItem.style.marginLeft = '';
		}
	}



	/**
	 * Go to previous slider view
	 */
	var goToPrevView = function ( manager ) {
		var moveToSlide = manager.activeSlide - getSlidesPerView( manager );

		// Give visual feedback for the last view
		if (  manager.activeSlide <= 0 ) {
			playEndSlidesFeedback( manager, moveToSlide, 25 );
		}
		else {
			_publicMethods.goTo( manager, moveToSlide );
		}
	}



	/**
	 * Go to next slider view
	 */
	var goToNextView = function ( manager ) {
		var moveToSlide = manager.activeSlide + getSlidesPerView( manager );

		// Give visual feedback for the last view
		if ( moveToSlide >= manager.slideCount ) {
			playEndSlidesFeedback( manager, moveToSlide, -25 );
		}
		else {
			_publicMethods.goTo( manager, moveToSlide );
		}
	}



	/**
	 * Move slider slightly to give visual feedback for end of slider reached
	 */
	var playEndSlidesFeedback = function ( manager, moveToSlide, distance ) {
		// Prepare to remove animating class when transition ends
		manager.sliderViewport.addEventListener( _transitionEndEvent, function removeAnimatingClass( e ) {
			if ( e.propertyName == 'transform' ) {
				manager.sliderViewport.classList.remove( _settings.isAnimatingClass );
				manager.sliderViewport.removeEventListener( _transitionEndEvent, removeAnimatingClass );

				// Move to slide after transition ends
				_publicMethods.goTo( manager, moveToSlide );
			}
		} );
		
		// Apply animating class
		manager.sliderViewport.classList.add( _settings.isAnimatingClass );
		
		// Slightly change position of slider container
		manager.sliderContainer.style.transform = 'translateX(' + ( getSlidePosition( manager, manager.activeSlide ) + distance ) + 'px)';
	}



	/**
	 * Handle the slider movement on pan event
	 */
	var handleSlidePan = function( manager, e ) {
		// Move slides while moving touch points
		var distance = getSlidePosition( manager, manager.activeSlide ) + e.deltaX ;
		manager.sliderContainer.style.transform = 'translateX(' + distance + 'px)';

		// Go to slide when touch released
		if( e.isFinal  ) {
			// Change to previous slide if swipe right is detected
			if ( e.velocityX > 1 ) {
				var slidesToMove = Math.abs( getSlidesPerView( manager ) );
				_publicMethods.goTo( manager, manager.activeSlide - slidesToMove );
			}
			// Change to next slide if swipe left is detected
			else if ( e.velocityX < -1 ) {
				var slidesToMove = Math.abs( getSlidesPerView( manager ) );
				_publicMethods.goTo( manager, manager.activeSlide + slidesToMove );
			}
			else {
				var distanceChange = ( _settings.slideSensitivity / 100 ) * manager.slideWidth;
				var slidesToMove = Math.abs( Math.round( e.deltaX / manager.slideWidth ) );

				// Change to next slide based on position
				if ( Math.abs( e.angle ) >= 150 && e.deltaX <= -(distanceChange) ) {
					_publicMethods.goTo( manager, manager.activeSlide + slidesToMove );
				}
				// Change to prev slide based on position
				else if ( Math.abs( e.angle ) <= 30 && e.deltaX >= distanceChange ) {
					_publicMethods.goTo( manager, manager.activeSlide - slidesToMove );
				}
				// Realign the current slide when a change is not needed
				else {
					_publicMethods.goTo( manager, manager.activeSlide );
				}
			}
		}
	}



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleCapturedClick = function ( e ) {
		// PREVIOUS VIEW
		if ( e.target.closest( _settings.navigationPrevSelector ) ) {
			e.preventDefault();
			var sliderWrapper = e.target.closest( _settings.sliderWrapperSelector );
			var manager = getManager( sliderWrapper.querySelector( _settings.sliderViewportSelector ) );
			goToPrevView( manager );
		}
		// NEXT VIEW
		else if ( e.target.closest( _settings.navigationNextSelector ) ) {
			e.preventDefault();
			var sliderWrapper = e.target.closest( _settings.sliderWrapperSelector );
			var manager = getManager( sliderWrapper.querySelector( _settings.sliderViewportSelector ) );
			goToNextView( manager );
		}
	};



	/**
	 * Resize slider elements with absolute values
	 */
	_publicMethods.resizeSlider = function( manager ) {
		// Clear sizings before resizing
		removeSlideSizing( manager );

		// Calculate width of container and slides
		manager.slideSpacingCount = ( manager.slideCount * ( manager.slideCount - 1 ) );
		manager.slideWidth = manager.slides.item(0).getBoundingClientRect().width;
		manager.slideSpacing = getSlidesSpacing( manager );
		manager.containerWidth = ( manager.slideSpacingCount * manager.slideSpacing ) + ( manager.slideCount * manager.slideWidth );

		// Set container width
		manager.sliderContainer.style.width = manager.containerWidth + 'px';

		// Set slide width for each slide item
		for ( var slideIndex = 0; slideIndex < manager.slides.length; slideIndex++ ) {
			var slideItem = manager.slides[ slideIndex ];
			slideItem.style.width = manager.slideWidth + 'px';
			if ( slideIndex > 0 ) { slideItem.style.marginLeft = manager.slideSpacing + 'px' };
		}

		// Release viewport after resizing
		manager.sliderViewport.classList.add( _settings.isActivatedClass );
		manager.sliderViewport.classList.remove( _settings.isResizingClass );
		manager.sliderViewport.style.height = '';

		// Make sure to display the correct active slides when resizing
		var slidesPerView = getSlidesPerView( manager );
		if ( manager.activeSlide > manager.slideCount - slidesPerView ) {
			_publicMethods.goTo( manager, manager.slideCount - slidesPerView );
		}
		else {
			_publicMethods.goTo( manager, manager.activeSlide );
		}

		// Only display pagination when necessary
		if ( manager.sliderNavigation ) {
			manager.sliderNavigation.style.display = manager.slideCount <= slidesPerView ? 'none' : '';
		}
		if ( manager.sliderNavigationBullets ) {
			manager.sliderNavigationBullets.style.display = manager.slideCount <= slidesPerView ? 'none' : '';
		}
	}



	/**
	 * Update current slide
	 */
	_publicMethods.goTo = function( manager, number ) {
		setActiveSlide( manager, number );
	};
	


	/**
	 * Initialize Slider for an element
	 */
	_publicMethods.initializeSlider = function( sliderWrapper ) {
		var manager = {};
		manager.sliderWrapper = sliderWrapper;
		manager.sliderViewport = sliderWrapper.querySelector( _settings.sliderViewportSelector );
		manager.sliderContainer = sliderWrapper.querySelector( _settings.sliderContainerSelector );
		manager.slides = sliderWrapper.querySelectorAll( _settings.sliderItemSelector );
		manager.slideCount = manager.slides.length;
		manager.activeSlide = 0;
		
		// Initialize Hammer Touch event listeners
		manager.hammerManager = new Hammer.Manager( manager.sliderViewport );
		manager.hammerManager.add( new Hammer.Pan( { direction: Hammer.DIRECTION_HORIZONTAL, threshold: _settings.touchEventsSensitivity, pointers: 0 } ) );
		manager.hammerManager.on( 'pan', function( e ) { handleSlidePan( manager, e ); } );
		
		// Add navigation buttons
		var createNavigationButtonsAttrValue = manager.sliderWrapper.getAttribute( _settings.createNavigationButtonsAttribute );
		var createNavigationButtonsValue = createNavigationButtonsAttrValue != null ? createNavigationButtonsAttrValue === 'true' : _settings.createNavigationButtons;
		if ( createNavigationButtonsValue ) {
			var navigationElements = document.createElement('div');
			navigationElements.innerHTML = _settings.navigationButtonsTemplate.trim();
			manager.sliderWrapper.appendChild( navigationElements.childNodes[0] );
			manager.sliderNavigation = sliderWrapper.querySelector( _settings.navigationButtonsSelector );
			manager.sliderNavigationPrev = sliderWrapper.querySelector( _settings.navigationPrevSelector );
			manager.sliderNavigationNext = sliderWrapper.querySelector( _settings.navigationNextSelector );
			manager.sliderWrapper.classList.add( _settings.hasNavigationButtonsClass );
		}

		// Add navigation bullets
		var createNavigationBulletsAttrValue = manager.sliderWrapper.getAttribute( _settings.createNavigationBulletsAttribute );
		var createNavigationBulletsValue = createNavigationBulletsAttrValue != null ? createNavigationBulletsAttrValue === 'true' : _settings.createNavigationBullets;
		if ( createNavigationBulletsValue ) {
			var navigationBulletsElements = document.createElement('div');
			navigationBulletsElements.innerHTML = _settings.navigationBulletsWrapperTemplate.trim();
			manager.sliderWrapper.appendChild( navigationBulletsElements.childNodes[0] );
			manager.sliderNavigationBullets = sliderWrapper.querySelector( _settings.navigationBulletsSelector );
			
			for ( var i = 0; i < manager.slideCount; i++ ) {
				var bulletElement = document.createElement('div');
				bulletElement.innerHTML = _settings.navigationBulletItemTemplate.trim();
				manager.sliderNavigationBullets.appendChild( bulletElement.childNodes[0] );
			}

			manager.sliderWrapper.classList.add( _settings.hasNavigationBulletsClass );
		}

		// Set slider element as activated
		manager.sliderViewport.classList.add( _settings.isActivatedClass );
		
		// Resize elements
		_publicMethods.resizeSlider( manager );
		window.addEventListener( 'resize', function() { _publicMethods.resizeSlider( manager ); } );
		
		// Event other listeners
		document.addEventListener( 'click', handleCapturedClick );
		
		// Add slider manager to public methods
		_publicMethods.managers.push( manager );
	}

	

	/**
	 * Initialize
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge with general settings with options
		_settings = extend( _defaults, options );
		
		// Require dependencies
		if ( RequireBundle ) {
			RequireBundle.require( ['hammerjs'], function() {
				var sliders = document.querySelectorAll( _settings.sliderWrapperSelector );
				for ( var slideIndex = 0; slideIndex < sliders.length; slideIndex++ ) {
					_publicMethods.initializeSlider( sliders[ slideIndex ] );
				}
				
				_hasInitialized = true;
			} );
		}
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
