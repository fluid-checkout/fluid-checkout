/**
 * Info tip tooltips on the Fluid Checkout settings page.
 *
 * DEPENDS ON:
 * - None (vanilla JS)
 */
( function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsTooltips = factory( root );
	}
} )( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		tipSelector:                       '.fc-settings-info-tip',
		buttonSelector:                    '.fc-settings-info-tip__button',
		contentSelector:                   '.fc-settings-info-tip__content',
		clipParentSelector:                '.fc-settings-content',
		openClass:                         'is-open',
		shiftProperty:                     '--fc-settings-info-tip-shift',
		viewportPadding:                   8,
	};
	var openTip = null;
	var hoveredTip = null;



	/**
	 * METHODS
	 */



	/**
	 * Clear the viewport shift on a tip popup.
	 *
	 * @param  {Element}  tip  Tip element.
	 */
	var clearTipPosition = function( tip ) {
		// Bail if tip is not available
		if ( ! tip ) { return; }

		tip.style.removeProperty( _settings.shiftProperty );
	};



	/**
	 * Close an open info tip.
	 *
	 * @param  {Element}  tip  Tip element to close.
	 */
	var closeTip = function( tip ) {
		var button;

		// Bail if tip is not available
		if ( ! tip ) { return; }

		tip.classList.remove( _settings.openClass );
		clearTipPosition( tip );

		button = tip.querySelector( _settings.buttonSelector );

		// Maybe collapse the button state
		if ( button ) {
			button.setAttribute( 'aria-expanded', 'false' );
		}

		// Maybe clear the open tip reference
		if ( openTip === tip ) {
			openTip = null;
		}
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

		content = tip.querySelector( _settings.contentSelector );

		// Bail if popup content is missing
		if ( ! content ) { return; }

		// Measure from the default centered position
		tip.style.setProperty( _settings.shiftProperty, '0px' );
		rect = content.getBoundingClientRect();

		minLeft = _settings.viewportPadding;
		maxRight = window.innerWidth - _settings.viewportPadding;

		// Also keep the popup inside the settings scroll/clip container
		clipParent = tip.closest( _settings.clipParentSelector );
		if ( clipParent ) {
			clipRect = clipParent.getBoundingClientRect();
			minLeft = Math.max( minLeft, clipRect.left + _settings.viewportPadding );
			maxRight = Math.min( maxRight, clipRect.right - _settings.viewportPadding );
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

		tip.style.setProperty( _settings.shiftProperty, Math.round( shift ) + 'px' );
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
		var button;

		// Bail if tip is not available
		if ( ! tip ) { return; }

		// Close previously open tip
		if ( openTip && openTip !== tip ) {
			closeTip( openTip );
		}

		tip.classList.add( _settings.openClass );
		positionTipContent( tip );

		button = tip.querySelector( _settings.buttonSelector );

		// Maybe expand the button state
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
		var button;
		var tip;

		button = e.target.closest( _settings.buttonSelector );

		// Bail if click was not on a tip button
		if ( ! button ) { return; }

		e.preventDefault();
		e.stopPropagation();

		tip = button.closest( _settings.tipSelector );

		// Bail if tip wrapper is not available
		if ( ! tip ) { return; }

		// TOGGLE TIP
		if ( tip.classList.contains( _settings.openClass ) ) {
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
		var tip = e.target.closest( _settings.tipSelector );

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
		var tip = e.target.closest( _settings.tipSelector );

		// Bail if the event is not inside a tip
		if ( ! tip ) { return; }

		// Bail if still moving within the same tip
		if ( tip.contains( e.relatedTarget ) ) { return; }

		// Maybe clear hover state
		if ( hoveredTip === tip ) {
			hoveredTip = null;
		}

		// Keep the shift while the tip stays open from a click
		if ( tip.classList.contains( _settings.openClass ) ) { return; }

		clearTipPosition( tip );
	};



	/**
	 * Position the tip popup when its button receives focus.
	 *
	 * @param  {Event}  e  Focus event.
	 */
	var handleTipFocusIn = function( e ) {
		var button;
		var tip;

		button = e.target.closest( _settings.buttonSelector );

		// Bail if focus was not on a tip button
		if ( ! button ) { return; }

		tip = button.closest( _settings.tipSelector );

		// Bail if tip wrapper is not available
		if ( ! tip ) { return; }

		positionTipContent( tip );
	};



	/**
	 * Initialize info tip event listeners.
	 */
	_publicMethods.init = function() {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		document.addEventListener( 'click', handleButtonClick );
		document.addEventListener( 'click', handleDocumentClick );
		document.addEventListener( 'keydown', handleKeydown );
		document.addEventListener( 'mouseover', handleTipMouseOver );
		document.addEventListener( 'mouseout', handleTipMouseOut );
		document.addEventListener( 'focusin', handleTipFocusIn );
		window.addEventListener( 'resize', syncVisibleTipPosition );
		window.addEventListener( 'scroll', syncVisibleTipPosition, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
