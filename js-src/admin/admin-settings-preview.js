/**
 * Page preview column for the Fluid Checkout settings page.
 * Expand/collapse, viewport size, page tabs, and visibility by settings tab.
 *
 * DEPENDS ON:
 * - FCUtils // Settings merge when available
 */

(function ( root, factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define( [], factory( root ) );
	}
	else if ( typeof exports === 'object' ) {
		module.exports = factory( root );
	}
	else {
		root.FCAdminSettingsPreview = factory( root );
	}
})( typeof global !== 'undefined' ? global : this.window || this.global, function ( root ) {

	'use strict';

	var _hasInitialized = false;
	var _publicMethods = {};
	var _settings = {
		layoutSelector:              '[data-fc-settings-layout]',
		previewSelector:             '[data-fc-settings-preview]',
		expandSelector:              '[data-fc-settings-preview-expand]',
		statusSelector:              '[data-fc-settings-preview-status]',
		panelSelector:               '[data-fc-settings-preview-panel]',
		frameWrapSelector:           '[data-fc-settings-preview-frame-wrap]',
		frameSelector:               '[data-fc-settings-preview-frame]',
		dimsSelector:                '[data-fc-settings-preview-dims]',
		pageTabSelector:             '[data-fc-settings-preview-page]',
		viewportInputName:           'fc-settings-preview-viewport',
		hasPreviewClass:             'has-preview',
		isExpandedClass:             'is-preview-expanded',
		isActiveClass:               'is-active',
		pageAttribute:               'data-fc-settings-preview-page',
		requiresProAttribute:        'data-requires-pro',
		viewportAttribute:           'data-viewport',
		hiddenTabs:                  [ 'dashboard', 'license_keys' ],
		initialTab:                  'checkout',
		initialPage:                 'checkout',
		previewProUrlTemplate:       'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=settings-preview-{page}&mtm_source=lite-plugin',
		i18n: {
			expand:                  'Expand preview',
			collapse:                'Collapse preview',
			preview:                 'Page preview',
			previewTitle:            '%s preview',
			previewSubtitle:         'Isolated session · fields read-only',
			previewSubtitlePro:      'Available with %s.',
			previewProLinkLabel:     'Fluid Checkout PRO',
		},
	};



	/**
	 * METHODS
	 */



	/**
	 * Shallow-merge settings when `FCUtils` is not available.
	 *
	 * @param   {Object}  target  Base settings.
	 * @param   {Object}  source  Overrides from PHP.
	 * @return  {Object}          Merged settings.
	 */
	var extendSettings = function( target, source ) {
		var merged = {};
		var key;

		if ( typeof FCUtils !== 'undefined' && typeof FCUtils.extendObject === 'function' ) {
			return FCUtils.extendObject( target, source );
		}

		for ( key in target ) {
			if ( Object.prototype.hasOwnProperty.call( target, key ) ) {
				merged[ key ] = target[ key ];
			}
		}

		if ( source && typeof source === 'object' ) {
			for ( key in source ) {
				if ( Object.prototype.hasOwnProperty.call( source, key ) ) {
					merged[ key ] = source[ key ];
				}
			}
		}

		return merged;
	};

	/**
	 * Get the settings layout element.
	 *
	 * @return  {Element|null}
	 */
	var getLayout = function() {
		return document.querySelector( _settings.layoutSelector );
	};

	/**
	 * Get the preview column element.
	 *
	 * @return  {Element|null}
	 */
	var getPreview = function() {
		return document.querySelector( _settings.previewSelector );
	};

	/**
	 * Whether the preview should be visible for a settings tab.
	 *
	 * @param   {string}  tab  Settings tab slug.
	 * @return  {boolean}
	 */
	var isPreviewVisibleForTab = function( tab ) {
		return -1 === _settings.hiddenTabs.indexOf( tab );
	};

	/**
	 * Map a settings tab slug to a preview page slug.
	 *
	 * @param   {string}  tab  Settings tab slug.
	 * @return  {string}       Preview page slug.
	 */
	var getPreviewPageForTab = function( tab ) {
		var pageTab = document.querySelector( '[' + _settings.pageAttribute + '="' + tab + '"]' );

		// Use the settings tab when a matching preview page tab exists
		if ( pageTab ) {
			return tab;
		}

		return 'checkout';
	};

	/**
	 * Update the iframe dimension label from the current frame size.
	 */
	var updatePreviewDims = function() {
		var frame = document.querySelector( _settings.frameSelector );
		var dims = document.querySelector( _settings.dimsSelector );
		var rect;
		var width;
		var height;

		// Bail if frame or dims label is missing
		if ( ! frame || ! dims ) { return; }

		rect = frame.getBoundingClientRect();
		width = Math.round( rect.width );
		height = Math.round( rect.height );
		dims.textContent = width + ' \u00d7 ' + height;
	};

	/**
	 * Escape text for safe HTML insertion.
	 *
	 * @param   {string}  text  Raw text.
	 * @return  {string}
	 */
	var escapeHtml = function( text ) {
		var el = document.createElement( 'span' );
		el.textContent = text || '';
		return el.innerHTML;
	};

	/**
	 * Build the PRO unlock subtitle HTML with a pricing link.
	 *
	 * @param   {string}  page  Preview page slug.
	 * @return  {string}
	 */
	var getProPreviewSubtitleHtml = function( page ) {
		var pageSlug = String( page || '' ).replace( /_/g, '-' );
		var url = ( _settings.previewProUrlTemplate || '' ).replace( '{page}', pageSlug );
		var linkLabel = _settings.i18n.previewProLinkLabel || 'Fluid Checkout PRO';
		var linkHtml = '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + escapeHtml( linkLabel ) + '</a>';

		return ( _settings.i18n.previewSubtitlePro || 'Available with %s.' ).replace( '%s', linkHtml );
	};

	/**
	 * Update the preview iframe title and subtitle for the selected page.
	 *
	 * @param  {string}   page         Preview page slug.
	 * @param  {string}   pageLabel    Label of the selected preview page.
	 * @param  {boolean}  requiresPro  Whether the page preview requires PRO.
	 */
	var setPreviewContent = function( page, pageLabel, requiresPro ) {
		var frame = document.querySelector( _settings.frameSelector );
		var doc;
		var titleEl;
		var subtitleEl;

		// Bail if frame is missing
		if ( ! frame ) { return; }

		try {
			doc = frame.contentDocument;
		} catch ( err ) {
			return;
		}

		// Bail if iframe document is not available
		if ( ! doc ) { return; }

		titleEl = doc.getElementById( 'fc-settings-preview-title' );
		if ( titleEl ) {
			titleEl.textContent = ( _settings.i18n.previewTitle || '%s preview' ).replace( '%s', pageLabel );
		}

		subtitleEl = doc.getElementById( 'fc-settings-preview-subtitle' );
		if ( subtitleEl ) {
			if ( requiresPro ) {
				subtitleEl.innerHTML = getProPreviewSubtitleHtml( page );
			}
			// Otherwise show the isolated-session subtitle
			else {
				subtitleEl.textContent = _settings.i18n.previewSubtitle || 'Isolated session · fields read-only';
			}
		}
	};

	/**
	 * Expand or collapse the preview to full width.
	 *
	 * @param  {boolean}  expanded  Whether the preview should cover the layout.
	 */
	var setPreviewExpanded = function( expanded ) {
		var layout = getLayout();
		var expandButton = document.querySelector( _settings.expandSelector );
		var label = expanded ? _settings.i18n.collapse : _settings.i18n.expand;

		// Bail if layout is missing
		if ( ! layout ) { return; }

		layout.classList.toggle( _settings.isExpandedClass, expanded );

		if ( expandButton ) {
			expandButton.setAttribute( 'aria-pressed', expanded ? 'true' : 'false' );
			expandButton.setAttribute( 'aria-label', label );
			expandButton.setAttribute( 'title', label );
		}

		updatePreviewDims();
	};

	/**
	 * Set the preview viewport size.
	 *
	 * @param  {string}  viewport  Viewport slug (`mobile`, `tablet`, or `desktop`).
	 */
	var setPreviewViewport = function( viewport ) {
		var frameWrap = document.querySelector( _settings.frameWrapSelector );

		// Bail if frame wrap is missing
		if ( ! frameWrap ) { return; }

		frameWrap.setAttribute( _settings.viewportAttribute, viewport || 'desktop' );
		updatePreviewDims();
	};

	/**
	 * Show or hide the preview column for a settings tab.
	 *
	 * @param  {string}  tab  Settings tab slug.
	 */
	var setPreviewVisibilityForTab = function( tab ) {
		var layout = getLayout();
		var preview = getPreview();
		var isVisible = isPreviewVisibleForTab( tab );

		// Bail if layout or preview is missing
		if ( ! layout || ! preview ) { return; }

		layout.classList.toggle( _settings.hasPreviewClass, isVisible );

		if ( isVisible ) {
			preview.removeAttribute( 'hidden' );
		}
		// Otherwise hide the preview and collapse it
		else {
			preview.setAttribute( 'hidden', '' );
			setPreviewExpanded( false );
		}
	};

	/**
	 * Activate a preview page tab and update the placeholder iframe.
	 *
	 * @param  {string}   page         Preview page slug.
	 * @param  {boolean}  requiresPro  Whether the page requires PRO when inactive.
	 */
	var setPreviewPage = function( page, requiresPro ) {
		var pageTabs = document.querySelectorAll( _settings.pageTabSelector );
		var panel = document.querySelector( _settings.panelSelector );
		var status = document.querySelector( _settings.statusSelector );
		var pageLabel = _settings.i18n.preview;
		var i;
		var tab;
		var isActive;

		// Iterate preview page tabs
		for ( i = 0; i < pageTabs.length; i++ ) {
			tab = pageTabs[ i ];
			isActive = page === tab.getAttribute( _settings.pageAttribute );
			tab.classList.toggle( _settings.isActiveClass, isActive );
			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );

			if ( isActive ) {
				pageLabel = tab.textContent.trim();
			}
		}

		if ( status ) {
			status.textContent = pageLabel;
		}

		// Always show the iframe placeholder panel
		if ( panel ) {
			panel.removeAttribute( 'hidden' );
		}

		setPreviewContent( page, pageLabel, requiresPro );
		updatePreviewDims();
	};

	/**
	 * Sync preview visibility and page from a settings tab change.
	 *
	 * @param  {string}  tab  Settings tab slug.
	 */
	var syncFromSettingsTab = function( tab ) {
		var page;
		var pageTab;
		var requiresPro;

		setPreviewVisibilityForTab( tab );

		// Bail if preview is hidden for this tab
		if ( ! isPreviewVisibleForTab( tab ) ) { return; }

		page = getPreviewPageForTab( tab );
		pageTab = document.querySelector( '[' + _settings.pageAttribute + '="' + page + '"]' );
		requiresPro = pageTab && 'yes' === pageTab.getAttribute( _settings.requiresProAttribute );

		setPreviewPage( page, requiresPro );
	};



	/**
	 * Handle expand button clicks.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleExpandClick = function( e ) {
		var layout = getLayout();

		// Bail if layout is missing
		if ( ! layout ) { return; }

		setPreviewExpanded( ! layout.classList.contains( _settings.isExpandedClass ) );
		e.preventDefault();
	};

	/**
	 * Handle viewport radio changes.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleViewportChange = function( e ) {
		var input = e.target;

		// Bail if the change is not for a viewport radio
		if ( ! input || _settings.viewportInputName !== input.name || ! input.checked ) { return; }

		setPreviewViewport( input.value );
	};

	/**
	 * Handle preview page tab clicks.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handlePageTabClick = function( e ) {
		var tab = e.target.closest( _settings.pageTabSelector );
		var page;
		var requiresPro;

		// Bail if click was not on a page tab
		if ( ! tab ) { return; }

		page = tab.getAttribute( _settings.pageAttribute );
		requiresPro = 'yes' === tab.getAttribute( _settings.requiresProAttribute );

		// Bail if page slug is missing
		if ( ! page ) { return; }

		setPreviewPage( page, requiresPro );
		e.preventDefault();
	};

	/**
	 * Handle settings tab activation from the SPA navigation script.
	 *
	 * @param  {CustomEvent}  e  Tab activated event.
	 */
	var handleSettingsTabActivated = function( e ) {
		var tab = e && e.detail ? e.detail.tab : '';

		// Bail if tab slug is missing
		if ( ! tab ) { return; }

		syncFromSettingsTab( tab );
	};



	/**
	 * Initialize the settings preview column.
	 *
	 * @param  {Object}  options  Localized settings from PHP.
	 */
	var init = function( options ) {
		var preview;
		var expandButton;
		var frame;

		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = extendSettings( _settings, options );

		preview = getPreview();

		// Bail if preview column is not available
		if ( ! preview ) { return; }

		// Add event listeners
		expandButton = document.querySelector( _settings.expandSelector );
		if ( expandButton ) {
			expandButton.addEventListener( 'click', handleExpandClick );
		}
		document.addEventListener( 'change', handleViewportChange, true );
		document.addEventListener( 'click', handlePageTabClick, true );
		window.addEventListener( 'fcSettingsTabActivated', handleSettingsTabActivated );

		frame = document.querySelector( _settings.frameSelector );
		if ( frame && typeof ResizeObserver !== 'undefined' ) {
			var previewResizeObserver = new ResizeObserver( function() {
				updatePreviewDims();
			} );
			previewResizeObserver.observe( frame );
		}
		// Otherwise update dims on window resize
		else {
			window.addEventListener( 'resize', updatePreviewDims );
		}

		// Sync from the initial settings tab
		syncFromSettingsTab( _settings.initialTab );

		_hasInitialized = true;
	};

	// Public APIs
	_publicMethods.init = init;

	return _publicMethods;
} );
