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
	var _previewZoom = 100;
	var _collapseTimer = null;
	var _settings = {
		layoutSelector:              '[data-fc-settings-layout]',
		previewSelector:             '[data-fc-settings-preview]',
		expandSelector:              '[data-fc-settings-preview-expand]',
		toggleSelector:              '[data-fc-settings-preview-toggle]',
		panelSelector:               '[data-fc-settings-preview-panel]',
		frameWrapSelector:           '[data-fc-settings-preview-frame-wrap]',
		frameSelector:               '[data-fc-settings-preview-frame]',
		dimsSelector:                '[data-fc-settings-preview-dims]',
		pageTabSelector:             '[data-fc-settings-preview-page]',
		zoomInSelector:              '[data-fc-settings-preview-zoom-in]',
		zoomOutSelector:             '[data-fc-settings-preview-zoom-out]',
		zoomValueSelector:           '[data-fc-settings-preview-zoom-value]',
		viewportInputName:           'fc-settings-preview-viewport',
		hasPreviewClass:             'has-preview',
		isExpandedClass:             'is-preview-expanded',
		isEnterClass:                'is-preview-enter',
		isCollapsingClass:           'is-preview-collapsing',
		isActiveClass:               'is-active',
		pageAttribute:               'data-fc-settings-preview-page',
		requiresProAttribute:        'data-requires-pro',
		viewportAttribute:           'data-viewport',
		zoomMin:                     50,
		zoomMax:                     200,
		zoomStep:                    25,
		compactBreakpoint:           1280,
		// Same as settings nav: title / menu bar is the full-width sticky section
		navCompactBreakpoint:        980,
		sidebarHeaderSelector:       '.fc-settings-sidebar-header',
		previewTransitionMs:         280,
		hiddenTabs:                  [ 'dashboard', 'license_keys' ],
		initialTab:                  'checkout',
		initialPage:                 'checkout',
		previewProUrlTemplate:       'https://fluidcheckout.com/pricing/?mtm_campaign=upgrade-pro&mtm_kwd=settings-preview-{page}&mtm_source=lite-plugin',
		i18n: {
			expand:                  'Expand preview',
			collapse:                'Collapse preview',
			showPreview:             'Preview',
			preview:                 'Page preview',
			previewTitle:            '%s preview',
			previewSubtitle:         'Isolated session · fields read-only',
			previewSubtitlePro:      'Available with %s.',
			previewProLinkLabel:     'Fluid Checkout PRO',
			zoomIn:                  'Zoom in',
			zoomOut:                 'Zoom out',
		},
	};
	var _compactMediaQuery = null;
	var _navCompactMediaQuery = null;



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
	 * Update the dimension label from the iframe layout viewport
	 * (projected CSS px size — changes with zoom like browser Ctrl+/−).
	 */
	var updatePreviewDims = function() {
		var frame = document.querySelector( _settings.frameSelector );
		var dims = document.querySelector( _settings.dimsSelector );
		var win;
		var doc;
		var width;
		var height;

		// Bail if frame or dims label is missing
		if ( ! frame || ! dims ) { return; }

		try {
			win = frame.contentWindow;
			doc = frame.contentDocument;
		} catch ( err ) {
			return;
		}

		// Bail if iframe document is not available
		if ( ! win || ! doc || ! doc.documentElement ) { return; }

		// Layout viewport inside the iframe (widens when zoomed out, narrows when zoomed in)
		width = Math.round( win.innerWidth || doc.documentElement.clientWidth || 0 );
		height = Math.round( win.innerHeight || doc.documentElement.clientHeight || 0 );

		// Bail if viewport size is not available yet
		if ( ! width || ! height ) { return; }

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
	 * Whether the layout is in the compact preview breakpoint.
	 *
	 * @return  {boolean}
	 */
	var isCompactPreviewLayout = function() {
		return !!( _compactMediaQuery && _compactMediaQuery.matches );
	};

	/**
	 * Whether the settings title / menu bar is in the compact (full-width) layout.
	 *
	 * @return  {boolean}
	 */
	var isNavCompactLayout = function() {
		return !!( _navCompactMediaQuery && _navCompactMediaQuery.matches );
	};

	/**
	 * Whether the user prefers reduced motion.
	 *
	 * @return  {boolean}
	 */
	var prefersReducedMotion = function() {
		return !!( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	};

	/**
	 * Anchor the compact preview drawer below the settings title / menu bar.
	 * Keeps `bottom: 0` and the upward slide animation; only the top edge follows
	 * the live title-bar position when the admin bar and page header scroll away.
	 */
	var syncPreviewDrawerPosition = function() {
		var preview = getPreview();
		var header;

		// Bail if preview is missing
		if ( ! preview ) { return; }

		// Clear inline offsets outside the compact title-bar layout
		if ( ! isCompactPreviewLayout() || ! isNavCompactLayout() ) {
			preview.style.top = '';
			return;
		}

		header = document.querySelector( _settings.sidebarHeaderSelector );

		// Bail if header is missing
		if ( ! header ) { return; }

		preview.style.top = Math.round( header.getBoundingClientRect().bottom ) + 'px';
	};

	/**
	 * Sync expand/collapse control attributes with the current state.
	 *
	 * @param  {boolean}  expanded  Whether the preview is expanded/open.
	 */
	var syncPreviewExpandedControls = function( expanded ) {
		var expandButton = document.querySelector( _settings.expandSelector );
		var toggleButton = document.querySelector( _settings.toggleSelector );
		var label = expanded ? _settings.i18n.collapse : _settings.i18n.expand;

		// Maybe update the expand button
		if ( expandButton ) {
			expandButton.setAttribute( 'aria-pressed', expanded ? 'true' : 'false' );
			expandButton.setAttribute( 'aria-label', label );
			expandButton.setAttribute( 'title', label );
		}

		// Maybe update the actions-bar toggle
		if ( toggleButton ) {
			toggleButton.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		}
	};

	/**
	 * Finish a desktop collapse after the exit transition.
	 *
	 * @param  {Element}  layout  Settings layout element.
	 */
	var finishPreviewCollapse = function( layout ) {
		// Bail if layout is missing
		if ( ! layout ) { return; }

		layout.classList.remove( _settings.isExpandedClass );
		layout.classList.remove( _settings.isCollapsingClass );
		layout.classList.remove( _settings.isEnterClass );
		_collapseTimer = null;
		syncPreviewExpandedControls( false );
		updatePreviewDims();
	};

	/**
	 * Expand or collapse the preview (full width on desktop, vertical drawer when compact).
	 *
	 * @param  {boolean}  expanded  Whether the preview should be expanded/open.
	 */
	var setPreviewExpanded = function( expanded ) {
		var layout = getLayout();
		var isCompact = isCompactPreviewLayout();
		var reduceMotion = prefersReducedMotion();
		var transitionMs = parseInt( _settings.previewTransitionMs, 10 ) || 280;

		// Bail if layout is missing
		if ( ! layout ) { return; }

		// Cancel a pending desktop collapse when toggling again
		if ( _collapseTimer ) {
			window.clearTimeout( _collapseTimer );
			_collapseTimer = null;
			layout.classList.remove( _settings.isCollapsingClass );
		}

		// Keep the drawer pinned to the title bar before toggling visibility
		syncPreviewDrawerPosition();

		// EXPAND
		if ( expanded ) {
			layout.classList.remove( _settings.isCollapsingClass );
			layout.classList.add( _settings.isExpandedClass );
			syncPreviewExpandedControls( true );

			// Compact CSS transitions transform/opacity; desktop needs an enter frame
			if ( ! isCompact && ! reduceMotion ) {
				layout.classList.add( _settings.isEnterClass );
				// Wait two frames so the off-screen start state paints, then transition in
				window.requestAnimationFrame( function() {
					window.requestAnimationFrame( function() {
						layout.classList.remove( _settings.isEnterClass );
						updatePreviewDims();
					} );
				} );
			}
			// Otherwise clear any leftover enter frame
			else {
				layout.classList.remove( _settings.isEnterClass );
				updatePreviewDims();
			}

			return;
		}

		// COLLAPSE — compact animates via CSS when the expanded class is removed
		if ( isCompact || reduceMotion || ! layout.classList.contains( _settings.isExpandedClass ) ) {
			layout.classList.remove( _settings.isExpandedClass );
			layout.classList.remove( _settings.isEnterClass );
			layout.classList.remove( _settings.isCollapsingClass );
			syncPreviewExpandedControls( false );
			updatePreviewDims();
			return;
		}

		// Desktop collapse: keep fixed positioning until the exit transition ends
		layout.classList.add( _settings.isCollapsingClass );
		syncPreviewExpandedControls( false );
		_collapseTimer = window.setTimeout( function() {
			finishPreviewCollapse( layout );
		}, transitionMs );
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

		// Re-apply zoom so the layout viewport matches the new visual frame size
		applyPreviewZoom();
		updatePreviewDims();
	};

	/**
	 * Clamp a zoom percentage to the allowed range.
	 *
	 * @param   {number}  percent  Requested zoom percentage.
	 * @return  {number}
	 */
	var clampPreviewZoom = function( percent ) {
		var min = parseInt( _settings.zoomMin, 10 ) || 50;
		var max = parseInt( _settings.zoomMax, 10 ) || 200;
		var value = parseInt( percent, 10 );

		if ( isNaN( value ) ) {
			value = 100;
		}

		if ( value < min ) { return min; }
		if ( value > max ) { return max; }
		return value;
	};

	/**
	 * Clear inline zoom sizing/transform from the preview iframe.
	 *
	 * @param  {Element}  frame  Preview iframe element.
	 */
	var clearPreviewZoomStyles = function( frame ) {
		frame.style.position = '';
		frame.style.top = '';
		frame.style.left = '';
		frame.style.width = '';
		frame.style.height = '';
		frame.style.transform = '';
		frame.style.transformOrigin = '';
	};

	/**
	 * Apply zoom like browser Ctrl+/−: keep the visual frame size, expand/shrink
	 * the iframe layout viewport, then scale so it fits. Dims then measure the
	 * projected CSS px size inside the iframe.
	 */
	var applyPreviewZoom = function() {
		var frame = document.querySelector( _settings.frameSelector );
		var frameWrap = document.querySelector( _settings.frameWrapSelector );
		var doc;
		var zoom;
		var visualWidth;
		var visualHeight;

		// Bail if frame or wrap is missing
		if ( ! frame || ! frameWrap ) { return; }

		// Remove document-level zoom if a previous build set it
		try {
			doc = frame.contentDocument;
			if ( doc && doc.documentElement ) {
				doc.documentElement.style.zoom = '';
			}
		} catch ( err ) {
			// Ignore cross-origin access errors
		}

		zoom = _previewZoom / 100;

		// At 100%, use normal flex-filled iframe sizing
		if ( 1 === zoom ) {
			clearPreviewZoomStyles( frame );
			return;
		}

		visualWidth = frameWrap.clientWidth;
		visualHeight = frameWrap.clientHeight;

		// Bail if the visual frame size is not available yet
		if ( ! visualWidth || ! visualHeight ) { return; }

		// Larger layout viewport when zoomed out; smaller when zoomed in
		frame.style.position = 'absolute';
		frame.style.top = '0';
		frame.style.left = '0';
		frame.style.width = ( visualWidth / zoom ) + 'px';
		frame.style.height = ( visualHeight / zoom ) + 'px';
		frame.style.transformOrigin = 'top left';
		frame.style.transform = 'scale( ' + zoom + ' )';
	};

	/**
	 * Update zoom control UI and apply zoom to the preview iframe.
	 *
	 * @param  {number}  percent  Zoom percentage to apply.
	 */
	var setPreviewZoom = function( percent ) {
		var valueEl = document.querySelector( _settings.zoomValueSelector );
		var zoomInButton = document.querySelector( _settings.zoomInSelector );
		var zoomOutButton = document.querySelector( _settings.zoomOutSelector );
		var min = parseInt( _settings.zoomMin, 10 ) || 50;
		var max = parseInt( _settings.zoomMax, 10 ) || 200;

		_previewZoom = clampPreviewZoom( percent );

		if ( valueEl ) {
			valueEl.textContent = _previewZoom + '%';
		}

		if ( zoomOutButton ) {
			zoomOutButton.disabled = _previewZoom <= min;
		}

		if ( zoomInButton ) {
			zoomInButton.disabled = _previewZoom >= max;
		}

		applyPreviewZoom();
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

			// Keep the compact drawer closed when switching to a tab that has preview
			if ( isCompactPreviewLayout() ) {
				setPreviewExpanded( false );
			}
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

		// Always show the iframe placeholder panel
		if ( panel ) {
			panel.removeAttribute( 'hidden' );
		}

		setPreviewContent( page, pageLabel, requiresPro );
		applyPreviewZoom();
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
	 * Handle actions-bar Preview toggle clicks (compact layout).
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleToggleClick = function( e ) {
		var toggleButton = e.target.closest( _settings.toggleSelector );

		// Bail if click was not on the preview toggle
		if ( ! toggleButton ) { return; }

		setPreviewExpanded( true );
		e.preventDefault();
	};

	/**
	 * Collapse the preview when crossing the compact breakpoint.
	 */
	var handleCompactBreakpointChange = function() {
		setPreviewExpanded( false );
		syncPreviewDrawerPosition();
		updatePreviewDims();
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
	 * Handle zoom in/out/reset control clicks.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleZoomClick = function( e ) {
		var zoomInButton = e.target.closest( _settings.zoomInSelector );
		var zoomOutButton = e.target.closest( _settings.zoomOutSelector );
		var zoomValueButton = e.target.closest( _settings.zoomValueSelector );
		var step = parseInt( _settings.zoomStep, 10 ) || 25;

		// Bail if click was not on a zoom control
		if ( ! zoomInButton && ! zoomOutButton && ! zoomValueButton ) { return; }

		if ( zoomValueButton ) {
			setPreviewZoom( 100 );
		}
		// Otherwise maybe zoom in
		else if ( zoomInButton && ! zoomInButton.disabled ) {
			setPreviewZoom( _previewZoom + step );
		}
		// Otherwise maybe zoom out
		else if ( zoomOutButton && ! zoomOutButton.disabled ) {
			setPreviewZoom( _previewZoom - step );
		}

		e.preventDefault();
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
		var breakpoint;
		var navCompactBreakpoint;

		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = extendSettings( _settings, options );

		preview = getPreview();

		// Bail if preview column is not available
		if ( ! preview ) { return; }

		// Track the compact preview breakpoint
		breakpoint = parseInt( _settings.compactBreakpoint, 10 ) || 1280;
		_compactMediaQuery = window.matchMedia( '(max-width: ' + breakpoint + 'px)' );
		if ( typeof _compactMediaQuery.addEventListener === 'function' ) {
			_compactMediaQuery.addEventListener( 'change', handleCompactBreakpointChange );
		}
		// Otherwise use the legacy MediaQueryList API
		else if ( typeof _compactMediaQuery.addListener === 'function' ) {
			_compactMediaQuery.addListener( handleCompactBreakpointChange );
		}

		// Track when the settings title / menu bar is the compact sticky section
		navCompactBreakpoint = parseInt( _settings.navCompactBreakpoint, 10 ) || 980;
		_navCompactMediaQuery = window.matchMedia( '(max-width: ' + navCompactBreakpoint + 'px)' );
		if ( typeof _navCompactMediaQuery.addEventListener === 'function' ) {
			_navCompactMediaQuery.addEventListener( 'change', syncPreviewDrawerPosition );
		}
		// Otherwise use the legacy MediaQueryList API
		else if ( typeof _navCompactMediaQuery.addListener === 'function' ) {
			_navCompactMediaQuery.addListener( syncPreviewDrawerPosition );
		}

		// Add event listeners
		expandButton = document.querySelector( _settings.expandSelector );
		if ( expandButton ) {
			expandButton.addEventListener( 'click', handleExpandClick );
		}
		document.addEventListener( 'click', handleToggleClick, true );
		document.addEventListener( 'change', handleViewportChange, true );
		document.addEventListener( 'click', handlePageTabClick, true );
		document.addEventListener( 'click', handleZoomClick, true );
		window.addEventListener( 'fcSettingsTabActivated', handleSettingsTabActivated );
		// Capture scroll from nested containers; keep the drawer aligned while sticky headers move
		window.addEventListener( 'scroll', syncPreviewDrawerPosition, true );
		window.addEventListener( 'resize', syncPreviewDrawerPosition );

		frame = document.querySelector( _settings.frameSelector );
		if ( frame ) {
			frame.addEventListener( 'load', function() {
				applyPreviewZoom();
				updatePreviewDims();
			} );
		}

		var frameWrap = document.querySelector( _settings.frameWrapSelector );
		if ( frameWrap && typeof ResizeObserver !== 'undefined' ) {
			var previewResizeObserver = new ResizeObserver( function() {
				applyPreviewZoom();
				updatePreviewDims();
			} );
			previewResizeObserver.observe( frameWrap );
		}
		// Otherwise re-apply zoom and dims on window resize
		else {
			window.addEventListener( 'resize', function() {
				applyPreviewZoom();
				updatePreviewDims();
			} );
		}

		// Sync from the initial settings tab and zoom UI
		setPreviewZoom( _previewZoom );
		syncFromSettingsTab( _settings.initialTab );

		// Start with the compact drawer closed
		if ( isCompactPreviewLayout() ) {
			setPreviewExpanded( false );
		}

		syncPreviewDrawerPosition();

		_hasInitialized = true;
	};

	// Public APIs
	_publicMethods.init = init;
	_publicMethods.setExpanded = setPreviewExpanded;

	return _publicMethods;
} );
