/**
 * Register resource for async load with require-bundle
 */
(function(){

    'use strict';

    
    // Bail if require bundle or server variables not loaded
    if ( ! RequireBundle || ! wfcSettings ) return;
    
    var settings = wfcSettings,
        ver = wfcSettings.assetsVersion;

    RequireBundle.register( 'wfc-ziptastic', [ settings.jsPath + 'ziptastic' + ver + '.js' ], '[data-ziptastic]', function(){ Ziptastic.init(); } );
    RequireBundle.register( 'wfc-checkout-validation', [ settings.jsPath + 'checkout-validation' + ver + '.js', settings.cssPath + 'checkout-validation' + ver + '.css' ], '.has-wfc-checkout-validation form.checkout', function(){ CheckoutValidation.init(); } );
    RequireBundle.register( 'wfc-checkout-steps', [ settings.jsPath + 'checkout-steps' + ver + '.js', settings.cssPath + 'checkout-steps' + ver + '.css', settings.cssPath + 'checkout-steps--default' + ver + '.css' ], '.has-wfc-checkout-steps form.checkout', function(){ CheckoutSteps.init(); } );
    
    RequireBundle.register( 'wfc-mailcheck', [ settings.jsPath + 'lib/mailcheck' + ver + '.js', settings.jsPath + 'mailcheck-init' + ver + '.js' ], '[data-mailcheck]', function(){ MailcheckInit.init(); } );
    
})();
