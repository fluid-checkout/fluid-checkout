/**
 * Add products to cart via ajax.
 *
 * DEPENDS ON:
 * - jQuery // Interact with WooCommerce events
 */

(function( $ ){

  'use strict';

  /**
   * VARIABLES
   */
  var wfcWrapper = document.querySelector('#wfc-wrapper'),
  		wfcInner = document.querySelector('.wfc-inside'),
  		progressBar = document.querySelector('#wfc-progressbar'),
  		frames,
  		steps;

	/**
	 * METHODS
	 */
	
	/**
	 * Get the id of current active step.
	 */
	var getActiveStepId = function() {
		var activeStep = document.querySelector('.wfc-step.active');
		return activeStep ? parseInt( activeStep.getAttribute('data-id') ) : null;
	};



	/**
	 * Clear all step status
	 */
	var clearStepStatus = function() {
		for (var i = frames.length - 1; i >= 0; i--) {
			// active
			frames[i].classList.remove('active');
			steps[i].classList.remove('active');
			// done
			steps[i].classList.remove('done');
		}
	};



	/**
	 * Mark steps as done.
	 */
	var markAllStepDone = function() {
		var activeStepId = getActiveStepId();
		for (var i = steps.length - 1; i >= 0; i--) {
			var stepId = steps[i].getAttribute('data-id');
			if ( stepId < activeStepId ) {
				steps[i].classList.add('done');
			}
		}
	};



	/**
	 * Mark step as active.
	 */
	var markStepActive = function( step, frame ) {
		// Set step as active
		step.classList.add('active');
		frame.classList.add('active');
	};



	/**
	 * Change active step.
	 */
	var showStep = function( stepId ) {
		var activeStepId = getActiveStepId(),
				step = document.querySelector('#step-'+stepId),
				frame = document.querySelector('#frame-'+stepId);

		// Bail if is current active step
		if ( activeStepId && activeStepId == stepId ) { return; };
		
		// Clear step status, mark as active and done
		clearStepStatus();
		markStepActive( step, frame );
		markAllStepDone();
	};



	/**
	 * Create progress bar steps.
	 */
	var initSteps = function() {
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

			progressBar.insertBefore(step, progressBar.firstChild);
			
			frames[i].setAttribute('id', 'frame-' + stepId);
			frames[i].setAttribute('data-id', stepId);
		}

		// Get steps
		steps = wfcInner.querySelectorAll('.wfc-step');

		// Show first step
		showStep( 1 );
	};


	
	/**
	 * Handle clicks on steps progress bar.
	 */
	var handleStepClick = function( e ) {
		e.preventDefault();
    var step = e.target.closest( '.wfc-step' );
    showStep( step.getAttribute( 'data-id' ) );
	};



	/**
	 * Handle clicks on next step buttons.
	 */
	var handleNextStepClick = function( e ) {
		e.preventDefault();
    var step = e.target.closest( '.wfc-next' ),
    		activeStepId = getActiveStepId(),
    		nextStepId = activeStepId + 1;
    showStep( nextStepId );
	};



	/**
   * Handle document clicks and route to the appropriate function.
   */
  var handleClick = function( e ) {
    if ( e.target.closest( '.wfc-step' ) ) {
      handleStepClick( e );
    }
    else if ( e.target.closest( '.wfc-next' ) ) {
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
