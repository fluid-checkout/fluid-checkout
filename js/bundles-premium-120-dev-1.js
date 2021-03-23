/**
 * Register resource for async load with require-bundle
 */
(function(){

    'use strict';

    // Bail if require bundle or server variables not loaded
    if ( ! RequireBundle || ! wfcSettings ) return;
    
    var settings = wfcSettings,
        ver = wfcSettings.assetsVersion;

    RequireBundle.register( 'wfc-address-book', [ settings.jsPath + 'address-book' + ver + '.js', settings.cssPath + 'address-book' + ver + '.css' ], '.has-wfc-address-book .wfc-address-book__form-wrapper', function(){ AddressBook.init(); } );
})();
