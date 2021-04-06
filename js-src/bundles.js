/**
 * Register resource for async load with require-bundle
 */
(function(){

	'use strict';

	// Bail if require bundle or server variables not loaded
	if ( ! RequireBundle || ! wfcSettings ) return;
	
	var settings = wfcSettings,
		ver = wfcSettings.assetsVersion;

	// Helper Libraries
	if ( ! RequireBundle.hasBundle( 'animate-helper' ) ) { RequireBundle.register( 'animate-helper', [ settings.jsLibPath + 'animate-helper' + ver + '.js' ] ); }
	if ( ! RequireBundle.hasBundle( 'flyout-block' ) ) { RequireBundle.register( 'flyout-block', [ settings.jsLibPath + 'flyout-block' + ver + '.js', settings.cssPath + 'flyout-block' + ver + '.css' ], '[data-flyout]', function(){ FlyoutBlock.init( settings.flyoutBlock ); } ); }

	RequireBundle.register( 'wfc-checkout-gift-options', [ settings.jsPath + 'checkout-gift-options' + ver + '.js' ], '.has-wfc-gift-options #wfc-gift-options__field-wrapper', function(){ CheckoutGiftOptions.init(); } );
	RequireBundle.register( 'wfc-checkout-gift-options-styles', [ settings.cssPath + 'checkout-gift-options' + ver + '.css' ], '.has-wfc-gift-options #wfc-gift-options__field-wrapper, .woocommerce-table--order-details' );
	RequireBundle.register( 'wfc-checkout-validation', [ settings.jsPath + 'checkout-validation' + ver + '.js', settings.cssPath + 'checkout-validation' + ver + '.css' ], '.has-wfc-checkout-validation form.checkout', function(){ CheckoutValidation.init( wfcSettings.checkoutValidation ); } );
	RequireBundle.register( 'wfc-mailcheck', [ settings.jsLibPath + 'mailcheck' + ver + '.js', settings.jsPath + 'mailcheck-init' + ver + '.js' ], '[data-mailcheck]', function(){ MailcheckInit.init( wfcSettings.checkoutValidation.mailcheckSuggestions ); } );
	
})();
