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
	var _eventsHooked = false;
	var _settings = {
		namespace: '.fcItalianAddOn',
	};

	/**
	 * Attach a change handler for the selector if the field exists.
	 * 
	 * @param {string} selector - The selector to bind the change handler to.
	 * @param {function} callback - The callback function to run when the field changes.
	 */
	var bind = function (selector, callback) {
		if (typeof _$ !== 'function') {
			return;
		}

		var $field = _$(selector);
		if (! $field.length || typeof callback !== 'function') {
			return;
		}

		$field.off(_settings.namespace).on('change' + _settings.namespace, callback);
	};

	/**
	 * Bind the Italian customer type / country fields to the add-on helpers.
	 */
	var bindFields = function () {
		bind('#billing_customer_type', wcpdf_IT_billing_customer_type_change);
		bind('#billing_country', wcpdf_IT_check_required);
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

	var _publicMethods = {
		refreshState: refreshState,
	};

	/**
	 * Watch the main body events so we rerun helpers after AJAX updates.
	 */
	var registerBodyEvents = function () {
		if (_eventsHooked || typeof _$ !== 'function') {
			return;
		}

		var $body = _$( 'body' );
		if (! $body.length) {
			return;
		}

		$body.on('updated_checkout' + _settings.namespace, _publicMethods.refreshState);
		$body.on('fc_fragments_refreshed' + _settings.namespace, _publicMethods.refreshState);
		_eventsHooked = true;
	};

	/**
	 * Initialize the compatibility handler and set related handlers.
	 * 
	 * @param {object} options - The options to pass to the compatibility handler.
	 */
	_publicMethods.init = function (options) {
		if (typeof _$ !== 'function') {
			return;
		}

		if (! _hasInitialized) {
			_settings = FCUtils.extendObject(_settings, options || {});
			bindFields();
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

