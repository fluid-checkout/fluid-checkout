/**
 * Page preview column for the Fluid Checkout settings page.
 * Expand/collapse, viewport size, page tabs, and visibility by settings tab.
 *
 * DEPENDS ON:
 * - jQuery // Settings field changes, as the color picker triggers them with jQuery
 * - FCUtils // Utility functions
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

	var $ = jQuery;
	var _hasJQuery = ( $ != null );

	var _hasInitialized = false;
	var _publicMethods = {};
	var _previewWidth = 0;
	var _isDesktopFillViewport = false;
	var _collapseTimer = null;
	var _previewMode = 'guest';
	var _isHeaderFooterVisible = false;
	var _cssVariables = {};
	var _colorScheme = '';
	var _bodyClasses = {};
	var _fieldValues = {};
	var _computedCssValueRequests = {};
	var _computedCssValueRequestId = 0;
	var _isPreviewLoading = false;
	var _isPreviewReady = false;
	var _isRefreshingStyles = false;
	var _hasUnsavedDraft = false;
	var _isSavingDraft = false;
	var _needsRefresh = false;
	var _needsReload = false;
	var _saveDraftDebounced = null;
	var _modeAutoChangedTimer = null;
	var _settings = {
		formSelector:                '[data-fc-settings-form]',
		layoutSelector:              '[data-fc-settings-layout]',
		previewSelector:             '[data-fc-settings-preview]',
		expandSelector:              '[data-fc-settings-preview-expand]',
		toggleSelector:              '[data-fc-settings-preview-toggle]',
		panelSelector:               '[data-fc-settings-preview-panel]',
		frameWrapSelector:           '[data-fc-settings-preview-frame-wrap]',
		frameSelector:               '[data-fc-settings-preview-frame]',
		dimsSelector:                '[data-fc-settings-preview-dims]',
		pageTabSelector:             '[data-fc-settings-preview-page]',
		widthDecreaseSelector:       '[data-fc-settings-preview-width-decrease]',
		widthIncreaseSelector:       '[data-fc-settings-preview-width-increase]',
		widthInputSelector:          '[data-fc-settings-preview-width]',
		zoomHintSelector:            '[data-fc-settings-preview-zoom-hint]',
		modeSelector:                '[data-fc-settings-preview-mode]',
		modeFieldSelector:           '[data-fc-settings-preview-mode-field]',
		modeAutoChangedClass:        'is-auto-changed',
		modeAutoChangedDurationMs:   3000,
		headerFooterSelector:        '[data-fc-settings-preview-header-footer]',
		viewportInputSelector:       'input[name="fc-settings-preview-viewport"]',
		enableSettingFieldSelector:  '[name="###NAME###"][type="checkbox"]',
		hasPreviewClass:             'has-preview',
		isExpandedClass:             'is-preview-expanded',
		isEnterClass:                'is-preview-enter',
		isCollapsingClass:           'is-preview-collapsing',
		isActiveClass:               'is-active',
		isBusyClass:                 'is-busy',
		pageAttribute:               'data-fc-settings-preview-page',
		urlAttribute:                'data-fc-settings-preview-url',
		enableSettingAttribute:      'data-fc-settings-preview-enable-setting',
		placeholderScaleProperty:    '--fc-settings-preview-scale',
		viewportWidths:              { mobile: 350, tablet: 750, desktop: 1000 },
		widthMin:                    320,
		widthMax:                    1920,
		widthStep:                   100,
		zoomHintThreshold:           0.6,
		compactBreakpoint:           1280,
		// Same as settings nav: title / menu bar is the full-width sticky section
		navCompactBreakpoint:        980,
		sidebarHeaderSelector:       '.fc-settings-sidebar-header',
		previewTransitionMs:         280,
		hiddenTabs:                  [ 'dashboard', 'license_keys' ],
		initialTab:                  'checkout',
		initialPage:                 'checkout',
		placeholderSrcdocs:          {},
		defaultPage:                 'checkout',
		modeQueryArg:                'fc_preview_mode',
		guestMode:                   'guest',
		loggedInMode:                'logged_in',
		tabPreviewModes:             {},
		messageSource:               'fc-admin-preview',
		storageKey:                  'fcAdminSettingsPreview',
		ajaxUrl:                     '',
		draftAction:                 'fc_admin_preview_save_draft',
		draftNonce:                  '',
		previewToken:                '',
		styleSettings:               [],
		draftSaveDelay:              300,
		i18n: {
			expand:                  'Expand preview',
			collapse:                'Collapse preview',
			showPreview:             'Preview',
			showHeaderFooter:        'Show header and footer',
			hideHeaderFooter:        'Hide header and footer',
		},
	};
	var _compactMediaQuery = null;
	var _navCompactMediaQuery = null;



	/**
	 * METHODS
	 */



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

		return _settings.defaultPage;
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
					} );
				} );
			}
			// Otherwise clear any leftover enter frame
			else {
				layout.classList.remove( _settings.isEnterClass );
			}

			return;
		}

		// COLLAPSE — compact animates via CSS when the expanded class is removed
		if ( isCompact || reduceMotion || ! layout.classList.contains( _settings.isExpandedClass ) ) {
			layout.classList.remove( _settings.isExpandedClass );
			layout.classList.remove( _settings.isEnterClass );
			layout.classList.remove( _settings.isCollapsingClass );
			syncPreviewExpandedControls( false );
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
	 * Get the width the page preview lays out at for an available panel width.
	 *
	 * @param   {number}  availableWidth  Width available for the page preview.
	 * @return  {number}                  Page preview layout width in pixels.
	 */
	var getPreviewLayoutWidth = function( availableWidth ) {
		// Maybe fill the available width on desktop, never below the desktop viewport minimum
		if ( _isDesktopFillViewport ) {
			return Math.max( _settings.viewportWidths.desktop, Math.min( availableWidth, _settings.widthMax ) );
		}

		return _previewWidth;
	};

	/**
	 * Get the page preview scale for an available width, scaled down to fit and never up.
	 *
	 * @param   {number}  availableWidth  Width available for the page preview.
	 * @param   {number}  layoutWidth     Width the page preview lays out at.
	 * @return  {number}                  Page preview scale.
	 */
	var getPreviewScale = function( availableWidth, layoutWidth ) {
		return Math.min( 1, availableWidth / layoutWidth );
	};

	/**
	 * Get the page preview scale while the preview expands or collapses, so the page preview grows or shrinks along with the column until the end.
	 * Fitting the available width alone reaches the full page preview width midway, as the page preview is never scaled up.
	 *
	 * @param   {Element}  preview         Preview column element.
	 * @param   {number}   availableWidth  Width available for the page preview.
	 * @return  {number|undefined}         Page preview scale, or `undefined` when the preview is not expanding or collapsing.
	 */
	var getTransitionPreviewScale = function( preview, availableWidth ) {
		var animations = typeof preview.getAnimations === 'function' ? preview.getAnimations() : [];
		var property;
		var keyframes;
		var edgeOffset;
		var startWidth;
		var endWidth;
		var startScale;
		var i;

		// Iterate preview animations
		for ( i = 0; i < animations.length; i++ ) {
			// Get the property the animation transitions
			property = animations[ i ].transitionProperty;

			// Skip animations other than the moving edge transition, which is the right edge in RTL
			if ( 'left' !== property && 'right' !== property ) { continue; }

			// Get the available widths at the start and end of the transition, as the width changes as much as the edge moves
			keyframes = animations[ i ].effect.getKeyframes();
			edgeOffset = parseFloat( window.getComputedStyle( preview )[ property ] );
			startWidth = availableWidth + edgeOffset - parseFloat( keyframes[ 0 ][ property ] );
			endWidth = availableWidth + edgeOffset - parseFloat( keyframes[ keyframes.length - 1 ][ property ] );
			startScale = getPreviewScale( startWidth, getPreviewLayoutWidth( startWidth ) );

			// Return the scale as far between the start and end scales as the available width is between the start and end widths
			return startScale + ( getPreviewScale( endWidth, getPreviewLayoutWidth( endWidth ) ) - startScale ) * ( availableWidth - startWidth ) / ( endWidth - startWidth );
		}
	};

	/**
	 * Apply the page preview width to the frame, scaled down to the available width and filling the available height.
	 */
	var applyPreviewSize = function() {
		var preview = getPreview();
		var panel = document.querySelector( _settings.panelSelector );
		var frameWrap = document.querySelector( _settings.frameWrapSelector );
		var frame = document.querySelector( _settings.frameSelector );
		var dims = document.querySelector( _settings.dimsSelector );
		var zoomHint = document.querySelector( _settings.zoomHintSelector );
		var layout = getLayout();
		var panelStyle;
		var borderWidth;
		var availableWidth;
		var availableHeight;
		var layoutWidth;
		var scale;
		var frameHeight;

		// Bail if the preview, panel, frame wrap or frame is missing
		if ( ! preview || ! panel || ! frameWrap || ! frame ) { return; }

		// Get the space available for the page preview, inside the panel padding and the frame border
		panelStyle = window.getComputedStyle( panel );
		borderWidth = frameWrap.offsetWidth - frameWrap.clientWidth;
		availableWidth = panel.clientWidth - parseFloat( panelStyle.paddingLeft ) - parseFloat( panelStyle.paddingRight ) - borderWidth;
		availableHeight = frameWrap.clientHeight;

		// Bail if the available space is not known, such as while the preview is hidden
		if ( availableWidth <= 0 || availableHeight <= 0 ) { return; }

		// Get the layout width, which fills the available width on desktop down to the desktop minimum
		layoutWidth = getPreviewLayoutWidth( availableWidth );

		// Scale the page preview down to the available width, never up, along with the column while it expands or collapses, and fill the available height
		scale = getTransitionPreviewScale( preview, availableWidth ) || getPreviewScale( availableWidth, layoutWidth );
		frameHeight = Math.round( availableHeight / scale );

		// Size the frame to the scaled page preview, and lay the page out at the preview width
		frameWrap.style.maxWidth = ( layoutWidth * scale + borderWidth ) + 'px';
		frame.style.width = layoutWidth + 'px';
		frame.style.height = frameHeight + 'px';
		frame.style.transform = 1 === scale ? '' : 'scale( ' + scale + ' )';

		// Maybe keep the placeholder text at its size on screen, as placeholders are scaled down along with page previews
		if ( ! getPreviewOrigin() && frame.contentDocument ) {
			frame.contentDocument.documentElement.style.setProperty( _settings.placeholderScaleProperty, scale );
		}

		// Maybe show the size and zoom of the page preview
		if ( dims ) {
			dims.textContent = layoutWidth + ' \u00d7 ' + frameHeight + ' \u00b7 ' + Math.round( scale * 100 ) + '%';
		}

		// Maybe sync the width field with the layout width on desktop, unless the merchant is editing it
		syncPreviewWidthControls( layoutWidth );

		// Maybe suggest expanding the preview when it is zoomed out a lot, unless already expanded or in the compact layout drawer
		if ( zoomHint ) {
			zoomHint.hidden = scale >= _settings.zoomHintThreshold || isCompactPreviewLayout() || ( layout && layout.classList.contains( _settings.isExpandedClass ) );
		}
	};

	/**
	 * Sync the width field, step buttons, and viewport radios with the current preview width.
	 *
	 * @param  {number}  layoutWidth  Width the page preview lays out at.
	 */
	var syncPreviewWidthControls = function( layoutWidth ) {
		var widthInput = document.querySelector( _settings.widthInputSelector );
		var decreaseButton = document.querySelector( _settings.widthDecreaseSelector );
		var increaseButton = document.querySelector( _settings.widthIncreaseSelector );
		var viewportInputs = document.querySelectorAll( _settings.viewportInputSelector );
		var i;

		// Maybe update the width field, unless the merchant is typing in it
		if ( widthInput && document.activeElement !== widthInput ) {
			widthInput.value = layoutWidth;
		}

		// Maybe disable the decrease button at the minimum width
		if ( decreaseButton ) {
			decreaseButton.disabled = layoutWidth <= ( _isDesktopFillViewport ? _settings.viewportWidths.desktop : _settings.widthMin );
		}

		// Maybe disable the increase button at the maximum width
		if ( increaseButton ) {
			increaseButton.disabled = layoutWidth >= _settings.widthMax;
		}

		// Iterate viewport buttons
		for ( i = 0; i < viewportInputs.length; i++ ) {
			// Select desktop while it fills the available width, otherwise select the preset with the same width
			if ( 'desktop' === viewportInputs[ i ].value ) {
				viewportInputs[ i ].checked = _isDesktopFillViewport;
			}
			else {
				viewportInputs[ i ].checked = ! _isDesktopFillViewport && _settings.viewportWidths[ viewportInputs[ i ].value ] === _previewWidth;
			}
		}
	};

	/**
	 * Set the width the page preview lays out at, and fit it in the frame.
	 *
	 * @param  {number|string}  width  Page preview width in pixels.
	 */
	var setPreviewWidth = function( width ) {
		var newWidth = parseInt( width, 10 );

		// Leave desktop fill mode when the merchant sets a custom width
		_isDesktopFillViewport = false;

		// Keep the current width for values that are not numbers, otherwise limit the width to the allowed range
		_previewWidth = isNaN( newWidth ) ? _previewWidth : Math.min( _settings.widthMax, Math.max( _settings.widthMin, newWidth ) );

		// Fit the page preview in the frame at the new width
		applyPreviewSize();
	};

	/**
	 * Use the desktop viewport, filling the available width down to the desktop minimum.
	 */
	var setDesktopFillViewport = function() {
		_isDesktopFillViewport = true;
		_previewWidth = _settings.viewportWidths.desktop;
		applyPreviewSize();
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
	 * Load a page preview in the iframe, or the placeholder for pages that cannot be previewed.
	 *
	 * @param  {string}  page  Preview page slug.
	 * @param  {string}  url   Preview URL of the page in guest mode, empty when the page cannot be previewed, such as pages that require PRO or are disabled in the settings.
	 */
	var setPreviewFrameSource = function( page, url ) {
		var frame = document.querySelector( _settings.frameSelector );
		var previewUrl = getPreviewModeUrl( url );

		// Bail if frame is missing
		if ( ! frame ) { return; }

		// Maybe show the placeholder
		if ( ! url ) {
			frame.setAttribute( 'srcdoc', _settings.placeholderSrcdocs[ page ] );
			frame.removeAttribute( 'src' );
			markPreviewLoading();
		}
		// Otherwise, maybe load the page preview, unless it is already loaded
		else if ( previewUrl !== frame.getAttribute( 'src' ) ) {
			frame.setAttribute( 'src', previewUrl );
			frame.removeAttribute( 'srcdoc' );
			markPreviewLoading();
		}
	};

	/**
	 * Activate a preview page tab and load its preview or placeholder in the iframe.
	 *
	 * @param  {string}  page  Preview page slug.
	 */
	var setPreviewPage = function( page ) {
		var pageTabs = document.querySelectorAll( _settings.pageTabSelector );
		var panel = document.querySelector( _settings.panelSelector );
		var url = '';
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
				url = getPageTabUrl( tab );
			}
		}

		// Always show the iframe placeholder panel
		if ( panel ) {
			panel.removeAttribute( 'hidden' );
		}

		// Load the page preview, or the placeholder, in the iframe
		setPreviewFrameSource( page, url );
	};

	/**
	 * Get the preview mode configured for a settings tab, if any.
	 *
	 * @param  {string}  tab  Settings tab slug.
	 *
	 * @return  {string}  Preview mode, or an empty string when the tab has no configured mode.
	 */
	var getTabPreviewMode = function( tab ) {
		return ( _settings.tabPreviewModes && _settings.tabPreviewModes[ tab ] ) || '';
	};

	/**
	 * Switch the preview mode when the active settings tab requires it.
	 *
	 * @param  {string}  tab  Settings tab slug.
	 *
	 * @return  {boolean}  Whether the preview mode changed.
	 */
	var maybeApplyPreviewModeForTab = function( tab ) {
		var tabMode = getTabPreviewMode( tab );

		// Bail if the tab has no configured mode, or the mode is already selected
		if ( ! tabMode || tabMode === _previewMode ) { return false; }

		setPreviewMode( tabMode, true );

		return true;
	};

	/**
	 * Sync preview visibility and page from a settings tab change.
	 *
	 * @param  {string}  tab  Settings tab slug.
	 */
	var syncFromSettingsTab = function( tab ) {
		setPreviewVisibilityForTab( tab );

		// Bail if preview is hidden for this tab
		if ( ! isPreviewVisibleForTab( tab ) ) { return; }

		maybeApplyPreviewModeForTab( tab );

		// Show the preview page of the settings tab
		setPreviewPage( getPreviewPageForTab( tab ) );
	};



	/**
	 * Get the active preview page tab.
	 *
	 * @return  {Element|null}
	 */
	var getActivePageTab = function() {
		return document.querySelector( _settings.pageTabSelector + '.' + _settings.isActiveClass );
	};

	/**
	 * Get the preview URL of a page tab, or an empty string when the page cannot be previewed: pages that require PRO, and pages disabled in the settings form, also by unsaved changes.
	 *
	 * @param  {Element|null}  pageTab  Preview page tab.
	 *
	 * @return  {string}  Preview URL of the page in guest mode, or an empty string.
	 */
	var getPageTabUrl = function( pageTab ) {
		var form = document.querySelector( _settings.formSelector );
		var enableSetting;
		var enableField;

		// Bail if there is no page tab
		if ( ! pageTab ) { return ''; }

		// Get the field of the setting that enables the page, for pages that have one
		enableSetting = pageTab.getAttribute( _settings.enableSettingAttribute );
		enableField = form && enableSetting ? form.querySelector( _settings.enableSettingFieldSelector.replace( '###NAME###', CSS.escape( enableSetting ) ) ) : null;

		// Bail if the page is disabled in the settings form
		if ( enableField && ! enableField.checked ) { return ''; }

		return pageTab.getAttribute( _settings.urlAttribute ) || '';
	};



	/**
	 * Get the preview state saved in the browser: preview mode, header and footer visibility, and expanded state.
	 *
	 * @return  {Object}  Saved preview state.
	 */
	var getSavedState = function() {
		// Read saved state, as browser storage can be blocked or unavailable
		try {
			return JSON.parse( window.localStorage.getItem( _settings.storageKey ) ) || {};
		}
		// Use an empty state when browser storage is not available
		catch ( error ) {
			return {};
		}
	};

	/**
	 * Save a value of the preview state in the browser.
	 *
	 * @param  {string}  key    State key.
	 * @param  {*}       value  State value.
	 */
	var saveState = function( key, value ) {
		var state = getSavedState();

		// Save state, as browser storage can be blocked or unavailable
		try {
			state[ key ] = value;
			window.localStorage.setItem( _settings.storageKey, JSON.stringify( state ) );
		}
		// Keep the state for this page load only when browser storage is not available
		catch ( error ) {
			// Intentionally empty
		}
	};



	/**
	 * Get the origin of the page preview loaded in the iframe.
	 *
	 * @return  {string}  Preview origin, or an empty string when the iframe shows a placeholder.
	 */
	var getPreviewOrigin = function() {
		var frame = document.querySelector( _settings.frameSelector );

		// Bail if the iframe shows a placeholder
		if ( ! frame || ! frame.getAttribute( 'src' ) ) { return ''; }

		return new URL( frame.getAttribute( 'src' ), window.location.href ).origin;
	};

	/**
	 * Send a message to the page preview loaded in the iframe.
	 *
	 * @param  {Object}  message  Message with its `type` and values.
	 */
	var postMessageToPreview = function( message ) {
		var frame = document.querySelector( _settings.frameSelector );
		var origin = getPreviewOrigin();

		// Bail if the iframe shows a placeholder
		if ( ! origin ) { return; }

		// Send message to the preview origin only
		message.source = _settings.messageSource;
		frame.contentWindow.postMessage( message, origin );
	};

	/**
	 * Send the preview state to the page preview, as each reload resets it.
	 */
	var sendPreviewState = function() {
		var requestId;

		// Send the header and footer visibility, the values entered in the fields in the current mode, and styles
		postMessageToPreview( { type: 'setHeaderFooterVisibility', visible: _isHeaderFooterVisible } );
		postMessageToPreview( { type: 'setFieldValues', values: _fieldValues[ _previewMode ] || [] } );
		postMessageToPreview( { type: 'setColorScheme', scheme: _colorScheme } );
		postMessageToPreview( { type: 'setCssVariables', variables: _cssVariables } );
		postMessageToPreview( { type: 'setBodyClasses', classes: _bodyClasses } );

		// Iterate pending computed value requests, sent again as a reload drops them
		for ( requestId in _computedCssValueRequests ) {
			postMessageToPreview( { type: 'getComputedCssValue', requestId: requestId, property: _computedCssValueRequests[ requestId ].property, value: _computedCssValueRequests[ requestId ].value } );
		}
	};

	/**
	 * Resolve a computed value request with the value sent by the page preview.
	 *
	 * @param  {number|string}  requestId  Request ID.
	 * @param  {string}         value      Computed value.
	 */
	var resolveComputedCssValueRequest = function( requestId, value ) {
		var request = _computedCssValueRequests[ requestId ];

		// Bail if the request is already resolved
		if ( ! request ) { return; }

		// Resolve the request with the computed value
		delete _computedCssValueRequests[ requestId ];
		request.resolve( value );
	};



	/**
	 * Add the preview mode to a page preview URL.
	 *
	 * @param   {string}  url  Page preview URL in guest mode.
	 * @return  {string}       Page preview URL in the selected mode.
	 */
	var getPreviewModeUrl = function( url ) {
		// Bail if no URL, or in guest mode which page preview URLs use by default
		if ( ! url || _settings.guestMode === _previewMode ) { return url; }

		return url + ( -1 === url.indexOf( '?' ) ? '?' : '&' ) + _settings.modeQueryArg + '=' + encodeURIComponent( _previewMode );
	};

	/**
	 * Show a grey outline on the preview mode field after an automatic mode change.
	 */
	var flashPreviewModeAutoChanged = function() {
		var modeField = document.querySelector( _settings.modeFieldSelector );

		// Bail if the mode field wrapper is missing
		if ( ! modeField ) { return; }

		modeField.classList.add( _settings.modeAutoChangedClass );

		// Maybe clear a previous auto-changed timer
		if ( _modeAutoChangedTimer ) {
			window.clearTimeout( _modeAutoChangedTimer );
		}

		_modeAutoChangedTimer = window.setTimeout( function() {
			modeField.classList.remove( _settings.modeAutoChangedClass );
			_modeAutoChangedTimer = null;
		}, _settings.modeAutoChangedDurationMs );
	};

	/**
	 * Set the preview mode, as selected in the guest / logged-in control.
	 *
	 * @param  {string}   mode          Preview mode.
	 * @param  {boolean}  isAutomatic   Whether the mode changed programmatically, such as when switching settings tabs.
	 */
	var setPreviewMode = function( mode, isAutomatic ) {
		var modeField = document.querySelector( _settings.modeFieldSelector );
		var modeInput;
		var modeRadios;
		var previousMode = _previewMode;

		// Bail if the mode field is missing
		if ( ! modeField ) { return; }

		modeInput = modeField.querySelector( _settings.modeSelector + '[value="' + mode + '"]' );

		// Use guest mode when the control has no matching option
		_previewMode = modeInput ? mode : _settings.guestMode;
		modeRadios = modeField.querySelectorAll( _settings.modeSelector );

		// Sync the segmented buttons with the preview mode
		modeRadios.forEach( function( radio ) {
			radio.checked = radio.value === _previewMode;
		} );

		// Maybe highlight the mode field when the mode changed automatically
		if ( isAutomatic && previousMode !== _previewMode ) {
			flashPreviewModeAutoChanged();
		}
	};

	/**
	 * Load the active page tab in the page preview, such as after changing the preview mode.
	 */
	var loadActivePreviewPage = function() {
		var pageTab = getActivePageTab();

		// Bail if no page tab is active
		if ( ! pageTab ) { return; }

		// Load the page of the active tab
		setPreviewPage( pageTab.getAttribute( _settings.pageAttribute ) );
	};

	/**
	 * Show or hide the site header and footer in the page preview.
	 *
	 * @param  {boolean}  visible  Whether the header and footer are visible.
	 */
	var setHeaderFooterVisible = function( visible ) {
		var button = document.querySelector( _settings.headerFooterSelector );
		var label = visible ? _settings.i18n.hideHeaderFooter : _settings.i18n.showHeaderFooter;

		// Remember the visibility, sent again after each reload
		_isHeaderFooterVisible = visible;

		// Maybe update the toggle button
		if ( button ) {
			button.setAttribute( 'aria-pressed', visible ? 'true' : 'false' );
			button.setAttribute( 'aria-label', label );
			button.setAttribute( 'title', label );
		}

		// Show or hide them in the page preview
		postMessageToPreview( { type: 'setHeaderFooterVisibility', visible: visible } );
	};



	/**
	 * Mark the page preview as loading a page, which shows the unsaved settings saved so far.
	 */
	var markPreviewLoading = function() {
		// Mark the page preview as loading, which also ends style refreshes and pending refreshes
		_isPreviewLoading = true;
		_isPreviewReady = false;
		_isRefreshingStyles = false;
		_needsRefresh = false;

		// Show the page preview as busy while it loads
		updatePreviewBusyState();
	};

	/**
	 * Show whether the page preview is busy, while it loads or until it shows the unsaved settings.
	 */
	var updatePreviewBusyState = function() {
		var frameWrap = document.querySelector( _settings.frameWrapSelector );
		var isBusy = _hasUnsavedDraft || _isSavingDraft || _needsRefresh || _isPreviewLoading || _isRefreshingStyles;

		// Bail if the frame wrap is missing
		if ( ! frameWrap ) { return; }

		// Dim the page preview and show the spinner
		frameWrap.classList.toggle( _settings.isBusyClass, isBusy );
		frameWrap.setAttribute( 'aria-busy', isBusy ? 'true' : 'false' );
	};



	/**
	 * Save the unsaved settings for the page preview, which shows them once refreshed.
	 */
	var saveDraft = function() {
		var formData;

		// Bail if a request is saving, which saves again once it finishes, so an older request cannot overwrite newer values
		if ( _isSavingDraft ) { return; }

		// Send the settings form values
		_hasUnsavedDraft = false;
		_isSavingDraft = true;
		formData = new FormData( document.querySelector( _settings.formSelector ) );
		formData.set( 'action', _settings.draftAction );
		formData.set( 'nonce', _settings.draftNonce );
		formData.set( 'token', _settings.previewToken );
		fetch( _settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData } ).then( handleDraftSaved, handleDraftSaved );
	};

	/**
	 * Handle the end of a request saving the unsaved settings.
	 *
	 * @param  {Response|Error}  response  Fetch response, or the error when the request failed.
	 */
	var handleDraftSaved = function( response ) {
		// Mark the request as finished
		_isSavingDraft = false;

		// Maybe save the changes made during the request
		if ( _hasUnsavedDraft ) {
			saveDraft();
		}
		// Otherwise, maybe refresh the page preview, unless the request failed
		else if ( response && response.ok ) {
			_needsRefresh = true;
			maybeRefreshPreview();
		}

		// Show whether the page preview is busy
		updatePreviewBusyState();
	};

	/**
	 * Maybe refresh the page preview with the unsaved settings, once it is not loading or refreshing its styles, as the page preview would miss the messages sent meanwhile.
	 */
	var maybeRefreshPreview = function() {
		var frame = document.querySelector( _settings.frameSelector );

		// Bail if nothing to refresh, or the page preview is loading or refreshing its styles
		if ( ! _needsRefresh || _isPreviewLoading || _isRefreshingStyles ) { return; }

		// Mark the refresh as handled
		_needsRefresh = false;

		// Maybe load the active page anew, when the settings enabled or disabled it, which switches between its preview and placeholder
		if ( ( '' === getPageTabUrl( getActivePageTab() ) ) !== ( '' === getPreviewOrigin() ) ) {
			_needsReload = false;
			loadActivePreviewPage();
		}
		// Otherwise, maybe skip placeholders, as page previews show the unsaved settings when they load
		else if ( ! getPreviewOrigin() ) {
			_needsReload = false;
		}
		// Otherwise, maybe refresh only the styles of the page preview
		else if ( ! _needsReload && _isPreviewReady ) {
			_isRefreshingStyles = true;
			postMessageToPreview( { type: 'refreshStyles' } );
		}
		// Otherwise, maybe reload the page preview from its page, which keeps the scroll position
		else if ( _isPreviewReady ) {
			_needsReload = false;
			postMessageToPreview( { type: 'reload' } );
			markPreviewLoading();
		}
		// Otherwise reload the iframe, for pages without the frame script such as error pages
		else {
			_needsReload = false;
			frame.setAttribute( 'src', frame.getAttribute( 'src' ) );
			markPreviewLoading();
		}
	};



	/**
	 * Toggle expanded/collapsed preview, matching the expand control button.
	 */
	var togglePreviewExpanded = function() {
		var layout = getLayout();
		var expanded;

		// Bail if layout is missing
		if ( ! layout ) { return; }

		// Toggle the expanded state
		expanded = ! layout.classList.contains( _settings.isExpandedClass );
		setPreviewExpanded( expanded );

		// Maybe remember the expanded state, except for the drawer of the compact layout
		if ( ! isCompactPreviewLayout() ) {
			saveState( 'expanded', expanded );
		}
	};



	/**
	 * Whether keyboard events should not trigger the preview expand shortcut.
	 *
	 * @param  {Element}  target  Event target.
	 * @return {boolean}
	 */
	var isPreviewExpandShortcutBlockedTarget = function( target ) {
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
	 * Toggle preview expanded/collapsed with Ctrl+Shift+P or Cmd+Shift+P.
	 *
	 * @param  {KeyboardEvent}  e  Key down event.
	 */
	var handlePreviewExpandKeydown = function( e ) {
		var layout;
		var isExpandShortcut;

		// Bail if FCUtils is not available
		if ( ! window.FCUtils ) { return; }

		isExpandShortcut = ( e.metaKey || e.ctrlKey ) && e.shiftKey && ! e.altKey && FCUtils.keyboardKeys.P === e.key.toLowerCase();

		// Bail if the expand shortcut was not pressed
		if ( ! isExpandShortcut ) { return; }

		// Bail if the user is typing in a field
		if ( isPreviewExpandShortcutBlockedTarget( e.target ) ) { return; }

		layout = getLayout();

		// Bail if the preview column is not visible for the current tab
		if ( ! layout || ! layout.classList.contains( _settings.hasPreviewClass ) ) { return; }

		e.preventDefault();
		togglePreviewExpanded();
	};



	/**
	 * Handle expand button clicks.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleExpandClick = function( e ) {
		togglePreviewExpanded();
		e.preventDefault();
	};



	/**
	 * Route preview-related click events.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleClick = function( e ) {
		var matchedElement;

		// EXPAND PREVIEW
		if ( matchedElement = e.target.closest( _settings.expandSelector ) ) {
			handleExpandClick( e );
		}
		// PREVIEW TOGGLE
		else if ( matchedElement = e.target.closest( _settings.toggleSelector ) ) {
			handleToggleClick( e );
		}
		// PAGE TAB
		else if ( matchedElement = e.target.closest( _settings.pageTabSelector ) ) {
			handlePageTabClick( e );
		}
		// WIDTH BUTTONS
		else if ( matchedElement = e.target.closest( _settings.widthDecreaseSelector ) || e.target.closest( _settings.widthIncreaseSelector ) ) {
			handleWidthClick( e, matchedElement );
		}
		// ZOOM HINT
		else if ( matchedElement = e.target.closest( _settings.zoomHintSelector ) ) {
			// Expand the preview, remembered in the browser like with the expand button
			setPreviewExpanded( true );
			saveState( 'expanded', true );
			e.preventDefault();
		}
		// HEADER AND FOOTER TOGGLE
		else if ( matchedElement = e.target.closest( _settings.headerFooterSelector ) ) {
			// Toggle the header and footer, remembered in the browser
			setHeaderFooterVisible( ! _isHeaderFooterVisible );
			saveState( 'headerFooter', _isHeaderFooterVisible );
			e.preventDefault();
		}
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
	 * Close the drawer when entering the compact layout, and restore the expanded state saved in the browser when leaving it.
	 */
	var handleCompactBreakpointChange = function() {
		// Close the drawer, or restore the saved expanded state outside the compact layout
		setPreviewExpanded( ! isCompactPreviewLayout() && true === getSavedState().expanded );
		syncPreviewDrawerPosition();
	};

	/**
	 * Handle viewport radio changes.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleViewportChange = function( e ) {
		var input = e.target;

		// Bail if the change is not for a viewport radio
		if ( ! input || ! input.matches( _settings.viewportInputSelector ) || ! input.checked ) { return; }

		// Maybe fill the available width on desktop, otherwise set the preset width
		if ( 'desktop' === input.value ) {
			setDesktopFillViewport();
		}
		else {
			setPreviewWidth( _settings.viewportWidths[ input.value ] );
		}
	};

	/**
	 * Handle width button clicks.
	 *
	 * @param  {Event}    e       Click event.
	 * @param  {Element}  button  Width button.
	 */
	var handleWidthClick = function( e, button ) {
		// Change the width by one step
		setPreviewWidth( _previewWidth + ( button.matches( _settings.widthIncreaseSelector ) ? _settings.widthStep : -_settings.widthStep ) );
		e.preventDefault();
	};

	/**
	 * Handle width field changes.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleWidthChange = function( e ) {
		// Bail if the change is not for the width field
		if ( ! e.target.matches( _settings.widthInputSelector ) ) { return; }

		// Set the typed width
		setPreviewWidth( e.target.value );
	};

	/**
	 * Handle key down events in the width field.
	 *
	 * @param  {Event}  e  Key down event.
	 */
	var handleWidthKeyDown = function( e ) {
		// Bail if not the enter key in the width field
		if ( FCUtils.keyboardKeys.ENTER !== e.key || ! e.target.matches( _settings.widthInputSelector ) ) { return; }

		// Apply the width instead of submitting the settings form
		setPreviewWidth( e.target.value );
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

		// Bail if click was not on a page tab
		if ( ! tab ) { return; }

		page = tab.getAttribute( _settings.pageAttribute );

		// Bail if page slug is missing
		if ( ! page ) { return; }

		// Load the page of the tab
		setPreviewPage( page );
		e.preventDefault();
	};

	/**
	 * Handle preview mode segmented button changes.
	 *
	 * @param  {Event}  e  Change event.
	 */
	var handleModeChange = function( e ) {
		// Bail if the change is not for the preview mode control
		if ( ! e.target.matches( _settings.modeSelector ) ) { return; }

		// Load the active page in the new mode, remembered in the browser
		setPreviewMode( e.target.value );
		saveState( 'mode', _previewMode );
		loadActivePreviewPage();
	};

	/**
	 * Handle settings field changes.
	 *
	 * @param  {Event}  e  jQuery change event.
	 */
	var handleSettingsChange = function( e ) {
		// Bail if the change is not for a settings field, such as for the preview controls
		if ( ! e.target.closest( _settings.formSelector ) || e.target.closest( _settings.previewSelector ) ) { return; }

		// Bail if the current user has no preview token
		if ( ! _settings.previewToken ) { return; }

		// Maybe reload the page preview, unless the field only changes styles
		if ( -1 === _settings.styleSettings.indexOf( e.target.name ) ) {
			_needsReload = true;
		}

		// Save the unsaved settings once the changes stop, showing the page preview as busy until it shows them
		_hasUnsavedDraft = true;
		_saveDraftDebounced();
		updatePreviewBusyState();
	};

	/**
	 * Handle iframe load events, including pages without the frame script such as placeholders and error pages.
	 *
	 * @param  {Event}  e  Load event.
	 */
	var handleFrameLoad = function( e ) {
		// Bail if the load event is not for the iframe
		if ( ! e.target.matches( _settings.frameSelector ) ) { return; }

		// Mark the page preview as loaded
		_isPreviewLoading = false;
		_isRefreshingStyles = false;

		// Size the preview again, so a placeholder that just loaded gets the scale
		applyPreviewSize();

		// Refresh the page preview with the settings saved while it loaded
		maybeRefreshPreview();
		updatePreviewBusyState();
	};

	/**
	 * Handle messages from the page preview and route to the appropriate handler.
	 *
	 * @param  {MessageEvent}  e  Message event.
	 */
	var handleMessage = function( e ) {
		var frame = document.querySelector( _settings.frameSelector );

		// Bail if the message does not come from the page preview in the iframe
		if ( ! frame || frame.contentWindow !== e.source || getPreviewOrigin() !== e.origin || ! e.data || _settings.messageSource !== e.data.source ) { return; }

		// READY
		if ( 'ready' === e.data.type ) {
			_isPreviewReady = true;
			sendPreviewState();
		}
		// STYLES REFRESHED
		else if ( 'stylesRefreshed' === e.data.type ) {
			_isRefreshingStyles = false;
			maybeRefreshPreview();
			updatePreviewBusyState();
		}
		// COMPUTED CSS VALUE
		else if ( 'computedCssValue' === e.data.type ) {
			resolveComputedCssValueRequest( e.data.requestId, e.data.value );
		}
		// FIELD VALUES
		else if ( 'fieldValues' === e.data.type ) {
			// Keep the values entered in the current mode in memory only, so reloading the settings page or saving the settings shows the dummy values again
			_fieldValues[ _previewMode ] = e.data.values;
		}
		// ADMIN SHORTCUT
		else if ( 'adminShortcut' === e.data.type ) {
			handleAdminShortcutMessage( e.data.shortcut );
		}
	};

	/**
	 * Handle admin shortcuts forwarded from the page preview iframe.
	 *
	 * @param  {string}  shortcut  Shortcut id from the frame script.
	 */
	var handleAdminShortcutMessage = function( shortcut ) {
		var layout;

		// EXPAND PREVIEW
		if ( 'expandPreview' === shortcut ) {
			layout = getLayout();

			// Bail if the preview column is not visible for the current tab
			if ( ! layout || ! layout.classList.contains( _settings.hasPreviewClass ) ) { return; }

			togglePreviewExpanded();
		}
		// SAVE SETTINGS
		else if ( 'saveSettings' === shortcut ) {
			// Let the settings page run the same save shortcut flow as a keydown on the admin document
			document.dispatchEvent( new CustomEvent( 'fc-settings-admin-shortcut', { detail: { shortcut: 'save' } } ) );
		}
		// MODIFIER HOLD START / END
		else if ( 'modifierHoldStart' === shortcut || 'modifierHoldEnd' === shortcut ) {
			document.dispatchEvent( new CustomEvent( 'fc-settings-admin-shortcut', { detail: { shortcut: shortcut } } ) );
		}
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
	 * Expand or collapse the preview column from other admin scripts.
	 *
	 * @param  {boolean}  expanded  Whether the preview should be expanded/open.
	 */
	_publicMethods.setExpanded = function( expanded ) {
		setPreviewExpanded( expanded );
	};

	/**
	 * Set CSS variables in the page preview, replacing the ones set before, as a map of selectors to CSS variable names and values like the `fc_css_variables` filter.
	 *
	 * @param  {Object}  variables  CSS variables by selector.
	 */
	_publicMethods.setCssVariables = function( variables ) {
		// Remember the CSS variables, sent again after each reload
		_cssVariables = variables;

		// Send the CSS variables to the page preview
		postMessageToPreview( { type: 'setCssVariables', variables: _cssVariables } );
	};

	/**
	 * Force the color scheme of the page preview, such as to show the token set being edited.
	 *
	 * @param  {string}  scheme  `light` or `dark`, or an empty string to keep the color scheme of the page.
	 */
	_publicMethods.setColorScheme = function( scheme ) {
		// Remember the color scheme, sent again after each reload
		_colorScheme = scheme;

		// Send the color scheme to the page preview
		postMessageToPreview( { type: 'setColorScheme', scheme: _colorScheme } );
	};

	/**
	 * Add or remove body classes of the page preview, keeping the classes set before.
	 *
	 * @param  {Object}  classes  Whether each class is added, by class name.
	 */
	_publicMethods.setBodyClasses = function( classes ) {
		// Remember the classes, sent again after each reload
		_bodyClasses = FCUtils.extendObject( _bodyClasses, classes );

		// Send the classes to the page preview
		postMessageToPreview( { type: 'setBodyClasses', classes: classes } );
	};

	/**
	 * Get the computed value of a CSS property value in the page preview, such as a font size in pixels.
	 * The page preview computes it, as the admin page cannot read the page preview when it is on another domain.
	 *
	 * @param   {string}   property  CSS property name.
	 * @param   {string}   value     CSS property value.
	 * @return  {Promise}            Promise resolving to the computed value, or an empty string when the value is not valid for the property or the iframe shows a placeholder.
	 */
	_publicMethods.getComputedCssValue = function( property, value ) {
		return new Promise( function( resolve ) {
			// Maybe ask the page preview, which sends the computed value back
			if ( getPreviewOrigin() ) {
				_computedCssValueRequestId++;
				_computedCssValueRequests[ _computedCssValueRequestId ] = { property: property, value: value, resolve: resolve };
				postMessageToPreview( { type: 'getComputedCssValue', requestId: _computedCssValueRequestId, property: property, value: value } );
			}
			// Otherwise resolve without a value, as the iframe shows a placeholder
			else {
				resolve( '' );
			}
		} );
	};



	/**
	 * Initialize the settings preview column.
	 *
	 * @param  {Object}  options  Localized settings from PHP.
	 */
	_publicMethods.init = function( options ) {
		var preview;
		var panel;
		var breakpoint;
		var navCompactBreakpoint;
		var savedState;

		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		// Merge settings
		_settings = FCUtils.extendObject( _settings, options );

		preview = getPreview();

		// Bail if preview column is not available
		if ( ! preview ) { return; }

		// Track the compact preview breakpoint (right column from 1280px; compact below that)
		breakpoint = parseInt( _settings.compactBreakpoint, 10 ) || 1280;
		_compactMediaQuery = window.matchMedia( '(max-width: ' + ( breakpoint - 1 ) + 'px)' );
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
		document.addEventListener( 'click', handleClick, true );
		document.addEventListener( 'change', handleViewportChange, true );
		document.addEventListener( 'change', handleWidthChange, true );
		document.addEventListener( 'keydown', handleWidthKeyDown, true );
		document.addEventListener( 'keydown', handlePreviewExpandKeydown, true );
		document.addEventListener( 'change', handleModeChange, true );
		window.addEventListener( 'message', handleMessage );
		document.addEventListener( 'load', handleFrameLoad, true ); // Load events do not bubble, but reach the document in the capture phase
		window.addEventListener( 'fcSettingsTabActivated', handleSettingsTabActivated );
		// Capture scroll from nested containers; keep the drawer aligned while sticky headers move
		window.addEventListener( 'scroll', syncPreviewDrawerPosition, true );
		window.addEventListener( 'resize', syncPreviewDrawerPosition );

		// Save the unsaved settings once changes stop, such as while dragging the color picker
		_saveDraftDebounced = FCUtils.debounce( saveDraft, _settings.draftSaveDelay );

		// Add jQuery event listeners, as the color picker triggers settings field changes with jQuery
		if ( _hasJQuery ) {
			$( document.body ).on( 'change', handleSettingsChange );
		}

		// Get preview panel
		panel = document.querySelector( _settings.panelSelector );

		// Maybe fit the page preview in the frame when the panel size changes, such as when expanding the preview
		if ( panel && window.ResizeObserver ) {
			new ResizeObserver( applyPreviewSize ).observe( panel );
		}

		// Restore the preview mode and header and footer visibility saved in the browser
		savedState = getSavedState();
		var initialTabMode = getTabPreviewMode( _settings.initialTab );
		var initialMode = initialTabMode || savedState.mode || _settings.guestMode;

		setPreviewMode( initialMode, !! initialTabMode && initialTabMode !== savedState.mode );
		setHeaderFooterVisible( true === savedState.headerFooter );

		// Sync from the initial settings tab, with the desktop page preview filling the available width
		setDesktopFillViewport();
		syncFromSettingsTab( _settings.initialTab );

		// Start with the compact drawer closed
		if ( isCompactPreviewLayout() ) {
			setPreviewExpanded( false );
		}
		// Otherwise, maybe sync the expand button with the expanded preview restored before the page rendered
		else if ( getLayout().classList.contains( _settings.isExpandedClass ) ) {
			syncPreviewExpandedControls( true );
		}

		syncPreviewDrawerPosition();

		_hasInitialized = true;
	};




	//
	// Public APIs
	//
	return _publicMethods;
} );
