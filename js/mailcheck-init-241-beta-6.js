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
		mailFieldSuggestedClass: 'has-email-suggestion',
		formFieldWrapperSelector: '.form-row',
		inputFieldWrapperSelector: '.woocommerce-input-wrapper',
		suggestionElementSelector: '[data-mailcheck-suggestion]',
		suggestionApplySelector: '[data-mailcheck-apply]',
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
		if ( _tempTarget === null ) return;
		
		var parent = _tempTarget.closest( _settings.inputFieldWrapperSelector );
		var suggestions = parent.querySelectorAll( _settings.suggestionElementSelector );
		
		for ( var i = 0; i < suggestions.length; i++ ) {
			suggestions[i].parentNode.removeChild( suggestions[i] );
		}

		_tempTarget.classList.remove( _settings.mailFieldSuggestedClass );
	}



	/**
	 * Add Mailcheck suggestions elements
	 */
	var handleSuggested = function( suggestion ) {
		removeSuggestions();

		if ( _tempTarget === null ) return;

		var suggestionHtml = _settings.suggestionTemplate.replaceAll( '{address}', suggestion.address ).replaceAll( '{domain}', suggestion.domain );
		var suggestedElementHtml = _settings.suggestedElementTemplate.replaceAll( '{suggestion}', suggestionHtml ).replaceAll( '{suggestion-value}', suggestion.full );

		// Create suggestion element and add it after the field
		var parent = _tempTarget.parentNode;
		var element = document.createElement( 'div' );
		element.innerHTML = suggestedElementHtml;
		parent.insertBefore( element.firstChild, _tempTarget.nextSibling );
		element = null;

		_tempTarget.classList.add( _settings.mailFieldSuggestedClass );
	}



	/**
	 * Apply an email suggestion value to the email field
	 */
	var applySuggestion = function( suggestionElement ){
		// Bail if there are no suggestions
		if ( suggestionElement === null ) return;

		var parentFormRow = suggestionElement.closest( _settings.formFieldWrapperSelector );
		
		// Bail if parent form row not found
		if ( parentFormRow === null ) return;

		var targetField = parentFormRow.querySelector( _settings.mailFieldSelector )

		// Bail if target field not found
		if ( targetField === null ) return;

		// Apply suggested value
		targetField.value = suggestionElement.getAttribute( _settings.suggestionValueAttr );
		
		// Remove suggestion message
		_tempTarget = targetField;
		removeSuggestions();
		_tempTarget = null;

		// Refocus on the target field
		targetField.focus();

		// Revalidate the field
		if ( window.CheckoutValidation ) {
			CheckoutValidation.validateField( targetField );
		}
	}



	/**
	 * Handle captured keyup event and route to appropriate function.
	 * @param  {Event} e Event data.
	 */
	var handleTriggerEvents = function( e ) {
		if ( Mailcheck !== undefined && e.target.matches( _settings.mailFieldSelector ) ) {
			_tempTarget = e.target;

			Mailcheck.run( {
				email: _tempTarget.value,
				suggested: handleSuggested,
				empty: removeSuggestions
			} );

			_tempTarget = null;
		}
	};



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
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
		if ( _hasInitialized ) return;

		// Merge settings
		_settings = FCUtils.extendObject( true, _settings, options );

		// Add event listeners
		window.addEventListener( 'keyup', handleTriggerEvents, true );
		window.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'keydown', handleKeyDown, true );

		_hasInitialized = true;
	};

	
	//
	// Public APIs
	//
	return _publicMethods;

});
