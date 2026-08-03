/**
 * File admin-settings-tools.js
 *
 * Handles Backup & reset tool actions on the Fluid Checkout Tools settings screen.
 */
( function( $ ) {

	'use strict';



	jQuery( document ).ready( function() {
		$( document.body ).on( 'click', '.fc-settings-tools__action', on_action_button_click );
	} );



	/**
	 * Confirm tool actions before submitting the associated standalone form.
	 *
	 * @param  {Event}  event  Click event.
	 */
	function on_action_button_click( event ) {
		var confirm_message = $( this ).attr( 'data-fc-confirm' );

		// Bail if no confirm message
		if ( ! confirm_message ) { return; }

		// Bail if user cancels
		if ( ! window.confirm( confirm_message ) ) {
			event.preventDefault();
		}
	}

} )( jQuery );
