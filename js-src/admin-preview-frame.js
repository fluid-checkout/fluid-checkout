/**
 * Keep pages shown in the admin preview frame inside the preview.
 *
 * DEPENDS ON:
 * - FCUtils // Utility functions
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
		define([], factory(root));
	} else if ( typeof exports === 'object' ) {
		module.exports = factory(root);
	} else {
		root.FCAdminPreviewFrame = factory(root);
	}
})(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		adminOrigin:                  '',
		tokenQueryArg:                'fc_preview',
		previewArgs:                  {},
		shippingMethodsQueryArg:      'fc_preview_shipping_methods',
		sessionFieldsQueryArg:        'fc_preview_session_fields',
		sessionFieldKeys:             [],
		messageSource:                'fc-admin-preview',
		darkModeCssVariables:         {},

		hideHeaderFooterClass:        'fc-admin-preview--hide-header-footer',
		darkModeClass:                'has-fc-dark-mode',

		stylesId:                     'fc-admin-preview-styles',
		probeId:                      'fc-admin-preview-probe',
		customStylesId:               'fc-custom-styles',

		inertActionSelector:          '#place_order, .fc-place-order-button, .fc-place-order__custom-buttons, .fc-express-checkout, .checkout-button, .fc-cart-action__proceed-checkout, .add_to_cart_button, .single_add_to_cart_button, .wc-block-components-product-button__button, .quantity, .fc-coupon-code__apply, [name="calc_shipping"], body :has( > iframe )',
		inertFieldSelector:           '.quantity input',
		couponCodeFieldSelector:      'input[name="coupon_code"]',
		linkSelector:                 'a[href], area[href]',
		scriptLinkSelector:           '.woocommerce-terms-and-conditions-link',
		checkoutFormSelector:         'form.checkout',
		checkoutFieldSelector:        'input[name]:not([type="hidden"]):not([type="password"]):not([type="submit"]):not([type="button"]):not([type="radio"]:not(:checked)), select[name], textarea[name]',
		checkableFieldSelector:       '[type="checkbox"], [type="radio"]',
		countryFieldSelector:         '.country_to_state',
		fieldByNameSelector:          '[name="###NAME###"]',
		fieldByNameValueSelector:     '[name="###NAME###"][value="###VALUE###"]',
		sessionFieldSelector:         '[name="###NAME###"]:not([type="radio"]:not(:checked))',
		shippingMethodSelector:       '#shipping_method input',
		colorSchemeSelector:          ':root',

		packageIndexAttribute:        'data-index',
	};
	var _cssVariables = {};
	var _colorScheme = '';
	var _hasDarkModeClass = false;
	var _shippingMethods = {};
	var _originalFetch = null;
	var _originalXhrOpen = null;
	var _originalSendBeacon = null;



	/**
	 * METHODS
	 */



	/**
	 * Stop an event from reaching other scripts and from running its default action.
	 *
	 * @param {Event}  e  Event to stop.
	 */
	var preventAction = function( e ) {
		// Stop the default action and the other listeners
		e.preventDefault();
		e.stopImmediatePropagation();
	};

	/**
	 * Stop scripts from opening other windows, such as social login popups.
	 *
	 * @return {null}  Always `null`, like browsers return for blocked popups.
	 */
	var preventWindowOpen = function() {
		return null;
	};



	/**
	 * Get a request URL with the preview token and mode, and the shipping methods and session field values in the page, when the request is sent to the frontend.
	 *
	 * @param {string}  requestUrl  Request URL, which can be relative to the preview page.
	 *
	 * @return {string}  The request URL with the preview arguments, or the same URL when sent to another origin or when there are no arguments to add.
	 */
	var getPreviewRequestUrl = function( requestUrl ) {
		var url;
		var addedArgs = new URLSearchParams();
		var name;
		var index;
		var field;
		var i;

		// Get request URL, resolving relative URLs from the preview page
		try {
			url = new URL( requestUrl, root.location.href );
		}
		// Bail if the URL is not valid
		catch ( error ) {
			return requestUrl;
		}

		// Bail if the request is sent to another origin, which must not receive the preview token
		if ( root.location.origin !== url.origin ) { return requestUrl; }

		// Maybe add the preview arguments, unless the URL already has them, such as WooCommerce AJAX URLs
		if ( ! url.searchParams.has( _settings.tokenQueryArg ) ) {
			// Iterate preview arguments
			for ( name in _settings.previewArgs ) {
				addedArgs.set( name, _settings.previewArgs[ name ] );
			}
		}

		// Iterate shipping methods chosen in the page, as preview requests do not keep them for the next requests
		for ( index in _shippingMethods ) {
			addedArgs.set( _settings.shippingMethodsQueryArg + '[' + index + ']', _shippingMethods[ index ] );
		}

		// Iterate session fields, reading their values from the page, as scripts can change them without change events
		for ( i = 0; i < _settings.sessionFieldKeys.length; i++ ) {
			// Get session field, the checked one for radio buttons
			field = document.querySelector( _settings.sessionFieldSelector.replace( '###NAME###', CSS.escape( _settings.sessionFieldKeys[ i ] ) ) );

			// Skip fields not in the page
			if ( ! field ) { continue; }

			// Add session field value
			addedArgs.set( _settings.sessionFieldsQueryArg + '[' + _settings.sessionFieldKeys[ i ] + ']', field.value );
		}

		// Bail if there are no arguments to add
		if ( ! addedArgs.toString() ) { return requestUrl; }

		// Add the arguments after the existing ones, keeping the existing ones as they are
		url.search = ( url.search ? url.search + '&' : '?' ) + addedArgs.toString();

		return url.href;
	};

	/**
	 * Send `fetch` requests with the preview arguments when sent to the frontend.
	 *
	 * @param {string|URL|Request}  resource  Request URL or request object.
	 * @param {Object}              options   Request options.
	 *
	 * @return {Promise}  The response promise.
	 */
	var fetchWithPreviewArgs = function( resource, options ) {
		// Get the request URL, and the same URL with the preview arguments
		var requestUrl = resource instanceof Request ? resource.url : String( resource );
		var previewRequestUrl = getPreviewRequestUrl( requestUrl );

		// Bail with the original request when its URL does not change, such as requests sent to other origins
		if ( previewRequestUrl === requestUrl ) { return _originalFetch.call( root, resource, options ); }

		// Maybe copy request objects with the new URL, keeping their method, headers and body
		if ( resource instanceof Request ) {
			return _originalFetch.call( root, new Request( previewRequestUrl, resource ), options );
		}

		// Send the request to the URL with the preview arguments
		return _originalFetch.call( root, previewRequestUrl, options );
	};

	/**
	 * Open `XMLHttpRequest` requests with the preview arguments when sent to the frontend, including jQuery AJAX requests.
	 *
	 * @param {string}  method  Request method.
	 * @param {string}  url     Request URL.
	 */
	var openXhrWithPreviewArgs = function( method, url ) {
		var args = Array.prototype.slice.call( arguments );

		// Use the request URL with the preview arguments, keeping the other arguments as they are
		args[ 1 ] = getPreviewRequestUrl( String( url ) );

		// Open the request
		return _originalXhrOpen.apply( this, args );
	};

	/**
	 * Send beacon requests with the preview arguments when sent to the frontend.
	 *
	 * @param {string}  url   Request URL.
	 * @param {*}       data  Request data.
	 *
	 * @return {boolean}  Whether the browser queued the request.
	 */
	var sendBeaconWithPreviewArgs = function( url, data ) {
		return _originalSendBeacon.call( root.navigator, getPreviewRequestUrl( String( url ) ), data );
	};



	/**
	 * Maybe send the ready message to the admin page, when the preview is shown in its frame.
	 */
	var maybeSendReadyMessage = function() {
		// Bail if not shown in a frame, or the admin origin is not available
		if ( root.parent === root || ! _settings.adminOrigin ) { return; }

		// Send ready message to the admin page
		root.parent.postMessage( { source: _settings.messageSource, type: 'ready' }, _settings.adminOrigin );
	};



	/**
	 * Send the values entered in the checkout fields to the admin page, which sends them back after the page preview reloads.
	 *
	 * @param {HTMLFormElement}  form  Checkout form.
	 */
	var sendFieldValues = function( form ) {
		var fields;
		var values;

		// Get the fields with the country fields first, so they are set again before the state fields they replace
		fields = Array.prototype.slice.call( form.querySelectorAll( _settings.checkoutFieldSelector ) ).sort( function( a, b ) {
			return b.matches( _settings.countryFieldSelector ) - a.matches( _settings.countryFieldSelector );
		} );

		// Get the name, value and checked state of each field, and the package index of shipping methods, leaving out radio buttons that are not checked
		values = fields.map( function( field ) {
			return { name: field.name, value: field.value, checked: field.matches( _settings.checkableFieldSelector ) ? field.checked : null, packageIndex: field.matches( _settings.shippingMethodSelector ) ? field.getAttribute( _settings.packageIndexAttribute ) : null };
		} );

		// Send field values to the admin page
		root.parent.postMessage( { source: _settings.messageSource, type: 'fieldValues', values: values }, _settings.adminOrigin );
	};

	/**
	 * Set the values entered in the checkout fields before the page preview reloaded.
	 *
	 * @param {Array}  values  Field values, each with the field name, value, checked state and package index, with the country fields first.
	 */
	var setFieldValues = function( values ) {
		var form = document.querySelector( _settings.checkoutFormSelector );
		var hasChanged = false;
		var field;
		var i;

		// Bail if not a checkout page
		if ( ! form ) { return; }

		// Iterate field values
		for ( i = 0; i < values.length; i++ ) {
			// Get the field, matching checkboxes and radio buttons by their value too
			field = form.querySelector( ( null !== values[ i ].checked ? _settings.fieldByNameValueSelector : _settings.fieldByNameSelector ).replace( '###NAME###', CSS.escape( values[ i ].name ) ).replace( '###VALUE###', CSS.escape( values[ i ].value ) ) );

			// Maybe remember shipping methods not in the page yet, such as pickup locations shown after the checkout update, to send them with the next requests
			if ( ! field && null !== values[ i ].packageIndex ) {
				_shippingMethods[ values[ i ].packageIndex ] = values[ i ].value;
			}

			// Skip fields no longer in the page, such as fields that settings changes removed, and fields that already have the value
			if ( ! field || ( null !== values[ i ].checked ? values[ i ].checked === field.checked : values[ i ].value === field.value ) ) { continue; }

			// Maybe set the checked state of checkboxes and radio buttons
			if ( null !== values[ i ].checked ) {
				field.checked = values[ i ].checked;
			}
			// Otherwise, set the value
			else {
				field.value = values[ i ].value;
			}

			// Let other scripts know about the change, such as to replace the state field when the country changes
			field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			hasChanged = true;
		}

		// Maybe update the checkout, so the order summary and the step review texts show the values
		if ( hasChanged ) {
			form.dispatchEvent( new Event( 'update' ) );
		}
	};

	/**
	 * Set the values entered in the checkout fields before the page preview reloaded, once the page scripts set up the fields.
	 *
	 * @param {Array}  values  Field values, each with the field name, value, checked state and package index, with the country fields first.
	 */
	var setFieldValuesWhenLoaded = function( values ) {
		// Maybe wait for the page scripts to set up the fields, such as the country fields that replace the state fields
		if ( 'complete' !== document.readyState ) {
			root.addEventListener( 'load', setFieldValues.bind( null, values ), { once: true } );
		}
		// Otherwise, set the values right away
		else {
			setFieldValues( values );
		}
	};



	/**
	 * Add CSS variables to a style sheet, as a map of selectors to CSS variable names and values.
	 * Rules and values are added through the CSS object model, so values cannot add other rules.
	 *
	 * @param {CSSStyleSheet}  sheet      Style sheet.
	 * @param {Object}         variables  CSS variables by selector.
	 */
	var addCssVariablesRules = function( sheet, variables ) {
		var selector;
		var name;
		var rule;

		// Iterate selectors
		for ( selector in variables ) {
			// Add an empty rule for the selector
			try {
				rule = sheet.cssRules[ sheet.insertRule( selector + '{}', sheet.cssRules.length ) ];
			}
			// Skip selectors that are not valid, such as selectors the browser does not support
			catch ( error ) {
				continue;
			}

			// Iterate CSS variables of the selector
			for ( name in variables[ selector ] ) {
				// Skip properties other than CSS variables
				if ( 0 !== name.indexOf( '--' ) ) { continue; }

				// Set the CSS variable, which the CSS object model ignores when the value is not valid
				rule.style.setProperty( name, variables[ selector ][ name ] );
			}
		}
	};

	/**
	 * Get the CSS variables that force the color scheme: the dark mode values for the dark scheme, or no value for the light scheme so the default colors apply.
	 *
	 * @return {Object}  CSS variables by selector.
	 */
	var getColorSchemeCssVariables = function() {
		var variables = {};
		var name;

		// Bail if the color scheme is not forced
		if ( 'light' !== _colorScheme && 'dark' !== _colorScheme ) { return variables; }

		// Define CSS variables of the color scheme selector
		variables[ _settings.colorSchemeSelector ] = {};

		// Iterate dark mode CSS variables
		for ( name in _settings.darkModeCssVariables ) {
			variables[ _settings.colorSchemeSelector ][ name ] = 'dark' === _colorScheme ? _settings.darkModeCssVariables[ name ] : 'initial';
		}

		return variables;
	};

	/**
	 * Update the preview styles with the CSS variables of the color scheme, then the CSS variables set by the admin page, which override them.
	 */
	var updatePreviewStyles = function() {
		var styleElement = document.getElementById( _settings.stylesId );

		// Maybe add the style element at the end of the body, so its rules come after the page styles
		if ( ! styleElement ) {
			styleElement = document.createElement( 'style' );
			styleElement.id = _settings.stylesId;
			document.body.appendChild( styleElement );
		}

		// Remove the previous rules
		while ( styleElement.sheet.cssRules.length ) {
			styleElement.sheet.deleteRule( 0 );
		}

		// Add the CSS variables
		addCssVariablesRules( styleElement.sheet, getColorSchemeCssVariables() );
		addCssVariablesRules( styleElement.sheet, _cssVariables );
	};

	/**
	 * Force the color scheme of the page preview, or keep the color scheme of the page.
	 *
	 * @param {string}  scheme  `light` or `dark`, or any other value to keep the color scheme of the page.
	 */
	var setColorScheme = function( scheme ) {
		// Remember the color scheme for the preview styles
		_colorScheme = scheme;

		// Add or remove the dark mode class, as some styles also depend on it
		document.body.classList.toggle( _settings.darkModeClass, 'dark' === scheme || ( 'light' !== scheme && _hasDarkModeClass ) );

		// Update the CSS variables of the color scheme
		updatePreviewStyles();
	};

	/**
	 * Add or remove body classes of the page preview.
	 *
	 * @param {Object}  classes  Whether each class is added, by class name.
	 */
	var setBodyClasses = function( classes ) {
		var className;

		// Iterate classes
		for ( className in classes ) {
			document.body.classList.toggle( className, true === classes[ className ] );
		}
	};

	/**
	 * Send the computed value of a CSS property value in the page preview to the admin page, such as a font size in pixels.
	 * Values are computed on a hidden text field, which gets the theme styles for text fields.
	 *
	 * @param {number|string}  requestId  Request ID from the admin page, sent back with the value.
	 * @param {string}         property   CSS property name.
	 * @param {string}         value      CSS property value.
	 */
	var sendComputedCssValue = function( requestId, property, value ) {
		var probe = document.getElementById( _settings.probeId );
		var computedValue = '';

		// Maybe add the hidden text field
		if ( ! probe ) {
			probe = document.createElement( 'input' );
			probe.type = 'text';
			probe.id = _settings.probeId;
			probe.hidden = true;
			document.body.appendChild( probe );
		}

		// Set the value on the hidden text field
		probe.style.setProperty( property, value );

		// Maybe get the computed value, unless the value is not valid for the property
		if ( probe.style.getPropertyValue( property ) ) {
			computedValue = root.getComputedStyle( probe ).getPropertyValue( property );
		}

		// Remove the value from the hidden text field
		probe.style.removeProperty( property );

		// Send the computed value to the admin page
		root.parent.postMessage( { source: _settings.messageSource, type: 'computedCssValue', requestId: requestId, value: computedValue }, _settings.adminOrigin );
	};



	/**
	 * Refresh the custom styles of the page from a fresh render, such as after the admin page changes style settings, without reloading the page.
	 */
	var refreshCustomStyles = function() {
		// Get a fresh render of the page, use its custom styles unless the request fails, then let the admin page know
		fetch( root.location.href, { credentials: 'same-origin' } )
			.then( function( response ) { return response.ok ? response.text() : Promise.reject( response ); } )
			.then( replaceCustomStyles )
			.then( sendStylesRefreshedMessage, sendStylesRefreshedMessage );
	};

	/**
	 * Replace the custom styles of the page with the custom styles of a fresh render.
	 *
	 * @param {string}  html  HTML of the fresh render.
	 */
	var replaceCustomStyles = function( html ) {
		var newStyleElement = new DOMParser().parseFromString( html, 'text/html' ).getElementById( _settings.customStylesId );
		var styleElement = document.getElementById( _settings.customStylesId );

		// Maybe add the custom styles element, as pages without custom styles have none
		if ( ! styleElement ) {
			styleElement = document.createElement( 'style' );
			styleElement.id = _settings.customStylesId;
			document.head.appendChild( styleElement );
		}

		// Replace the custom styles, or clear them when the fresh render has none
		styleElement.textContent = newStyleElement ? newStyleElement.textContent : '';
	};

	/**
	 * Send the styles refreshed message to the admin page, which shows the page preview as busy until then.
	 */
	var sendStylesRefreshedMessage = function() {
		// Send styles refreshed message to the admin page
		root.parent.postMessage( { source: _settings.messageSource, type: 'stylesRefreshed' }, _settings.adminOrigin );
	};



	/**
	 * Maybe stop a link from navigating away from the preview page.
	 *
	 * @param {Event}              e     Click or auxiliary click event.
	 * @param {HTMLAnchorElement}  link  Link element.
	 */
	var maybePreventLinkNavigation = function( e, link ) {
		// Bail if the link points to the current page, which only changes the URL fragment
		if ( root.location.origin === link.origin && root.location.pathname === link.pathname && root.location.search === link.search ) { return; }

		// Maybe only stop the default action of script links and links that scripts handle within the page, so their scripts still run
		if ( 'javascript:' === link.protocol || link.matches( _settings.scriptLinkSelector ) ) {
			e.preventDefault();
		}
		// Otherwise, stop the link scripts too, such as social login scripts that open other windows
		else {
			preventAction( e );
		}
	};



	/**
	 * Handle document clicks, including middle clicks, and route to the appropriate handler.
	 *
	 * @param {Event}  e  Click or auxiliary click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// INERT ACTIONS
		if ( e.target.closest( _settings.inertActionSelector ) ) {
			preventAction( e );
		}
		// LINKS
		else if ( matchedElement = e.target.closest( _settings.linkSelector ) ) {
			maybePreventLinkNavigation( e, matchedElement );
		}
	};

	/**
	 * Whether keyboard events should not forward admin shortcuts to the settings page.
	 *
	 * @param  {Element}  target  Event target.
	 * @return {boolean}
	 */
	var isAdminShortcutBlockedTarget = function( target ) {
		var inputType;

		// Bail if target is missing
		if ( ! target || ! target.tagName ) { return false; }

		// Skip text fields and other editable controls
		if ( 'TEXTAREA' === target.tagName || 'SELECT' === target.tagName ) {
			return true;
		}

		// Maybe skip text-like inputs
		if ( 'INPUT' === target.tagName ) {
			inputType = ( target.type || 'text' ).toLowerCase();

			if ( -1 !== [ 'text', 'search', 'url', 'tel', 'email', 'password', 'number', 'color' ].indexOf( inputType ) ) {
				return true;
			}
		}

		// Skip contenteditable regions
		if ( target.isContentEditable ) {
			return true;
		}

		return false;
	};

	/**
	 * Whether the event key is Cmd or Ctrl.
	 *
	 * @param  {KeyboardEvent}  e  Keyboard event.
	 * @return {boolean}
	 */
	var isShortcutModifierKey = function( e ) {
		return 'Meta' === e.key || 'Control' === e.key || 'MetaLeft' === e.code || 'MetaRight' === e.code || 'ControlLeft' === e.code || 'ControlRight' === e.code;
	};

	/**
	 * Forward a message to the settings page holding the frame.
	 *
	 * @param  {Object}  data  Message payload without the shared source field.
	 */
	var postToAdmin = function( data ) {
		// Bail if not shown in a frame, or the admin origin is not available
		if ( root.parent === root || ! _settings.adminOrigin ) { return; }

		data.source = _settings.messageSource;
		root.parent.postMessage( data, _settings.adminOrigin );
	};

	/**
	 * Forward admin keyboard shortcuts to the settings page holding the frame.
	 *
	 * @param  {KeyboardEvent}  e  Key down event.
	 * @return {boolean}  Whether an admin shortcut was handled.
	 */
	var maybeForwardAdminShortcut = function( e ) {
		var isExpandShortcut;
		var isSaveShortcut;

		// Bail if not shown in a frame, or the admin origin is not available
		if ( root.parent === root || ! _settings.adminOrigin ) { return false; }

		// Bail if FCUtils is not available
		if ( ! window.FCUtils ) { return false; }

		isExpandShortcut = ( e.metaKey || e.ctrlKey ) && e.shiftKey && ! e.altKey && FCUtils.keyboardKeys.P === e.key.toLowerCase();
		isSaveShortcut = ( e.metaKey || e.ctrlKey ) && ! e.altKey && ! e.shiftKey && FCUtils.keyboardKeys.S === e.key.toLowerCase();

		// Bail if no admin shortcut was pressed
		if ( ! isExpandShortcut && ! isSaveShortcut ) { return false; }

		// Bail if expanding while the user is typing in a field
		if ( isExpandShortcut && isAdminShortcutBlockedTarget( e.target ) ) { return false; }

		// Stop the browser shortcut and keep it inside the settings page flow
		e.preventDefault();
		e.stopImmediatePropagation();

		// EXPAND PREVIEW
		if ( isExpandShortcut ) {
			postToAdmin( { type: 'adminShortcut', shortcut: 'expandPreview' } );
		}
		// SAVE SETTINGS
		else if ( isSaveShortcut ) {
			postToAdmin( { type: 'adminShortcut', shortcut: 'saveSettings' } );
		}

		return true;
	};

	/**
	 * Forward Cmd/Ctrl hold start to the settings page so shortcut hints can show.
	 *
	 * @param  {KeyboardEvent}  e  Key down event.
	 */
	var maybeForwardModifierHoldStart = function( e ) {
		// Bail if this is not Cmd or Ctrl
		if ( ! isShortcutModifierKey( e ) ) { return; }

		// Bail on key repeat while the key is already held
		if ( e.repeat ) { return; }

		postToAdmin( { type: 'adminShortcut', shortcut: 'modifierHoldStart' } );
	};

	/**
	 * Forward Cmd/Ctrl release to the settings page so shortcut hints can hide.
	 *
	 * @param  {KeyboardEvent}  e  Key up event.
	 */
	var maybeForwardModifierHoldEnd = function( e ) {
		// Bail if this is not Cmd or Ctrl
		if ( ! isShortcutModifierKey( e ) ) { return; }

		// Bail while the other modifier is still held
		if ( e.metaKey || e.ctrlKey ) { return; }

		postToAdmin( { type: 'adminShortcut', shortcut: 'modifierHoldEnd' } );
	};

	/**
	 * Hide shortcut hints on the settings page when focus leaves the preview frame.
	 */
	var handleWindowBlur = function() {
		postToAdmin( { type: 'adminShortcut', shortcut: 'modifierHoldEnd' } );
	};

	/**
	 * Handle document key down events and route to the appropriate handler.
	 * Quantity changes and coupon codes are inert, as the preview starts again from the dummy cart on each request.
	 *
	 * @param {Event}  e  Key down event.
	 */
	var handleKeyDown = function( e ) {
		// MODIFIER HOLD
		maybeForwardModifierHoldStart( e );

		// ADMIN SHORTCUTS
		if ( maybeForwardAdminShortcut( e ) ) {
			return;
		}
		// INERT FIELDS
		else if ( FCUtils.keyboardKeys.TAB !== e.key && e.target.closest( _settings.inertFieldSelector ) ) {
			preventAction( e );
		}
		// COUPON CODE FIELDS
		else if ( FCUtils.keyboardKeys.ENTER === e.key && e.target.closest( _settings.couponCodeFieldSelector ) ) {
			preventAction( e );
		}
	};

	/**
	 * Handle document key up events and route to the appropriate handler.
	 *
	 * @param {Event}  e  Key up event.
	 */
	var handleKeyUp = function( e ) {
		// MODIFIER HOLD
		maybeForwardModifierHoldEnd( e );
	};

	/**
	 * Handle document before input events and route to the appropriate handler.
	 *
	 * @param {Event}  e  Before input event.
	 */
	var handleBeforeInput = function( e ) {
		// INERT FIELDS
		if ( e.target.closest( _settings.inertFieldSelector ) ) {
			preventAction( e );
		}
	};

	/**
	 * Handle document input and change events and route to the appropriate handler.
	 * Shipping methods are remembered by package index, to send them with the next requests.
	 *
	 * @param {Event}  e  Input or change event.
	 */
	var handleChange = function( e ) {
		var matchedElement;

		// SHIPPING METHODS
		if ( e.target.matches( _settings.shippingMethodSelector ) ) {
			_shippingMethods[ e.target.getAttribute( _settings.packageIndexAttribute ) ] = e.target.value;
		}

		// CHECKOUT FIELDS
		if ( matchedElement = e.target.closest( _settings.checkoutFormSelector ) ) {
			sendFieldValues( matchedElement );
		}
	};

	/**
	 * Handle messages and route to the appropriate handler.
	 *
	 * @param {MessageEvent}  e  Message event.
	 */
	var handleMessage = function( e ) {
		// Bail if the message does not come from the admin page holding the frame
		if ( root.parent !== e.source || _settings.adminOrigin !== e.origin || ! e.data || _settings.messageSource !== e.data.source ) { return; }

		// SET HEADER AND FOOTER VISIBILITY
		if ( 'setHeaderFooterVisibility' === e.data.type ) {
			document.body.classList.toggle( _settings.hideHeaderFooterClass, true !== e.data.visible );
		}
		// SET FIELD VALUES
		else if ( 'setFieldValues' === e.data.type ) {
			setFieldValuesWhenLoaded( e.data.values );
		}
		// SET CSS VARIABLES
		else if ( 'setCssVariables' === e.data.type ) {
			_cssVariables = e.data.variables;
			updatePreviewStyles();
		}
		// SET COLOR SCHEME
		else if ( 'setColorScheme' === e.data.type ) {
			setColorScheme( e.data.scheme );
		}
		// SET BODY CLASSES
		else if ( 'setBodyClasses' === e.data.type ) {
			setBodyClasses( e.data.classes );
		}
		// GET COMPUTED CSS VALUE
		else if ( 'getComputedCssValue' === e.data.type ) {
			sendComputedCssValue( e.data.requestId, e.data.property, e.data.value );
		}
		// REFRESH STYLES
		else if ( 'refreshStyles' === e.data.type ) {
			refreshCustomStyles();
		}
		// RELOAD
		else if ( 'reload' === e.data.type ) {
			root.location.reload();
		}
	};



	/**
	 * Initialize the script.
	 *
	 * @param {Object}  options  Optional settings passed from PHP. Will be merged with the default settings when provided.
	 */
	_publicMethods.init = function( options ) {
		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		// Stop scripts from opening other windows
		root.open = preventWindowOpen;

		// Keep the dark mode class of the page, to restore it when the color scheme is no longer forced
		_hasDarkModeClass = document.body.classList.contains( _settings.darkModeClass );

		// Add event listeners
		window.addEventListener( 'click', handleClick, true );
		window.addEventListener( 'auxclick', handleClick, true ); // Middle clicks open links in new tabs
		window.addEventListener( 'keydown', handleKeyDown, true );
		window.addEventListener( 'keyup', handleKeyUp, true );
		window.addEventListener( 'blur', handleWindowBlur );
		window.addEventListener( 'beforeinput', handleBeforeInput, true );
		window.addEventListener( 'input', handleChange, true );
		window.addEventListener( 'change', handleChange, true );
		window.addEventListener( 'submit', preventAction, true ); // Stops all form submissions, as forms must not submit from the preview
		window.addEventListener( 'message', handleMessage, true );

		// Send requests to the frontend with the preview arguments, from jQuery and any other script
		_originalFetch = root.fetch;
		_originalXhrOpen = root.XMLHttpRequest.prototype.open;
		_originalSendBeacon = root.navigator.sendBeacon;
		root.fetch = fetchWithPreviewArgs;
		root.XMLHttpRequest.prototype.open = openXhrWithPreviewArgs;
		root.navigator.sendBeacon = sendBeaconWithPreviewArgs;

		// Let the admin page know the preview is ready
		maybeSendReadyMessage();

		_hasInitialized = true;
	};

	//
	// Public APIs
	//
	return _publicMethods;

} );
