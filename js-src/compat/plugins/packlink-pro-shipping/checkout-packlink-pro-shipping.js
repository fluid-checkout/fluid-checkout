/**
 * Checkout scripts for: Packlink PRO Shipping (by Packlink Shipping S.L.).
 */

(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutPacklinkProShipping = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		buttonSelector: '.lp-select-button',
		shippingSectionSelector: '.fc-shipping-method__packages, .woocommerce-checkout-review-order-table, .woocommerce-shipping-methods, .shipping',
		dropOffIdFieldSelector: 'input[name="packlink_drop_off_id"]',
		updateCheckoutDelay: 300,
		selectionPollInterval: 100,
		selectionPollMaxAttempts: 20,
	};



	/**
	 * METHODS
	 */



	/**
	 * Trigget checkout fragments update.
	 */
	var triggerUpdateCheckout = function() {
		// Bail if jQuery is not available
		if ( ! _hasJQuery ) { return; }

		// Trigger update checkout
		$( document.body ).trigger( 'update_checkout' );
	}

	/**
	 * Wait until Packlink fills the drop-off id field, then trigger checkout update.
	 */
	var maybeTriggerUpdateCheckoutAfterSelection = function() {
		var attempts = 0;

		var intervalId = window.setInterval( function() {
			attempts++;

			var dropOffIdField = document.querySelector( _settings.dropOffIdFieldSelector );
			if ( dropOffIdField && dropOffIdField.value ) {
				window.clearInterval( intervalId );
				window.setTimeout( triggerUpdateCheckout, _settings.updateCheckoutDelay );
				return;
			}

			// Stop polling after max attempts
			if ( attempts >= _settings.selectionPollMaxAttempts ) {
				window.clearInterval( intervalId );
			}
		}, _settings.selectionPollInterval );
	};

	/**
	 * Re-run Packlink checkout config scripts after fragment updates.
	 * WooCommerce/jQuery fragment replacement inserts script tags without executing them.
	 *
	 * Packlink.checkout.init() is not idempotent (adds click listeners each call),
	 * so only initialize once per drop-off button DOM node.
	 */
	var maybeReinitializePacklink = function() {
		// Bail if Packlink checkout API is not available
		if ( ! window.Packlink || ! Packlink.checkout ) { return; }

		var shippingSection = document.querySelector( _settings.shippingSectionSelector );
		if ( ! shippingSection ) { return; }

		// Bail if Packlink drop-off UI is not present
		var dropOffButton = shippingSection.querySelector( '#packlink-drop-off-picker' );
		if ( ! dropOffButton ) { return; }

		// Fragment was not replaced: button already initialized, only restore address/fields
		if ( '1' === dropOffButton.getAttribute( 'data-fc-packlink-bound' ) ) {
			if ( 'function' === typeof Packlink.checkout.setDropOffAddress ) {
				Packlink.checkout.setDropOffAddress();
			}
			return;
		}

		// Re-execute Packlink config scripts (locations, selected id, etc.),
		// but skip init scripts to avoid binding handlers twice.
		var scripts = shippingSection.querySelectorAll( 'script' );
		for ( var i = 0; i < scripts.length; i++ ) {
			var scriptContent = scripts[ i ].textContent || '';
			if ( -1 === scriptContent.indexOf( 'Packlink.checkout' ) ) { continue; }
			if ( -1 !== scriptContent.indexOf( 'Packlink.checkout.init' ) ) { continue; }

			var newScript = document.createElement( 'script' );
			newScript.text = scriptContent;
			scripts[ i ].parentNode.replaceChild( newScript, scripts[ i ] );
		}

		// Bind handlers once for this button node
		if ( 'function' === typeof Packlink.checkout.init ) {
			Packlink.checkout.init();
		}
		dropOffButton.setAttribute( 'data-fc-packlink-bound', '1' );

		// Restore selected drop-off address/hidden fields when available
		if ( 'function' === typeof Packlink.checkout.setDropOffAddress ) {
			Packlink.checkout.setDropOffAddress();
		}
	};



	/**
	 * Handle document clicks and route to the appropriate function.
	 */
	var handleClick = function( e ) {
		// CHOOSE PICKUP POINT
		if ( e.target.closest( _settings.buttonSelector ) ) {
			// Wait until Packlink writes the selected drop-off into the hidden field
			// before triggering checkout update, so the selection is not discarded.
			maybeTriggerUpdateCheckoutAfterSelection();
		}
	};



	/**
	 * Initialize component and set related handlers.
	 */
	_publicMethods.init = function( options ) {
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );

		// Re-initialize Packlink after checkout fragments are replaced
		if ( _hasJQuery ) {
			$( document.body ).on( 'updated_checkout', maybeReinitializePacklink );
		}

		_hasInitialized = true;
	};


	
	//
	// Public APIs
	//
	return _publicMethods;

});
