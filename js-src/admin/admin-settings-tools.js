/**
 * Handles Backup & reset tool confirmations on the Fluid Checkout Tools settings screen.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsTools = factory( root );
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		actionButtonSelector:    '.fc-settings-tools__action',
		confirmMessageAttribute: 'data-fc-confirm',
	};



	/**
	 * Confirm a tool action before the associated standalone form submits.
	 *
	 * @param   {Event}    e               Click event.
	 * @param   {Element}  matchedElement  Matched action button.
	 */
	var handleActionButtonClick = function( e, matchedElement ) {
		var confirmMessage = matchedElement.getAttribute( _settings.confirmMessageAttribute );

		// Bail if no confirm message
		if ( ! confirmMessage ) { return; }

		// Bail if user cancels
		if ( ! window.confirm( confirmMessage ) ) {
			e.preventDefault();
		}
	};



	/**
	 * Handle document clicks and route to the appropriate handler.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// ACTION BUTTON
		if ( matchedElement = e.target.closest( _settings.actionButtonSelector ) ) {
			handleActionButtonClick( e, matchedElement );
		}
	};





	/**
	 * Initialize the script.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};

	//
	// Public APIs
	//
	return _publicMethods;

} );
