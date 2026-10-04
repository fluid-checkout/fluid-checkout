/**
 * Info tip tooltips on the Fluid Checkout settings page.
 */

( function() {
	'use strict';

	var openTip = null;
	var hoveredTip = null;
	var viewportPadding = 8;
	var clipParentSelector = '.fc-settings-content__scroll, .fc-settings-content';



	/**
	 * Close an open info tip.
	 *
	 * @param  {Element}  tip  Tip element to close.
	 */
	var closeTip = function( tip ) {
		// Bail if tip is not available
		if ( ! tip ) { return; }

		tip.classList.remove( 'is-open' );
		clearTipPosition( tip );

		var button = tip.querySelector( '.fc-settings-info-tip__button' );
		if ( button ) {
			button.setAttribute( 'aria-expanded', 'false' );
		}

		if ( openTip === tip ) {
			openTip = null;
		}
	};

	/**
	 * Clear the viewport shift on a tip popup.
	 *
	 * @param  {Element}  tip  Tip element.
	 */
	var clearTipPosition = function( tip ) {
		// Bail if tip is not available
		if ( ! tip ) { return; }

		tip.style.removeProperty( '--fc-settings-info-tip-shift' );
	};

	/**
	 * Shift the tip popup horizontally so it stays inside the viewport and clip parent.
	 *
	 * @param  {Element}  tip  Tip element to position.
	 */
	var positionTipContent = function( tip ) {
		var content;
		var rect;
		var clipParent;
		var clipRect;
		var minLeft;
		var maxRight;
		var shift;

		// Bail if tip is not available
		if ( ! tip ) { return; }

		content = tip.querySelector( '.fc-settings-info-tip__content' );

		// Bail if popup content is missing
		if ( ! content ) { return; }

		// Measure from the default centered position
		tip.style.setProperty( '--fc-settings-info-tip-shift', '0px' );
		rect = content.getBoundingClientRect();

		minLeft = viewportPadding;
		maxRight = window.innerWidth - viewportPadding;

		// Also keep the popup inside the settings scroll/clip container
		clipParent = tip.closest( clipParentSelector );
		if ( clipParent ) {
			clipRect = clipParent.getBoundingClientRect();
			minLeft = Math.max( minLeft, clipRect.left + viewportPadding );
			maxRight = Math.min( maxRight, clipRect.right - viewportPadding );
		}

		shift = 0;

		// Maybe push the popup right when it overflows the left edge
		if ( rect.left < minLeft ) {
			shift = minLeft - rect.left;
		}
		// Maybe push the popup left when it overflows the right edge
		else if ( rect.right > maxRight ) {
			shift = maxRight - rect.right;
		}

		tip.style.setProperty( '--fc-settings-info-tip-shift', Math.round( shift ) + 'px' );
	};

	/**
	 * Reposition the tip that is currently visible (open or hovered).
	 */
	var syncVisibleTipPosition = function() {
		var tip = openTip || hoveredTip;

		// Bail if no tip is visible
		if ( ! tip ) { return; }

		positionTipContent( tip );
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
		positionTipContent( tip );

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
	 * Position the tip popup when the pointer enters the tip.
	 *
	 * @param  {Event}  e  Mouse event.
	 */
	var handleTipMouseOver = function( e ) {
		var tip = e.target.closest( '.fc-settings-info-tip' );

		// Bail if the event is not inside a tip
		if ( ! tip ) { return; }

		// Bail if still moving within the same tip
		if ( tip.contains( e.relatedTarget ) ) { return; }

		hoveredTip = tip;
		positionTipContent( tip );
	};

	/**
	 * Clear hover tracking when the pointer leaves the tip.
	 *
	 * @param  {Event}  e  Mouse event.
	 */
	var handleTipMouseOut = function( e ) {
		var tip = e.target.closest( '.fc-settings-info-tip' );

		// Bail if the event is not inside a tip
		if ( ! tip ) { return; }

		// Bail if still moving within the same tip
		if ( tip.contains( e.relatedTarget ) ) { return; }

		// Maybe clear hover state
		if ( hoveredTip === tip ) {
			hoveredTip = null;
		}

		// Keep the shift while the tip stays open from a click
		if ( tip.classList.contains( 'is-open' ) ) { return; }

		clearTipPosition( tip );
	};

	/**
	 * Position the tip popup when its button receives focus.
	 *
	 * @param  {Event}  e  Focus event.
	 */
	var handleTipFocusIn = function( e ) {
		var button = e.target.closest( '.fc-settings-info-tip__button' );
		var tip;

		// Bail if focus was not on a tip button
		if ( ! button ) { return; }

		tip = button.closest( '.fc-settings-info-tip' );

		// Bail if tip wrapper is not available
		if ( ! tip ) { return; }

		positionTipContent( tip );
	};



	/**
	 * Initialize info tip event listeners.
	 */
	var init = function() {
		document.addEventListener( 'click', handleButtonClick );
		document.addEventListener( 'click', handleDocumentClick );
		document.addEventListener( 'keydown', handleKeydown );
		document.addEventListener( 'mouseover', handleTipMouseOver );
		document.addEventListener( 'mouseout', handleTipMouseOut );
		document.addEventListener( 'focusin', handleTipFocusIn );
		window.addEventListener( 'resize', syncVisibleTipPosition );
		window.addEventListener( 'scroll', syncVisibleTipPosition, true );
	};



	init();

} )();
