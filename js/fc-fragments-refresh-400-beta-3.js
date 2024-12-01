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
	 * Maybe enhance select fields with TomSelect.
	 */
	var maybeEnhanceFields = function() {
		// Bail if FCEnhancedSelect is not available
		if ( ! window.FCEnhancedSelect ) { return; }

		FCEnhancedSelect.enhanceFields();
	}

	/**
	 * Reinitialize collapsible blocks after checkout update.
	 */
	var maybeReinitializeCollapsibleBlocks = function() {
		// Bail if collapsible blocks are not available
		if ( ! window.CollapsibleBlock ) { return; }

		requestAnimationFrame( function() {
			// Get the current focused element
			var currentFocusedElement = document.activeElement;

			var collapsibleBlocks = document.querySelectorAll( '[data-collapsible]' );
			for ( var i = 0; i < collapsibleBlocks.length; i++ ) {
				var collapsibleBlock = collapsibleBlocks[i];

				// Maybe initialize the collapsible block
				if ( ! CollapsibleBlock.getInstance( collapsibleBlock ) ) {
					CollapsibleBlock.initializeElement( collapsibleBlock );
				}

				//  Maybe expand section if it was previously expanded
				if ( collapsibleBlock.contains( currentFocusedElement ) ) {
					CollapsibleBlock.expand( collapsibleBlock, false, true );

					// Get collapsible block for the toggle link of optional fields
					var wrapper = currentFocusedElement.closest( '.fc-expansible-form-section' );
					var toggleCollapsibleBlock = wrapper ? wrapper.querySelector( '.fc-expansible-form-section__toggle' ) : false;

					// Maybe collapse the toggle section
					if ( false !== toggleCollapsibleBlock ) {
						CollapsibleBlock.collapse( toggleCollapsibleBlock, false );
					}
				}
			}
		} );
	}



	/*
	 * Maybe add loading class to the form row.
	 */
	var maybeSetLoadingIndicator = function ( e ) {
		if ( e.target && e.target.matches( _settings.loadingInputSelector ) ) {
			var formRow = e.target.closest( _settings.formRowSelector );
			if ( formRow ) {
				formRow.classList.add( _settings.loadingClass );
			}
		}
	}

	/**
	 * Add function to remove loading classes from elements after updating the checkout fragments
	 */
	var maybeStopLoadingIndicators = function() {
		var maybeLoadingFields = document.querySelectorAll( _settings.loadingInputSelector );
		for ( var i = 0; i < maybeLoadingFields.length; i++ ) {
			var input = maybeLoadingFields[ i ];
			var formRow = input.closest( _settings.formRowSelector );
			if ( formRow ) {
				formRow.classList.remove( _settings.loadingClass );
			}
		}
	}



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

				// Set variables for current focused element
				FCUtils.setCurrentFocusedElementGlobalVariables();

				// Always update the fragments
				if ( result && result.fragments ) {

					$.each( result.fragments, function ( key, value ) {
						// Declare local variables needed for some checks before replacing the fragment
						var fragmentToReplace = document.querySelector( key );
						var replaceFragment = true;

						// Allow fragments to be replaced every time even when their contents are equal the existing elements in the DOM
						if ( value && -1 !== value.toString().indexOf( 'fc-fragment-always-replace' ) ) {
							replaceFragment = true;
						}
						
						// Allow fragments to be replaced every time even when their contents are equal the existing elements in the DOM
						if ( replaceFragment && ( ! _fragments || _fragments[ key ] !== value ) ) {
							// Log replaced fragment to console if debug mode is enabled.
							if ( 'yes' === fcSettings.debugMode ) {
								console.log( 'Replacing fragment: ' + key );
							}
							$( key ).replaceWith( value );
						}
						$( key ).unblock();
					} );
					_fragments = result.fragments;
				}

				// Re-set focus to the element with focus previously to updating fragments
				FCUtils.maybeRefocusElement( window.fcCurrentFocusedElement, window.fcCurrentFocusedElementValue );

				// Maybe remove loading class from form rows when completing the ajax request
				maybeStopLoadingIndicators();

				// Maybe scroll to notices
				var messagesWrapper = document.querySelector( _settings.messagesWrapperSelector );
				if ( messagesWrapper && messagesWrapper.children.length > 0 && window.FCUtils && 'function' === typeof FCUtils.scrollToElement ) {
					FCUtils.scrollToElement( messagesWrapper );
				}

				// Triger fragments refreshed event
				$( document.body ).trigger( 'fc_fragments_refreshed' );
			}

		});
	};

	

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
	}

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
			$( document.body ).on( 'fc_fragments_refresh', _debouncedUpdateFragments );

			// After fragments has been updated
			$( document.body ).on( 'fc_fragments_refreshed', maybeReinitializeCollapsibleBlocks );
			$( document.body ).on( 'fc_fragments_refreshed', maybeChangeSectionState );
			$( document.body ).on( 'fc_fragments_refreshed', maybeEnhanceFields );
		}

		// Refresh triggers
		window.addEventListener( 'change', handleChange );
		document.addEventListener( 'keydown', handleKeyDown, true );

		// Add init class
		document.body.classList.add( _settings.bodyClass );

		_hasInitialized = true;
	}



	//
	// Public APIs
	//
	return _publicMethods;

});
