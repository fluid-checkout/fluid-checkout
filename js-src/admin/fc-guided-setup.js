/**
 * Shared admin guided setup (Fluid Checkout Lite).
 *
 * Plugin-specific settings (AJAX actions, selectors, i18n) are passed via init settings.
 * Requires `guidedSetupId` (slug). Extension hooks: `isContinueButtonEnabled`, `onStepVisible`,
 * `onBeforeContinue`, `onStepProcessSuccess`.
 *
 * DEPENDS ON:
 * - FCUtils // Settings merge and keyboard constants
 * - CollapsibleBlock // Sub-step expand/collapse UI
 */
( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCGuidedSetup = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		guidedSetupContainerSelector:    '.fc-guided-setup',
		stepsWrapperSelector:            '.fc-setup-steps-wrapper',
		cardSelector:                    '.fc-setup-card',
		submitWrapperSelector:           '.fc-setup-submit',
		formSelector:                    'form',
		spinnerSelector:                 '.spinner',

		stepSelector:                    '.fc-setup-step',
		stepNameSelector:                '.fc-setup-step__name',
		stepIndexSelector:               '.fc-setup-step__index',
		stepNavSelector:                 '[data-step-nav]',
		backButtonSelector:              '[data-setup-step-back]',
		continueButtonSelector:          '.fc-setup-submit .button-primary[data-step]',
		continueButtonInnerSelector:     '.button-primary[data-step]',

		subStepSelector:                 '[data-setup-sub-step]',
		subStepToggleSelector:           '[data-setup-sub-step-toggle]',
		subStepSkipSelector:             '[data-setup-sub-step-skip]',
		subStepCompleteSelector:         '[data-setup-sub-step-complete]',
		subStepIncompleteSelector:       '[data-setup-sub-step-incomplete]',
		subStepCollapsibleHandlerSelector: '[data-setup-sub-step] [data-collapsible-handler]',
		subStepHeaderSelector:           '.fc-setup-collapsible__header',
		subStepActionsSelector:          '.fc-setup-sub-step__actions',
		subStepSubstepActionsSelector:   '.fc-setup-sub-step__substep-actions',
		collapsibleContentSelector:      '[data-collapsible-content]',
		collapsibleHandlerSelector:      '[data-collapsible-handler]',
		collapsibleContentInnerSelector: '.collapsible-content__inner',

		stepImagePreviewSelector:        '.fc-setup-step-image__preview, .fc-setup-step-image__thumbnail',
		imageModalSelector:              '[data-guided-setup-image-modal]',
		imageModalImageSelector:         '.fc-setup-image-modal__image',
		imageModalCloseSelector:         '[data-guided-setup-image-modal-close]',
		imageModalCloseButtonSelector:   '.fc-setup-image-modal__close',
		imageThumbnailTriggerSelector:   '[data-guided-setup-image-full]',

		cardVisibleClass:                'fc-setup-card--visible',
		stepDoneClass:                   'fc-setup-step--done',
		stepDoneNextReachedClass:        'fc-setup-step--done-next-reached',
		stepCurrentClass:                'fc-setup-step--current',
		stepCurrentNextReachedClass:     'fc-setup-step--current-next-reached',
		stepIndexLinkClass:              'fc-setup-step__index',
		subStepCompleteClass:            'fc-setup-sub-step--complete',
		isExpandedClass:                 'is-expanded',
		spinnerActiveClass:              'is-active',
		continueButtonLoadingClass:      'fc-setup-is-loading',
		continueButtonDisabledClass:     'fc-setup-disabled',
		imageModalOpenBodyClass:         'fc-setup-image-modal-open',
		subStepActionsClass:             'fc-setup-sub-step__actions',
		subStepSkipButtonClass:          'button button-link fc-setup-skip',
		subStepToggleButtonClass:        'button button-secondary fc-setup-sub-step__toggle',
		subStepSubstepActionsClass:      'fc-setup-sub-step__substep-actions',
		subStepIncompleteButtonClass:    'button button-secondary fc-setup-sub-step__incomplete',
		subStepCompleteButtonClass:      'button button-primary fc-setup-sub-step__complete',

		nonceFieldId:                    'fc_guided_setup_token',
		nonceParamName:                  'fc_guided_setup_token',

		stepSlugAttribute:               'data-step-slug',
		stepLabelAttribute:              'data-step-label',
		stepNavAttribute:                'data-step-nav',
		subStepStepSlugAttribute:        'data-setup-step-slug',
		subStepIdAttribute:              'data-setup-sub-step-id',
		stepAttribute:                   'data-step',
		requiresTestAttribute:           'data-requires-test',
		copyTextAttribute:               'data-copy-text',
		fallbackSrcAttribute:            'data-fallback-src',
		imageFullAttribute:              'data-guided-setup-image-full',
		imageAltAttribute:               'data-guided-setup-image-alt',
		subStepSkipAttribute:            'data-setup-sub-step-skip',
		subStepToggleAttribute:          'data-setup-sub-step-toggle',
		subStepCompleteAttribute:        'data-setup-sub-step-complete',
		subStepIncompleteAttribute:      'data-setup-sub-step-incomplete',
		ariaExpandedAttribute:           'aria-expanded',
		ariaLabelAttribute:              'aria-label',
		ariaHiddenAttribute:             'aria-hidden',
		hiddenAttribute:                 'hidden',
		readonlyAttribute:               'readonly',

		stepCardAttribute:               'data-fc-setup-step',

		currentStep:                     '',
		currentStepIndex:                0,
		maxReachedStepIndex:             0,

		guidedSetupId:                   '',

		ajaxUrl:                         '',
		ajaxStepActionPrefix:            '',
		ajaxSubStepAction:               '',

		steps:                           [],
		subSteps:                        {},
		subStepProgress:                 {},

		i18n: {
			subStepsRequired:      'Please complete all sub-steps before continuing.',
			subStepExpand:         'Expand',
			subStepCollapse:       'Collapse',
			subStepSkip:           'Skip',
			subStepMarkComplete:   'Mark as complete',
			subStepMarkIncomplete: 'Mark as incomplete',
			backToPreviousStep:    'Back to previous step',
		},
	};

	var _guidedSetupContainer = null;
	var _nonceField = null;
	var _currentStepSlug = '';
	var _imageModal = null;
	var _imageModalImage = null;
	var _lastFocusedElement = null;
	var _maxReachedStepIndex = 0;
	var _subStepProgress = { };



	/**
	 * METHODS
	 */



	/**
	 * Get the guided setup container element.
	 *
	 * @return  Element|null
	 */
	var getWizardContainer = function() {
		return _guidedSetupContainer;
	};



	/**
	 * Get the guided setup nonce field value.
	 *
	 * @return  string
	 */
	var getNonceValue = function() {
		return _nonceField ? _nonceField.value : '';
	};

	/**
	 * Get the AJAX nonce parameter name.
	 *
	 * @return  string
	 */
	var getNonceParamName = function() {
		return _settings.nonceParamName || _settings.nonceFieldId || 'fc_guided_setup_token';
	};



	/**
	 * Notify extensions when a step becomes visible.
	 *
	 * @param   string   stepSlug  Step slug.
	 * @param   Element  card      Step card element.
	 */
	var maybeNotifyStepVisible = function( stepSlug, card ) {
		if ( 'function' === typeof _settings.onStepVisible ) {
			_settings.onStepVisible( stepSlug, card );
		}
	};



	/**
	 * Whether the continue button should be enabled for a step.
	 *
	 * @param   string   stepSlug        Step slug.
	 * @param   Element  card            Step card element.
	 * @param   Element  submitWrapper   Submit wrapper element.
	 *
	 * @return  boolean
	 */
	var isContinueButtonEnabledForStep = function( stepSlug, card, submitWrapper ) {
		if ( 'function' === typeof _settings.isContinueButtonEnabled ) {
			var enabled = _settings.isContinueButtonEnabled( stepSlug, card, submitWrapper );

			if ( enabled !== null && enabled !== undefined ) {
				return !! enabled;
			}
		}

		return isGuidedSetupStepComplete( stepSlug );
	};


	/**
	 * Get a guided setup card element by step slug.
	 *
	 * @param   string  stepSlug  Step slug.
	 *
	 * @return  Element|null
	 */
	var getCardForStep = function( stepSlug ) {
		// Get container element
		var container = getWizardContainer();
		var stepAttribute = _settings.stepCardAttribute || 'data-fc-setup-step';

		// Bail if container or step slug is not available
		if ( ! container || ! stepSlug ) {
			return null;
		}

		return container.querySelector( '[' + stepAttribute + '="' + stepSlug + '"]' );
	};



	/**
	 * Get the index of a guided setup step slug.
	 *
	 * @param   string  stepSlug  Step slug.
	 *
	 * @return  number
	 */
	var getStepIndex = function( stepSlug ) {
		// Get steps list from settings
		var steps = _settings.steps || [];

		return steps.indexOf( stepSlug );
	};



	/**
	 * Get a guided setup step slug by index.
	 *
	 * @param   number  stepIndex  Step index.
	 *
	 * @return  string
	 */
	var getStepSlugByIndex = function( stepIndex ) {
		var steps = _settings.steps || [];

		// Bail if step index is out of bounds
		if ( stepIndex < 0 || stepIndex >= steps.length ) {
			return '';
		}

		return steps[ stepIndex ];
	};



	/**
	 * Get the furthest guided setup step index reached.
	 *
	 * @return  int
	 */
	var getMaxReachedStepIndex = function() {
		return _maxReachedStepIndex;
	};



	/**
	 * Update the furthest guided setup step index reached.
	 *
	 * @param   int  stepIndex  Step index.
	 */
	var setMaxReachedStepIndex = function( stepIndex ) {
		// Maybe update max reached index when advancing to a new step
		if ( stepIndex >= 0 && stepIndex > _maxReachedStepIndex ) {
			_maxReachedStepIndex = stepIndex;
		}
	};



	/**
	 * Reset the furthest guided setup step index reached.
	 *
	 * @param   int  stepIndex  Step index.
	 */
	var resetMaxReachedStepIndex = function( stepIndex ) {
		// Set max reached index when reverting progress
		if ( stepIndex >= 0 ) {
			_maxReachedStepIndex = stepIndex;
		}
	};



	/**
	 * Get the step slug associated with a guided setup card element.
	 *
	 * @param   Element  card  Guided setup card element.
	 *
	 * @return  string
	 */
	var getStepSlugFromCard = function( card ) {
		// Bail if card element not provided
		if ( ! card ) {
			return '';
		}

		var stepAttribute = _settings.stepCardAttribute || 'data-fc-setup-step';

		// Iterate through step card class map to find a matching slug
		if ( card.hasAttribute( stepAttribute ) ) {
			return card.getAttribute( stepAttribute );
		}

		return '';
	};



	/**
	 * Update the step progress bar to match a step slug.
	 *
	 * @param   string  viewedStepSlug  Step slug currently being viewed.
	 */
	var updateStepProgress = function( viewedStepSlug ) {
		// Initialize variables
		var container = getWizardContainer();
		var stepItems = container.querySelectorAll( _settings.stepSelector );
		var viewedIndex = getStepIndex( viewedStepSlug );
		var maxReachedIndex = getMaxReachedStepIndex();

		// Bail if viewed step index is invalid
		if ( viewedIndex < 0 ) {
			return;
		}

		// Iterate through step items
		for ( var i = 0; i < stepItems.length; i++ ) {
			var stepItem = stepItems[ i ];
			var itemSlug = stepItem.getAttribute( _settings.stepSlugAttribute );
			var stepName = stepItem.querySelector( _settings.stepNameSelector );
			var stepIndex = stepItem.querySelector( _settings.stepIndexSelector );
			var stepLabel = stepItem.getAttribute( _settings.stepLabelAttribute ) || '';
			var stepNumber = String( i + 1 );
			var stepIndexLabel = stepNumber + '. ' + stepLabel;

			// Reset step item state classes
			stepItem.classList.remove( _settings.stepDoneClass, _settings.stepDoneNextReachedClass, _settings.stepCurrentClass, _settings.stepCurrentNextReachedClass );

			// Maybe mark the step as current
			if ( i === viewedIndex ) {
				stepItem.classList.add( _settings.stepCurrentClass );

				// Maybe mark current step as having a later reached step
				if ( viewedIndex < maxReachedIndex ) {
					stepItem.classList.add( _settings.stepCurrentNextReachedClass );
				}
			}
			// Maybe mark the step as completed
			else if ( i <= maxReachedIndex && i !== viewedIndex ) {
				stepItem.classList.add( _settings.stepDoneClass );

				// Maybe mark completed step as having a later reached step
				if ( i < maxReachedIndex ) {
					stepItem.classList.add( _settings.stepDoneNextReachedClass );
				}
			}

			if ( stepIndex && itemSlug ) {
				// Convert step index to link or static label based on reach state
				if ( i <= maxReachedIndex ) {
					// Maybe replace step index span with a navigation link
					if ( 'A' !== stepIndex.tagName ) {
						var indexLink = document.createElement( 'a' );
						indexLink.className = _settings.stepIndexLinkClass;
						indexLink.href = '#';
						indexLink.setAttribute( _settings.stepNavAttribute, itemSlug );
						indexLink.setAttribute( _settings.ariaLabelAttribute, stepIndexLabel );
						indexLink.textContent = stepNumber;
						stepIndex.replaceWith( indexLink );
					}
					// Otherwise update the existing navigation link
					else {
						stepIndex.setAttribute( _settings.stepNavAttribute, itemSlug );
						stepIndex.setAttribute( _settings.ariaLabelAttribute, stepIndexLabel );
						stepIndex.textContent = stepNumber;
					}
				}
				// Maybe replace navigation link with a static label for unreached steps
				else if ( 'A' === stepIndex.tagName ) {
					var indexSpan = document.createElement( 'span' );
					indexSpan.className = _settings.stepIndexLinkClass;
					indexSpan.setAttribute( _settings.ariaHiddenAttribute, 'true' );
					indexSpan.textContent = stepNumber;
					stepIndex.replaceWith( indexSpan );
				}
				// Otherwise update the static step index label
				else {
					stepIndex.textContent = stepNumber;
				}
			}

			// Skip step name updates when required elements are missing
			if ( ! stepName || ! itemSlug ) {
				continue;
			}

			if ( i <= maxReachedIndex ) {
				// Replace step name with navigation link for reachable steps
				stepName.innerHTML = '';

				var stepLink = document.createElement( 'a' );
				stepLink.href = '#';
				stepLink.setAttribute( _settings.stepNavAttribute, itemSlug );
				stepLink.textContent = stepLabel;
				stepName.appendChild( stepLink );
			}
			// Otherwise keep the step name as plain text
			else {
				stepName.textContent = stepLabel;
			}
		}
	};







	/**
	 * Get saved state for a guided setup sub-step.
	 *
	 * @param   string  stepSlug    Step slug.
	 * @param   string  subStepId   Sub-step identifier.
	 *
	 * @return  Object
	 */
	var getSubStepState = function( stepSlug, subStepId ) {
		// Get saved progress for the step and sub-step
		var stepProgress = _subStepProgress[ stepSlug ] || { };
		var subStepState = stepProgress[ subStepId ] || { };

		return {
			completed: !! subStepState.completed,
		};
	};



	/**
	 * Update saved state for a guided setup sub-step locally.
	 *
	 * @param   string   stepSlug    Step slug.
	 * @param   string   subStepId   Sub-step identifier.
	 * @param   boolean  completed   Whether the sub-step is complete.
	 */
	var setSubStepProgressLocally = function( stepSlug, subStepId, completed ) {
		// Maybe initialize the sub-step progress for the step
		if ( ! _subStepProgress[ stepSlug ] ) {
			_subStepProgress[ stepSlug ] = { };
		}

		// Set the sub-step progress locally
		_subStepProgress[ stepSlug ][ subStepId ] = {
			completed: completed,
		};
	};



	/**
	 * Whether all sub-steps for a step are complete.
	 *
	 * @param   string  stepSlug  Step slug.
	 *
	 * @return  boolean
	 */
	var isAllSubStepsComplete = function( stepSlug ) {
		var subSteps = ( _settings.subSteps && _settings.subSteps[ stepSlug ] ) ? _settings.subSteps[ stepSlug ] : null;

		// Bail if step has no sub-steps
		if ( ! subSteps ) {
			return true;
		}

		var subStepIds = Object.keys( subSteps );

		// Iterate through sub-steps and check completion state
		for ( var i = 0; i < subStepIds.length; i++ ) {
			if ( ! getSubStepState( stepSlug, subStepIds[ i ] ).completed ) {
				return false;
			}
		}

		return true;
	};



	/**
	 * Whether a guided setup step has been completed.
	 *
	 * Steps without sub-steps are complete once reached. Steps with sub-steps require all sub-steps to be complete.
	 *
	 * @param   string  stepSlug  Step slug.
	 *
	 * @return  boolean
	 */
	var isGuidedSetupStepComplete = function( stepSlug ) {
		var subSteps = ( _settings.subSteps && _settings.subSteps[ stepSlug ] ) ? _settings.subSteps[ stepSlug ] : null;

		// Steps without sub-steps are complete once the user has reached them
		if ( ! subSteps ) {
			var stepIndex = getStepIndex( stepSlug );

			return stepIndex >= 0 && stepIndex <= getMaxReachedStepIndex();
		}

		// Steps with sub-steps require every sub-step to be complete
		return isAllSubStepsComplete( stepSlug );
	};



	/**
	 * Get the collapsible content element for a sub-step.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 *
	 * @return  Element|null
	 */
	var getSubStepContentElement = function( subStepElement ) {
		// Bail if sub-step element not provided
		if ( ! subStepElement ) {
			return null;
		}

		return subStepElement.querySelector( _settings.collapsibleContentSelector );
	};



	/**
	 * Expand a guided setup sub-step.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 */
	var expandSubStep = function( subStepElement ) {
		// Get collapsible content element
		var contentElement = getSubStepContentElement( subStepElement );

		// Bail if content element or CollapsibleBlock is unavailable
		if ( ! contentElement || ! root.CollapsibleBlock ) {
			return;
		}

		// Expand sub-step content without transition or focus change
		root.CollapsibleBlock.expand( contentElement, false, false );
	};



	/**
	 * Collapse a guided setup sub-step.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 */
	var collapseSubStep = function( subStepElement ) {
		// Get collapsible content element
		var contentElement = getSubStepContentElement( subStepElement );

		// Bail if content element or CollapsibleBlock is unavailable
		if ( ! contentElement || ! root.CollapsibleBlock ) {
			return;
		}

		// Collapse sub-step content without transition
		root.CollapsibleBlock.collapse( contentElement, false );
	};



	/**
	 * Whether a guided setup sub-step is expanded.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 *
	 * @return  boolean
	 */
	var isSubStepExpanded = function( subStepElement ) {
		// Get collapsible content and handler elements
		var contentElement = getSubStepContentElement( subStepElement );
		var handler = subStepElement ? subStepElement.querySelector( _settings.collapsibleHandlerSelector ) : null;

		// Check expanded state from content element class
		if ( contentElement && contentElement.classList.contains( _settings.isExpandedClass ) ) {
			return true;
		}

		// Check expanded state from handler aria attribute
		if ( handler && 'true' === handler.getAttribute( _settings.ariaExpandedAttribute ) ) {
			return true;
		}

		return false;
	};



	/**
	 * Set the Expand/Collapse toggle button label for a sub-step.
	 *
	 * @param   Element   subStepElement  Sub-step element.
	 * @param   boolean   isExpanded      Whether the sub-step is expanded.
	 */
	var setSubStepToggleButtonLabel = function( subStepElement, isExpanded ) {
		var toggleButton = subStepElement ? subStepElement.querySelector( _settings.subStepToggleSelector ) : null;

		// Bail if toggle button not found
		if ( ! toggleButton ) {
			return;
		}

		// Set toggle button label based on expanded state
		toggleButton.textContent = isExpanded
			? ( _settings.i18n.subStepCollapse || 'Collapse' )
			: ( _settings.i18n.subStepExpand || 'Expand' );
	};



	/**
	 * Update the Expand/Collapse toggle button label for a sub-step.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 */
	var updateSubStepToggleButtonLabel = function( subStepElement ) {
		setSubStepToggleButtonLabel( subStepElement, isSubStepExpanded( subStepElement ) );
	};



	/**
	 * Update the sub-step header action buttons.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 * @param   Object   subStepState    Sub-step state.
	 */
	var updateSubStepActionButtons = function( subStepElement, subStepState ) {
		// Get sub-step header and action button elements
		var header = subStepElement.querySelector( _settings.subStepHeaderSelector );
		var actionsWrapper = subStepElement.querySelector( _settings.subStepActionsSelector );
		var skipButton = subStepElement.querySelector( _settings.subStepSkipSelector );
		var toggleButton = subStepElement.querySelector( _settings.subStepToggleSelector );

		// Bail if sub-step header not found
		if ( ! header ) {
			return;
		}

		// Maybe create the header actions wrapper
		if ( ! actionsWrapper ) {
			actionsWrapper = document.createElement( 'div' );
			actionsWrapper.className = _settings.subStepActionsClass;
			header.appendChild( actionsWrapper );
		}

		// Maybe add Skip button for incomplete sub-steps
		if ( ! subStepState.completed && ! skipButton ) {
			skipButton = document.createElement( 'button' );
			skipButton.type = 'button';
			skipButton.className = _settings.subStepSkipButtonClass;
			skipButton.setAttribute( _settings.subStepSkipAttribute, '' );
			skipButton.textContent = _settings.i18n.subStepSkip || 'Skip';
			actionsWrapper.insertBefore( skipButton, actionsWrapper.firstChild );
		}
		// Maybe remove Skip button for completed sub-steps
		else if ( subStepState.completed && skipButton ) {
			skipButton.remove();
		}

		// Maybe create Expand/Collapse toggle button
		if ( ! toggleButton ) {
			toggleButton = document.createElement( 'button' );
			toggleButton.type = 'button';
			toggleButton.className = _settings.subStepToggleButtonClass;
			toggleButton.setAttribute( _settings.subStepToggleAttribute, '' );
			actionsWrapper.appendChild( toggleButton );
		}

		// Update toggle button label to match current state
		updateSubStepToggleButtonLabel( subStepElement );
	};



	/**
	 * Update the sub-step actions section for a sub-step.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 * @param   Object   subStepState    Sub-step state.
	 */
	var updateSubStepFooter = function( subStepElement, subStepState ) {
		// Get sub-step footer action elements
		var actionsSection = subStepElement.querySelector( _settings.subStepSubstepActionsSelector );
		var contentInner = subStepElement.querySelector( _settings.collapsibleContentInnerSelector );
		var completeButton = subStepElement.querySelector( _settings.subStepCompleteSelector );
		var incompleteButton = subStepElement.querySelector( _settings.subStepIncompleteSelector );

		// Maybe create the footer actions section
		if ( ! actionsSection && contentInner ) {
			actionsSection = document.createElement( 'div' );
			actionsSection.className = _settings.subStepSubstepActionsClass;
			contentInner.appendChild( actionsSection );
		}

		// Bail if actions section could not be created
		if ( ! actionsSection ) {
			return;
		}

		if ( subStepState.completed ) {
			// Remove Mark as complete button
			if ( completeButton ) {
				completeButton.remove();
			}

			// Maybe add Mark as incomplete button
			if ( ! incompleteButton ) {
				incompleteButton = document.createElement( 'button' );
				incompleteButton.type = 'button';
				incompleteButton.className = _settings.subStepIncompleteButtonClass;
				incompleteButton.setAttribute( _settings.subStepIncompleteAttribute, '' );
				incompleteButton.textContent = _settings.i18n.subStepMarkIncomplete || 'Mark as incomplete';
				actionsSection.appendChild( incompleteButton );
			}
		}
		else {
			// Remove Mark as incomplete button
			if ( incompleteButton ) {
				incompleteButton.remove();
			}

			// Maybe add Mark as complete button
			if ( ! completeButton ) {
				completeButton = document.createElement( 'button' );
				completeButton.type = 'button';
				completeButton.className = _settings.subStepCompleteButtonClass;
				completeButton.setAttribute( _settings.subStepCompleteAttribute, '' );
				completeButton.textContent = _settings.i18n.subStepMarkComplete || 'Mark as complete';
				actionsSection.appendChild( completeButton );
			}
		}

		// Remove empty footer actions section
		if ( ! actionsSection.children.length ) {
			actionsSection.remove();
		}
	};



	/**
	 * Mark a guided setup sub-step as complete.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 */
	var markSubStepComplete = function( subStepElement ) {
		// Get sub-step identifiers and parent card
		var stepSlug = subStepElement.getAttribute( _settings.subStepStepSlugAttribute ) || '';
		var subStepId = subStepElement.getAttribute( _settings.subStepIdAttribute ) || '';
		var card = subStepElement.closest( _settings.cardSelector );

		// Bail if step slug or sub-step id is missing
		if ( ! stepSlug || ! subStepId ) {
			return;
		}

		// Update local progress and refresh card UI immediately
		setSubStepProgressLocally( stepSlug, subStepId, true );
		updateSubStepsInCard( card );

		// Persist progress via AJAX
		saveSubStepProgress( stepSlug, subStepId, 'complete', function( responseData ) {
			// Maybe sync local progress from server response
			if ( responseData && responseData.subStepProgress ) {
				_subStepProgress = responseData.subStepProgress;
			}

			// Collapse sub-step after marking complete
			collapseSubStep( subStepElement );
			updateSubStepsInCard( card );
		} );
	};



	/**
	 * Mark a guided setup sub-step as incomplete.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 */
	var markSubStepIncomplete = function( subStepElement ) {
		// Get sub-step identifiers, step indexes, and parent card
		var stepSlug = subStepElement.getAttribute( _settings.subStepStepSlugAttribute ) || '';
		var subStepId = subStepElement.getAttribute( _settings.subStepIdAttribute ) || '';
		var revertedStepIndex = getStepIndex( stepSlug );
		var currentStepIndex = getStepIndex( _currentStepSlug );
		var card = subStepElement.closest( _settings.cardSelector );

		// Bail if step slug or sub-step id is missing
		if ( ! stepSlug || ! subStepId ) {
			return;
		}

		// Update local progress and refresh card UI immediately
		setSubStepProgressLocally( stepSlug, subStepId, false );
		updateSubStepsInCard( card );

		// Persist progress via AJAX
		saveSubStepProgress( stepSlug, subStepId, 'incomplete', function( responseData ) {
			// Maybe sync local progress from server response
			if ( responseData && responseData.subStepProgress ) {
				_subStepProgress = responseData.subStepProgress;
			}

			// Maybe revert max reached step index from server response
			if ( responseData && responseData.maxReachedStepIndex !== undefined && responseData.maxReachedStepIndex !== null ) {
				resetMaxReachedStepIndex( responseData.maxReachedStepIndex );
			}
			// Otherwise revert to the step containing the incomplete sub-step
			else if ( revertedStepIndex >= 0 ) {
				resetMaxReachedStepIndex( revertedStepIndex );
			}

			// Expand sub-step after marking incomplete
			expandSubStep( subStepElement );
			updateSubStepsInCard( card );

			// Maybe navigate back to the reverted step when viewing a later step
			if ( currentStepIndex > revertedStepIndex && revertedStepIndex >= 0 ) {
				goToGuidedSetupStep( stepSlug );
			}
			// Otherwise refresh the progress bar for the current step
			else {
				updateStepProgress( _currentStepSlug );
			}
		} );
	};



	/**
	 * Update the continue button state for a guided setup card.
	 *
	 * @param   Element  card  Guided setup card element.
	 */
	var updateContinueButtonForCard = function( card ) {
		var submitWrapper;
		var stepSlug;

		// Bail if card not provided
		if ( ! card ) {
			return;
		}

		// Get submit wrapper for the card
		submitWrapper = card.querySelector( _settings.submitWrapperSelector );

		// Bail if submit wrapper not found
		if ( ! submitWrapper ) {
			return;
		}

		stepSlug = getStepSlugFromCard( card );

		// Enable or disable continue button based on step completion
		if ( isContinueButtonEnabledForStep( stepSlug, card, submitWrapper ) ) {
			enableContinueButton( submitWrapper );
		}
		else {
			disableContinueButton( submitWrapper );
		}
	};



	/**
	 * Update a guided setup sub-step element UI.
	 *
	 * @param   Element  subStepElement  Sub-step element.
	 */
	var updateSubStepElement = function( subStepElement ) {
		// Bail if sub-step element not provided
		if ( ! subStepElement ) {
			return;
		}

		// Get sub-step identifiers and saved state
		var stepSlug = subStepElement.getAttribute( _settings.subStepStepSlugAttribute ) || '';
		var subStepId = subStepElement.getAttribute( _settings.subStepIdAttribute ) || '';
		var subStepState = getSubStepState( stepSlug, subStepId );

		// Toggle complete state class on the sub-step element
		subStepElement.classList.toggle( _settings.subStepCompleteClass, subStepState.completed );

		// Update header and footer action buttons
		updateSubStepActionButtons( subStepElement, subStepState );
		updateSubStepFooter( subStepElement, subStepState );
	};



	/**
	 * Update all sub-steps within a card.
	 *
	 * @param   Element  card  Guided setup card element.
	 */
	var updateSubStepsInCard = function( card ) {
		// Bail if card not provided
		if ( ! card ) {
			return;
		}

		var subStepElements = card.querySelectorAll( _settings.subStepSelector );

		// Iterate through sub-steps and update each element
		for ( var i = 0; i < subStepElements.length; i++ ) {
			updateSubStepElement( subStepElements[ i ] );
		}

		// Sync continue button state after sub-step updates
		updateContinueButtonForCard( card );
	};



	/**
	 * Save guided setup sub-step progress via AJAX.
	 *
	 * @param   string    stepSlug         Step slug.
	 * @param   string    subStepId        Sub-step identifier.
	 * @param   string    progressAction   Progress action. `complete` or `incomplete`.
	 * @param   Function  callback         Optional callback after save.
	 */
	var saveSubStepProgress = function( stepSlug, subStepId, progressAction, callback ) {
		// Get AJAX URL and initialize request parameters
		var params = new URLSearchParams();
		var ajaxUrl = _settings.ajaxUrl || '';

		params.append( 'action', _settings.ajaxSubStepAction );
		params.append( getNonceParamName(), getNonceValue() );
		params.append( 'guided_setup_id', _settings.guidedSetupId );
		params.append( 'step_slug', stepSlug );
		params.append( 'sub_step_id', subStepId );
		params.append( 'progress_action', progressAction );

		// Send the AJAX request
		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: params.toString(),
		} )
			.then( function( response ) {
				// Parse the response as JSON
				return response.json();
			} )
			.then( function( response ) {
				// Bail if response is not successful
				if ( ! response || ! response.success || ! response.data ) {
					return;
				}

				// Maybe sync local progress from server response
				if ( response.data.subStepProgress ) {
					_subStepProgress = response.data.subStepProgress;
				}

				// Maybe run callback after save
				if ( callback ) {
					callback( response.data );
				}
			} )
			.catch( function() { } );
	};





	/**
	 * Navigate to a guided setup step without reloading the page.
	 *
	 * @param   string  stepSlug  Step slug.
	 */
	var goToGuidedSetupStep = function( stepSlug ) {
		// Get guided setup container, target card, and all cards
		var container = getWizardContainer();
		var targetCard = getCardForStep( stepSlug );
		var cards = container.querySelectorAll( _settings.cardSelector );

		// Bail if container or target card not found
		if ( ! container || ! targetCard ) {
			return;
		}

		// Reset visibility on all cards
		for ( var i = 0; i < cards.length; i++ ) {
			cards[ i ].classList.remove( _settings.cardVisibleClass );
			cards[ i ].style.display = '';
			cards[ i ].style.opacity = '1';
			setCardOffset( cards[ i ], '0' );
			cards[ i ].style.top = '0';
		}

		// Show target card
		targetCard.classList.add( _settings.cardVisibleClass );
		targetCard.style.display = 'block';
		targetCard.style.opacity = '1';
		setCardOffset( targetCard, '0' );
		targetCard.style.top = '0';

		// Track current step slug
		_currentStepSlug = stepSlug;

		// Update progress bar and sub-step UI for the target card
		updateStepProgress( stepSlug );
		updateSubStepsInCard( targetCard );

		maybeNotifyStepVisible( stepSlug, targetCard );
	};



	/**
	 * Get the currently visible guided setup card.
	 *
	 * @return  Element|null
	 */
	var getVisibleCard = function() {
		var container = getWizardContainer();

		// Bail if container not found
		if ( ! container ) { return null; }

		return container.querySelector( _settings.cardSelector + '.' + _settings.cardVisibleClass );
	};



	/**
	 * Serialize a form into a plain object.
	 *
	 * @param   HTMLFormElement  form  Form element.
	 *
	 * @return  Object
	 */
	var serializeFormData = function( form ) {
		var formData = new FormData( form );
		var data = { };

		// Iterate through form fields and build a plain object
		formData.forEach( function( value, key ) {
			// Maybe convert duplicate keys to arrays
			if ( Object.prototype.hasOwnProperty.call( data, key ) ) {
				if ( ! Array.isArray( data[ key ] ) ) {
					data[ key ] = [ data[ key ] ];
				}

				data[ key ].push( value );
			}
			else {
				data[ key ] = value;
			}
		} );

		return data;
	};



	/**
	 * Get the continue button in a submit wrapper.
	 *
	 * @param   Element  submitWrapper  Submit wrapper element.
	 *
	 * @return  Element|null
	 */
	var getContinueButton = function( submitWrapper ) {
		return submitWrapper ? submitWrapper.querySelector( _settings.continueButtonInnerSelector ) : null;
	};



	/**
	 * Show the spinner in a submit wrapper.
	 *
	 * @param   Element  submitWrapper  Submit wrapper element.
	 */
	var showSubmitSpinner = function( submitWrapper ) {
		// Get continue button and spinner elements
		var continueButton = getContinueButton( submitWrapper );
		var spinner = continueButton ? continueButton.querySelector( _settings.spinnerSelector ) : null;

		// Bail if continue button or spinner not found
		if ( ! continueButton || ! spinner ) {
			return;
		}

		// Add loading state classes
		spinner.classList.add( _settings.spinnerActiveClass );
		continueButton.classList.add( _settings.continueButtonLoadingClass );
	};



	/**
	 * Hide the spinner in a submit wrapper.
	 *
	 * @param   Element  submitWrapper  Submit wrapper.
	 */
	var hideSubmitSpinner = function( submitWrapper ) {
		// Get continue button and spinner elements
		var continueButton = getContinueButton( submitWrapper );
		var spinner = continueButton ? continueButton.querySelector( _settings.spinnerSelector ) : null;

		// Bail if continue button or spinner not found
		if ( ! continueButton || ! spinner ) {
			return;
		}

		// Remove loading state classes
		spinner.classList.remove( _settings.spinnerActiveClass );
		continueButton.classList.remove( _settings.continueButtonLoadingClass );
	};



	/**
	 * Disable the continue button in a submit wrapper.
	 *
	 * @param   Element  submitWrapper  Submit wrapper.
	 */
	var disableContinueButton = function( submitWrapper ) {
		var continueButton = getContinueButton( submitWrapper );

		// Add disabled class to continue button
		if ( continueButton ) {
			continueButton.classList.add( _settings.continueButtonDisabledClass );
		}
	};



	/**
	 * Enable the continue button in a submit wrapper.
	 *
	 * @param   Element  submitWrapper  Submit wrapper.
	 */
	var enableContinueButton = function( submitWrapper ) {
		var continueButton = getContinueButton( submitWrapper );

		// Remove disabled class from continue button
		if ( continueButton ) {
			continueButton.classList.remove( _settings.continueButtonDisabledClass );
		}
	};



	/**
	 * Whether the guided setup steps wrapper is in the viewport.
	 *
	 * @return  boolean
	 */
	var isGuidedSetupStepsInView = function() {
		var stepsWrapper = getWizardContainer().querySelector( _settings.stepsWrapperSelector );

		// Bail if steps wrapper not found
		if ( ! stepsWrapper ) { return false; }

		// Get steps wrapper bounding rectangle
		var rect = stepsWrapper.getBoundingClientRect();

		return rect.top >= 0 && rect.top <= window.innerHeight;
	};



	/**
	 * Scroll to the guided setup steps wrapper.
	 *
	 * @param   Function  callback  Callback after scroll.
	 */
	var scrollToSetupSteps = function( callback ) {
		var stepsWrapper = getWizardContainer().querySelector( _settings.stepsWrapperSelector );

		// Bail if steps wrapper not found
		if ( ! stepsWrapper ) {
			callback();
			return;
		}

		// Calculate scroll target with offset for admin bar spacing
		var targetTop = window.pageYOffset + stepsWrapper.getBoundingClientRect().top - 50;

		// Scroll to the steps wrapper
		window.scrollTo( {
			top: targetTop,
			behavior: 'smooth',
		} );

		// Run callback after scroll animation completes
		window.setTimeout( callback, 350 );
	};



	/**
	 * Whether the document is rendered right to left.
	 *
	 * @return  boolean
	 */
	var isDocumentRtl = function() {
		var root = document.documentElement;

		// Bail if the document element is not available
		if ( ! root ) { return false; }

		// WordPress sets `dir` on the root element for RTL languages
		if ( 'rtl' === root.dir ) {
			return true;
		}

		return 'rtl' === window.getComputedStyle( root ).direction;
	};



	/**
	 * Horizontal property used to slide setup cards.
	 * Matches `fcgaa.$alignment-left` in the Sass module (`left` in LTR, `right` in RTL).
	 *
	 * @return  string
	 */
	var getCardOffsetProperty = function() {
		return isDocumentRtl() ? 'right' : 'left';
	};



	/**
	 * Set a card's horizontal offset and clear the opposite side.
	 * Setting both `left` and `right` would stretch a relatively positioned card.
	 *
	 * @param   Element  element  Card element.
	 * @param   string   value    Offset value, such as `0` or `-40px`.
	 */
	var setCardOffset = function( element, value ) {
		var property = getCardOffsetProperty();
		var opposite = 'left' === property ? 'right' : 'left';

		// Bail if element not provided
		if ( ! element ) { return; }

		element.style[ opposite ] = '';
		element.style[ property ] = value;
	};



	/**
	 * Animate an element with opacity and optional top or horizontal offset.
	 * `offset` is applied to `left` in LTR and `right` in RTL.
	 *
	 * @param   Element   element     Element to animate.
	 * @param   Object    properties  Animation properties.
	 * @param   number    duration    Animation duration in ms.
	 * @param   Function  callback    Callback when animation ends.
	 */
	var animateElement = function( element, properties, duration, callback ) {
		var offsetProperty;

		// Bail if element not provided
		if ( ! element ) {
			if ( callback ) { callback(); }
			return;
		}

		offsetProperty = getCardOffsetProperty();

		// Set transition properties for the animation
		element.style.transition = 'opacity ' + duration + 'ms ease, top ' + duration + 'ms ease, ' + offsetProperty + ' ' + duration + 'ms ease';

		// Apply animation properties
		if ( Object.prototype.hasOwnProperty.call( properties, 'opacity' ) ) {
			element.style.opacity = properties.opacity;
		}

		if ( Object.prototype.hasOwnProperty.call( properties, 'top' ) ) {
			element.style.top = properties.top;
		}

		if ( Object.prototype.hasOwnProperty.call( properties, 'offset' ) ) {
			setCardOffset( element, properties.offset );
		}

		// Run callback after animation duration
		window.setTimeout( function() {
			if ( callback ) { callback(); }
		}, duration );
	};



	/**
	 * Progress the guided setup to the next step card.
	 */
	var guidedSetupNextStep = function() {
		// Get guided setup container and current visible card
		var container = getWizardContainer();
		var currentCard = getVisibleCard();

		// Bail if container or current card not found
		if ( ! container || ! currentCard ) { return; }

		var nextCard = currentCard.nextElementSibling;

		// Skip non-card siblings between step cards
		while ( nextCard && ! nextCard.classList.contains( _settings.cardSelector.substring( 1 ) ) ) {
			nextCard = nextCard.nextElementSibling;
		}

		// Animate current card out toward the start side and the next card in from the end side.
		// LTR exits to the left. RTL exits to the right because the offset property is `right`.
		animateElement( currentCard, { opacity: '0', offset: '-40px' }, 230, function() {
			// Hide current card after animation
			currentCard.classList.remove( _settings.cardVisibleClass );
			currentCard.style.display = 'none';

			// Bail if there is no next card
			if ( ! nextCard ) { return; }

			// Prepare next card for entrance animation
			nextCard.classList.add( _settings.cardVisibleClass );
			nextCard.style.display = 'block';
			nextCard.style.opacity = '0';
			setCardOffset( nextCard, '40px' );
			nextCard.style.top = '0';

			// Commit the start offset before transitioning back to zero.
			// Otherwise both offsets are written in one frame and the card only fades in.
			nextCard.getBoundingClientRect();

			// Animate next card into view
			animateElement( nextCard, { opacity: '1', offset: '0', top: '0' }, 230 );
		} );

		var nextStepSlug = getStepSlugFromCard( nextCard );

		if ( nextStepSlug ) {
			// Update max reached step and current step slug
			setMaxReachedStepIndex( getStepIndex( nextStepSlug ) );
			_currentStepSlug = nextStepSlug;

			// Update progress after card transition completes
			window.setTimeout( function() {
				updateStepProgress( nextStepSlug );
				updateSubStepsInCard( nextCard );

				maybeNotifyStepVisible( nextStepSlug, nextCard );
			}, 230 );
		}
	};



	/**
	 * Process the guided setup step via AJAX.
	 *
	 * @param   string    step          Step slug.
	 * @param   Object    formData      Form data object.
	 * @param   Element   submitWrapper Submit wrapper element.
	 */
	var processGuidedSetupStep = function( step, formData, submitWrapper ) {
		// Get the AJAX URL
		var ajaxUrl = _settings.ajaxUrl || '';

		// Initalize URL parameters
		var params = new URLSearchParams();
		params.append( 'action', _settings.ajaxStepActionPrefix + step );
		params.append( getNonceParamName(), getNonceValue() );
		params.append( 'guided_setup_id', _settings.guidedSetupId );

		// Add form data to the URL parameters
		Object.keys( formData ).forEach( function( key ) {
			var value = formData[ key ];

			if ( Array.isArray( value ) ) {
				value.forEach( function( item ) {
					params.append( 'form_data[' + key + '][]', item );
				} );
				return;
			}

			params.append( 'form_data[' + key + ']', value );
		} );

		// Update UI states
		showSubmitSpinner( submitWrapper );
		disableContinueButton( submitWrapper );

		// Send the AJAX request
		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: params.toString(),
		} )
			.then( function( response ) {
				// Parse the response as JSON
				return response.json();
			} )
			.then( function( response ) {
				// Update UI states
				hideSubmitSpinner( submitWrapper );
				enableContinueButton( submitWrapper );

				// Bail if the response is not successful
				if ( ! response || ! response.success ) {
					// Show an alert if the response has a message
					if ( response && response.data && response.data.message ) {
						window.alert( response.data.message );
					}

					return;
				}

				if ( 'function' === typeof _settings.onStepProcessSuccess ) {
					_settings.onStepProcessSuccess( step, formData, response );
				}

				window.setTimeout( function() {
					// Maybe scroll to steps before advancing
					if ( isGuidedSetupStepsInView() ) {
						guidedSetupNextStep();
					}
					else {
						scrollToSetupSteps( guidedSetupNextStep );
					}
				}, 350 );
			} )
			.catch( function() {
				// Restore UI state on request failure
				hideSubmitSpinner( submitWrapper );
				enableContinueButton( submitWrapper );
			} );
	};







	/**
	 * Fall back to the full-size image when a WordPress thumbnail is unavailable.
	 *
	 * @param   Event  e  Error event.
	 */
	var handleStepImageThumbnailError = function( e ) {
		var thumbnail = e.target;
		var fallbackSrc;

		// Bail if target is not a step image thumbnail
		if ( ! thumbnail || ! thumbnail.matches || ! thumbnail.matches( _settings.stepImagePreviewSelector ) ) { return; }

		fallbackSrc = thumbnail.getAttribute( _settings.fallbackSrcAttribute );

		// Load full-size image when thumbnail fails to load
		if ( fallbackSrc && thumbnail.src !== fallbackSrc ) {
			thumbnail.src = fallbackSrc;
		}
	};



	/**
	 * Open the guided setup image modal.
	 *
	 * @param   string  fullUrl   Full-size image URL.
	 * @param   string  altText   Accessible image description.
	 */
	var openGuidedSetupImageModal = function( fullUrl, altText ) {
		var closeButton;

		// Bail if modal elements or image URL not available
		if ( ! _imageModal || ! _imageModalImage || ! fullUrl ) {
			return;
		}

		// Store focus to restore when modal closes
		_lastFocusedElement = document.activeElement;

		// Set image source and show modal
		_imageModalImage.src = fullUrl;
		_imageModalImage.alt = altText || '';
		_imageModal.removeAttribute( _settings.hiddenAttribute );
		document.body.classList.add( _settings.imageModalOpenBodyClass );

		closeButton = _imageModal.querySelector( _settings.imageModalCloseButtonSelector );

		// Move focus to the close button
		if ( closeButton ) {
			closeButton.focus();
		}
	};



	/**
	 * Close the guided setup image modal.
	 */
	var closeGuidedSetupImageModal = function() {
		// Bail if modal not initialized
		if ( ! _imageModal || ! _imageModalImage ) {
			return;
		}

		// Hide modal and clear image
		_imageModal.setAttribute( _settings.hiddenAttribute, '' );
		_imageModalImage.src = '';
		_imageModalImage.alt = '';
		document.body.classList.remove( _settings.imageModalOpenBodyClass );

		// Restore focus to the element that opened the modal
		if ( _lastFocusedElement && 'function' === typeof _lastFocusedElement.focus ) {
			_lastFocusedElement.focus();
		}
	};



	/**
	 * EVENT HANDLERS
	 */



	/**
	 * Handle clicks on the guided setup image modal close control.
	 *
	 * @param   Event  e  Click event.
	 */
	var handleImageModalCloseClick = function( e ) {
		// Prevent the default action
		e.preventDefault();

		// Close the image modal
		closeGuidedSetupImageModal();
	};



	/**
	 * Handle clicks on the continue button.
	 *
	 * @param   Event    e               Click event.
	 * @param   Element  continueButton  Continue button element.
	 */
	var handleContinueButtonClick = function( e, continueButton ) {
		// Get card, form, and step data from the continue button
		var card = continueButton.closest( _settings.cardSelector );
		var submitWrapper = continueButton.closest( _settings.submitWrapperSelector );
		var form = card ? card.querySelector( _settings.formSelector ) : null;
		var step = continueButton.getAttribute( _settings.stepAttribute );
		var formData = form ? serializeFormData( form ) : { };

		// Prevent the default action
		e.preventDefault();

		if ( 'function' === typeof _settings.onBeforeContinue ) {
			var shouldContinue = _settings.onBeforeContinue( step, formData, continueButton );

			if ( false === shouldContinue ) {
				return;
			}
		}

		// Process the guided setup step via AJAX
		processGuidedSetupStep( step, formData, submitWrapper );
	};



	/**
	 * Handle clicks on the back to previous step button.
	 *
	 * @param   Event  e  Click event.
	 */
	var handleBackToPreviousStepClick = function( e ) {
		var currentIndex = getStepIndex( _currentStepSlug );
		var previousStepSlug;

		// Prevent the default action
		e.preventDefault();

		// Bail if already on the first step
		if ( currentIndex <= 0 ) {
			return;
		}

		// Get previous step slug
		previousStepSlug = getStepSlugByIndex( currentIndex - 1 );

		// Bail if previous step slug not found
		if ( ! previousStepSlug ) {
			return;
		}

		// Maybe scroll to steps before navigating back
		if ( isGuidedSetupStepsInView() ) {
			goToGuidedSetupStep( previousStepSlug );
		}
		else {
			scrollToSetupSteps( function() {
				goToGuidedSetupStep( previousStepSlug );
			} );
		}
	};



	/**
	 * Handle clicks on the step progress bar.
	 *
	 * @param   Event    e         Click event.
	 * @param   Element  stepLink  Step navigation link element.
	 */
	var handleStepNavClick = function( e, stepLink ) {
		var stepSlug = stepLink.getAttribute( _settings.stepNavAttribute );
		var targetIndex = getStepIndex( stepSlug );

		// Prevent the default action
		e.preventDefault();

		// Bail if target step has not been reached yet
		if ( targetIndex < 0 || targetIndex > getMaxReachedStepIndex() ) {
			return;
		}

		// Navigate to the selected step
		goToGuidedSetupStep( stepSlug );
	};







	/**
	 * Get the sub-step element related to a sub-step button.
	 *
	 * @param   Element  button  Sub-step button element.
	 *
	 * @return  Element|null
	 */
	var getSubStepElementFromButton = function( button ) {
		return button.closest( _settings.subStepSelector );
	};



	/**
	 * Handle clicks on a sub-step Skip button.
	 *
	 * @param   Event    e           Click event.
	 * @param   Element  skipButton  Skip button element.
	 */
	var handleSubStepSkipClick = function( e, skipButton ) {
		var subStepElement = getSubStepElementFromButton( skipButton );

		// Prevent the default action and stop event propagation
		e.preventDefault();
		e.stopPropagation();

		// Bail if sub-step element not found
		if ( ! subStepElement ) {
			return;
		}

		// Mark sub-step as complete (Skip counts as complete)
		markSubStepComplete( subStepElement );
	};



	/**
	 * Handle clicks on a sub-step Mark as complete button.
	 *
	 * @param   Event    e               Click event.
	 * @param   Element  completeButton  Complete button element.
	 */
	var handleSubStepCompleteClick = function( e, completeButton ) {
		var subStepElement = getSubStepElementFromButton( completeButton );

		// Prevent the default action and stop event propagation
		e.preventDefault();
		e.stopPropagation();

		// Bail if sub-step element not found
		if ( ! subStepElement ) {
			return;
		}

		// Mark sub-step as complete
		markSubStepComplete( subStepElement );
	};



	/**
	 * Handle clicks on a sub-step Mark as incomplete button.
	 *
	 * @param   Event    e                 Click event.
	 * @param   Element  incompleteButton  Incomplete button element.
	 */
	var handleSubStepIncompleteClick = function( e, incompleteButton ) {
		var subStepElement = getSubStepElementFromButton( incompleteButton );

		// Prevent the default action and stop event propagation
		e.preventDefault();
		e.stopPropagation();

		// Bail if sub-step element not found
		if ( ! subStepElement ) {
			return;
		}

		// Mark sub-step as incomplete
		markSubStepIncomplete( subStepElement );
	};



	/**
	 * Handle clicks on a sub-step Expand/Collapse button.
	 *
	 * @param   Event    e             Click event.
	 * @param   Element  toggleButton  Toggle button element.
	 */
	var handleSubStepToggleClick = function( e, toggleButton ) {
		var subStepElement = getSubStepElementFromButton( toggleButton );
		var wasExpanded;

		// Prevent the default action and stop event propagation
		e.preventDefault();
		e.stopPropagation();

		// Bail if sub-step element not found
		if ( ! subStepElement ) {
			return;
		}

		wasExpanded = isSubStepExpanded( subStepElement );

		// Toggle expanded state
		if ( wasExpanded ) {
			collapseSubStep( subStepElement );
		}
		else {
			expandSubStep( subStepElement );
		}

		// Update toggle button label after CollapsibleBlock state change
		window.setTimeout( function() {
			updateSubStepToggleButtonLabel( subStepElement );
		}, 0 );
	};



	/**
	 * Sync the Expand/Collapse label after toggling from the title handler.
	 *
	 * @param   Event    e        Click event.
	 * @param   Element  handler  Collapsible handler element.
	 */
	var handleSubStepCollapsibleHandlerClick = function( e, handler ) {
		// Get sub-step element from the collapsible handler
		var subStepElement = getSubStepElementFromButton( handler );

		// Bail if no sub-step element is found
		if ( ! subStepElement ) { return; }

		// CollapsibleBlock toggles after this handler runs, so invert the current state.
		var wasExpanded = isSubStepExpanded( subStepElement );
		setSubStepToggleButtonLabel( subStepElement, ! wasExpanded );
	};



	/**
	 * Handle clicks on a guided setup step image thumbnail.
	 *
	 * @param   Event    e        Click event.
	 * @param   Element  trigger  Image thumbnail trigger element.
	 */
	var handleGuidedSetupImageClick = function( e, trigger ) {
		// Prevent the default action
		e.preventDefault();

		// Open the image modal
		openGuidedSetupImageModal(
			trigger.getAttribute( _settings.imageFullAttribute ),
			trigger.getAttribute( _settings.imageAltAttribute ) || ''
		);
	};



	/**
	 * Close the guided setup image modal when Escape is pressed.
	 *
	 * @param   Event  e  Keydown event.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// Bail if not Escape key or image modal is not visible
		if ( FCUtils.keyboardKeys.ESC !== e.key || ! _imageModal || _imageModal.hasAttribute( _settings.hiddenAttribute ) ) {
			return;
		}

		// Prevent the default action
		e.preventDefault();

		// Close the image modal
		closeGuidedSetupImageModal();
	};



	/**
	 * Handle document clicks and route to the appropriate handler.
	 *
	 * @param   Event  e  Click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// Bail if click is outside the guided setup
		if ( ! e.target.closest( _settings.guidedSetupContainerSelector ) ) { return; }

		// IMAGE MODAL CLOSE
		if ( e.target.closest( _settings.imageModalCloseSelector ) ) {
			handleImageModalCloseClick( e );
		}
		// CONTINUE
		else if ( ( matchedElement = e.target.closest( _settings.continueButtonSelector ) ) && ! matchedElement.classList.contains( _settings.continueButtonDisabledClass ) ) {
			handleContinueButtonClick( e, matchedElement );
		}
		// BACK TO PREVIOUS STEP
		else if ( e.target.closest( _settings.backButtonSelector ) ) {
			handleBackToPreviousStepClick( e );
		}
		// STEP NAV
		else if ( matchedElement = e.target.closest( _settings.stepNavSelector ) ) {
			handleStepNavClick( e, matchedElement );
		}
		// SUB-STEP SKIP
		else if ( matchedElement = e.target.closest( _settings.subStepSkipSelector ) ) {
			handleSubStepSkipClick( e, matchedElement );
		}
		// SUB-STEP COMPLETE
		else if ( matchedElement = e.target.closest( _settings.subStepCompleteSelector ) ) {
			handleSubStepCompleteClick( e, matchedElement );
		}
		// SUB-STEP INCOMPLETE
		else if ( matchedElement = e.target.closest( _settings.subStepIncompleteSelector ) ) {
			handleSubStepIncompleteClick( e, matchedElement );
		}
		// SUB-STEP TOGGLE
		else if ( matchedElement = e.target.closest( _settings.subStepToggleSelector ) ) {
			handleSubStepToggleClick( e, matchedElement );
		}
		// SUB-STEP COLLAPSIBLE HANDLER
		else if ( matchedElement = e.target.closest( _settings.subStepCollapsibleHandlerSelector ) ) {
			handleSubStepCollapsibleHandlerClick( e, matchedElement );
		}
		// STEP IMAGE THUMBNAIL
		else if ( matchedElement = e.target.closest( _settings.imageThumbnailTriggerSelector ) ) {
			handleGuidedSetupImageClick( e, matchedElement );
		}
	};



	/**
	 * Initialize the guided setup.
	 *
	 * @param   Object  options  Optional settings passed from PHP.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Bail if guided setup id is missing (required for multi-tenant progress storage)
		if ( ! _settings.guidedSetupId ) {
			if ( window.console && 'function' === typeof window.console.error ) {
				window.console.error( 'FCGuidedSetup: guidedSetupId is required.' );
			}
			return;
		}

		// Get the guided setup container
		_guidedSetupContainer = document.querySelector( _settings.guidedSetupContainerSelector );

		// Bail if guided setup container is not found
		if ( ! _guidedSetupContainer ) { return; }

		// Initialize guided setup state from PHP settings
		_subStepProgress = _settings.subStepProgress || { };
		_nonceField = _guidedSetupContainer.querySelector( '#' + _settings.nonceFieldId );
		_currentStepSlug = _settings.currentStep || '';
		_maxReachedStepIndex = ( _settings.maxReachedStepIndex !== undefined && _settings.maxReachedStepIndex !== null )
			? _settings.maxReachedStepIndex
			: getStepIndex( _currentStepSlug );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'keydown', handleKeyDown, true );
		document.addEventListener( 'error', handleStepImageThumbnailError, true );

		// Cache image modal element references
		_imageModal = _guidedSetupContainer.querySelector( _settings.imageModalSelector );
		_imageModalImage = _imageModal ? _imageModal.querySelector( _settings.imageModalImageSelector ) : null;

		// Initialize CollapsibleBlock if it is available
		if ( window.CollapsibleBlock ) {
			CollapsibleBlock.init( window.fcSettings ? fcSettings.collapsibleBlock : {} );
		}

		maybeNotifyStepVisible( _currentStepSlug, getVisibleCard() );

		// Maybe update sub-steps in the currently visible card
		var visibleCard = getVisibleCard();
		if ( visibleCard ) {
			updateSubStepsInCard( visibleCard );
		}

		// Mark the script as initialized
		_hasInitialized = true;
	};


	//
	// Public APIs
	//
	return _publicMethods;

} );
