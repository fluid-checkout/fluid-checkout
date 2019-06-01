/**
 * Manage checkout steps state.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */

(function( $ ){

  'use strict';

  /**
   * VARIABLES
   */
  var _initClass      = 'js-fluid-checkout-steps',
      _wfcWrapper,
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
    for (var i = 0; i < _frames.length; i++) {
      if ( ! _frames[ i + 1 ].hasAttribute( 'disabled' ) ) {
        return i + 1;
      }
    }
  };



  /**
	 * Get the id of current step.
	 */
	var getCurrentStepId = function() {
		var currentStep = document.querySelector( '.wfc-step.current' );
		return currentStep ? parseInt( currentStep.getAttribute( 'data-step-id' ) ) : null;
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
      if ( ! _frames[ i - 1 ].hasAttribute( 'disabled' ) ) {
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
    for (var i = currentStepId - 1; i < _frames.length; i++) {
      if ( ! _frames[ i + 1 ].hasAttribute('disabled') ) {
        return currentStepId + 1;
      }
    }

    return null;
  };



	/**
	 * Clear all step status
	 */
	var clearStepStatus = function() {
		for (var i = _frames.length - 1; i >= 0; i--) {
			// active
			_frames[i].classList.remove( 'current' );
			_steps[i].classList.remove( 'current' );
			
      // done
			if ( ! _steps[i].hasAttribute( 'disabled' ) ) {
        _steps[i].classList.remove( 'done' );
      }
		}
	};



	/**
	 * Mark steps as done.
	 */
	var markStepsDone = function() {
		var currentStepId = getCurrentStepId();
		for (var i = _steps.length - 1; i >= 0; i--) {
			var stepId = _steps[i].getAttribute( 'data-step-id' );
			if ( ! _steps[i].hasAttribute( 'disabled' ) && stepId < currentStepId ) {
				_steps[i].classList.add( 'done' );
			}
		}
	};



	/**
	 * Mark step as current.
	 */
	var markStepActive = function( step, frame, scrollToElement ) {
		// Set step as current
		step.classList.add( 'current' );
		frame.classList.add( 'current' );

    // TODO: Better animation handling with slide left/right depending on the position of the step
    
    // Maybe scroll step into view
    if ( scrollToElement ) {
      if ( _progressBar ) {
        scrollTo( _progressBar );
      }
      else {
        scrollTo( frame );
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
    // Bail if inner element not present
    if ( ! _wfcInner ) { return; }

    // Clear progress bar placeholders
    while ( _progressBar.firstChild ) {
      _progressBar.removeChild( _progressBar.firstChild );
    }

    // Get frames
		_frames = _wfcInner.querySelectorAll( '.wfc-frame' );

		// Add ID to each frame and steps to progress bar
		for (var i = _frames.length - 1; i >= 0; i--) {
			var stepId = i + 1,
					label = _frames[i].getAttribute( 'data-label' ),
					step = document.createElement( 'div' );

			step.classList.add('wfc-step');
			step.setAttribute('id', 'step-' + stepId);
			step.setAttribute('data-step-id', stepId);
      step.textContent = label;

      if ( _frames[i].hasAttribute('disabled') ) {
        step.setAttribute('disabled', 'disabled');
      }

      if ( _frames[i].classList.contains('done') ) {
        step.classList.add('done');
      }

			_progressBar.insertBefore(step, _progressBar.firstChild);
			
			_frames[i].setAttribute('id', 'frame-' + stepId);
			_frames[i].setAttribute('data-frame-id', stepId);
		}

		// Get steps
		_steps = _wfcInner.querySelectorAll('.wfc-step');

		// Show first available step
		setCurrentStep( getNextStepId(), false );
	};



  /**
   * Scroll element into viewport.
   * @param  {Element} element Element to get position and scroll viewport to.
   */
  var scrollTo = function( element ) {
    window.scroll({
      behavior: 'smooth',
      left: 0,
      top: element.offsetTop
    });
  };


	
	/**
	 * Handle clicks on steps progress bar.
	 */
	var handleStepClick = function( e ) {
    e.preventDefault();
    var step = e.target.closest( '[data-step-id]' );
    setCurrentStep( step.getAttribute( 'data-step-id' ), true );
	};



	/**
	 * Handle clicks on next step buttons.
	 */
	var handleNextStepClick = function( e ) {
    e.preventDefault();

    // Validate step fields
    if ( window.fluidCheckoutValidation ) {
      var currentStepId = getCurrentStepId(),
          frame = _wfcWrapper.querySelector( '#frame-' + currentStepId );
      
      // Bail if not all fields valid and stay in the same step
      if ( ! window.fluidCheckoutValidation.validate_all_fields( frame ) ) {
        var element = frame.querySelector( '.woocommerce-invalid' );
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
    if ( e.target.closest( '[data-step-id]:not([disabled])' ) ) {
      handleStepClick( e );
    }
    else if ( e.target.closest( '.wfc-prev:not([disabled])' ) ) {
      handlePrevStepClick( e );
    }
    else if ( e.target.closest( '.wfc-next:not([disabled])' ) ) {
    	handleNextStepClick( e );
    }
  };



  /**
   * Set plugin as active.
   */
  var setPluginActive = function() {
  	_wfcWrapper.classList.add('active');
  };



  /**
   * Initialize component and set related handlers.
   */
  var init = function() {
    _wfcWrapper = document.querySelector('#wfc-wrapper');
    _wfcInner = document.querySelector('.wfc-inside');
    _progressBar = document.querySelector('#wfc-progressbar');

    // Bail if elements not present
    if ( ! _wfcWrapper || ! _wfcInner || ! _progressBar ) { return; }

  	initSteps();
  	setPluginActive();

    // Add init class
    document.body.classList.add( _initClass );
  };



  // Add event listeners
  window.addEventListener( 'click', handleClick );
  window.addEventListener( 'load', init );

  // Run on checkout or cart changes
  $( document ).on( 'load_ajax_content_done', init );



})( jQuery );
