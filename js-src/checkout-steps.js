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
		bodyClass: 'wfc-checkout-steps--active',
		activeStepBodyClassPattern: 'wfc-checkout-step--active-{ID}',

		wrapperSelector: '#wfc-wrapper',
		wrapperInsideSelector: '.wfc-inside',
		targetWrapperAttribute: 'data-target',
		
		progressBarSelector: '#wfc-progressbar',
		progressBarStepSelector: '.wfc-progress-bar-step',
		progressBarStepClass: 'wfc-progress-bar-step',
		progressBarStepDoneClass: 'done',
		progressBarStepCurrentClass: 'current',

		stepIdPattern: 'step-{ID}',
		stepIdSelector: '.wfc-progress-bar-step[data-step-index]',
		stepButtonSelector: '.wfc-step-button',
		stepSelector: '.wfc-progress-bar-step[data-step-index="{ID}"]',
		stepIndexAttribute: 'data-step-index',
		stepIdAttribute: 'data-step-id',
		stepNavigationPrevSelector: '.wfc-prev',
		stepNavigationNextSelector: '.wfc-next',

		frameIdAttribute: 'data-frame-id',
		frameIdPattern: 'step-frame-{ID}',
		frameIdSelectorPattern: '#step-frame-{ID}',
		frameSelector: '.wfc-frame[data-step-index="{ID}"]',

		woocommerceInvalidFieldClass: '.woocommerce-invalid',

		editContactSelector: '[data-user-contact-edit]',
		userDataSelector: '[data-user-data-wrapper]',

		topScrollOffset: 100,

		states: {
			ACTIVE: 'active',
			DISABLED: 'disabled',
		}
	}

	var _wfcWrapper,
		_wfcInner,
		_progressBar,
		_frames,
		_steps;



	/**
	 * METHODS
	 */
	
	/**
	 * Get the id of current step.
	 */
	var getFirstStepIndex = function() {
		// Return next available step
		for ( var i = 0; i < _frames.length; i++ ) {
			if ( ! _frames[ i + 1 ].hasAttribute( _settings.states.DISABLED ) ) {
				return i + 1;
			}
		}
	};



	/**
	 * Get the id of current step.
	 */
	var getCurrentStepIndex = function() {
		var currentStep = document.querySelector( '.wfc-progress-bar-step.current' );
		return currentStep ? parseInt( currentStep.getAttribute( _settings.stepIndexAttribute ) ) : null;
	};



	/**
	 * Get the id of previous available step.
	 */
	var getPrevStepId = function() {
		var currentStepIndex = getCurrentStepIndex();

		// Get first enabled step if not step is active
		if ( ! currentStepIndex ) {
			return getFirstStepIndex();
		}

		// Return next available step
		for ( var i = currentStepIndex - 1; i >= 0; i-- ) {
			if ( ! _frames[ i - 1 ].hasAttribute( _settings.states.DISABLED ) ) {
				return currentStepIndex - 1;
			}
		}

		return 1;
	};



	/**
	 * Get the id of next available step.
	 */
	var getNextStepIndex = function() {
		var currentStepIndex = getCurrentStepIndex();

		// Get first enabled step if not step is active
		if ( ! currentStepIndex ) {
			return getFirstStepIndex();
		}

		// Return next available step
		for ( var i = currentStepIndex - 1; i < _frames.length; i++ ) {
			if ( ! _frames[ i + 1 ].hasAttribute( _settings.states.DISABLED ) ) {
				return currentStepIndex + 1;
			}
		}

		return null;
	};



	/**
	 * Clear all step status
	 */
	var clearStepStatus = function() {
		for ( var i = _frames.length - 1; i >= 0; i-- ) {
			// active
			_frames[i].classList.remove( _settings.progressBarStepCurrentClass );
			_steps[i].classList.remove( _settings.progressBarStepCurrentClass );
			document.body.classList.remove( _settings.activeStepBodyClassPattern.replace( '{ID}', _frames[i].getAttribute( _settings.frameIdAttribute ) ) );
			
			// done
			if ( ! _steps[i].hasAttribute( _settings.states.DISABLED ) ) {
				_steps[i].classList.remove( _settings.progressBarStepDoneClass );
			}
		}
	};



	/**
	 * Mark steps as done.
	 */
	var markStepsDone = function() {
		var currentStepIndex = getCurrentStepIndex();
		for ( var i = _steps.length - 1; i >= 0; i-- ) {
			var stepId = _steps[i].getAttribute( _settings.stepIndexAttribute );
			if ( ! _steps[i].hasAttribute( _settings.states.DISABLED ) && stepId < currentStepIndex ) {
				_steps[i].classList.add( _settings.progressBarStepDoneClass );
			}
		}
	};



	/**
	 * Mark step as current.
	 */
	var markStepActive = function( step, frame, scrollToElement ) {
		// Set step as current
		step.classList.add( _settings.progressBarStepCurrentClass );
		frame.classList.add( _settings.progressBarStepCurrentClass );
		document.body.classList.add( _settings.activeStepBodyClassPattern.replace( '{ID}', frame.getAttribute( _settings.frameIdAttribute ) ) );

		// TODO: Better animation handling with slide left/right depending on the position of the step
		
		// Maybe scroll step into view
		if ( scrollToElement ) {
			if ( frame ) {
				scrollTo( frame );
			}
			else {
				scrollTo( _progressBar );
			}
		}
	};



	/**
	 * Change current step.
	 */
	var setCurrentStep = function( stepIndex, scrollToElement ) {
		var currentStepIndex = getCurrentStepIndex();

		// Bail if is current active step
		if ( currentStepIndex && currentStepIndex == stepIndex ) { return; }
		
		var	step = document.querySelector( _settings.stepSelector.replace( '{ID}', stepIndex ) ),
			frame = document.querySelector( _settings.frameSelector.replace( '{ID}', stepIndex ) );

		// Clear step status, mark as active and done
		clearStepStatus();
		markStepActive( step, frame, scrollToElement );
		markStepsDone();
	};



	/**
	 * Create progress bar steps.
	 */
	var initSteps = function() {

		// Clear progress bar placeholders
		while ( _progressBar.firstChild ) {
			_progressBar.removeChild( _progressBar.firstChild );
		}

		// Get frames
		_frames = document.querySelectorAll( '.wfc-frame' );

		// Add ID to each frame and steps on progress bar
		for ( var i = _frames.length - 1; i >= 0; i-- ) {
			var index = i + 1,
				stepId = _frames[i].getAttribute( _settings.stepIdAttribute ),
				label = _frames[i].getAttribute( 'data-label' ),
				step = document.createElement( 'div' );
			
			// Make sure stepId has a value
			if ( stepId == null || _frames[i].getAttribute( _settings.stepIdAttribute ) == '' ) { stepId = index; };

			step.classList.add( _settings.progressBarStepClass );
			step.setAttribute( 'id', _settings.stepIdPattern.replace( '{ID}', stepId ) );
			step.setAttribute( _settings.stepIndexAttribute, index );
			step.textContent = label;

			if ( _frames[i].hasAttribute( _settings.states.DISABLED ) ) {
				step.setAttribute( _settings.states.DISABLED , _settings.states.DISABLED );
			}

			if ( _frames[i].classList.contains( _settings.progressBarStepDoneClass ) ) {
				step.classList.add( _settings.progressBarStepDoneClass );
			}

			_progressBar.insertBefore( step, _progressBar.firstChild );
			
			_frames[i].setAttribute( 'id', _settings.frameIdPattern.replace( '{ID}', stepId ) );
			_frames[i].setAttribute( _settings.frameIdAttribute, stepId );
			_frames[i].setAttribute( _settings.stepIndexAttribute, index );
		}

		// Get steps
		_steps = document.querySelectorAll( _settings.progressBarStepSelector );

		// Show first available step
		setCurrentStep( getNextStepIndex(), false );
	};



	/**
	 * Get element offset values from page limits
	 * 
	 * @see https://stackoverflow.com/a/442474/5732235
	 */
	var getOffset = function( el ) {
		var _x = 0;
		var _y = 0;
		while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
			_x += el.offsetLeft - el.scrollLeft;
			_y += el.offsetTop - el.scrollTop;
			el = el.offsetParent;
		}
		return { top: _y, left: _x };
	}



	/**
	 * Scroll element into viewport.
	 * @param  {Element} element Element to get position and scroll viewport to.
	 */
	var scrollTo = function( element ) {
		window.scroll( {
			behavior: 'smooth',
			left: 0,
			top: getOffset( element ).top - _settings.topScrollOffset
		} );
	};


	
	/**
	 * Handle clicks on steps progress bar.
	 */
	var handleStepClick = function( e ) {
		e.preventDefault();
		var step = e.target.closest( _settings.stepIdSelector );
		setCurrentStep( step.getAttribute( _settings.stepIndexAttribute ), true );
	};



	/**
	 * Handle clicks on buttons that lead to a specific step.
	 */
	var handleStepButtonClick = function( e ) {
		e.preventDefault();
		var button = e.target.closest( _settings.stepButtonSelector );
		setCurrentStep( button.getAttribute( _settings.stepIndexAttribute ), true );
	};



	/**
	 * Handle clicks on next step buttons.
	 */
	var handleNextStepClick = function( e ) {
		e.preventDefault();

		// Validate step fields
		if ( window.CheckoutValidation ) {
			
			var currentStepIndex = getCurrentStepIndex(),
				frame = _wfcWrapper.querySelector( _settings.frameSelector.replace( '{ID}', currentStepIndex ) );
			
			// Bail if not all fields valid and stay in the same step
			if ( ! window.CheckoutValidation.validateAllFields( frame ) ) {
				var element = frame.querySelector( _settings.woocommerceInvalidFieldClass );
				scrollTo( element );
				return;
			}
		}

		// Go to next step
		setCurrentStep( getNextStepIndex(), true );
	};



	/**
	 * Handle clicks on previous step buttons.
	 */
	var handlePrevStepClick = function( e ) {
		e.preventDefault();

		// Go to prev step
		setCurrentStep( getPrevStepId(), true );
	};



	/**
	 * Show contact details form for editing
	 */
	 var removeUserData = function() {
        var userDataWrapper = document.querySelector( _settings.userDataSelector );
        if ( userDataWrapper ) {
            userDataWrapper.parentNode.removeChild( userDataWrapper );
        }
    }



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		if ( e.target.closest( _settings.stepIdSelector + ':not([disabled])' ) ) {
			handleStepClick( e );
		}
		else if ( e.target.closest( _settings.stepButtonSelector + ':not([disabled])' ) ) {
			handleStepButtonClick( e );
		}
		else if ( e.target.closest( _settings.stepNavigationPrevSelector + ':not([disabled])' ) ) {
			handlePrevStepClick( e );
		}
		else if ( e.target.closest( _settings.stepNavigationNextSelector + ':not([disabled])' ) ) {
			handleNextStepClick( e );
		}
		else if ( e.target.closest( _settings.editContactSelector ) ) {
			e.preventDefault();
			removeUserData();
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.refreshSteps = function() {
		_wfcWrapper = document.querySelector( _settings.wrapperSelector );
		_wfcInner = document.querySelector( _settings.wrapperInsideSelector );
		_progressBar = document.querySelector( _settings.progressBarSelector );

		// Bail if elements not present
		if ( ! _wfcWrapper || ! _wfcInner || ! _progressBar ) { return; }

		initSteps();
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) return;

		_publicMethods.refreshSteps();
		
		// Add event listeners
		window.addEventListener( 'click', handleClick );
		$( document ).on( 'load_ajax_content_done', _publicMethods.refreshSteps );

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
