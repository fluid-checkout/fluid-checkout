/**
 * Client-side tab navigation for the Fluid Checkout settings page.
 * Switches panels without a full page reload and keeps the URL in sync.
 */

( function() {
	'use strict';

	var _hasInitialized = false;
	var _settings = {
		formSelector:         '[data-fc-settings-form]',
		navSelector:          '[data-fc-settings-nav]',
		navItemSelector:      '[data-fc-settings-nav-item]',
		navLinkSelector:      '[data-fc-settings-nav-link]',
		tabSelector:          '[data-fc-settings-tab]',
		submitSelector:       '[data-fc-settings-submit]',
		saveButtonSelector:   '[data-fc-settings-save]',
		activeClass:          'is-active',
		tabAttribute:         'data-fc-settings-tab',
		showSaveAttribute:    'data-fc-settings-show-save',
		navItemAttribute:     'data-fc-settings-nav-item',
		navLinkAttribute:     'data-fc-settings-nav-link',
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

		// Recalculate enhanced select widths after a previously hidden tab becomes visible
		if ( typeof jQuery !== 'undefined' ) {
			jQuery( tabPanel ).find( '.select2-hidden-accessible' ).each( function() {
				var $field = jQuery( this );
				var $container = $field.next( '.select2-container' );
				if ( $container.length ) {
					$container.css( 'width', '100%' );
				}
			} );
		}

		// Sync the browser URL
		if ( updateUrl ) {
			if ( replaceState ) {
				window.history.replaceState( { fcSettingsTab: tab }, '', getUrlForTab( tab ) );
			} else {
				window.history.pushState( { fcSettingsTab: tab }, '', getUrlForTab( tab ) );

				// Scroll back to the top when switching tabs via the sidebar
				var content = document.querySelector( '.fc-settings-content' );
				if ( content ) {
					content.scrollTop = 0;
				}
				window.scrollTo( 0, 0 );
			}
		}

		return true;
	};

	/**
	 * Handle clicks on settings sidebar navigation links.
	 *
	 * @param  {Event}  e  Click event.
	 */
	var handleNavClick = function( e ) {
		var link = e.target.closest( _settings.navLinkSelector );
		var tab;

		// Bail if click was not on a nav link
		if ( ! link ) { return; }

		// Bail if modifier keys were used (allow open in new tab)
		if ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || 1 === e.button ) { return; }

		tab = link.getAttribute( _settings.navLinkAttribute );

		// Bail if tab slug is missing
		if ( ! tab ) { return; }

		// Bail if already on this tab
		var activePanel = getActiveTabPanel();
		if ( activePanel && tab === activePanel.getAttribute( _settings.tabAttribute ) ) {
			e.preventDefault();
			return;
		}

		// Bail if tab cannot be activated (fall through to full navigation)
		if ( ! activateTab( tab, true, false ) ) { return; }

		e.preventDefault();
	};

	/**
	 * Handle browser back / forward navigation.
	 */
	var handlePopState = function() {
		var tab = getTabFromUrl( window.location.href );

		// Bail if tab slug is missing
		if ( ! tab ) { return; }

		activateTab( tab, false, false );
	};



	/**
	 * Initialize settings tab navigation.
	 */
	var init = function() {
		var form;

		// Bail if already initialized
		if ( _hasInitialized ) { return; }

		form = document.querySelector( _settings.formSelector );

		// Bail if settings form is not available
		if ( ! form ) { return; }

		document.addEventListener( 'click', handleNavClick, true );
		window.addEventListener( 'popstate', handlePopState );

		// Ensure the current URL is represented in history state for back / forward
		var initialTab = getTabFromUrl( window.location.href );
		var activePanel = getActiveTabPanel();

		if ( ! activateTab( initialTab, true, true ) && activePanel ) {
			activateTab( activePanel.getAttribute( _settings.tabAttribute ), true, true );
		}

		_hasInitialized = true;
	};

	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}

} )();
