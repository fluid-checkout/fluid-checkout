/**
 * Client-side tab navigation for the Fluid Checkout settings page.
 * Switches panels without a full page reload and keeps the URL in sync.
 * On narrow viewports, toggles the expandable settings menu.
 */

( function() {
	'use strict';

	var _hasInitialized = false;
	var _navCompactMediaQuery = null;
	var _settings = {
		formSelector:           '[data-fc-settings-form]',
		sidebarSelector:        '[data-fc-settings-sidebar]',
		sidebarHeaderSelector:  '.fc-settings-sidebar-header',
		navSelector:            '[data-fc-settings-nav]',
		navItemSelector:        '[data-fc-settings-nav-item]',
		navLinkSelector:        '[data-fc-settings-nav-link]',
		navToggleSelector:      '[data-fc-settings-nav-toggle]',
		tabSelector:            '[data-fc-settings-tab]',
		submitSelector:         '[data-fc-settings-submit]',
		saveButtonSelector:     '[data-fc-settings-save]',
		activeClass:            'is-active',
		isNavExpandedClass:     'is-nav-expanded',
		tabAttribute:           'data-fc-settings-tab',
		showSaveAttribute:      'data-fc-settings-show-save',
		navItemAttribute:       'data-fc-settings-nav-item',
		navLinkAttribute:       'data-fc-settings-nav-link',
		labelOpenAttribute:     'data-label-open',
		labelCloseAttribute:    'data-label-close',
		navCompactBreakpoint:   980,
	};



	/**
	 * Get the settings tab slug from a URL string.
	 *
	 * @param   {string}  url  Absolute or relative URL.
	 * @return  {string}       Tab slug, or empty string when missing.
	 */
	var getTabFromUrl = function( url ) {
		try {
			var parsed = new URL( url, window.location.origin );
			return parsed.searchParams.get( 'tab' ) || '';
		} catch ( err ) {
			return '';
		}
	};

	/**
	 * Resolve a settings tab slug from a clicked link.
	 *
	 * Prefers `data-fc-settings-nav-link`. Falls back to same-origin
	 * `admin.php?page=fluid-checkout&tab=…` links inside the settings form.
	 *
	 * @param   {Element}  link  Anchor element.
	 * @return  {string}         Tab slug, or empty string when not a settings tab link.
	 */
	var getTabFromSettingsLink = function( link ) {
		var tab;
		var href;
		var parsed;

		// Bail if link is missing
		if ( ! link ) { return ''; }

		tab = link.getAttribute( _settings.navLinkAttribute );

		// Prefer the explicit nav-link attribute
		if ( tab ) {
			return tab;
		}

		// Bail if the link is outside the settings form
		if ( ! link.closest( _settings.formSelector ) ) { return ''; }

		// Bail if the link opens in a new browsing context
		if ( link.target && '_self' !== String( link.target ).toLowerCase() ) { return ''; }

		href = link.getAttribute( 'href' );

		// Bail if href is missing or is a non-navigation value
		if ( ! href || 0 === href.indexOf( '#' ) || 0 === href.indexOf( 'javascript:' ) ) { return ''; }

		try {
			parsed = new URL( href, window.location.origin );
		} catch ( err ) {
			return '';
		}

		// Bail if the link leaves this admin origin
		if ( parsed.origin !== window.location.origin ) { return ''; }

		// Bail if the link is not a Fluid Checkout settings URL
		if ( 'fluid-checkout' !== parsed.searchParams.get( 'page' ) ) { return ''; }

		return parsed.searchParams.get( 'tab' ) || '';
	};

	/**
	 * Build the settings page URL for a tab slug.
	 *
	 * @param   {string}  tab  Tab slug.
	 * @return  {string}       Absolute URL for the tab.
	 */
	var getUrlForTab = function( tab ) {
		var url = new URL( window.location.href );
		url.searchParams.set( 'tab', tab );
		url.searchParams.delete( 'settings-updated' );
		return url.toString();
	};

	/**
	 * Get the currently active tab panel element.
	 *
	 * @return  {Element|null}
	 */
	var getActiveTabPanel = function() {
		return document.querySelector( _settings.tabSelector + '.' + _settings.activeClass );
	};

	/**
	 * Whether the layout is in the compact settings-nav breakpoint.
	 *
	 * @return  {boolean}
	 */
	var isNavCompactLayout = function() {
		return !!( _navCompactMediaQuery && _navCompactMediaQuery.matches );
	};

	/**
	 * Get the settings sidebar element.
	 *
	 * @return  {Element|null}
	 */
	var getSidebar = function() {
		return document.querySelector( _settings.sidebarSelector );
	};

	/**
	 * Sync the nav toggle button attributes with the expanded state.
	 *
	 * @param  {boolean}  expanded  Whether the nav menu is open.
	 */
	var syncNavToggleControls = function( expanded ) {
		var toggleButton = document.querySelector( _settings.navToggleSelector );
		var label;

		// Bail if toggle is missing
		if ( ! toggleButton ) { return; }

		label = expanded
			? toggleButton.getAttribute( _settings.labelCloseAttribute )
			: toggleButton.getAttribute( _settings.labelOpenAttribute );

		toggleButton.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );

		// Maybe update the accessible label
		if ( label ) {
			toggleButton.setAttribute( 'aria-label', label );
		}
	};

	/**
	 * Anchor the compact nav drawer below the settings title / menu bar.
	 * Uses the live header position so the drawer stays correct when the admin bar
	 * and page header have scrolled out of view.
	 */
	var syncNavDrawerPosition = function() {
		var nav = document.querySelector( _settings.navSelector );
		var header;

		// Bail if nav is missing
		if ( ! nav ) { return; }

		// Clear inline offsets outside the compact nav layout
		if ( ! isNavCompactLayout() ) {
			nav.style.top = '';
			return;
		}

		header = document.querySelector( _settings.sidebarHeaderSelector );

		// Bail if header is missing
		if ( ! header ) { return; }

		nav.style.top = Math.round( header.getBoundingClientRect().bottom ) + 'px';
	};

	/**
	 * Expand or collapse the compact settings navigation menu.
	 *
	 * @param  {boolean}  expanded  Whether the menu should be open.
	 */
	var setNavExpanded = function( expanded ) {
		var sidebar = getSidebar();

		// Bail if sidebar is missing
		if ( ! sidebar ) { return; }

		// Only keep the expanded class while the compact menu layout is active
		if ( ! isNavCompactLayout() ) {
			expanded = false;
		}

		// Keep the drawer pinned to the title bar before toggling visibility
		syncNavDrawerPosition();

		sidebar.classList.toggle( _settings.isNavExpandedClass, expanded );
		syncNavToggleControls( expanded );
	};

	/**
	 * Collapse the settings preview drawer when available.
	 */
	var collapsePreviewDrawer = function() {
		// Bail if the preview API is not available
		if ( typeof FCAdminSettingsPreview === 'undefined' || typeof FCAdminSettingsPreview.setExpanded !== 'function' ) { return; }

		FCAdminSettingsPreview.setExpanded( false );
	};

	/**
	 * Update save button visibility and disabled state for a tab panel.
	 *
	 * @param  {Element}  tabPanel  Active tab panel element.
	 */
	var updateSaveControls = function( tabPanel ) {
		var canSave = tabPanel && 'yes' === tabPanel.getAttribute( _settings.showSaveAttribute );
		var submit = document.querySelector( _settings.submitSelector );
		var saveButtons = document.querySelectorAll( _settings.saveButtonSelector );
		var i;

		if ( submit ) {
			if ( canSave ) {
				submit.removeAttribute( 'hidden' );
			} else {
				submit.setAttribute( 'hidden', '' );
			}
		}

		for ( i = 0; i < saveButtons.length; i++ ) {
			saveButtons[ i ].disabled = ! canSave;
		}
	};

	/**
	 * Activate a settings tab panel and sync the sidebar navigation.
	 *
	 * @param   {string}   tab          Tab slug.
	 * @param   {boolean}  updateUrl    Whether to update the browser history.
	 * @param   {boolean}  replaceState Whether to replace the current history entry.
	 * @return  {boolean}               True when the tab was activated.
	 */
	var activateTab = function( tab, updateUrl, replaceState ) {
		var tabPanel = document.querySelector( '[' + _settings.tabAttribute + '="' + tab + '"]' );
		var navItems = document.querySelectorAll( _settings.navItemSelector );
		var form = document.querySelector( _settings.formSelector );
		var i;
		var navItem;
		var navLink;
		var isActive;

		// Bail if tab panel is not available
		if ( ! tabPanel ) { return false; }

		// Update tab panels
		document.querySelectorAll( _settings.tabSelector ).forEach( function( panel ) {
			isActive = panel === tabPanel;
			panel.classList.toggle( _settings.activeClass, isActive );
		} );

		// Update sidebar navigation
		for ( i = 0; i < navItems.length; i++ ) {
			navItem = navItems[ i ];
			isActive = tab === navItem.getAttribute( _settings.navItemAttribute );
			navItem.classList.toggle( _settings.activeClass, isActive );

			navLink = navItem.querySelector( _settings.navLinkSelector );
			if ( navLink ) {
				if ( isActive ) {
					navLink.setAttribute( 'aria-current', 'page' );
				} else {
					navLink.removeAttribute( 'aria-current' );
				}
			}
		}

		// Keep the form action pointed at the active tab for correct save redirects
		if ( form ) {
			form.setAttribute( 'action', getUrlForTab( tab ) );
		}

		updateSaveControls( tabPanel );

		// Sync the browser URL
		if ( updateUrl ) {
			if ( replaceState ) {
				window.history.replaceState( { fcSettingsTab: tab }, '', getUrlForTab( tab ) );
			} else {
				window.history.pushState( { fcSettingsTab: tab }, '', getUrlForTab( tab ) );

				// Scroll back to the top when switching tabs via the sidebar
				window.scrollTo( 0, 0 );
			}
		}

		// Notify listeners that a settings tab became active (SPA navigation)
		window.dispatchEvent( new CustomEvent( 'fcSettingsTabActivated', { detail: { tab: tab } } ) );

		return true;
	};

	/**
	 * Handle clicks on settings tab links (sidebar and in-page links).
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleNavClick = function( e ) {
		var link = e.target.closest( 'a' );
		var tab;
		var activePanel;

		// Bail if click was not on a link
		if ( ! link ) { return; }

		// Bail if modifier keys were used (allow open in new tab)
		if ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || 1 === e.button ) { return; }

		tab = getTabFromSettingsLink( link );

		// Bail if the link does not target a settings tab
		if ( ! tab ) { return; }

		activePanel = getActiveTabPanel();

		// Already on this tab: close the compact menu and stay put
		if ( activePanel && tab === activePanel.getAttribute( _settings.tabAttribute ) ) {
			collapsePreviewDrawer();
			setNavExpanded( false );
			e.preventDefault();
			return;
		}

		// Bail if tab cannot be activated (fall through to full navigation)
		if ( ! activateTab( tab, true, false ) ) { return; }

		// Close the preview and compact menu after choosing a tab
		collapsePreviewDrawer();
		setNavExpanded( false );
		e.preventDefault();
	};

	/**
	 * Handle clicks on the compact settings-menu toggle.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleNavToggleClick = function( e ) {
		var toggleButton = e.target.closest( _settings.navToggleSelector );
		var sidebar;

		// Bail if click was not on the nav toggle
		if ( ! toggleButton ) { return; }

		sidebar = getSidebar();

		// Bail if sidebar is missing
		if ( ! sidebar ) { return; }

		setNavExpanded( ! sidebar.classList.contains( _settings.isNavExpandedClass ) );
		e.preventDefault();
	};

	/**
	 * Collapse the compact nav when leaving the breakpoint.
	 */
	var handleNavCompactBreakpointChange = function() {
		setNavExpanded( false );
		syncNavDrawerPosition();
	};

	/**
	 * Handle browser back / forward navigation.
	 */
	var handlePopState = function() {
		var tab = getTabFromUrl( window.location.href );

		// Bail if tab slug is missing
		if ( ! tab ) { return; }

		activateTab( tab, false, false );
		setNavExpanded( false );
	};



	/**
	 * Initialize settings tab navigation.
	 */
	var init = function() {
		var form;
		var breakpoint;
		var initialTab;
		var activePanel;

		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		form = document.querySelector( _settings.formSelector );

		// Bail if settings form is not available
		if ( ! form ) { return; }

		document.addEventListener( 'click', handleNavClick, true );
		document.addEventListener( 'click', handleNavToggleClick, true );
		window.addEventListener( 'popstate', handlePopState );
		// Capture scroll from nested containers; keep the drawer aligned while sticky headers move
		window.addEventListener( 'scroll', syncNavDrawerPosition, true );
		window.addEventListener( 'resize', syncNavDrawerPosition );

		// Track the compact settings-nav breakpoint
		breakpoint = parseInt( _settings.navCompactBreakpoint, 10 ) || 980;
		_navCompactMediaQuery = window.matchMedia( '(max-width: ' + breakpoint + 'px)' );
		if ( typeof _navCompactMediaQuery.addEventListener === 'function' ) {
			_navCompactMediaQuery.addEventListener( 'change', handleNavCompactBreakpointChange );
		}
		// Older browsers
		else if ( typeof _navCompactMediaQuery.addListener === 'function' ) {
			_navCompactMediaQuery.addListener( handleNavCompactBreakpointChange );
		}

		// Ensure the current URL is represented in history state for back / forward
		initialTab = getTabFromUrl( window.location.href );
		activePanel = getActiveTabPanel();

		if ( ! activateTab( initialTab, true, true ) && activePanel ) {
			activateTab( activePanel.getAttribute( _settings.tabAttribute ), true, true );
		}

		// Start with the compact menu closed
		setNavExpanded( false );
		syncNavDrawerPosition();

		_hasInitialized = true;
	};

	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}

} )();
