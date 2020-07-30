/**
 * Register resource for async load with require-bundle
 */
(function(){

    'use strict';

    
    // Bail if require bundle or server variables not loaded
    if ( ! RequireBundle || ! wfcSettings ) return;
    
    var settings = wfcSettings,
        ver = wfcSettings.assetsVersion;

    // Libraries
    RequireBundle.register( 'fluid-slider', [ settings.jsPath + 'lib/fluid-slider' + ver + '.js', settings.cssPath + 'fluid-slider' + ver + '.css' ], '.slider-wrapper', function(){ FluidSlider.init(); } );

    RequireBundle.register( 'wfc-ziptastic', [ settings.jsPath + 'ziptastic' + ver + '.js' ], '[data-ziptastic]', function(){ Ziptastic.init(); } );
    RequireBundle.register( 'wfc-checkout-validation', [ settings.jsPath + 'checkout-validation' + ver + '.js', settings.cssPath + 'checkout-validation' + ver + '.css' ], '.has-wfc-checkout-validation form.checkout', function(){ CheckoutValidation.init(); } );
    RequireBundle.register( 'wfc-checkout-layout', [ settings.jsPath + 'checkout-layout' + ver + '.js', settings.cssPath + 'checkout-layout' + ver + '.css', settings.cssPath + 'checkout-layout--default' + ver + '.css' ], '.has-wfc-checkout-layout form.checkout', function(){ CheckoutSteps.init(); } );
    
})();
