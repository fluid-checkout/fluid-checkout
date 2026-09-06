/**
 * License key settings field — per-field Activate AJAX and status refresh.
 */

(function() {

	'use strict';

	var _settings = {
		wrapSelector:      '.fc-license-key__field-wrap',
		rowSelector:       '.fc-license-key__row',
		inputSelector:     '.fc-license-key__input',
		activateSelector:  '.fc-license-key__activate',
		statusSelector:    '.fc-license-key__status',
	};



	/**
	 * Get localized admin settings.
	 */
	var getConfig = function() {
		return window.fcAdminLicenseKeySettings || {};
	};



	/**
	 * Clear status markup under a field.
	 *
	 * @param {Element} input License key input.
	 */
	var clearStatusForInput = function( input ) {
		var cell = input.closest( 'td' );

		// Bail if cell not found
		if ( ! cell ) { return; }

		var status = cell.querySelector( _settings.statusSelector );

		// Bail if status element not found
		if ( ! status ) { return; }

		status.innerHTML = '';
	};



	/**
	 * Apply status UI payload to a field.
	 *
	 * @param {Element} input  License key input.
	 * @param {Object}  status Status payload from AJAX.
	 */
	var applyStatusToInput = function( input, status ) {
		var cell = input.closest( 'td' );

		// Bail if cell or status missing
		if ( ! cell || ! status ) { return; }

		var statusEl = cell.querySelector( _settings.statusSelector );

		// Bail if status element not found
		if ( ! statusEl ) { return; }

		var strongClass = status.status_class || 'fc-license-key__status-label--empty';
		var statusText = status.status_text || '';
		var actionHtml = status.action_html || '';

		statusEl.innerHTML = '<strong class="' + strongClass + '">' + statusText + '</strong> ' + actionHtml;
	};



	/**
	 * Find license key input by option id.
	 *
	 * @param {string} optionId Setting option id.
	 */
	var getInputByOptionId = function( optionId ) {
		return document.querySelector( _settings.inputSelector + '[data-option-id="' + optionId + '"]' );
	};



	/**
	 * Handle Activate click for one field.
	 *
	 * @param {Event}   e      Click event.
	 * @param {Element} button Activate button.
	 */
	var handleActivateClick = function( e, button ) {
		e.preventDefault();

		var config = getConfig();
		var wrap = button.closest( _settings.wrapSelector );

		// Bail if wrap missing or AJAX not configured
		if ( ! wrap || ! config.ajaxUrl || ! config.nonce ) { return; }

		var input = wrap.querySelector( _settings.inputSelector );

		// Bail if input missing
		if ( ! input ) { return; }

		var pluginSlug = input.getAttribute( 'data-plugin-slug' ) || '';
		var optionId = input.getAttribute( 'data-option-id' ) || '';
		var productUrl = input.getAttribute( 'data-product-url' ) || '';
		var licenseKey = input.value || '';

		button.disabled = true;
		button.classList.add( 'is-busy' );

		var body = new window.FormData();
		body.append( 'action', 'fc_lcs_activate_license' );
		body.append( 'nonce', config.nonce );
		body.append( 'plugin_slug', pluginSlug );
		body.append( 'option_id', optionId );
		body.append( 'license_key', licenseKey );
		body.append( 'product_url', productUrl );

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} ).then( function( response ) {
			return response.json();
		} ).then( function( payload ) {
			button.disabled = false;
			button.classList.remove( 'is-busy' );

			if ( ! payload || ! payload.success || ! payload.data ) {
				var message = config.i18n && config.i18n.genericError ? config.i18n.genericError : 'Error';
				if ( payload && payload.data && payload.data.message ) {
					message = payload.data.message;
				}
				applyStatusToInput( input, {
					status_class: 'fc-license-key__status-label--error',
					status_text: message,
					action_html: '',
				} );
				return;
			}

			if ( typeof payload.data.masked_value === 'string' ) {
				input.value = payload.data.masked_value;
			}

			applyStatusToInput( input, payload.data.status );
		} ).catch( function() {
			button.disabled = false;
			button.classList.remove( 'is-busy' );

			applyStatusToInput( input, {
				status_class: 'fc-license-key__status-label--error',
				status_text: config.i18n && config.i18n.genericError ? config.i18n.genericError : 'Error',
				action_html: '',
			} );
		} );
	};



	/**
	 * Refresh license statuses on page load when needed.
	 */
	var refreshStatusesOnLoad = function() {
		var config = getConfig();
		var inputs = document.querySelectorAll( _settings.inputSelector );

		// Bail if no fields or AJAX not configured
		if ( ! inputs.length || ! config.ajaxUrl || ! config.nonce ) { return; }

		var body = new window.FormData();
		body.append( 'action', 'fc_lcs_refresh_license_statuses' );
		body.append( 'nonce', config.nonce );
		body.append( 'force', '0' );

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} ).then( function( response ) {
			return response.json();
		} ).then( function( payload ) {
			if ( ! payload || ! payload.success || ! payload.data || ! payload.data.statuses ) { return; }

			Object.keys( payload.data.statuses ).forEach( function( optionId ) {
				var input = getInputByOptionId( optionId );
				if ( ! input ) { return; }
				applyStatusToInput( input, payload.data.statuses[ optionId ] );
			} );
		} ).catch( function() {
			// Silent fail on page-load refresh — cached status remains visible.
		} );
	};



	/**
	 * Route click events for Activate buttons.
	 *
	 * @param {Event} e Click event.
	 */
	var handleClick = function( e ) {
		var activateButton = e.target.closest( _settings.activateSelector );

		// ACTIVATE
		if ( activateButton ) {
			handleActivateClick( e, activateButton );
		}
	};



	/**
	 * Clear field status when the user edits the input.
	 *
	 * @param {Event} e Input/change event.
	 */
	var handleInputChange = function( e ) {
		var input = e.target.closest( _settings.inputSelector );

		// Bail if not a license key input
		if ( ! input ) { return; }

		clearStatusForInput( input );
	};



	/**
	 * Prevent accidental WooCommerce settings form submit on this section.
	 *
	 * @param {Event} e Submit event.
	 */
	var handleFormSubmit = function( e ) {
		var form = e.target;

		// Bail if form does not contain license key fields
		if ( ! form || ! form.querySelector( _settings.inputSelector ) ) { return; }

		// Bail if not the license keys section (tab/section in URL)
		if ( window.location.search.indexOf( 'section=license_keys' ) === -1 ) { return; }

		e.preventDefault();
		e.stopPropagation();
	};



	document.addEventListener( 'click', handleClick, true );
	document.addEventListener( 'input', handleInputChange, true );
	document.addEventListener( 'change', handleInputChange, true );
	document.addEventListener( 'submit', handleFormSubmit, true );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', refreshStatusesOnLoad );
	} else {
		refreshStatusesOnLoad();
	}

})();
