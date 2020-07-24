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

})();
