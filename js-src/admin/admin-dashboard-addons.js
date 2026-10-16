/**
 * Dashboard Add-ons — local plugin activate via AJAX.
 *
 * DEPENDS ON:
 * - FCUtils // Settings merge
 */
(function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminDashboardAddons = factory( root );
	}
})( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = { };
	var _settings = {
		activateSelector:                     '.fc-addons__item-action--activate',
		itemSelector:                         '.fc-addons__item, .fc-settings-card--promo',
		actionsSelector:                      '.fc-addons__item-actions',
		itemActionNoticeSelector:             '.fc-addons__item-action-notice',

		itemActionNoticeClass:                'fc-addons__item-action-notice',
		itemActionNoticeSuccessClass:         'fc-addons__item-action-notice--success',
		itemActionNoticeErrorClass:           'fc-addons__item-action-notice--error',

		pluginAttribute:                      'data-plugin',
		labelAttribute:                       'data-fc-activate-label',
		disabledClass:                        'disabled',
		activatedButtonModifierClass:         'button--activated',
		activateActionClass:                  'fc-addons__item-action--activate',
		reloadAfterActivateDelay:             400,

		activatePluginAction:                 'fc_activate_plugin',
		pluginFieldName:                      'plugin',
		nonceFieldName:                       'nonce',
		actionFieldName:                      'action',

		ajaxUrl:                              '',
		activateNonce:                        '',
		i18n: {
			processing:                       'Processing…',
			genericError:                     'Something went wrong. Please try again.',
			activate:                         'Activate add-on',
			activated:                        'Activated',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Get the generic error message from settings.
	 *
	 * @return {string} Generic error message.
	 */
	var getGenericErrorMessage = function() {
		return _settings.i18n && _settings.i18n.genericError ? _settings.i18n.genericError : 'Error';
	};



	/**
	 * Get the Activate button label from settings.
	 *
	 * @return {string} Activate button label.
	 */
	var getActivateLabel = function() {
		return _settings.i18n && _settings.i18n.activate ? _settings.i18n.activate : 'Activate add-on';
	};



	/**
	 * Get the processing label from settings.
	 *
	 * @return {string} Processing label.
	 */
	var getProcessingLabel = function() {
		return _settings.i18n && _settings.i18n.processing ? _settings.i18n.processing : 'Processing…';
	};



	/**
	 * Get the Activated button label from settings.
	 *
	 * @return {string} Activated button label.
	 */
	var getActivatedLabel = function() {
		return _settings.i18n && _settings.i18n.activated ? _settings.i18n.activated : 'Activated';
	};



	/**
	 * Hide the inline notice on an add-on card or promo footer.
	 *
	 * @param {Element} button Activate button.
	 */
	var hideItemNotice = function( button ) {
		var item;
		var actions;
		var notice;

		// Bail if button missing
		if ( ! button ) { return; }

		actions = button.closest( _settings.actionsSelector );
		notice = actions ? actions.querySelector( _settings.itemActionNoticeSelector ) : null;

		// Hide the notice in the actions block when present
		if ( notice ) {
			notice.hidden = true;
			notice.textContent = '';
			return;
		}

		item = button.closest( _settings.itemSelector );
		notice = item ? item.querySelector( _settings.itemActionNoticeSelector ) : null;

		// Hide the notice on the card when present
		if ( notice ) {
			notice.hidden = true;
			notice.textContent = '';
		}
	};



	/**
	 * Replace the Activate control with the Activated state before a settings reload.
	 *
	 * @param {Element} button Activate button.
	 */
	var convertActivateButtonToActivated = function( button ) {
		// Bail if button missing
		if ( ! button ) { return; }

		button.classList.remove( 'button-primary', _settings.activateActionClass );
		button.classList.add( _settings.activatedButtonModifierClass, _settings.disabledClass );
		button.disabled = true;
		button.removeAttribute( _settings.labelAttribute );
		button.textContent = getActivatedLabel();
	};



	/**
	 * Reload the settings page after a short delay so the Activated state is visible.
	 */
	var scheduleSettingsReload = function() {
		var delay = _settings.reloadAfterActivateDelay;

		// Bail if delay is not a positive number
		if ( 'number' !== typeof delay || 0 >= delay ) {
			window.location.reload();
			return;
		}

		window.setTimeout( function() {
			window.location.reload();
		}, delay );
	};



	/**
	 * Get the notice class list for a result type.
	 *
	 * @param  {string} type success|error.
	 * @return {string} Class attribute value.
	 */
	var getNoticeClassName = function( type ) {
		var modifierClass = _settings.itemActionNoticeErrorClass;

		// Success notices use the success modifier
		if ( 'success' === type ) {
			modifierClass = _settings.itemActionNoticeSuccessClass;
		}

		return _settings.itemActionNoticeClass + ' ' + modifierClass;
	};



	/**
	 * Show an inline notice on an add-on card.
	 *
	 * @param {Element} button  Action button.
	 * @param {string}  message Notice message.
	 * @param {string}  type    success|error.
	 */
	var showItemNotice = function( button, message, type ) {
		var item = button.closest( _settings.itemSelector );
		var actions = button.closest( _settings.actionsSelector );
		var notice;

		// Prefer the actions block notice when present
		if ( actions ) {
			notice = actions.querySelector( _settings.itemActionNoticeSelector );

			// Create the notice when the card does not have one yet
			if ( ! notice ) {
				notice = document.createElement( 'div' );
				notice.className = _settings.itemActionNoticeClass;
				actions.appendChild( notice );
			}
		}
		// Otherwise attach the notice to the card
		else {
			// Bail if item missing
			if ( ! item ) { return; }

			notice = item.querySelector( _settings.itemActionNoticeSelector );

			// Create the notice when the card does not have one yet
			if ( ! notice ) {
				notice = document.createElement( 'div' );
				notice.className = _settings.itemActionNoticeClass;
				button.parentNode.appendChild( notice );
			}
		}

		notice.hidden = false;
		notice.className = getNoticeClassName( type );
		notice.textContent = message;
	};



	/**
	 * Restore the Activate button after a failed request.
	 *
	 * @param {Element} button Activate button.
	 */
	var restoreActivateButton = function( button ) {
		var storedLabel;

		// Bail if button missing
		if ( ! button ) { return; }

		storedLabel = button.getAttribute( _settings.labelAttribute );
		button.disabled = false;
		button.textContent = storedLabel ? storedLabel : getActivateLabel();
	};



	/**
	 * Handle a successful Activate AJAX payload.
	 *
	 * @param {Object}  payload Parsed JSON response.
	 * @param {Element} button  Activate button.
	 */
	var handleActivateResponse = function( payload, button ) {
		var message;

		// Bail if button missing
		if ( ! button ) { return; }

		// Maybe show an error notice when the request failed
		if ( ! payload || ! payload.success ) {
			message = ( payload && payload.data && payload.data.message ) ? payload.data.message : getGenericErrorMessage();
			showItemNotice( button, message, 'error' );
			restoreActivateButton( button );
			return;
		}

		// Maybe show Activated and reload when the server requests a refresh
		if ( payload.data && payload.data.reload ) {
			hideItemNotice( button );
			convertActivateButtonToActivated( button );
			scheduleSettingsReload();
			return;
		}

		message = ( payload.data && payload.data.message ) ? payload.data.message : '';
		showItemNotice( button, message, 'success' );
	};



	/**
	 * Parse a fetch response body as JSON when possible.
	 *
	 * @param  {Response} response Fetch response.
	 * @return {Promise}  Promise resolving to a parsed payload or null.
	 */
	var parseJsonResponse = function( response ) {
		return response.json().catch( function() {
			return null;
		} );
	};



	/**
	 * Handle Activate click.
	 *
	 * @param {Event}   e      Click event.
	 * @param {Element} button Activate button.
	 */
	var handleActivateClick = function( e, button ) {
		var plugin = button.getAttribute( _settings.pluginAttribute ) || '';
		var body;

		e.preventDefault();

		// Bail if the control is disabled (e.g. add-on installed while PRO is inactive)
		if ( button.disabled || button.classList.contains( _settings.disabledClass ) ) { return; }

		// Bail if AJAX is not configured
		if ( ! plugin || ! _settings.ajaxUrl || ! _settings.activateNonce ) { return; }

		// Keep the button's own label so "Activate plugin" is not replaced with "Activate add-on"
		if ( ! button.getAttribute( _settings.labelAttribute ) ) {
			button.setAttribute( _settings.labelAttribute, button.textContent );
		}

		button.disabled = true;
		button.textContent = getProcessingLabel();

		body = new window.URLSearchParams();
		body.append( _settings.actionFieldName, _settings.activatePluginAction );
		body.append( _settings.nonceFieldName, _settings.activateNonce );
		body.append( _settings.pluginFieldName, plugin );

		window.fetch( _settings.ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body:        body.toString(),
		} )
			.then( parseJsonResponse )
			.then( function( payload ) {
				handleActivateResponse( payload, button );
			} )
			.catch( function() {
				handleActivateResponse( null, button );
			} );
	};



	/**
	 * Route click events.
	 *
	 * @param {Event} e Click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// ACTIVATE
		if ( matchedElement = e.target.closest( _settings.activateSelector ) ) {
			handleActivateClick( e, matchedElement );
		}
	};





	/**
	 * Initialize the dashboard add-ons admin script.
	 *
	 * @param {Object} options Optional settings passed from PHP.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );

		_hasInitialized = true;
	};



	//
	// Public APIs
	//
	return _publicMethods;

} );
