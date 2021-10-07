/**
 * Register resource for async load with require-bundle
 */
(function(){

	'use strict';

	// Bail if require bundle or server variables not loaded
	if ( ! RequireBundle || ! fcSettings ) return;

	var settings = fcSettings,
		ver = fcSettings.assetsVersion;

	// Helper Libraries
	if ( ! RequireBundle.hasBundle( 'animate-helper' ) ) { RequireBundle.register( 'animate-helper', [ settings.jsLibPath + 'animate-helper' + ver + '.js' ] ); }
	if ( ! RequireBundle.hasBundle( 'collapsible-block' ) ) { RequireBundle.register( 'collapsible-block', [ settings.jsLibPath + 'collapsible-block' + ver + '.js' ], '[data-collapsible]', function(){ CollapsibleBlock.init( settings.collapsibleBlock ); } ); }
	if ( ! RequireBundle.hasBundle( 'flyout-block' ) ) { RequireBundle.register( 'flyout-block', [ settings.jsLibPath + 'flyout-block' + ver + '.js', settings.cssPath + 'flyout-block' + ver + '.css' ], '[data-flyout]', function(){ FlyoutBlock.init( settings.flyoutBlock ); } ); }
	if ( ! RequireBundle.hasBundle( 'polyfill-inert' ) ) { RequireBundle.register( 'polyfill-inert', [ settings.jsLibPath + 'inert' + ver + '.js' ] ); }
	if ( ! RequireBundle.hasBundle( 'sticky-states' ) ) { RequireBundle.register( 'sticky-states', [ settings.jsLibPath + 'sticky-states' + ver + '.js', settings.cssPath + 'sticky-states' + ver + '.css' ], '[data-sticky-states]', function(){ StickyStates.init( settings.stickyStates ); } ); }

	RequireBundle.register( 'mailcheck', [ settings.jsLibPath + 'mailcheck' + ver + '.js', settings.jsPath + 'mailcheck-init' + ver + '.js' ], '[data-mailcheck]', function(){ MailcheckInit.init( fcSettings.checkoutValidation.mailcheckSuggestions ); } );

})();
