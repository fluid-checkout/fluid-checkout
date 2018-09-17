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
  var wfcWrapper,
  		wfcInner,
  		progressBar,
  		frames,
  		steps;



	/**
	 * METHODS
	 */
	
	/**
   * Get the id of current step.
   */
  var getFirstStepId = function() {
    // Return next available step
    for (var i = 0; i < frames.length; i++) {
      if ( ! frames[i].hasAttribute('disabled') ) {
        return i + 1;
      }
    }
  };



  /**
	 * Get the id of current step.
	 */
	var getCurrentStepId = function() {
		var currentStep = document.querySelector('.wfc-step.current');
		return currentStep ? parseInt( currentStep.getAttribute('data-id') ) : null;
	};



  /**
   * Get the id of next available step.
   */
  var getNextStepId = function( ) {
    var currentStepId = getCurrentStepId();

    // Get first enabled step
    if ( ! currentStepId ) {
      return getFirstStepId();
    }

    // Return next available step
    for (var i = currentStepId; i < frames.length; i++) {
      if ( ! frames[i].hasAttribute('disabled') ) {
        return currentStepId + 1;
      }
    }

    return null;
  };



	/**
	 * Clear all step status
	 */
	var clearStepStatus = function() {
		for (var i = frames.length - 1; i >= 0; i--) {
			// active
			frames[i].classList.remove('current');
			steps[i].classList.remove('current');
			// done
			if ( ! steps[i].hasAttribute('disabled') ) {
        steps[i].classList.remove('done');
      }
		}
	};



	/**
	 * Mark steps as done.
	 */
	var markAllStepDone = function() {
		var currentStepId = getCurrentStepId();
		for (var i = steps.length - 1; i >= 0; i--) {
			var stepId = steps[i].getAttribute('data-id');
			if ( ! steps[i].hasAttribute('disabled') && stepId < currentStepId ) {
				steps[i].classList.add('done');
			}
		}
	};



	/**
	 * Mark step as current.
	 */
	var markStepActive = function( step, frame ) {
		// Set step as current
		step.classList.add('current');
		frame.classList.add('current');

    // TODO: Better animation handling with slide left/right depending on the position of the step
	};



	/**
	 * Change current step.
	 */
	var setCurrentStep = function( stepId ) {
		var currentStepId = getCurrentStepId();

    // Bail if is current active step
    if ( currentStepId && currentStepId == stepId ) { return; };
		
    var	step = document.querySelector('#step-'+stepId),
				frame = document.querySelector('#frame-'+stepId);
		
		// Clear step status, mark as active and done
		clearStepStatus();
		markStepActive( step, frame );
		markAllStepDone();
	};



	/**
	 * Create progress bar steps.
	 */
	var initSteps = function() {
		// Bail if inner element not present
    if ( ! wfcInner ) { return; }

    // Get frames
		frames = wfcInner.querySelectorAll('.wfc-frame');

		// Add ID to each frame and steps to progress bar
		for (var i = frames.length - 1; i >= 0; i--) {
			var stepId = i + 1,
					label = $(frames[i]).data('label'),
					step = document.createElement('div');

			step.classList.add('wfc-step');
			step.setAttribute('id', 'step-' + stepId);
			step.setAttribute('data-id', stepId);
      step.textContent = label;

      if ( frames[i].hasAttribute('disabled') ) {
        step.setAttribute('disabled', 'disabled');
      }

      if ( frames[i].classList.contains('done') ) {
        step.classList.add('done');
      }

			progressBar.insertBefore(step, progressBar.firstChild);
			
			frames[i].setAttribute('id', 'frame-' + stepId);
			frames[i].setAttribute('data-id', stepId);
		}

		// Get steps
		steps = wfcInner.querySelectorAll('.wfc-step');

		// Show first available step
		setCurrentStep( getNextStepId() );
	};


	
	/**
	 * Handle clicks on steps progress bar.
	 */
	var handleStepClick = function( e ) {
		e.preventDefault();
    var step = e.target.closest( '.wfc-step' );
    setCurrentStep( step.getAttribute( 'data-id' ) );
	};



	/**
	 * Handle clicks on next step buttons.
	 */
	var handleNextStepClick = function( e ) {
		e.preventDefault();
    setCurrentStep( getNextStepId() );
	};



	/**
   * Handle document clicks and route to the appropriate function.
   */
  var handleClick = function( e ) {
    if ( e.target.closest( '.wfc-step:not([disabled])' ) ) {
      handleStepClick( e );
    }
    else if ( e.target.closest( '.wfc-next:not([disabled])' ) ) {
    	handleNextStepClick( e );
    }
  };


  /**
   * Set plugin as active.
   */
  var setPluginActive = function() {
  	wfcWrapper.classList.add('active');
  };


  /**
   * Initialize component and set related handlers.
   */
  var init = function() {
    wfcWrapper = document.querySelector('#wfc-wrapper');
    wfcInner = document.querySelector('.wfc-inside');
    progressBar = document.querySelector('#wfc-progressbar');

    // Bail if elements not present
    if ( ! wfcWrapper || ! wfcInner || ! progressBar ) { return; }

  	initSteps();
  	setPluginActive();
  };



  // Add event listeners
  window.addEventListener( 'click', handleClick );
  window.addEventListener( 'load', init );

  // Run on checkout or cart changes
  $(document).on( 'load_ajax_content_done', function() {
		init();
	});



})( jQuery );
