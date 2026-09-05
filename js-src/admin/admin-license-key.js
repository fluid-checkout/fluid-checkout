/**
 * License key settings field — unlock a saved value via Clear.
 */

(function() {

	'use strict';

	var _settings = {
		clearSelector:     '.fc-license-key__clear',
		wrapSelector:      '.fc-license-key__field-wrap',
		inputSelector:     '.fc-license-key__input',
		preservedSelector: '.fc-license-key__preserved-value',
		statusSelector:    '.fc-license-key__status',
	};



	/**
	 * Unlock the license key field for entering a new value.
	 *
	 * @param {Event}   e           Click event.
	 * @param {Element} clearButton Clear control element.
	 */
	var handleClearClick = function( e, clearButton ) {
		e.preventDefault();

		var wrap = clearButton.closest( _settings.wrapSelector );

		// Bail if field wrap not found
		if ( ! wrap ) { return; }

		var input = wrap.querySelector( _settings.inputSelector );
		var preserved = wrap.querySelector( _settings.preservedSelector );

		// Remove preserved value so an empty field can be saved
		if ( preserved && preserved.parentNode ) {
			preserved.parentNode.removeChild( preserved );
		}

		// Unlock and clear the visible field
		if ( input ) {
			input.disabled = false;
			input.value = '';
			input.focus();
		}

		// Remove the clear control
		if ( clearButton.parentNode ) {
			clearButton.parentNode.removeChild( clearButton );
		}

		// Clear status text under the field
		var cell = wrap.closest( 'td' );
		if ( cell ) {
			var status = cell.querySelector( _settings.statusSelector );
			if ( status ) {
				status.textContent = '';
			}
		}
	};



	/**
	 * Route click events for license key clear controls.
	 *
	 * @param {Event} e Click event.
	 */
	var handleClick = function( e ) {
		var clearButton = e.target.closest( _settings.clearSelector );

		// CLEAR FIELD
		if ( clearButton ) {
			handleClearClick( e, clearButton );
		}
	};



	document.addEventListener( 'click', handleClick, true );

})();
