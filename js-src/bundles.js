/**
 * Register resource for async load with require-bundle
 */
(function(){

    'use strict';

    // Bail if require bundle or server variables not loaded
    if ( ! RequireBundle || ! wfcSettings ) return;
    
    var settings = wfcSettings,
        ver = wfcSettings.assetsVersion;

    RequireBundle.register( 'wfc-checkout-gift-options', [ settings.jsPath + 'checkout-gift-options' + ver + '.js' ], '.has-wfc-gift-options #wfc-gift-options__field-wrapper', function(){ CheckoutGiftOptions.init(); } );
    RequireBundle.register( 'wfc-checkout-gift-options-styles', [ settings.cssPath + 'checkout-gift-options' + ver + '.css' ], '.has-wfc-gift-options #wfc-gift-options__field-wrapper, .woocommerce-table--order-details' );
    RequireBundle.register( 'wfc-checkout-validation', [ settings.jsPath + 'checkout-validation' + ver + '.js', settings.cssPath + 'checkout-validation' + ver + '.css' ], '.has-wfc-checkout-validation form.checkout', function(){ CheckoutValidation.init( wfcSettings.checkoutValidation ); } );
    RequireBundle.register( 'wfc-mailcheck', [ settings.jsPath + 'lib/mailcheck' + ver + '.js', settings.jsPath + 'mailcheck-init' + ver + '.js' ], '[data-mailcheck]', function(){ MailcheckInit.init( wfcSettings.checkoutValidation.mailcheckSuggestions ); } );
    
})();
