/**
 * Auto load polyfills for features missing in the browser
 */
(function () {
    
    var _hasInitialized = false;

    /**
     * Declare polyfills with function that returns
     * `true` if the feature is supported by the current browser and
     * `false` if the feature is not supported and needs to be polyfilled
     */
    var _polyfills = {
        'polyfill-bind': function(){ return !! Function.bind; },
        'polyfill-Array': function(){ return ! ( ! Array.prototype.includes || ! Array.prototype.indexOf ); },
        'polyfill-classList': function(){ return 'classList' in document.createElement('_'); },
        'polyfill-matches': function(){ return !! Element.prototype.matches; }, // Needed for `closest` polyfill
        'polyfill-closest': function(){ return !! Element.prototype.closest; },
        'polyfill-CustomEvents': function(){ return typeof window.CustomEvent === "function" },
        'polyfill-matchMedia': function(){ return typeof window.matchMedia === "function" },
        'polyfill-ObjectEntries': function(){ return !! Object.entries; },
        'polyfill-Promise': function(){ return 'Promise' in window; },
        'polyfill-requestAnimationFrame': function(){ return !! window.requestAnimationFrame; },
    };



    /**
     * Current Script Path
     *
     * Get the dir path to the currently executing script file
     * which is always the last one in the scripts array with
     * an [src] attr
     */
    var currentScriptPath = function () {
        var scripts = document.querySelectorAll( 'script[src]' );
        var currentScript = scripts[ scripts.length - 1 ].src;
        var currentScriptChunks = currentScript.split( '/' );
        var currentScriptFile = currentScriptChunks[ currentScriptChunks.length - 1 ];

        return currentScript.replace( currentScriptFile, '' );
    }



    /**
	 * Get list of declared polyfill
	 *
	 * @return  {Array}  List of declared polyfill ids
	 */
	var getIds = function() {
		return Object.keys( _polyfills );
	};



    /**
     * Auto load polyfills for missing features in the browser
     */
    var autoLoad = function() {
        // Bail if already executed or require-bundle not loaded
        if ( _hasInitialized || ! window.RequireBundle ) { return; }

        // Register Bundles
        getIds().forEach( function( polyfillId ) {
            RequireBundle.register( polyfillId, [ currentScriptPath() + '' + polyfillId + '.min.js' ] );
        } );

        // Load bundles
        getIds().forEach( function( polyfillId ) {
            if ( ! _polyfills[ polyfillId ]() ) {
                RequireBundle.require( polyfillId );
                console.log( polyfillId );
            }
        } );
    }
  


    // Execute immediatelly
    autoLoad();

  })();
  