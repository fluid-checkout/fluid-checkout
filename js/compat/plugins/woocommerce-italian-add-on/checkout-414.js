/**
 * Compatibility helpers for the WooCommerce Italian Add-On.
 *
 * Ensures the Italian helper callbacks trigger whenever Fluid Checkout updates
 * the UI so that the add-on enforces its required logic after AJAX refreshes.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.CheckoutCompatibilityWooCommerceItalianAddOn = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {
	'use strict';

	var _$ = root.jQuery;
	var _hasInitialized = false;
	var _publicMethods = {};
	var _eventsHooked = false;
	var _settings = {
		namespace: '.fcItalianAddOn',
	};


	/**
	 * METHODS
	 */



	/**
	 * Handle change events on the fields Fluid Checkout exposes to the Italian add-on.
	 */
	var handleChange = function (event) {
		if (! event || ! event.target) {
			return;
		}

		// When the Italian customer type selector changes, run its helper if available.
		if (event.target.matches('#billing_customer_type') && typeof wcpdf_IT_billing_customer_type_change === 'function') {
			wcpdf_IT_billing_customer_type_change();
		}

		// When the billing country selector changes, ask the add-on to revalidate required fields.
		if (event.target.matches('#billing_country') && typeof wcpdf_IT_check_required === 'function') {
			wcpdf_IT_check_required();
		}
	};

	/**
	 * Run all available Italian Add-On helpers so they reapply their rules after updates.
	 */
	var refreshState = function () {
		if (typeof _$ !== 'function') {
			return;
		}

		var helpers = [
			wcpdf_IT_billing_customer_type_change,
			wcpdf_IT_check_required,
			wcpdf_IT_check_cf2,
			wcpdf_IT_check_PEC,
			wcpdf_IT_check_billing_company,
			wcpdf_IT_check_visible_required_fields,
			wcpdf_IT_billing_invoice_type_change,
		];

		helpers.forEach(function (helper) {
			if (typeof helper === 'function') {
				helper();
			}
		});
	};

	/**
	 * Refresh the state of the Italian add-on helpers so they reapply their rules after updates.
	 */
	_publicMethods.refreshState = refreshState;

	/**
	 * Register body events so we rerun helpers after AJAX updates.
	 */
	var registerBodyEvents = function () {
		if (_eventsHooked) {
			return;
		}

		if (typeof _$ === 'function') {
			var $body = _$( 'body' );

			if ($body.length) {
				$body.on('updated_checkout' + _settings.namespace, _publicMethods.refreshState);
				$body.on('fc_fragments_refreshed' + _settings.namespace, _publicMethods.refreshState);
			}
		}

		// Mirror other compat scripts by listening for change events on the document.
		document.addEventListener('change', handleChange, true);
		_eventsHooked = true;
	};

	/**
	 * Initialize the compatibility handler.
	 * 
	 * @param {object} options - The options to pass to the compatibility handler.
	 */
	_publicMethods.init = function (options) {
		if (typeof _$ !== 'function') {
			return;
		}

		if (! _hasInitialized) {
			_settings = FCUtils.extendObject(_settings, options || {});
			registerBodyEvents();
			_hasInitialized = true;
		}

		_publicMethods.refreshState();
	};

	if (typeof _$ === 'function') {
		_$(function () {
			_publicMethods.init();
		});
	}

	//
	// Public APIs
	//
	return _publicMethods;
});

