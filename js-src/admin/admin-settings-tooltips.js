/**
 * Info tip tooltips on the Fluid Checkout settings page.
 */

( function() {
	'use strict';

	var openTip = null;



	/**
	 * Close an open info tip.
	 *
	 * @param  {Element}  tip  Tip element to close.
	 */
	var closeTip = function( tip ) {
		// Bail if tip is not available
		if ( ! tip ) { return; }

		tip.classList.remove( 'is-open' );

		var button = tip.querySelector( '.fc-settings-info-tip__button' );
		if ( button ) {
			button.setAttribute( 'aria-expanded', 'false' );
		}

		if ( openTip === tip ) {
			openTip = null;
		}
	};



	/**
	 * Open an info tip and close any previously open tip.
	 *
	 * @param  {Element}  tip  Tip element to open.
	 */
	var openTipElement = function( tip ) {
		// Bail if tip is not available
		if ( ! tip ) { return; }

		// Close previously open tip
		if ( openTip && openTip !== tip ) {
			closeTip( openTip );
		}

		tip.classList.add( 'is-open' );

		var button = tip.querySelector( '.fc-settings-info-tip__button' );
		if ( button ) {
			button.setAttribute( 'aria-expanded', 'true' );
		}

		openTip = tip;
	};



	/**
	 * Toggle an info tip when its button is clicked or tapped.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleButtonClick = function( e ) {
		var button = e.target.closest( '.fc-settings-info-tip__button' );

		// Bail if click was not on a tip button
		if ( ! button ) { return; }

		e.preventDefault();
		e.stopPropagation();

		var tip = button.closest( '.fc-settings-info-tip' );

		// Bail if tip wrapper is not available
		if ( ! tip ) { return; }

		// TOGGLE TIP
		if ( tip.classList.contains( 'is-open' ) ) {
			closeTip( tip );
		}
		// Otherwise open the tip
		else {
			openTipElement( tip );
		}
	};



	/**
	 * Dismiss the open tip when clicking outside it.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleDocumentClick = function( e ) {
		// Bail if no tip is open
		if ( ! openTip ) { return; }

		// Bail if click was inside the open tip
		if ( openTip.contains( e.target ) ) { return; }

		closeTip( openTip );
	};



	/**
	 * Dismiss the open tip when pressing Escape.
	 *
	 * @param  {Event}  e  Keyboard event.
	 */
	var handleKeydown = function( e ) {
		// Bail if Escape was not pressed or no tip is open
		if ( 'Escape' !== e.key || ! openTip ) { return; }

		closeTip( openTip );
	};



	/**
	 * Initialize info tip event listeners.
	 */
	var init = function() {
		document.addEventListener( 'click', handleButtonClick );
		document.addEventListener( 'click', handleDocumentClick );
		document.addEventListener( 'keydown', handleKeydown );
	};



	init();

} )();
