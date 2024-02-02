/**
 * Manage fragments refres update actions for other pages that don't use the default WooCommerce AJAX events.
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
		root.FCFragmentsRefresh = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		bodyClass:                             'has-fc-fragments-refresh',

		dataFormSelector:                      'div.woocommerce form',
		sectionSelector:                       '.fc-section',
		messagesWrapperSelector:               '.woocommerce-notices-wrapper',
		formRowSelector:                       '.form-row',
		updateFieldsSelector:                  '.update_totals_on_change input.input-text, .update_totals_on_change select',
		loadingInputSelector:                  '.loading_indicator_on_change input.input-text',

		sectionVisibleStateFieldSelector:      '.fc-section-visible-state[type="hidden"]',
		sectionVisibleStateAttribute:          'data-section-visible',

		loadingClass:                           'fc-loading',
		uiProcessingClass:                     'processing',

		scrollOffsetSelector:                  '.fc-checkout-header',
		scrollBehavior:                        'smooth',
		scrollOffset:                          0,

		updateFragmentsNonce:                  '', // Value updated during runtime
		updateWaitTime:                        500,
	}
	var _xhr = false;
	var _debouncedUpdateFragments;
	var _fragments = {};



	/**
	 * METHODS
	 */



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
	 * Maybe change visibility status of cart sections.
	 *
	 * @param   Event  _event  An unused `jQuery.Event` object.
	 * @param   Array  data   The updated checkout data.
	 */
	var maybeChangeSectionState = function( _event, data ) {
		var sectionElements = document.querySelectorAll( _settings.sectionSelector );
		for ( var i = 0; i < sectionElements.length; i++ ) {
			var sectionElement = sectionElements[i];

			// Handle visibility state
			var visibilityHiddenField = sectionElement.querySelector( _settings.sectionVisibleStateFieldSelector );
			if ( visibilityHiddenField && 'no' === visibilityHiddenField.value ) {
				sectionElement.setAttribute( _settings.sectionVisibleStateAttribute, visibilityHiddenField.value );
			}
			else {
				sectionElement.removeAttribute( _settings.sectionVisibleStateAttribute );
			}
		}
	}



	/**
	 * Maybe set the focus back to the element with focus previously to fragments replacement.
	 *
	 * @param   HTMLElement  focusedElement  The current focused element.
	 * @param   mixed        value           Value for the current focused element.
	 */
	var maybeRefocusElement = function( focusedElement, value ) {
		// Bail if no element to focus
		if ( null === focusedElement ) { return; }

		requestAnimationFrame( function() {
			var elementToFocus;

			// Try findind the the current focused element after updating updated element by ID
			if ( focusedElement.id ) {
				elementToFocus = document.getElementById( focusedElement.id );
			}
			// Try findind the updated element by name
			else if ( focusedElement.getAttribute( 'name' ) ) {
				var nameAttr = focusedElement.getAttribute( 'name' );
				elementToFocus = document.querySelector( '[name="'+nameAttr+'"]' );
			}
			// Try findind the `select2` focusable element
			else if ( focusedElement.closest( '.form-row' ) ) {
				var formRow = focusedElement.closest( '.form-row' );
				if ( formRow.id ) {
					elementToFocus = document.querySelector( '.form-row[id="'+formRow.id+'"] .select2-selection' );
				}
			}

			// Try setting focus if element is found
			if ( elementToFocus ) {
				elementToFocus.focus();

				// Try to set current value to the focused element
				if ( null !== value && value !== elementToFocus.value ) {
					elementToFocus.value = value;
				}

				// Set keyboard track position back to that previously to update
				setTimeout( function(){
					// Try to set the same track position
					if( null !== elementToFocus.selectionStart && null !== elementToFocus.selectionEnd ) {
						if ( focusedElement.selectionStart && focusedElement.selectionEnd ) {
							elementToFocus.selectionStart = focusedElement.selectionStart;
							elementToFocus.selectionEnd = focusedElement.selectionEnd;
						}
						// Otherwise try set the track position to the end of the field
						// @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/setSelectionRange
						// @see https://html.spec.whatwg.org/multipage/input.html#concept-input-apply
						else {
							elementToFocus.selectionStart = elementToFocus.selectionEnd = Number.MAX_SAFE_INTEGER || 10000;
						}
					}
					// Try to select the entire content of the field
					// @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLInputElement/select
					// @see https://html.spec.whatwg.org/multipage/input.html#concept-input-apply
					else {
						try { elementToFocus.select(); }
						catch { /* Do nothing */ }
					}
				}, 0 );
			}
		} );
	};



	// CHANGE: Maybe add loading class to the form row
	var maybeSetLoadingIndicator = function ( e ) {
		if ( e.target && e.target.matches( _settings.loadingInputSelector ) ) {
			var formRow = e.target.closest( _settings.formRowSelector );
			if ( formRow ) {
				formRow.classList.add( _settings.loadingClass );
			}
		}
	};

	// CHANGE: Add function to remove loading classes from elements after updating the checkout fragments
	var maybeStopLoadingIndicators = function() {
		var maybeLoadingFields = document.querySelectorAll( _settings.loadingInputSelector );
		for ( var i = 0; i < maybeLoadingFields.length; i++ ) {
			var input = maybeLoadingFields[ i ];
			var formRow = input.closest( _settings.formRowSelector );
			if ( formRow ) {
				formRow.classList.remove( _settings.loadingClass );
			}
		}
	};



	/**
	 * Update the cart fragments.
	 * @throws  {TypeError}  Throws TypeError when used directly with event handlers because events pass the Event object as the first parameter while this function expects the first parameter to be the extra data to be sent with the AJAX request.
	 */
	_publicMethods.updateFragments = function() {
		// Cancel existing update request
		if ( _xhr ) { _xhr.abort(); }

		// Get data to send
		var data = FCUtils.extendObject( data, {
			security:     _settings.updateFragmentsNonce,
			post_data :   $( _settings.dataFormSelector ).serialize(),
		} );

		_xhr = $.ajax({
			type:		'POST',
			url:		fcSettings.wcAjaxUrl.toString().replace( '%%endpoint%%', 'fc_update_fragments' ),
			data:		data,
			success:	function( result ) {

				// Reload the page if requested
				if ( result && true === result.reload ) {
					window.location.reload();
					return;
				}

				// Get current element with focus, will reset after updating the fragments
				var currentFocusedElement = document.activeElement;
				var currentValue = document.activeElement.value;

				// Always update the fragments
				if ( result && result.fragments ) {

					$.each( result.fragments, function ( key, value ) {
						// CHANGE: Declare local variables needed for some checks before replacing the fragment
						var fragmentToReplace = document.querySelector( key );
						var replaceFragment = true;

						// CHANGE: Allow fragments to be replaced every time even when their contents are equal the existing elements in the DOM
						if ( value && -1 !== value.toString().indexOf( 'fc-fragment-always-replace' ) ) {
							replaceFragment = true;
						}
						
						// CHANGE: Allow fragments to be replaced every time even when their contents are equal the existing elements in the DOM
						if ( replaceFragment && ( ! _fragments || _fragments[ key ] !== value ) ) {
							// CHANGE: Log replaced fragment to console if debug mode is enabled.
							if ( fcSettings.debugMode ) {
								console.log( 'Replacing fragment: ' + key );
							}
							$( key ).replaceWith( value );
						}
						$( key ).unblock();
					} );
					_fragments = result.fragments;
				}

				// Re-set focus to the element with focus previously to updating fragments
				maybeRefocusElement( currentFocusedElement, currentValue );

				// Maybe remove loading class from form rows when completing the ajax request
				maybeStopLoadingIndicators();

				// Maybe scroll to notices
				var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );
				if ( messagesWrapper && messagesWrapper.children.length > 0 ) {
					_publicMethods.scrollToNotices( messagesWrapper );
				}

				$( document.body ).trigger( 'fc_fragments_refreshed' );
			}

		});
	};



	/**
	 * Change scroll position after changing steps.
	 */
	_publicMethods.scrollToNotices = function() {
		var element = document.querySelector( _settings.messagesWrapperSelector );
		var stickyElementsOffset = 0;

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

		// Scroll to the top of the collapsed step
		var elementOffset = getOffsetTop( element ) + ( _settings.scrollOffset * -1 ) + ( stickyElementsOffset * -1 );
		requestAnimationFrame( function() {
			window.scrollTo( {
				top: elementOffset,
				behavior: _settings.scrollBehavior,
			} );
		} );
	}

	

	/**
	 * Use the same method that WooCommerce uses to block other parts of the checkout form while updating.
	 * The UI is unblocked by the WooCommerce `checkout.js` script (which is replaced with a modified version but keeps the same behavior)
	 * using the checkout fragment selector, then unblocking after the checkout update is completed.
	 *
	 * @param   HTMLElement  element  Element to block the UI and show the loading indicator.
	 */
	_publicMethods.blockUI = function( element ) {
		$( element ).addClass( _settings.uiProcessingClass ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		} );
	}

	/**
	 * Unblock UI element to be used again.
	 *
	 * @param   HTMLElement  element  Element to get the UI unblocked.
	 *
	 * @see  blockUI
	 */
	_publicMethods.unblockUI = function( element ) {
		$( element ).removeClass( _settings.uiProcessingClass ).unblock();
	}



	/**
	 * Handle form field change event and route to the appropriate function.
	 */
	var handleChange = function( e ) {
		// LOADING INDICATOR
		maybeSetLoadingIndicator( e );

		// UPDATE ON CHANGE
		if ( e.target.matches( _settings.updateFieldsSelector ) ) {
			_debouncedUpdateFragments();
		}
	};

	/**
	 * Handle keypress event.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// Bail if control keys pressed
		if (
			FCUtils.keyboardKeys.ESC === e.key
			|| FCUtils.keyboardKeys.ENTER === e.key
			|| FCUtils.keyboardKeys.TAB === e.key
			|| FCUtils.keyboardKeys.CAPS === e.key
			|| FCUtils.keyboardKeys.SHIFT === e.key
			|| FCUtils.keyboardKeys.FUNCTION === e.key
			|| FCUtils.keyboardKeys.CONTROL === e.key
			|| FCUtils.keyboardKeys.COMMAND_OR_WINDOWS === e.key
			|| FCUtils.keyboardKeys.ALT === e.key
			|| FCUtils.keyboardKeys.ARROW_LEFT === e.key
			|| FCUtils.keyboardKeys.ARROW_RIGHT === e.key
			|| FCUtils.keyboardKeys.ARROW_UP === e.key
			|| FCUtils.keyboardKeys.ARROW_DOWN === e.key
		) { return; }

		// LOADING INDICATOR
		maybeSetLoadingIndicator( e );

		// UPDATE ON CHANGE
		if ( e.target.matches( _settings.updateFieldsSelector ) ) {
			_debouncedUpdateFragments();
		}
	}



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Set debounced update fragments function
		_debouncedUpdateFragments = FCUtils.debounce( _publicMethods.updateFragments, _settings.updateWaitTime );

		// Add jQuery event listeners
		if ( _hasJQuery ) {
			// Refresh triggers
			$( document.body ).on( 'fc_fragment_refresh', _debouncedUpdateFragments );

			// After fragments has been updated
			$( document.body ).on( 'fc_fragments_refreshed', maybeChangeSectionState );
		}

		// Refresh triggers
		window.addEventListener( 'change', handleChange );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
