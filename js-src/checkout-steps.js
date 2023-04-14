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
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass: 'has-fc-checkout-steps',
		bodyClassActiveStepPattern: 'fc-checkout-step--active-{ID}',

		isMultistepLayout: 'yes',
		maybeDisablePlaceOrderButton: 'yes',

		wrapperSelector: '.fc-wrapper',

		progressBarSelector: '.fc-progress-bar',
		progressBarCurrentSelector: '.fc-progress-bar__current-step',
		progressBarItemSelector: '.fc-progress-bar__bar',

		stepsWrapperSelector: '.fc-checkout-steps',
		stepSelector: '.fc-checkout-step',
		currentStepSelector: '[data-step-current]',
		lastStepSelector: '[data-step-last]',
		nextStepSelector: '[data-step-current] ~ .fc-checkout-step',
		nextStepButtonSelector: '[data-step-next]',
		focusableElementsSelector: 'a[role="button"], a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), select:not([disabled]), details, summary, iframe, object, embed, [contenteditable] [tabindex]:not([tabindex="-1"])',

		checkoutFormSelector: 'form.checkout',
		fieldSubmitFormSelector: 'input[type="text"], input[type="checkbox"], input[type="color"], input[type="date"], input[type="datetime"], input[type="datetime-local"], input[type="email"], input[type="file"], input[type="image"], input[type="month"], input[type="number"], input[type="password"], input[type="radio"], input[type="search"], input[type="tel"], input[type="time"], input[type="url"], input[type="week"]',

		substepSelector: '.fc-step__substep',
		substepTextContentSelector: '.fc-step__substep-text-content',
		substepFieldsSelector: '.fc-step__substep-fields',
		substepTextSelector: '.fc-step__substep-text',
		substepEditButtonSelector: '[data-step-edit]',
		substepSaveButtonSelector: '[data-step-save]',

		stepCompleteAttribute: 'data-step-complete',
		stepCurrentAttribute: 'data-step-current',
		stepIndexAttribute: 'data-step-index',
		stepIdAttribute: 'data-step-id',

		isEditingClass: 'is-editing',
		isLoadingClass: 'is-loading',
		isCurrentClass: 'is-current',
		isCompleteClass: 'is-complete',
		stepNextIncompleteClass: 'fc-checkout-step--next-step-incomplete',
		currentStepClassTemplate: 'fc-checkout-step-current--##STEP_ID##',
		currentLastStepClass: 'fc-checkout-step-current-last',

		substepEditableStateFieldSelector: '.fc-substep-editable-state[type="hidden"]',
		substepEditableStateAttribute: 'data-substep-editable',
		substepVisibleStateFieldSelector: '.fc-substep-visible-state[type="hidden"]',
		substepVisibleStateAttribute: 'data-substep-visible',
		substepExpandedStateFieldSelector: '.fc-substep-expanded-state[type="hidden"]',

		invalidFieldRowSelector: '.woocommerce-invalid .input-text, .woocommerce-invalid select',

		placeOrderButtonSelector: '.fc-place-order-button',
		placeOrderSkipMoveSelector: '.has-place-order--below_order_summary',
		placeOrderSectionMainSelector: '.fc-place-order__section--main',
		placeOrderPlaceholderMainSelector: '.fc-inside .fc-place-order__section-placeholder',
		placeOrderPlaceholderSidebarSelector: '.fc-sidebar .fc-place-order__section-placeholder',
		placeOrderRefreshRate: 50,

		scrollOffsetSelector: '.fc-checkout-header',
		scrollBehavior: 'smooth',
		scrollOffset: 0,
		scrollDelay: 50,
	}
	var _resizeObserver;



	/**
	 * METHODS
	 */



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
		
		// Get elements that are keyboard-focusable, but might be `disabled`
		return element.querySelectorAll( _settings.focusableElementsSelector );
	}



	/**
	 * Get the offset position of the element recursively adding the offset position of parent elements until the `stopElement` (or the `body` element).
	 *
	 * @param   HTMLElement  element      Element to get the offset position for.
	 * @param   HTMLElement  stopElement  Parent element where to stop adding the offset position to the total offset top position of the element.
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
	 * Get all step elements.
	 *
	 * @return  Array  List of step elements.
	 */
	var getAllSteps = function() {
		var stepsWrapper = document.querySelector( _settings.stepsWrapperSelector );

		// Bail if steps wrapper not found, returns empty `Array`.
		if ( ! stepsWrapper ) { return []; }

		return Array.from( stepsWrapper.querySelectorAll( _settings.stepSelector ) );
	}




	/**
	 * Expand the substep fields for edition, and collapse the substep values in text format.
	 *
	 * @param   HTMLElement  substepElement  Substep element to change the state of.
	 * @param   Boolean      withTransition  Whether to use transitions between states. Defaults to `true`.
	 * @param   Boolean      withFocus       Whether to set the focus to the field when expanding. Cannot be used with `withTransition = true`. Defaults to `true`.
	 */
	var expandSubstepEdit = function( substepElement, withTransition, withFocus ) {
		// Bail if editButton not valid
		if ( ! substepElement ) { return; }

		var substepFieldsElement = substepElement.querySelector( _settings.substepFieldsSelector );
		var substepTextElement = substepElement.querySelector( _settings.substepTextSelector );

		// Change expanded/collapsed states for the fields and text blocks
		CollapsibleBlock.expand( substepFieldsElement, withTransition, withFocus );
		CollapsibleBlock.collapse( substepTextElement, withTransition );

		// Add editing class to the substep element
		substepElement.classList.add( _settings.isEditingClass );
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
		substepElement.classList.remove( _settings.isEditingClass );

		// Focus on the substep edit button
		var editbutton = substepElement.querySelector( _settings.substepEditButtonSelector );
		if ( editbutton ) {
			editbutton.focus();
		}

		// Change scroll position after collapsing substep
		scrollTo( substepElement );
	}



	/**
	 * Use the same method that WooCommerce uses to block other parts of the checkout form while updating.
	 * The UI is unblocked by the WooCommerce `checkout.js` script (which is replaced with a modified version but keeps the same behavior)
	 * using the checkout fragment selector, then unblocking after the checkout update is completed.
	 *
	 * @param   HTMLElement  element  Element to block the UI and show the loading indicator.
	 */
	var blockUI = function( element ) {
		if ( _hasJQuery ) {
			$( element ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
		}
	}



	/**
	 * Maybe remove `is-loading` class from fragments after checkout update.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 * @param   Array  data   The updated checkout data.
	 */
	var maybeRemoveFragmentsLoadingClass = function( _event, data ) {
		// Iterate fragments
		if ( data && data.fragments ) {
			for ( var key in data.fragments ) {
				// Try to get the target fragment element
				var framentElement = document.querySelector( key );
				if ( framentElement ) {
					// Remove `is-loading` class from the fragment element
					framentElement.classList.remove( _settings.isLoadingClass );
				}
			}
		}
	}



	/**
	 * Collapse the substep fields, and expand the substep values in text format for review.
	 *
	 * @param   HTMLElement  substepElement  Substep element to change the state of.
	 */
	var maybeSaveSubstep = function( substepElement ) {
		// Bail if editButton not valid
		if ( ! substepElement ) { return; }
		
		// Maybe validate fields
		if ( window.CheckoutValidation && ! CheckoutValidation.validateAllFields( substepElement ) ) {
			// Try to focus the first invalid field
			var firstInvalidField = substepElement.querySelector( _settings.invalidFieldRowSelector );
			if ( firstInvalidField ) {
				firstInvalidField.focus();
			}

			// Bail when substep has invalid fields
			return;
		}

		// Collapse substep fields and display step in text format
		collapseSubstepEdit( substepElement );

		// Update checkout
		if ( _hasJQuery ) {
			// Get text content element, then block IU
			var contentElement = substepElement.querySelector( _settings.substepTextContentSelector );
			blockUI( contentElement );

			// Remove messages prior to updating
			$( '.woocommerce-error, .woocommerce-message' ).remove();

			// Trigger update checkout
			$( document.body ).trigger( 'update_checkout' );
		}
	}



	/**
	 * Update the progress bar state.
	 */
	var updateProgressBar = function() {
		// Get current step
		var currentStepElement = document.querySelector( _settings.currentStepSelector );
		
		// Bail if no current step was found
		if ( ! currentStepElement ) { return; }

		// Get index of the current step
		var currentStepIndex = parseInt( currentStepElement.getAttribute( _settings.stepIndexAttribute ) );
		currentStepIndex = isNaN( currentStepIndex ) ? -1 : currentStepIndex;

		// Get progress bar items
		var progressBarElement = document.querySelector( _settings.progressBarSelector )

		// Bail if progress bar element not found
		if ( ! progressBarElement ) { return; }

		var progressBarItems = progressBarElement.querySelectorAll( _settings.progressBarItemSelector );
		var progressBarItemsCount = progressBarItems.length;

		// Update progress bar items status
		for ( var i = 0; i < progressBarItems.length; i++ ) {
			var bar = progressBarItems[i];
			var stepIndex = parseInt( bar.getAttribute( _settings.stepIndexAttribute ) );
			stepIndex = isNaN( stepIndex ) ? -1 : stepIndex;

			// Update the `current` status for each progress bar item
			if ( stepIndex == currentStepIndex ) {
				bar.classList.add( _settings.isCurrentClass );
			}
			else {
				bar.classList.remove( _settings.isCurrentClass );
			}

			// Update the `complete` status for each progress bar item
			if ( stepIndex <= currentStepIndex ) {
				bar.classList.add( _settings.isCompleteClass );
			}
			else {
				bar.classList.remove( _settings.isCompleteClass );
			}
		}

		// Calculate the current step text value
		var currentStepValue = currentStepIndex + 1;
		currentStepValue = currentStepValue <= progressBarItemsCount ? currentStepValue : progressBarItemsCount;

		// Change value of the current step text indicator
		var currentStepTextElement = progressBarElement.querySelector( _settings.progressBarCurrentSelector );
		if ( currentStepTextElement ) {
			currentStepTextElement.innerText = currentStepValue;
		}
	}


	/**
	 * Get the scroll offset position for the sticky elements.
	 */
	var getStickyElementsOffset = function() {
		var stickyElementsOffset = 0;

		// Maybe add height of the progress bar to scroll position
		var progressBarElement = document.querySelector( _settings.progressBarSelector );
		if ( progressBarElement ) {
			var height = progressBarElement.getBoundingClientRect().height;
			stickyElementsOffset += height;
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

		return stickyElementsOffset;
	}

	/**
	 * Change scroll position to top of the element after the sticky elements.
	 *
	 * @param   HTMLElement  element      The element of to scroll to.
	 */
	var scrollTo = function( element ) {
		// Bail if step element not provided
		if ( ! element ) { return; }

		var stickyElementsOffset = getStickyElementsOffset();
		var elementOffset = getOffsetTop( element ) + ( _settings.scrollOffset * -1 ) + ( stickyElementsOffset * -1 );
		window.setTimeout( function() {
			window.scrollTo( {
				top: elementOffset,
				behavior: _settings.scrollBehavior,
			} );
		}, _settings.scrollDelay );
	}



	/**
	 * Collapse the substep fields, and expand the substep values in text format for review.
	 *
	 * @param   HTMLElement  substepElement  Substep element to change the state of.
	 */
	var maybeProceedNextStep = function( stepElement ) {
		// Bail if editButton not valid
		if ( ! stepElement ) { return; }

		// Maybe validate fields
		if ( window.CheckoutValidation && ! CheckoutValidation.validateAllFields( stepElement ) ) {
			// Try to focus the first invalid field
			var firstInvalidField = stepElement.querySelector( _settings.invalidFieldRowSelector );
			if ( firstInvalidField ) {
				firstInvalidField.focus();
			}

			// Bail when any substep has invalid fields
			return;
		}

		// Set current step as complete
		stepElement.setAttribute( _settings.stepCompleteAttribute, '' );

		// Collapse substeps fields and display step in text format
		var substepElements = stepElement.querySelectorAll( _settings.substepSelector );
		for ( var i = 0; i < substepElements.length; i++ ) {
			var substepElement = substepElements[i];

			// Get text content element, then block IU
			var contentElement = substepElement.querySelector( _settings.substepTextContentSelector );
			if ( contentElement ) {
				contentElement.classList.add( _settings.isLoadingClass );
				blockUI( contentElement );
			}

			// Collapse substep
			collapseSubstepEdit( substepElement );
		}

		// Get next step, and set it as current
		var nextStepElement = stepElement.parentElement.querySelector( _settings.nextStepSelector );
		nextStepElement.setAttribute( _settings.stepCurrentAttribute, '' );

		// Unset `current` from the step that is closing
		// (needs to run after setting the next step as the current one)
		stepElement.removeAttribute( _settings.stepCurrentAttribute );

		// Remove `next-step-incomplete` class from previous steps
		var allSteps = getAllSteps();
		var stepIndex = allSteps.indexOf( stepElement );
		for ( var i = 0; i < allSteps.length; i++ ) {
			if ( i < stepIndex ) {
				var step = allSteps[i];
				step.classList.remove( _settings.stepNextIncompleteClass );
			}
		}

		// Update progress bar
		updateProgressBar();

		// Maybe set focusElement to the first focusable element that is visible
		var focusElement = null;
		var focusableElements = Array.from( getFocusableElements( nextStepElement ) );
		for ( var i = 0; i < focusableElements.length; i++ ) {
			var focusableElement = focusableElements[i];
			if ( isVisible( focusableElement ) ) {
				focusElement = focusableElement;
				break;
			}
		}

		// Set focus
		if ( focusElement ) {
			focusElement.focus();
		}

		// Change scroll position after moving to next step
		scrollTo( stepElement );

		// Trigger update checkout
		if ( _hasJQuery ) {
			$( document.body ).trigger( 'update_checkout' );
		}
	}



	/**
	 * Update the body classes for step states.
	 */
	var updateStepBodyClasses = function() {
		// Bail if not using multistep layout
		if ( 'yes' !== _settings.maybeDisablePlaceOrderButton || 'yes' !== _settings.isMultistepLayout ) { return; }

		// Remove current step class
		var allSteps = getAllSteps();
		for ( var i = 0; i < allSteps.length; i++ ) {
			var step = allSteps[i];
			var stepId = step.getAttribute( _settings.stepIdAttribute );
			var className = _settings.currentStepClassTemplate.replace( '##STEP_ID##', stepId );
			document.body.classList.remove( className );
		}

		// Maybe add current step class
		var currentStepElement = document.querySelector( _settings.currentStepSelector );
		var lastStepElement = document.querySelector( _settings.lastStepSelector );
		if ( currentStepElement ) {
			var stepId = currentStepElement.getAttribute( _settings.stepIdAttribute );
			
			// Add current step class
			var className = _settings.currentStepClassTemplate.replace( '##STEP_ID##', stepId );
			document.body.classList.add( className );
			
			// Maybe add last step class
			if ( lastStepElement ) {
				var currentStepId = currentStepElement.getAttribute( _settings.stepIdAttribute );
				var lastStepId = lastStepElement.getAttribute( _settings.stepIdAttribute );
				if ( lastStepId === currentStepId ) {
					document.body.classList.add( _settings.currentLastStepClass );
				}
			}
		}
	}

	/**
	 * Maybe disable the place order buttons.
	 */
	var maybeDisablePlaceOrderButton = function() {
		// Bail if not using multistep layout
		if ( 'yes' !== _settings.maybeDisablePlaceOrderButton || 'yes' !== _settings.isMultistepLayout ) { return; }

		// Check if current step is same as last step
		var currentStepElement = document.querySelector( _settings.currentStepSelector );
		var lastStepElement = document.querySelector( _settings.lastStepSelector );
		if ( currentStepElement && lastStepElement ) {
			var currentStepId = currentStepElement.getAttribute( _settings.stepIdAttribute );
			var lastStepId = lastStepElement.getAttribute( _settings.stepIdAttribute );
			var isDisabled = currentStepId !== lastStepId;

			var placeOrderButtons = document.querySelectorAll( _settings.placeOrderButtonSelector );
			for ( var i = 0; i < placeOrderButtons.length; i++ ) {
				var button = placeOrderButtons[i];
				button.disabled = isDisabled;
			}
		}
	}



	/**
	 * Update the global state of steps.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 * @param   Array  data   The updated checkout data.
	 */
	var updateGlobalStepStates = function( _event, data ) {
		updateStepBodyClasses();
		maybeDisablePlaceOrderButton();
	}



	/**
	 * Maybe change visibility status of checkout substep sections.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 * @param   Array  data   The updated checkout data.
	 */
	var maybeChangeSubstepState = function( _event, data ) {
		var substepElements = document.querySelectorAll( _settings.substepSelector );
		for ( var i = 0; i < substepElements.length; i++ ) {
			var substepElement = substepElements[i];
			
			// Handle editable state
			var editableHiddenField = substepElement.querySelector( _settings.substepEditableStateFieldSelector );
			if ( editableHiddenField && 'no' === editableHiddenField.value ) {
				substepElement.setAttribute( _settings.substepEditableStateAttribute, editableHiddenField.value );
			}
			else {
				substepElement.removeAttribute( _settings.substepEditableStateAttribute );
			}

			// Handle visibility state
			var visibilityHiddenField = substepElement.querySelector( _settings.substepVisibleStateFieldSelector );
			if ( visibilityHiddenField && 'no' === visibilityHiddenField.value ) {
				substepElement.setAttribute( _settings.substepVisibleStateAttribute, visibilityHiddenField.value );
			}
			else {
				substepElement.removeAttribute( _settings.substepVisibleStateAttribute );
			}

			// Handle expanded state
			var expandedHiddenField = substepElement.querySelector( _settings.substepExpandedStateFieldSelector );
			if ( expandedHiddenField && 'yes' === expandedHiddenField.value ) {
				var substepElement = expandedHiddenField.closest( _settings.substepSelector );
				expandSubstepEdit( substepElement, true, false );
			}
		}
	}



	/**
	 * Maybe move place order section to order summary for small screens.
	 */
	var maybeMovePlaceOrderSection = function() {
		// Bail if displaying the place order only on the sidebar. In this case there is no need to move the sections.
		if ( document.body.matches( _settings.placeOrderSkipMoveSelector ) ) { return; }

		// Get viewport width
		var viewportWidth = window.innerWidth;

		// Get place order sections
		var placeOrderMain = document.querySelector( _settings.placeOrderSectionMainSelector );
		var placeOrderPlaceholderMain = document.querySelector( _settings.placeOrderPlaceholderMainSelector );
		var placeOrderPlaceholderSidebar = document.querySelector( _settings.placeOrderPlaceholderSidebarSelector );

		// Bail if elements are not found
		if ( ! placeOrderMain || ! placeOrderPlaceholderMain || ! placeOrderPlaceholderSidebar ) { return; }

		// Maybe move to sidebar section
		if ( viewportWidth < 1000 && placeOrderPlaceholderSidebar.parentNode !== placeOrderMain.parentNode ) {
			placeOrderPlaceholderSidebar.parentNode.insertBefore( placeOrderMain, placeOrderPlaceholderSidebar.nextSibling );
		}
		// Maybe move to steps section
		else if ( viewportWidth >= 1000 && placeOrderPlaceholderMain.parentNode !== placeOrderMain.parentNode ) {
			placeOrderPlaceholderMain.parentNode.insertBefore( placeOrderMain, placeOrderPlaceholderMain.nextSibling );
		}
	};



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// NEXT STEP
		if ( e.target.closest( _settings.nextStepButtonSelector ) ) {
			e.preventDefault();
			var step = e.target.closest( _settings.stepSelector );
			maybeProceedNextStep( step );
		}
		// EDIT SUBSTEP
		else if ( e.target.closest( _settings.substepEditButtonSelector ) ) {
			e.preventDefault();
			var substepElement = e.target.closest( _settings.substepSelector );
			expandSubstepEdit( substepElement );
		}
		// SAVE SUBSTEP
		else if ( e.target.closest( _settings.substepSaveButtonSelector ) ) {
			e.preventDefault();
			var substepElement = e.target.closest( _settings.substepSelector );
			maybeSaveSubstep( substepElement );
		}
	};



	/**
	 * Handle keypress event.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// ENTER or SPACE on handler elements
		if ( ( FCUtils.keyboardKeys.ENTER === e.key || FCUtils.keyboardKeys.SPACE === e.key ) && ( e.target.closest( _settings.substepEditButtonSelector ) || e.target.closest( _settings.substepSaveButtonSelector ) ) ) {
			// Simulate click
			handleClick( e );
		}
		// ENTER on form fields.
		// Prevents submit of the checkout form when pressing ENTER on a field inside the checkout form.
		else if ( FCUtils.keyboardKeys.ENTER === e.key && e.target.closest( _settings.checkoutFormSelector ) && e.target.closest( _settings.fieldSubmitFormSelector ) ) {
			e.preventDefault();

			// Maybe validate field
			if ( window.CheckoutValidation ) {
				var field = e.target.closest( _settings.fieldSubmitFormSelector );
				CheckoutValidation.validateField( field );
			}
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Maybe move place order section, and initialize resize observers
		maybeMovePlaceOrderSection();
		if ( window.ResizeObserver ) {
			_resizeObserver = new ResizeObserver( FCUtils.debounce( maybeMovePlaceOrderSection, _settings.placeOrderRefreshRate ) );
			_resizeObserver.observe( document.body );
		}

		// Add event listeners
		window.addEventListener( 'click', handleClick );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', updateGlobalStepStates );
			$( document.body ).on( 'updated_checkout', maybeChangeSubstepState );
			$( document.body ).on( 'updated_checkout', maybeRemoveFragmentsLoadingClass );
		}

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
