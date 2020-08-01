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

		wrapperSelector: '#wfc-wrapper',
		wrapperInsideSelector: '.wfc-inside',
		
		progressBarSelector: '#wfc-progressbar',
		progressBarStepSelector: '.wfc-progress-bar-step',
		progressBarStepClass: 'wfc-progress-bar-step',
		progressBarStepDoneClass: 'done',
		progressBarStepCurrentClass: 'current',

		stepIdSelector: '[data-step-id]',
		stepIdAttribute: 'data-step-id',
		stepNavigationPrevSelector: '.wfc-prev',
		stepNavigationNextSelector: '.wfc-next',

		frameIdAttribute: 'data-frame-id',

		woocommerceInvalidFieldClass: '.woocommerce-invalid',

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
	var getFirstStepId = function() {
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
	var getCurrentStepId = function() {
		var currentStep = document.querySelector( '.wfc-progress-bar-step.current' );
		return currentStep ? parseInt( currentStep.getAttribute( _settings.stepIdAttribute ) ) : null;
	};



	/**
	 * Get the id of previous available step.
	 */
	var getPrevStepId = function() {
		var currentStepId = getCurrentStepId();


		// Get first enabled step if not step is active
		if ( ! currentStepId ) {
			return getFirstStepId();
		}

		// Return next available step
		for ( var i = currentStepId - 1; i >= 0; i-- ) {
			if ( ! _frames[ i - 1 ].hasAttribute( _settings.states.DISABLED ) ) {
				return currentStepId - 1;
			}
		}

		return 1;
	};



	/**
	 * Get the id of next available step.
	 */
	var getNextStepId = function() {
		var currentStepId = getCurrentStepId();

		// Get first enabled step if not step is active
		if ( ! currentStepId ) {
			return getFirstStepId();
		}

		// Return next available step
		for ( var i = currentStepId - 1; i < _frames.length; i++ ) {
			if ( ! _frames[ i + 1 ].hasAttribute( _settings.states.DISABLED ) ) {
				return currentStepId + 1;
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
		var currentStepId = getCurrentStepId();
		for ( var i = _steps.length - 1; i >= 0; i-- ) {
			var stepId = _steps[i].getAttribute( _settings.stepIdAttribute );
			if ( ! _steps[i].hasAttribute( _settings.states.DISABLED ) && stepId < currentStepId ) {
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
	var setCurrentStep = function( stepId, scrollToElement ) {
		var currentStepId = getCurrentStepId();

		// Bail if is current active step
		if ( currentStepId && currentStepId == stepId ) { return; }
		
		var	step = document.querySelector( '#step-' + stepId ),
			frame = document.querySelector( '#frame-' + stepId );
		
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
			var stepId = i + 1,
					label = _frames[i].getAttribute( 'data-label' ),
					step = document.createElement( 'div' );

			step.classList.add( _settings.progressBarStepClass );
			step.setAttribute( 'id', 'step-' + stepId );
			step.setAttribute( _settings.stepIdAttribute, stepId );
			step.textContent = label;

			if ( _frames[i].hasAttribute( _settings.states.DISABLED ) ) {
				step.setAttribute( _settings.states.DISABLED , _settings.states.DISABLED );
			}

			if ( _frames[i].classList.contains( _settings.progressBarStepDoneClass ) ) {
				step.classList.add( _settings.progressBarStepDoneClass );
			}

			_progressBar.insertBefore( step, _progressBar.firstChild );
			
			_frames[i].setAttribute( 'id', 'frame-' + stepId );
			_frames[i].setAttribute( _settings.frameIdAttribute, stepId );
		}

		// Get steps
		_steps = document.querySelectorAll( _settings.progressBarStepSelector );

		// Show first available step
		setCurrentStep( getNextStepId(), false );
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
		setCurrentStep( step.getAttribute( _settings.stepIdAttribute ), true );
	};



	/**
	 * Handle clicks on next step buttons.
	 */
	var handleNextStepClick = function( e ) {
		e.preventDefault();

		// Validate step fields
		if ( window.CheckoutValidation ) {
			var currentStepId = getCurrentStepId(),
					frame = _wfcWrapper.querySelector( '#frame-' + currentStepId );
			
			// Bail if not all fields valid and stay in the same step
			if ( ! window.CheckoutValidation.validateAllFields( frame ) ) {
				var element = frame.querySelector( _settings.woocommerceInvalidFieldClass );
				scrollTo( element );
				return;
			}
		}

		// Go to next step
		setCurrentStep( getNextStepId(), true );
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
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		if ( e.target.closest( _settings.stepIdSelector + ':not([disabled])' ) ) {
			handleStepClick( e );
		}
		else if ( e.target.closest( _settings.stepNavigationPrevSelector + ':not([disabled])' ) ) {
			handlePrevStepClick( e );
		}
		else if ( e.target.closest( _settings.stepNavigationNextSelector + ':not([disabled])' ) ) {
			handleNextStepClick( e );
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
