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
		itemSelector:                         '.fc-addons__item',
		itemActionNoticeSelector:             '.fc-addons__item-action-notice',

		itemActionNoticeClass:                'fc-addons__item-action-notice',
		itemActionNoticeModifierClassTemplate: 'fc-addons__item-action-notice--###TYPE###',

		pluginAttribute:                      'data-plugin',

		activatePluginAction:                 'fc_activate_plugin',
		pluginFieldName:                      'plugin',

		ajaxUrl:                              '',
		activateNonce:                        '',
		i18n: {
			processing:                       'Processing…',
			genericError:                     'Something went wrong. Please try again.',
			activate:                         'Activate plugin',
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
		return _settings.i18n && _settings.i18n.activate ? _settings.i18n.activate : 'Activate plugin';
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
	 * Build a modifier class from a template setting.
	 *
	 * @param  {string} template Class template with ###TYPE### placeholder.
	 * @param  {string} type     Modifier type value.
	 * @return {string} Resolved class name.
	 */
	var getModifierClass = function( template, type ) {
		return template.replace( '###TYPE###', type );
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
		var notice;

		// Bail if item missing
		if ( ! item ) { return; }

		notice = item.querySelector( _settings.itemActionNoticeSelector );

		// Maybe create the notice element
		if ( ! notice ) {
			notice = document.createElement( 'div' );
			notice.className = _settings.itemActionNoticeClass;
			button.parentNode.appendChild( notice );
		}

		notice.hidden = false;
		notice.className = _settings.itemActionNoticeClass + ' ' + getModifierClass( _settings.itemActionNoticeModifierClassTemplate, type );
		notice.textContent = message;
	};



	/**
	 * Parse a fetch response as JSON.
	 *
	 * @param  {Response} response Fetch response.
	 * @return {Promise}  Promise resolving to the JSON payload.
	 */
	var parseJsonResponse = function( response ) {
		return response.json();
	};



	/**
	 * Restore the Activate button after a failed request.
	 *
	 * @param {Element} button Activate button.
	 */
	var restoreActivateButton = function( button ) {
		button.disabled = false;
		button.textContent = getActivateLabel();
	};



	/**
	 * Handle a successful Activate AJAX payload.
	 *
	 * @param {Object}  payload Parsed JSON response.
	 * @param {Element} button  Activate button.
	 */
	var handleActivateResponse = function( payload, button ) {
		var message;

		// Maybe show an error notice when the request failed
		if ( ! payload || ! payload.success ) {
			message = ( payload && payload.data && payload.data.message ) ? payload.data.message : getGenericErrorMessage();
			showItemNotice( button, message, 'error' );
			restoreActivateButton( button );
			return;
		}

		// Maybe reload the page when requested by the server
		if ( payload.data && payload.data.reload ) {
			window.location.reload();
			return;
		}

		message = ( payload.data && payload.data.message ) ? payload.data.message : '';
		showItemNotice( button, message, 'success' );
	};



	/**
	 * Handle a failed Activate AJAX request.
	 *
	 * @param {Element} button Activate button.
	 */
	var handleActivateFailure = function( button ) {
		showItemNotice( button, getGenericErrorMessage(), 'error' );
		restoreActivateButton( button );
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

		// Bail if AJAX not configured
		if ( ! plugin || ! _settings.ajaxUrl || ! _settings.activateNonce ) { return; }

		button.disabled = true;
		button.textContent = getProcessingLabel();

		body = new window.FormData();
		body.append( 'action', _settings.activatePluginAction );
		body.append( 'nonce', _settings.activateNonce );
		body.append( _settings.pluginFieldName, plugin );

		window.fetch( _settings.ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        body,
		} )
			.then( parseJsonResponse )
			.then( function( payload ) {
				handleActivateResponse( payload, button );
			} )
			.catch( function() {
				handleActivateFailure( button );
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
