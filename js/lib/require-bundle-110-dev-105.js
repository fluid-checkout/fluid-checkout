loadjs = (function () {
/**
 * Global dependencies.
 * @global {Object} document - DOM
 */

var devnull = function() {},
    bundleIdCache = {},
    bundleResultCache = {},
    bundleCallbackQueue = {};


/**
 * Subscribe to bundle load event.
 * @param {string[]} bundleIds - Bundle ids
 * @param {Function} callbackFn - The callback function
 */
function subscribe(bundleIds, callbackFn) {
  // listify
  bundleIds = bundleIds.push ? bundleIds : [bundleIds];

  var depsNotFound = [],
      i = bundleIds.length,
      numWaiting = i,
      fn,
      bundleId,
      r,
      q;

  // define callback function
  fn = function (bundleId, pathsNotFound) {
    if (pathsNotFound.length) depsNotFound.push(bundleId);

    numWaiting--;
    if (!numWaiting) callbackFn(depsNotFound);
  };

  // register callback
  while (i--) {
    bundleId = bundleIds[i];

    // execute callback if in result cache
    r = bundleResultCache[bundleId];
    if (r) {
      fn(bundleId, r);
      continue;
    }

    // add to callback queue
    q = bundleCallbackQueue[bundleId] = bundleCallbackQueue[bundleId] || [];
    q.push(fn);
  }
}


/**
 * Publish bundle load event.
 * @param {string} bundleId - Bundle id
 * @param {string[]} pathsNotFound - List of files not found
 */
function publish(bundleId, pathsNotFound) {
  // exit if id isn't defined
  if (!bundleId) return;

  var q = bundleCallbackQueue[bundleId];

  // cache result
  bundleResultCache[bundleId] = pathsNotFound;

  // exit if queue is empty
  if (!q) return;

  // empty callback queue
  while (q.length) {
    q[0](bundleId, pathsNotFound);
    q.splice(0, 1);
  }
}


/**
 * Execute callbacks.
 * @param {Object or Function} args - The callback args
 * @param {string[]} depsNotFound - List of dependencies not found
 */
function executeCallbacks(args, depsNotFound) {
  // accept function as argument
  if (args.call) args = {success: args};

  // success and error callbacks
  if (depsNotFound.length) (args.error || devnull)(depsNotFound);
  else (args.success || devnull)(args);
}


/**
 * Load individual file.
 * @param {string} path - The file path
 * @param {Function} callbackFn - The callback function
 */
function loadFile(path, callbackFn, args, numTries) {
  var doc = document,
      async = args.async,
      maxTries = (args.numRetries || 0) + 1,
      beforeCallbackFn = args.before || devnull,
      pathname = path.replace(/[\?|#].*$/, ''),
      pathStripped = path.replace(/^(css|img)!/, ''),
      isLegacyIECss,
      e;

  numTries = numTries || 0;

  if (/(^css!|\.css$)/.test(pathname)) {
    // css
    e = doc.createElement('link');
    e.rel = 'stylesheet';
    e.href = pathStripped;

    // tag IE9+
    isLegacyIECss = 'hideFocus' in e;

    // use preload in IE Edge (to detect load errors)
    if (isLegacyIECss && e.relList) {
      isLegacyIECss = 0;
      e.rel = 'preload';
      e.as = 'style';
    }
  } else if (/(^img!|\.(png|gif|jpg|svg|webp)$)/.test(pathname)) {
    // image
    e = doc.createElement('img');
    e.src = pathStripped;    
  } else {
    // javascript
    e = doc.createElement('script');
    e.src = path;
    e.async = async === undefined ? true : async;
  }

  e.onload = e.onerror = e.onbeforeload = function (ev) {
    var result = ev.type[0];

    // treat empty stylesheets as failures to get around lack of onerror
    // support in IE9-11
    if (isLegacyIECss) {
      try {
        if (!e.sheet.cssText.length) result = 'e';
      } catch (x) {
        // sheets objects created from load errors don't allow access to
        // `cssText` (unless error is Code:18 SecurityError)
        if (x.code != 18) result = 'e';
      }
    }

    // handle retries in case of load failure
    if (result == 'e') {
      // increment counter
      numTries += 1;

      // exit function and try again
      if (numTries < maxTries) {
        return loadFile(path, callbackFn, args, numTries);
      }
    } else if (e.rel == 'preload' && e.as == 'style') {
      // activate preloaded stylesheets
      return e.rel = 'stylesheet'; // jshint ignore:line
    }
    
    // execute callback
    callbackFn(path, result, ev.defaultPrevented);
  };

  // add to document (unless callback returns `false`)
  if (beforeCallbackFn(path, e) !== false) doc.head.appendChild(e);
}


/**
 * Load multiple files.
 * @param {string[]} paths - The file paths
 * @param {Function} callbackFn - The callback function
 */
function loadFiles(paths, callbackFn, args) {
  // listify paths
  paths = paths.push ? paths : [paths];

  var numWaiting = paths.length,
      x = numWaiting,
      pathsNotFound = [],
      fn,
      i;

  // define callback function
  fn = function(path, result, defaultPrevented) {
    // handle error
    if (result == 'e') pathsNotFound.push(path);

    // handle beforeload event. If defaultPrevented then that means the load
    // will be blocked (ex. Ghostery/ABP on Safari)
    if (result == 'b') {
      if (defaultPrevented) pathsNotFound.push(path);
      else return;
    }

    numWaiting--;
    if (!numWaiting) callbackFn(pathsNotFound);
  };

  // load scripts
  for (i=0; i < x; i++) loadFile(paths[i], fn, args);
}


/**
 * Initiate script load and register bundle.
 * @param {(string|string[])} paths - The file paths
 * @param {(string|Function|Object)} [arg1] - The (1) bundleId or (2) success
 *   callback or (3) object literal with success/error arguments, numRetries,
 *   etc.
 * @param {(Function|Object)} [arg2] - The (1) success callback or (2) object
 *   literal with success/error arguments, numRetries, etc.
 */
function loadjs(paths, arg1, arg2) {
  var bundleId,
      args;

  // bundleId (if string)
  if (arg1 && arg1.trim) bundleId = arg1;

  // args (default is {})
  args = (bundleId ? arg2 : arg1) || {};

  // throw error if bundle is already defined
  if (bundleId) {
    if (bundleId in bundleIdCache) {
      throw "LoadJS";
    } else {
      bundleIdCache[bundleId] = true;
    }
  }

  function loadFn(resolve, reject) {
    loadFiles(paths, function (pathsNotFound) {
      // execute callbacks
      executeCallbacks(args, pathsNotFound);
      
      // resolve Promise
      if (resolve) {
        executeCallbacks({success: resolve, error: reject}, pathsNotFound);
      }

      // publish bundle load event
      publish(bundleId, pathsNotFound);
    }, args);
  }
  
  if (args.returnPromise) return new Promise(loadFn);
  else loadFn();
}


/**
 * Execute callbacks when dependencies have been satisfied.
 * @param {(string|string[])} deps - List of bundle ids
 * @param {Object} args - success/error arguments
 */
loadjs.ready = function ready(deps, args) {
  // subscribe to bundle load event
  subscribe(deps, function (depsNotFound) {
    // execute callbacks
    executeCallbacks(args, depsNotFound);
  });

  return loadjs;
};


/**
 * Manually satisfy bundle dependencies.
 * @param {string} bundleId - The bundle id
 */
loadjs.done = function done(bundleId) {
  publish(bundleId, []);
};


/**
 * Reset loadjs dependencies statuses
 */
loadjs.reset = function reset() {
  bundleIdCache = {};
  bundleResultCache = {};
  bundleCallbackQueue = {};
};


/**
 * Determine if bundle has already been defined
 * @param String} bundleId - The bundle id
 */
loadjs.isDefined = function isDefined(bundleId) {
  return bundleId in bundleIdCache;
};


// export
return loadjs;

})();

/**
 * Resource dependency manager to asyncronously load JS, CSS or Images as a bundle.
 */
(function (root, factory) {
	if ( typeof define === 'function' && define.amd ) {
	  define([], factory(root));
	} else if ( typeof exports === 'object' ) {
	  module.exports = factory(root);
	} else {
	  root.RequireBundle = factory(root);
	}
  })(typeof global !== 'undefined' ? global : this.window || this.global, function (root) {

	'use strict';


	var _publicMethods = {};
	var _bundles = {};



	/******************
	 * PRIVATE METHODS
	 *****************/



	/**
	 * Maybe autoload bundles
	 */
	var maybeAutoload = function() {
		_publicMethods.getIds().forEach( function( bundleId ) {
			var bundle = _bundles[ bundleId ];
			if ( typeof bundle.autoLoadSelector === 'string' && document.querySelector( bundle.autoLoadSelector ) ) {
				_publicMethods.require( bundleId, bundle.callbackFn );
			}
		} );
	}



	/******************
	 * PUBLIC METHODS
	 *****************/



	/**
	 * Get list of registered bundles
	 *
	 * @return  {Array}  List of registered bundle ids
	 */
	_publicMethods.getIds = function() {
		return Object.keys( _bundles );
	};



	/**
	 * Check if a bundle has been registered
	 *
	 * @param   {String}  bundleId  Bundle Id
	 *
	 * @return  {Boolean}           True if a bundle has been registered with the id passed
	 */
	_publicMethods.hasBundle = function( bundleId ) {
		return _bundles.hasOwnProperty( bundleId );
	};



	/**
	 * Get the bundle dependencies values
	 * 
	 * @param   {String}  bundleId  Bundle Id
	 *
	 * @return  {Array/Boolean}  Bundle dependencies values or false if not registered
	 */
	_publicMethods.getBundle = function( bundleId ) {
		if ( ! _publicMethods.hasBundle( bundleId ) ) return [];
		return _bundles[ bundleId ];
	};



	/**
	 * Register new bundle of resources
	 *
	 * @param   {String}  bundleId  Bundle ID
	 * @param   {Array}   deps      Array of resource paths
	 */
	_publicMethods.register = function( bundleId, deps, autoLoadSelector, callbackFn ) {
		// Already registered
		if ( _publicMethods.hasBundle( bundleId ) ) { 
			console.log( 'Bundle already registered: `' + bundleId + '`' );
			return false;
		};

		// Register
		_bundles[ bundleId ] = {
			bundleId: bundleId,
			deps: deps,
			autoLoadSelector: autoLoadSelector,
			callbackFn: callbackFn,
		}

		return true;
	};



	/**
	 * Deregister a bundle of resources
	 *
	 * @param   {String}  bundleId  Bundle ID
	 */
	_publicMethods.deregister = function( bundleId ) {
		// Already registered
		if ( _publicMethods.hasBundle( bundleId ) ) { 
			delete _bundles[ bundleId ];
			return true;
		};

		return false;
	};



	/**
	 * Load bundle of dependencies using LoadJS
	 *
	 * @param   {Array}     bundleIds   Array of Bundle IDs to load
	 * @param   {Function}  callbackFn  Function executed after bundle is successfully loaded
	 */
	_publicMethods.require = function( bundleIds, callbackFn ) {
		// Make sure variables are in the expected format
		if ( ! Array.isArray( bundleIds ) ) bundleIds = [ bundleIds ];
		if ( typeof callbackFn !== 'function' ) {
			callbackFn = function(){};
		}
		
		// Load each bundle
		bundleIds.forEach( function( bundleId ) {
			var bundle = _publicMethods.getBundle( bundleId );
			if ( bundle ) {
				if ( ! loadjs.isDefined( bundleId ) ) loadjs( bundle.deps, bundleId );
			}
		});
		
		// Run callback when ready
		loadjs.ready( bundleIds, callbackFn );
	};



	/**
	 * Add load event listener to auto-load bundles
	 */
	window.addEventListener( 'load', maybeAutoload );


	//
	// Expose Public APIs
	//
	return _publicMethods;

});
