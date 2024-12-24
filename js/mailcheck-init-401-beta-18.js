/**
 * Handles Mailcheck runs on email fields
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.MailcheckInit = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;

	var _hasInitialized = false;
	var _publicMethods = { }
	var _settings = {
		mailFieldSelector: '[data-mailcheck]',

		formFieldWrapperSelector: '.form-row',
		inputFieldWrapperSelector: '.woocommerce-input-wrapper',
		suggestionElementSelector: '[data-mailcheck-suggestion]',
		suggestionApplySelector: '[data-mailcheck-apply]',
		errorMessageElementSelector: '.woocommerce-error',

		mailFieldSuggestedClass: 'has-email-suggestion',

		suggestionValueAttr: 'data-suggestion-value',

		suggestedElementTemplate: '<div class="fc-mailcheck-suggestion" data-mailcheck-suggestion>Did you mean <a class="mailcheck-suggestion" href="#apply-suggestion" data-mailcheck-apply data-suggestion-value="{suggestion-value}">{suggestion}</a>?</div>',
		suggestionTemplate: '{address}@<span class="mailcheck-suggestion-domain">{domain}</span>',
	}
	var _tempTarget = null;



	/**
	 * METHODS
	 */



	/**
	 * Remove Mailcheck suggestions elements
	 */
	var removeSuggestions = function() {
		// Bail if there is no target
		if ( ! _tempTarget ) { return; }

		// Get variables
		var parent = _tempTarget.closest( _settings.formFieldWrapperSelector );

		// Bail if no parent found
		if ( ! parent ) { return; }

		// Get suggestion elements to be removed
		var suggestions = parent.querySelectorAll( _settings.suggestionElementSelector );

		// Remove suggestions
		for ( var i = 0; i < suggestions.length; i++ ) {
			suggestions[ i ].parentNode.removeChild( suggestions[i] );
		}

		// Remove suggested class
		_tempTarget.classList.remove( _settings.mailFieldSuggestedClass );
	}



	/**
	 * Add Mailcheck suggestions elements
	 */
	var handleSuggested = function( suggestion ) {
		// Remove existing suggestions
		removeSuggestions();

		// Bail if there is no target
		if ( ! _tempTarget ) { return; }

		// Get variables
		var parent = _tempTarget.closest( _settings.inputFieldWrapperSelector );
		var suggestionHtml = _settings.suggestionTemplate.replaceAll( '{address}', suggestion.address ).replaceAll( '{domain}', suggestion.domain );
		var suggestedElementHtml = _settings.suggestedElementTemplate.replaceAll( '{suggestion}', suggestionHtml ).replaceAll( '{suggestion-value}', suggestion.full );

		// Get target sibling
		var insertPositionElement = _tempTarget.nextSibling;

		// Handle position when error messages are present
		if ( parent.querySelector( _settings.errorMessageElementSelector ) ) {
			var errorMessageElements = parent.querySelectorAll( _settings.errorMessageElementSelector );

			// Get last error message element
			insertPositionElement = errorMessageElements[ errorMessageElements.length - 1 ].nextSibling;
		}

		// Create suggestion element and add it after the field
		var element = document.createElement( 'div' );
		element.innerHTML = suggestedElementHtml;
		parent.insertBefore( element.firstChild, insertPositionElement );
		element = null;

		_tempTarget.classList.add( _settings.mailFieldSuggestedClass );
	}



	/**
	 * Apply an email suggestion value to the email field
	 */
	var applySuggestion = function( suggestionElement ){
		// Bail if there are no suggestions
		if ( suggestionElement === null ) { return; }

		// Get form row
		var parentFormRow = suggestionElement.closest( _settings.formFieldWrapperSelector );
		
		// Bail if parent form row not found
		if ( parentFormRow === null ) { return; }

		var targetField = parentFormRow.querySelector( _settings.mailFieldSelector )

		// Bail if target field not found
		if ( targetField === null ) { return; }

		// Apply suggested value
		targetField.value = suggestionElement.getAttribute( _settings.suggestionValueAttr );
		
		// Remove existing suggestions
		_tempTarget = targetField;
		removeSuggestions();
		_tempTarget = null;

		// Refocus on the target field
		targetField.focus();

		// Revalidate the field
		if ( window.CheckoutValidation ) {
			CheckoutValidation.validateField( targetField, 'apply-mailcheck-suggestion' );
		}
	}



	/**
	 * Handle captured keyup event and route to appropriate function.
	 * @param  {Event} e Event data.
	 */
	var handleTriggerEvents = function( e ) {
		// Bail if Mailcheck not available
		if ( undefined === Mailcheck ) { return; }

		// Get the target field
		_tempTarget = e.target;

		// Bail if target field not found
		if ( ! _tempTarget.matches( _settings.mailFieldSelector ) ) { return; }

		// Run Mailcheck and handle suggestions
		Mailcheck.run( {
			email: _tempTarget.value,
			suggested: handleSuggested,
			empty: removeSuggestions
		} );

		// Clear the temp target
		_tempTarget = null;
	};
	var handleTriggerEventsDebounced = FCUtils.debounce( handleTriggerEvents, 100 );



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// APPLY SUGGESTION
		if ( e.target.closest( _settings.suggestionApplySelector ) ) {
			e.preventDefault();
			applySuggestion( e.target.closest( _settings.suggestionApplySelector ) );
		}
	}



	/**
	 * Handle keypress event.
	 */
	var handleKeyDown = function( e ) {
		// Should do nothing if the default action has been cancelled
		if ( e.defaultPrevented ) { return; }

		// ENTER or SPACE on apply-suggestion element
		if ( ( FCUtils.keyboardKeys.ENTER === e.key || FCUtils.keyboardKeys.SPACE === e.key ) && e.target.closest( _settings.suggestionApplySelector ) ) {
			// Simulate click
			handleClick( e );
		}
	};



	/**
	 * Initialize Mailcheck feature
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( true, _settings, options );

		// Add event listeners
		window.addEventListener( 'input', handleTriggerEventsDebounced, true );
		window.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'keydown', handleKeyDown, true );

		_hasInitialized = true;
	};

	
	//
	// Public APIs
	//
	return _publicMethods;

});
