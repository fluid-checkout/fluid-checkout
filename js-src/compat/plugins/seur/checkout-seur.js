/**
 * Checkout scripts for: SEUR Oficial (by SEUR Oficial).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutSeur = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		select2FieldsSelector: '.seur-pickup-select2',
	};



	/**
	 * METHODS
	 */



	/**
	 * Sync the enhanced select display with the native select value.
	 * Required because Maplace sets the select value with jQuery `.val()` and no `change` event when a map marker is clicked.
	 *
	 * @param  {Element}  select  The SEUR pickup select element.
	 */
	_publicMethods.syncPickupSelectDisplay = function( select ) {
		// Bail if select is not available
		if ( ! select ) { return; }

		// Maybe sync the TomSelect UI
		if ( window.FCEnhancedSelect && select.tomselect ) {
			FCEnhancedSelect.syncSelectedValue( select );
			return;
		}

		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Maybe sync the Select2 UI without triggering the native `change`
		// The `change.select2` event updates the Select2 display without running Maplace `ViewOnMap` or reloading checkout fragments
		var $select = $( select );
		if ( $select.data( 'select2' ) ) {
			$select.trigger( 'change.select2' );
		}
	};



	/**
	 * Initialize Select2 / enhanced select on SEUR pickup fields.
	 */
	var initSelect2Fields = function() {
		setTimeout( function() {
			$( _settings.select2FieldsSelector ).select2();
		}, 30 ); // Arbitrary delay to ensure the fields are properly initialized.
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function() {
		if ( _hasInitialized ) { return; }

		// Maybe initialize select2 fields
		if ( _hasJQuery ) {
			initSelect2Fields();
			$( document.body ).on( 'updated_checkout', initSelect2Fields );
		}

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

});
