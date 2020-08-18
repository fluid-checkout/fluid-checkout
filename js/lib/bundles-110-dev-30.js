/**
 * Register resource for async load with require-bundle
 */
(function(){

    'use strict';

    
    // Bail if require bundle or server variables not loaded
    if ( ! RequireBundle || ! wfcSettings ) return;
    
    var settings = wfcSettings,
        ver = wfcSettings.assetsVersion;

    RequireBundle.register( 'wfc-checkout-validation', [ settings.jsPath + 'checkout-validation' + ver + '.js', settings.cssPath + 'checkout-validation' + ver + '.css' ], '.has-wfc-checkout-validation form.checkout', function(){ CheckoutValidation.init(); } );
    RequireBundle.register( 'wfc-checkout-gift-options', [ settings.jsPath + 'checkout-gift-options' + ver + '.js', settings.cssPath + 'checkout-gift-options' + ver + '.css' ], '.has-wfc-gift-options #wfc-gift-options__field-wrapper', function(){ CheckoutGiftOptions.init(); } );
    RequireBundle.register( 'wfc-address-book', [ settings.jsPath + 'address-book' + ver + '.js', settings.cssPath + 'address-book' + ver + '.css' ], '.has-wfc-address-book .wfc-address-book__form-wrapper', function(){ AddressBook.init(); } );
        
    RequireBundle.register( 'wfc-mailcheck', [ settings.jsPath + 'lib/mailcheck' + ver + '.js', settings.jsPath + 'mailcheck-init' + ver + '.js' ], '[data-mailcheck]', function(){ MailcheckInit.init(); } );
    
    RequireBundle.register( 'wfc-ziptastic', [ settings.jsPath + 'ziptastic' + ver + '.js' ], '[data-ziptastic]', function(){ Ziptastic.init(); } );
})();
