/**
 * Dashboard Add-ons — site key validate/remove and plugin install/activate via AJAX.
 */

(function() {

	'use strict';

	var _settings = {
		wrapSelector:           '.fc-addons__site-key',
		formSelector:           '.fc-addons__site-key-form',
		inputSelector:          '.fc-addons__site-key-input',
		actionsSelector:        '.fc-addons__site-key-field-actions',
		descriptionSelector:    '.fc-addons__site-key-description',
		validateSelector:       '.fc-addons__site-key-validate',
		removeSelector:         '.fc-addons__site-key-remove',
		resultSelector:         '.fc-addons__site-key-result',
		addonsListSelector:     'ul.fc-addons-list:not(.fc-addons-list--pro)',
		activateSelector:       '.fc-addons__item-action--activate',
		installSelector:        '.fc-addons__item-action--install',
		licenseSelectSelector:  '.fc-addons__item-license-select',
	};



	/**
	 * Get localized admin settings.
	 */
	var getConfig = function() {
		return window.fcAdminDashboardAddonsSettings || {};
	};



	/**
	 * Show an inline site key notice.
	 *
	 * @param {string} message Notice message (plain text or trusted HTML with links).
	 * @param {string} type    success|error.
	 */
	var showSiteKeyNotice = function( message, type ) {
		var wrap = document.querySelector( _settings.wrapSelector );

		// Bail if wrap missing
		if ( ! wrap ) { return; }

		var result = wrap.querySelector( _settings.resultSelector );
		var line;

		if ( ! result ) {
			result = document.createElement( 'div' );
			result.className = 'fc-addons__site-key-result';
			wrap.appendChild( result );
		}

		result.className = 'fc-addons__site-key-result fc-addons__site-key-result--' + type;
		result.innerHTML = '<p class="fc-addons__site-key-result-line"></p>';
		line = result.querySelector( '.fc-addons__site-key-result-line' );

		// Allow server-provided HTML links in error messages
		if ( -1 !== String( message ).indexOf( '<' ) ) {
			line.innerHTML = message;
		} else {
			line.textContent = message;
		}

		result.hidden = false;
	};



	/**
	 * Clear the inline site key notice.
	 */
	var clearSiteKeyNotice = function() {
		var result = document.querySelector( _settings.resultSelector );

		// Bail if result missing
		if ( ! result ) { return; }

		result.innerHTML = '';
		result.hidden = true;
		result.className = 'fc-addons__site-key-result';
	};



	/**
	 * Set busy state on a control.
	 *
	 * @param {Element} control Control element.
	 * @param {boolean} isBusy  Whether busy.
	 */
	var setBusy = function( control, isBusy ) {
		var config = getConfig();

		// Bail if control missing
		if ( ! control ) { return; }

		control.disabled = isBusy;
		control.classList.toggle( 'is-busy', isBusy );

		if ( isBusy ) {
			control.dataset.originalLabel = control.textContent;
			control.textContent = ( config.i18n && config.i18n.processing ) ? config.i18n.processing : 'Processing…';
			return;
		}

		if ( control.dataset.originalLabel ) {
			control.textContent = control.dataset.originalLabel;
			delete control.dataset.originalLabel;
		}
	};



	/**
	 * Post an admin-ajax FormData request.
	 *
	 * @param {FormData} body Request body.
	 * @return {Promise}
	 */
	var postAjax = function( body ) {
		var config = getConfig();

		return window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} ).then( function( response ) {
			return response.json();
		} );
	};



	/**
	 * Replace the main add-ons list items from an HTML fragment.
	 *
	 * @param {string} html List items HTML.
	 */
	var updateAddonsList = function( html ) {
		var list = document.querySelector( _settings.addonsListSelector );

		// Bail if list missing or HTML empty
		if ( ! list || 'string' !== typeof html ) { return; }

		list.innerHTML = html;
	};



	/**
	 * Render Validate or Remove actions for the site key field.
	 *
	 * @param {boolean} hasSiteKey Whether a site key is saved.
	 */
	var renderSiteKeyActions = function( hasSiteKey ) {
		var wrap = document.querySelector( _settings.wrapSelector );
		var actions = wrap ? wrap.querySelector( _settings.actionsSelector ) : null;
		var config = getConfig();
		var i18n = config.i18n || {};
		var validateLabel = i18n.validate || 'Validate';
		var removeLabel = i18n.removeKey || 'Remove key';

		// Bail if actions container missing
		if ( ! actions ) { return; }

		if ( hasSiteKey ) {
			actions.innerHTML = '<button type="button" class="button fc-addons__site-key-remove"></button>';
			actions.querySelector( _settings.removeSelector ).textContent = removeLabel;
			return;
		}

		actions.innerHTML = '<button type="button" class="button button-primary fc-addons__site-key-validate"></button>';
		actions.querySelector( _settings.validateSelector ).textContent = validateLabel;
	};



	/**
	 * Append the Manage licenses description link.
	 *
	 * @param {Element} description Description element.
	 */
	var appendManageLicensesLink = function( description ) {
		var config = getConfig();
		var i18n = config.i18n || {};
		var manageLabel = i18n.manageLicenses || 'Manage licenses at fluidcheckout.com';
		var manageUrl = i18n.manageLicensesUrl || '';
		var manageLink;

		// Bail if description or URL missing
		if ( ! description || ! manageUrl ) { return; }

		description.appendChild( document.createTextNode( ' ' ) );

		manageLink = document.createElement( 'a' );
		manageLink.className = 'fc-addons__site-key-manage';
		manageLink.href = manageUrl;
		manageLink.target = '_blank';
		manageLink.rel = 'noopener noreferrer';
		manageLink.textContent = manageLabel;
		description.appendChild( manageLink );
	};



	/**
	 * Update the site key field description for saved or empty state.
	 *
	 * @param {boolean} hasSiteKey Whether a site key is saved.
	 */
	var renderSiteKeyDescription = function( hasSiteKey ) {
		var wrap = document.querySelector( _settings.wrapSelector );
		var description = wrap ? wrap.querySelector( _settings.descriptionSelector ) : null;
		var config = getConfig();
		var i18n = config.i18n || {};
		var text = hasSiteKey
			? ( i18n.descriptionSaved || 'Install and activate below the add-ons you own, or purchase the add-ons you need.' )
			: ( i18n.descriptionEmpty || 'Paste your site key to install and activate products you already own.' );
		var linkLabel = i18n.getSiteKey || 'Get your site key at fluidcheckout.com';
		var linkUrl = i18n.getSiteKeyUrl || 'https://fluidcheckout.com/my-account/sites/';
		var link;

		// Bail if description missing
		if ( ! description ) { return; }

		description.textContent = '';
		description.appendChild( document.createTextNode( text + ( hasSiteKey ? '' : ' ' ) ) );

		// Empty state: get site key link only (no manage licenses)
		if ( ! hasSiteKey ) {
			link = document.createElement( 'a' );
			link.href = linkUrl;
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			link.textContent = linkLabel;
			description.appendChild( link );
			return;
		}

		appendManageLicensesLink( description );
	};



	/**
	 * Apply saved or empty site key field state after validate/clear.
	 *
	 * @param {Object} data AJAX success payload data.
	 */
	var applySiteKeyFieldState = function( data ) {
		var wrap = document.querySelector( _settings.wrapSelector );
		var input = wrap ? wrap.querySelector( _settings.inputSelector ) : null;
		var hasSiteKey = !!( data && data.has_site_key );
		var displayValue = ( data && data.site_key_display ) ? data.site_key_display : '';

		// Bail if input missing
		if ( ! input ) { return; }

		input.value = hasSiteKey ? displayValue : '';
		input.disabled = hasSiteKey;
		input.classList.toggle( 'fc-addons__site-key-input--valid', hasSiteKey );
		renderSiteKeyActions( hasSiteKey );
		renderSiteKeyDescription( hasSiteKey );
	};



	/**
	 * Get or create the notice element for an add-on action button.
	 *
	 * Prefers a shared notice in the actions container when multiple buttons are stacked.
	 *
	 * @param {Element} control Action button.
	 * @return {Element|null}
	 */
	var getAddonActionNoticeElement = function( control ) {
		var notice;
		var actions;

		// Bail if control missing
		if ( ! control ) { return null; }

		notice = control.nextElementSibling;

		// Reuse the notice placed directly after this button
		if ( notice && notice.classList.contains( 'fc-addons__item-action-notice' ) ) {
			return notice;
		}

		actions = control.closest( '.fc-addons__item-actions' );

		if ( actions ) {
			notice = actions.querySelector( '.fc-addons__item-action-notice' );

			if ( notice ) {
				return notice;
			}

			notice = document.createElement( 'div' );
			notice.className = 'fc-addons__item-action-notice';
			actions.appendChild( notice );
			return notice;
		}

		notice = document.createElement( 'div' );
		notice.className = 'fc-addons__item-action-notice';
		control.insertAdjacentElement( 'afterend', notice );

		return notice;
	};



	/**
	 * Show an inline notice below an add-on action button.
	 *
	 * @param {Element} control Control that triggered the request.
	 * @param {string}  message Notice message.
	 * @param {string}  type    success|error.
	 */
	var showAddonActionNotice = function( control, message, type ) {
		var notice = getAddonActionNoticeElement( control );

		// Bail if notice missing
		if ( ! notice ) { return; }

		notice.className = 'fc-addons__item-action-notice fc-addons__item-action-notice--' + type;
		notice.textContent = message;
		notice.hidden = false;
	};



	/**
	 * Clear the inline notice below an add-on action button.
	 *
	 * @param {Element} control Control in the actions block.
	 */
	var clearAddonActionNotice = function( control ) {
		var notice = getAddonActionNoticeElement( control );

		// Bail if notice missing
		if ( ! notice ) { return; }

		notice.textContent = '';
		notice.hidden = true;
		notice.className = 'fc-addons__item-action-notice';
	};



	/**
	 * Convert an Install button into an Activate button after a successful install.
	 *
	 * @param {Element} button Install button.
	 */
	var convertInstallButtonToActivate = function( button ) {
		var config = getConfig();
		var i18n = config.i18n || {};
		var activateLabel = i18n.activate || 'Activate plugin';

		// Bail if button missing
		if ( ! button ) { return; }

		button.classList.remove( 'button-primary', 'button--install', 'fc-addons__item-action--install', 'is-busy' );
		button.classList.add( 'fc-addons__item-action--activate' );
		button.textContent = activateLabel;
		button.disabled = false;
		button.removeAttribute( 'aria-busy' );

		if ( button.dataset.originalLabel ) {
			delete button.dataset.originalLabel;
		}
	};



	/**
	 * Handle a successful AJAX payload.
	 *
	 * @param {Object}  payload Parsed JSON response.
	 * @param {Element} control Control that triggered the request.
	 * @param {string}  noticeTarget siteKey|addonAction.
	 */
	var handleSuccessPayload = function( payload, control, noticeTarget ) {
		noticeTarget = noticeTarget || 'siteKey';

		if ( ! payload || ! payload.success ) {
			var message = ( payload && payload.data && payload.data.message ) ? payload.data.message : ( getConfig().i18n && getConfig().i18n.genericError ? getConfig().i18n.genericError : 'Error' );

			if ( 'addonAction' === noticeTarget ) {
				showAddonActionNotice( control, message, 'error' );
			} else {
				showSiteKeyNotice( message, 'error' );
			}

			setBusy( control, false );
			return;
		}

		if ( payload.data && payload.data.message ) {
			if ( 'addonAction' === noticeTarget ) {
				showAddonActionNotice( control, payload.data.message, 'success' );
			} else {
				showSiteKeyNotice( payload.data.message, 'success' );
			}
		}

		// Update site key field and add-ons list without a full page reload
		if ( payload.data && ( 'undefined' !== typeof payload.data.has_site_key || 'undefined' !== typeof payload.data.addons_html ) ) {
			if ( 'undefined' !== typeof payload.data.has_site_key ) {
				applySiteKeyFieldState( payload.data );
			}

			if ( 'string' === typeof payload.data.addons_html ) {
				updateAddonsList( payload.data.addons_html );
			}

			setBusy( control, false );
			return;
		}

		// Swap Install → Activate after a successful package install
		if ( payload.data && payload.data.show_activate ) {
			convertInstallButtonToActivate( control );
			return;
		}

		if ( payload.data && payload.data.reload ) {
			window.location.reload();
			return;
		}

		setBusy( control, false );
	};



	/**
	 * Handle Validate click.
	 *
	 * @param {Event}   e      Click event.
	 * @param {Element} button Validate button.
	 */
	var handleValidateClick = function( e, button ) {
		e.preventDefault();

		var config = getConfig();
		var wrap = button.closest( _settings.wrapSelector );
		var input = wrap ? wrap.querySelector( _settings.inputSelector ) : null;

		// Bail if AJAX not configured
		if ( ! config.ajaxUrl || ! config.validateNonce ) { return; }

		// Bail if the field is locked with a saved key
		if ( input && input.disabled ) { return; }

		clearSiteKeyNotice();
		setBusy( button, true );

		var body = new window.FormData();
		body.append( 'action', 'fc_validate_site_key' );
		body.append( 'nonce', config.validateNonce );
		body.append( 'fc_site_key', input ? input.value : '' );

		postAjax( body ).then( function( payload ) {
			handleSuccessPayload( payload, button );
		} ).catch( function() {
			showSiteKeyNotice( config.i18n && config.i18n.genericError ? config.i18n.genericError : 'Error', 'error' );
			setBusy( button, false );
		} );
	};



	/**
	 * Handle Remove key click.
	 *
	 * @param {Event}   e      Click event.
	 * @param {Element} button Remove button.
	 */
	var handleRemoveClick = function( e, button ) {
		e.preventDefault();

		var config = getConfig();

		// Bail if AJAX not configured
		if ( ! config.ajaxUrl || ! config.clearNonce ) { return; }

		clearSiteKeyNotice();
		setBusy( button, true );

		var body = new window.FormData();
		body.append( 'action', 'fc_clear_site_key' );
		body.append( 'nonce', config.clearNonce );

		postAjax( body ).then( function( payload ) {
			handleSuccessPayload( payload, button );
		} ).catch( function() {
			showSiteKeyNotice( config.i18n && config.i18n.genericError ? config.i18n.genericError : 'Error', 'error' );
			setBusy( button, false );
		} );
	};



	/**
	 * Resolve the license key hash for an Install/Activate control.
	 *
	 * @param {Element} button Action button.
	 * @return {string}
	 */
	var getSelectedLicenseKeyHash = function( button ) {
		var actions;
		var select;
		var hash = button && button.getAttribute( 'data-license-key-hash' ) ? button.getAttribute( 'data-license-key-hash' ) : '';

		// Bail if button missing
		if ( ! button ) { return ''; }

		actions = button.closest( '.fc-addons__item-actions' );
		select = actions ? actions.querySelector( _settings.licenseSelectSelector ) : null;

		if ( select && select.value ) {
			return select.value;
		}

		return hash;
	};



	/**
	 * Handle Install / Activate plugin click.
	 *
	 * @param {Event}   e          Click event.
	 * @param {Element} button     Action button.
	 * @param {string}  ajaxAction WordPress AJAX action.
	 * @param {string}  nonceKey   Localized nonce key.
	 */
	var handlePluginActionClick = function( e, button, ajaxAction, nonceKey ) {
		e.preventDefault();

		var config = getConfig();
		var plugin = button.getAttribute( 'data-plugin' ) || '';
		var licenseKeyHash = getSelectedLicenseKeyHash( button );
		var nonce = config[ nonceKey ];

		// Bail if AJAX not configured
		if ( ! config.ajaxUrl || ! nonce || ! plugin ) { return; }

		clearAddonActionNotice( button );
		setBusy( button, true );

		var body = new window.FormData();
		body.append( 'action', ajaxAction );
		body.append( 'nonce', nonce );
		body.append( 'plugin', plugin );

		if ( licenseKeyHash ) {
			body.append( 'license_key_hash', licenseKeyHash );
		}

		postAjax( body ).then( function( payload ) {
			handleSuccessPayload( payload, button, 'addonAction' );
		} ).catch( function() {
			showAddonActionNotice( button, config.i18n && config.i18n.genericError ? config.i18n.genericError : 'Error', 'error' );
			setBusy( button, false );
		} );
	};



	/**
	 * Prevent accidental form submit for the site key form.
	 *
	 * @param {Event} e Submit event.
	 */
	var handleFormSubmit = function( e ) {
		var form = e.target.closest( _settings.formSelector );

		// Bail if not the site key form
		if ( ! form ) { return; }

		e.preventDefault();
	};



	/**
	 * Route click events.
	 *
	 * @param {Event} e Click event.
	 */
	var handleClick = function( e ) {
		var validateButton = e.target.closest( _settings.validateSelector );
		var removeButton = e.target.closest( _settings.removeSelector );
		var activateButton = e.target.closest( _settings.activateSelector );
		var installButton = e.target.closest( _settings.installSelector );

		// VALIDATE
		if ( validateButton ) {
			handleValidateClick( e, validateButton );
			return;
		}

		// REMOVE
		if ( removeButton ) {
			handleRemoveClick( e, removeButton );
			return;
		}

		// ACTIVATE
		if ( activateButton ) {
			handlePluginActionClick( e, activateButton, 'fc_activate_plugin', 'activateNonce' );
			return;
		}

		// INSTALL
		if ( installButton ) {
			handlePluginActionClick( e, installButton, 'fc_install_plugin', 'installNonce' );
		}
	};



	/**
	 * Initialize dashboard add-ons AJAX handlers.
	 */
	var init = function() {
		// Bail if settings missing
		if ( ! getConfig().ajaxUrl ) { return; }

		document.addEventListener( 'click', handleClick );
		document.addEventListener( 'submit', handleFormSubmit );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

})();
