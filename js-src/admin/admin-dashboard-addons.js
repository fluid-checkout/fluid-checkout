/**
 * Dashboard Add-ons — local plugin activate via AJAX.
 */

(function() {

	'use strict';

	var _settings = {
		activateSelector: '.fc-addons__item-action--activate',
	};



	/**
	 * Get localized admin settings.
	 */
	var getConfig = function() {
		return window.fcAdminDashboardAddonsSettings || {};
	};



	/**
	 * Show an inline notice on an add-on card.
	 *
	 * @param {Element} button Action button.
	 * @param {string}  message Notice message.
	 * @param {string}  type    success|error.
	 */
	var showItemNotice = function( button, message, type ) {
		var item = button.closest( '.fc-addons__item, .fc-settings-card--promo' );
		var actions = button.closest( '.fc-addons__item-actions' );
		var notice;

		// Prefer the actions block notice when present
		if ( actions ) {
			notice = actions.querySelector( '.fc-addons__item-action-notice' );

			if ( ! notice ) {
				notice = document.createElement( 'div' );
				notice.className = 'fc-addons__item-action-notice';
				actions.appendChild( notice );
			}
		} else {
			// Bail if item missing
			if ( ! item ) { return; }

			notice = item.querySelector( '.fc-addons__item-action-notice' );

			if ( ! notice ) {
				notice = document.createElement( 'div' );
				notice.className = 'fc-addons__item-action-notice';
				button.parentNode.appendChild( notice );
			}
		}

		notice.hidden = false;
		notice.className = 'fc-addons__item-action-notice fc-addons__item-action-notice--' + type;
		notice.textContent = message;
	};



	/**
	 * Activate a locally installed plugin.
	 *
	 * @param {Event} event Click event.
	 */
	var onActivateClick = function( event ) {
		var button = event.target.closest( _settings.activateSelector );
		var config = getConfig();
		var i18n = config.i18n || {};
		var plugin;
		var body;

		// Bail if not an activate button
		if ( ! button ) { return; }

		event.preventDefault();

		plugin = button.getAttribute( 'data-plugin' ) || '';

		// Bail if plugin missing
		if ( ! plugin || ! config.ajaxUrl || ! config.activateNonce ) { return; }

		button.disabled = true;
		button.textContent = i18n.processing || 'Processing…';

		body = new FormData();
		body.append( 'action', 'fc_activate_plugin' );
		body.append( 'nonce', config.activateNonce );
		body.append( 'plugin', plugin );

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( payload ) {
				if ( ! payload || ! payload.success ) {
					showItemNotice( button, ( payload && payload.data && payload.data.message ) || i18n.genericError || 'Error', 'error' );
					button.disabled = false;
					button.textContent = i18n.activate || 'Activate plugin';
					return;
				}

				if ( payload.data && payload.data.reload ) {
					window.location.reload();
					return;
				}

				showItemNotice( button, ( payload.data && payload.data.message ) || '', 'success' );
			} )
			.catch( function() {
				showItemNotice( button, i18n.genericError || 'Error', 'error' );
				button.disabled = false;
				button.textContent = i18n.activate || 'Activate plugin';
			} );
	};



	/**
	 * Initialize Dashboard Add-ons interactions.
	 */
	var init = function() {
		document.addEventListener( 'click', onActivateClick );
	};

	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}

})();
