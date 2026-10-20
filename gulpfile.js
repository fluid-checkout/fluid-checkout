/**
 * Fluid Checkout shared Gulpfile.
 *
 * Canonical source: fluid-checkout-standards/shared/root/gulpfile.js
 * Synced to plugin repositories as `gulpfile.js` via `sync-shared-files.js`
 * (manifest id `gulpfile`). Keep plugin-specific options in `gulp-settings.json`
 * and secrets in `gulp-settings.local.json` (API keys, BrowserSync, zip path).
 *
 * Auto-translation skips DeepL/Google when keys are missing, empty, or
 * placeholders (`*****`) and does not fail the package build.
 */

// Defining requirements
var fs = require('fs');
var gulp = require('gulp');
var plumber = require('gulp-plumber');
var sass = require('gulp-sass')(require('sass'));
var sassImportJson = require('gulp-sass-import-json');
var cssnano = require('gulp-cssnano');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var replace = require('gulp-replace');
var loadJsonFile = require('load-json-file');
var browserSync = require('browser-sync').create();
var autoprefixer = require('gulp-autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var del = require('del');
var exec = require('child_process').exec;
var wpPot = require('gulp-wp-pot');
var po2mo = require('gulp-po2mo');
var po2json = require('gulp-po2json');
var through2 = require('through2');
var path = require('path');
var url = require('url');



// Load package.json
var _package = loadJsonFile.sync( 'package.json' );

// Defining settings
var _gulpSettings = loadJsonFile.sync( 'gulp-settings.json' );
var _gulpSettingsLocal = {};
var _assetsVersion = '';
var _translationErrors = [];
var _translationWarnings = [];
var _translationErrorLimit = 5;
var _translationAborted = false;
var _loggedFirstTranslationApiError = false;

// Dart Sass options passed through gulp-sass 6 (modern compile API).
//
// First-party stylesheets use @use / @forward. Third-party Sass that still
// contains @import lives under sass/vendor/ when present. quietDeps hides
// those deprecation warnings; this plugin's own stylesheets still warn.
var _sassLogger = {
	warn: function( message, options ) {
		var spanUrl = options && options.span && options.span.url;
		var pathname = spanUrl ? ( spanUrl.pathname || String( spanUrl ) ) : '';
		if ( pathname.indexOf( 'node_modules' ) !== -1 ) {
			return;
		}
		process.stderr.write( message + '\n' );
	},
	debug: function( message ) {
		process.stderr.write( message + '\n' );
	},
};
var _sassOptions = {
	quietDeps: true,
	loadPaths: [
		path.resolve( 'sass' ),
		path.resolve( 'sass/vendor' ),
	],
	logger: _sassLogger,
};

// Zip and rsync listings from large plugins exceed exec's default 1 MB buffer.
var _execOptions = { maxBuffer: 1024 * 1024 * 50 };



// Try loading local gulpfile settings
try { _gulpSettingsLocal = loadJsonFile.sync( 'gulp-settings.local.json' ); }
catch ( err ) {}

var _defaultAction = _gulpSettingsLocal.defaultAction || _gulpSettings.defaultAction || 'watch';



// Run:
// gulp update-ver
// Update the project version
gulp.task( 'update-ver', gulp.series( function( done ) {
	// Define assets version
	_assetsVersion = '-' + _package.version.replace( /\./gi, '' );

	gulp.src( _package.main )
	// See http://mdn.io/string.replace#Specifying_a_string_as_a_parameter
	.pipe(replace(/Version: (.)*/g, 'Version: ' + _package.version ))
	.pipe(gulp.dest('./'));

	done();
} ) );

// Run:
// gulp update-ver
// Update the project version for full releases, including the version and date in the changelog
gulp.task( 'update-ver-release', gulp.series( 'update-ver', function( done ) {
	// Define assets version
	_assetsVersion = '-' + _package.version.replace( /\./gi, '' );

	// Get current date
	var today = new Date();

	// Set patterns to skip version updates
	var skipVersionPatterns = [ 'beta', 'dev', 'alpha', 'rc' ];
	var skip = false;
	for ( var i = 0; i < skipVersionPatterns.length; i++ ) {
		if ( 0 < _package.version.indexOf( skipVersionPatterns[ i ] ) ) {
			skip = true;
			break;
		}
	}

	// Only update readme.txt for full release versions
	if ( ! skip ) {
		gulp.src( _gulpSettings.changelogFile )
		// See http://mdn.io/string.replace#Specifying_a_string_as_a_parameter
		.pipe(replace(/Stable tag: (.)*/g, 'Stable tag: ' + _package.version ))
		.pipe(replace(/= Unreleased (.)*/g, '= ' + _package.version + ' - ' + today.toISOString().slice(0, 10) + ' =' ))
		.pipe(gulp.dest('./'));
	}

	done();
} ) );



// Run:
// gulp clean-css
// Delete existing generated css files
gulp.task( 'clean-css', function( done ) {
	del.sync( [ './css' ] );
	done();
} );



// Run:
// gulp clean-js
// Delete existing generated js files
gulp.task( 'clean-js', function( done ) {
	del.sync( [ './js' ] );
	done();
} );



// Process CSS for production environment.
function processCss( src, dest ) {
	return gulp.src( src )
		.pipe(plumber())
		// No source maps
		.pipe(sassImportJson({
			isScss: true
		}))
		.pipe(sass( _sassOptions ))
		.pipe(autoprefixer({ cascade: false }))
		.pipe(rename({suffix: _assetsVersion}))
		.pipe(gulp.dest( dest )) // save .css
		.pipe(cssnano( { zindex:false, discardComments: {removeAll: true}, discardUnused: {fontFace: false}, reduceIdents: {keyframes: false} } ) )
		.pipe(rename( { suffix: '.min' } ) )
		.pipe(sourcemaps.write('maps'))
		.pipe(gulp.dest( dest )); // save .min.css
}

// Process CSS for development environment.
function processCssDev( src, dest ) {
	return gulp.src( src )
		.pipe(plumber())
		.pipe(sourcemaps.init())
		.pipe(sassImportJson({ isScss: true }))
		.pipe(sass( _sassOptions ))
		.pipe(autoprefixer({ cascade: false }))
		.pipe(rename({ suffix: _assetsVersion }))
		.pipe(gulp.dest( dest )) // save .css
		.pipe(rename({ suffix: '.min' })) // Files are not minified on dev build but file name needs the .min suffix
		.pipe(sourcemaps.write('maps'))
		.pipe(gulp.dest( dest )) // save .min.css
		.pipe(browserSync.stream());
}



// Build all css for production
function buildCss( done ) {
	// Define functions for each css source
	var buildMainStyles = function() { if ( _gulpSettings.sassSources.main ) { return processCss( _gulpSettings.sassSources.main, './css/' ); } else { return Promise.resolve(); } };
	var buildAdminStyles = function() { if ( _gulpSettings.sassSources.admin ) { return processCss( _gulpSettings.sassSources.admin, './css/admin/' ); } else { return Promise.resolve(); } };
	var buildDesignTemplatesStyles = function() { if ( _gulpSettings.sassSources.designTemplates ) { return processCss( _gulpSettings.sassSources.designTemplates, './css/design-templates/' ); } else { return Promise.resolve(); } };
	var buildPluginCompatStyles = function() { if ( _gulpSettings.sassSources.pluginCompat ) { return processCss( _gulpSettings.sassSources.pluginCompat, './css/compat/plugins/' ); } else { return Promise.resolve(); } };
	var buildThemeCompatStyles = function() { if ( _gulpSettings.sassSources.themeCompat ) { return processCss( _gulpSettings.sassSources.themeCompat, './css/compat/themes/' ); } else { return Promise.resolve(); } };

	// Define tasks
	var tasks = [
		buildMainStyles,
		buildAdminStyles,
		buildDesignTemplatesStyles,
		buildPluginCompatStyles,
		buildThemeCompatStyles
	];

	// Run tasks in parallel
	return gulp.parallel( tasks )( done );
}

// Run:
// gulp build-css
// Builds css from scss for production environment and apply other changes.
gulp.task( 'build-css', gulp.series( 'update-ver', 'clean-css', buildCss ) );




// Build all CSS for development
function buildCssDev( done ) {
	// Define functions for each css source
	var buildMainStylesDev = function() { if ( _gulpSettings.sassSources.main ) { return processCssDev( _gulpSettings.sassSources.main, './css/' ); } else { return Promise.resolve(); } };
	var buildAdminStylesDev = function() { if ( _gulpSettings.sassSources.admin ) { return processCssDev( _gulpSettings.sassSources.admin, './css/admin/' ); } else { return Promise.resolve(); } };
	var buildDesignTemplatesStylesDev = function() { if ( _gulpSettings.sassSources.designTemplates ) { return processCssDev( _gulpSettings.sassSources.designTemplates, './css/design-templates/' ); } else { return Promise.resolve(); } };
	var buildPluginCompatStylesDev = function() { if ( _gulpSettings.sassSources.pluginCompat ) { return processCssDev( _gulpSettings.sassSources.pluginCompat, './css/compat/plugins/' ); } else { return Promise.resolve(); } };
	var buildThemeCompatStylesDev = function() { if ( _gulpSettings.sassSources.themeCompat ) { return processCssDev( _gulpSettings.sassSources.themeCompat, './css/compat/themes/' ); } else { return Promise.resolve(); } };

	// Define tasks
	var tasks = [
		buildMainStylesDev,
		buildAdminStylesDev,
		buildDesignTemplatesStylesDev,
		buildPluginCompatStylesDev,
		buildThemeCompatStylesDev
	];

	// Run tasks in parallel
	return gulp.parallel( tasks )( done );
}

// Run:
// gulp build-css-dev
// Builds css from scss for development environment and apply other changes.
gulp.task( 'build-css-dev', gulp.series( 'update-ver', 'clean-css', buildCssDev ) );




// Process JS for production environment.
function processJs( src, dest ) {
	return gulp.src( src )
		.pipe(sourcemaps.init())
		.pipe(rename({suffix: _assetsVersion}))
		.pipe(gulp.dest( dest )) // save .js
		.pipe(uglify())
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest( dest )); // save .min.js
}

// Build all JS for production
function buildJS( done ) {
	// Define functions for each js source
	var buildLibraries = function() { if ( _gulpSettings.jsSources.libraries ) { return processJs( _gulpSettings.jsSources.libraries, './js/lib/' ); } else { return Promise.resolve(); } };
	var buildPolyfills = function() { if ( _gulpSettings.jsSources.polyfills ) { return processJs( _gulpSettings.jsSources.polyfills, './js/lib/' ); } else { return Promise.resolve(); } };
	var buildBundles = function() { if ( _gulpSettings.jsSources.bundles ) { return processJs( _gulpSettings.jsSources.bundles, './js/' ); } else { return Promise.resolve(); } };
	var buildMain = function() { if ( _gulpSettings.jsSources.main ) { return processJs( _gulpSettings.jsSources.main, './js/' ); } else { return Promise.resolve(); } };
	var buildStandalone = function() { if ( _gulpSettings.jsSources.standalone ) { return processJs( _gulpSettings.jsSources.standalone, './js/' ); } else { return Promise.resolve(); } };
	var buildAdmin = function() { if ( _gulpSettings.jsSources.admin ) { return processJs( _gulpSettings.jsSources.admin, './js/admin/' ); } else { return Promise.resolve(); } };
	var buildPluginCompat = function() { if ( _gulpSettings.jsSources.pluginCompat ) { return processJs( _gulpSettings.jsSources.pluginCompat, './js/compat/plugins/' ); } else { return Promise.resolve(); } };
	var buildThemeCompat = function() { if ( _gulpSettings.jsSources.themeCompat ) { return processJs( _gulpSettings.jsSources.themeCompat, './js/compat/themes/' ); } else { return Promise.resolve(); } };

	// Define tasks
	var tasks = [
		buildLibraries,
		buildPolyfills,
		buildBundles,
		buildMain,
		buildStandalone,
		buildAdmin,
		buildPluginCompat,
		buildThemeCompat
	];

	// Run tasks in parallel
	return gulp.parallel( tasks )( done );
}

// Run:
// gulp build-js.
// Uglifies and concat all JS files into one
gulp.task( 'build-js', gulp.series( 'update-ver', 'clean-js', buildJS ) );



// Run:
// gulp npm-run-build
// Run the command `npm run build` to build the project assets for WordPress blocks.
gulp.task( 'npm-run-build', gulp.series( function( done ) {
	// Bail if the `blocks` folder does not exist
	if ( ! fs.existsSync( './blocks' ) ) { return done(); }

	exec( 'npm run build', _execOptions, function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		if ( err ) {
			// If an error occurred, make the Gulp task fail
			done( err );
			return;
		}

		done();
	} );
} ) );



// Run:
// gulp build
// Build css and js assets
gulp.task( 'build', gulp.series( gulp.parallel( 'build-js', 'build-css', 'npm-run-build' ) ) );



/**
 * Merge plugin zip rsync settings from shared and local config.
 */
function getMergedPluginZipRsyncSettings() {
	var base = _gulpSettings.pluginZipRsync || {};
	var local = _gulpSettingsLocal.pluginZipRsync || {};

	return {
		include: ( base.include || [] ).concat( local.include || [] ),
		exclude: ( base.exclude || [] ).concat( local.exclude || [] ),
		excludeFrom: local.excludeFrom || base.excludeFrom || null,
		pruneEmptyDirs: local.pruneEmptyDirs !== undefined ? local.pruneEmptyDirs : base.pruneEmptyDirs,
	};
}

/**
 * Build rsync filter arguments for plugin zip exports.
 */
function getPluginZipRsyncArgs() {
	var rsync = getMergedPluginZipRsyncSettings();
	var args = [];

	rsync.exclude.forEach( function( pattern ) {
		args.push( '--exclude="' + pattern + '"' );
	} );

	rsync.include.forEach( function( pattern ) {
		args.push( '--include="' + pattern + '"' );
	} );

	if ( rsync.excludeFrom ) {
		args.push( '--exclude-from=' + rsync.excludeFrom );
	}

	if ( rsync.pruneEmptyDirs ) {
		args.push( '--prune-empty-dirs' );
	}

	return args.join( ' ' );
}

// Run:
// gulp copy-plugin-files
// Copy the plugin files which are commited to the project into the export folder.
gulp.task( 'copy-plugin-files', gulp.series( function( done ) {
	// Load package.json
	_package = loadJsonFile.sync( 'package.json' );

	// Bail if destination path or plugin files does not exist
	if ( ! _gulpSettingsLocal.pluginZipPath || ! fs.existsSync( _gulpSettingsLocal.pluginZipPath ) ) {
		console.log( 'Skipping: Plugin zip export path not defined or folder does not exist.' );
		return done();
	}

	// Maybe delete existing destination folder
	var destinationFolder = _gulpSettingsLocal.pluginZipPath + '/' + _package.name;
	if ( fs.existsSync( destinationFolder ) ) {
		console.log( 'Deleting existing destination folder: ' + destinationFolder );
		del.sync( destinationFolder, { force: true } );
	}

	// Rsync filters are configured in gulp-settings.json → pluginZipRsync
	var rsyncArgs = getPluginZipRsyncArgs();
	exec(
		'rsync -av ' +
		( rsyncArgs ? rsyncArgs + ' ' : '' ) +
		'../' + _package.name + '/ ' +
		_gulpSettingsLocal.pluginZipPath + '/' + _package.name,
		_execOptions,
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			if ( err ) {
				// If an error occurred, make the Gulp task fail
				done( err );
				return;
			}

			done();
		}
	);
} ) );

// Run:
// gulp pack-plugin-zip
// Pack the extracted plugin folder into a new installable zip file with the version number.
gulp.task( 'pack-plugin-zip', gulp.series( function( done ) {
	// Load package.json
	_package = loadJsonFile.sync( 'package.json' );

	// Determine plugin file based on the package name and version
	var destinationFile = _package.name + '-' + _package.version + '.zip';

	// Maybe delete existing destination file
	var destinationFileWithPath = _gulpSettingsLocal.pluginZipPath + '/' + destinationFile;
	if ( fs.existsSync( destinationFileWithPath ) ) {
		del.sync( destinationFileWithPath, { force: true } );
	}

	// Zip the plugin folder
	exec( 'cd ' + _gulpSettingsLocal.pluginZipPath + ' && zip -r ' + destinationFile + ' ' + _package.name, _execOptions, function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		if ( err ) {
			// If an error occurred, make the Gulp task fail
			done( err );
			return;
		}

		done();
	} );
} ) );



// Function to get translation log file path
function getTranslationLogFile() {
	return _gulpSettings.translationLogFile || './logs/translations.log';
}



// Function to get translation concurrency limit
function getTranslationConcurrency() {
	return _gulpSettings.translationConcurrency || 10;
}



// Function to initialize translation log file
function initTranslationLog() {
	var logFile = getTranslationLogFile();
	var logDir = path.dirname( logFile );

	fs.mkdirSync( logDir, { recursive: true } );

	var header = 'Translation run started: ' + new Date().toISOString() + '\n';
	header += 'Concurrency: ' + getTranslationConcurrency() + '\n';
	header += 'Languages: ' + Object.keys( _gulpSettings.languages ).length + '\n\n';

	fs.writeFileSync( logFile, header, { flush: true } );
}



// Function to print translation log file link in the terminal
function printTranslationLogLink() {
	var logFile = path.resolve( getTranslationLogFile() );

	console.log( 'Translation log: ' + url.pathToFileURL( logFile ).href );
}



// Function to append a line to the translation log file
function appendTranslationLog( message ) {
	var logLine = '[' + new Date().toISOString() + '] ' + message + '\n';

	fs.appendFileSync( getTranslationLogFile(), logLine, { flush: true } );
}



// Function to update translation progress in the terminal
function updateTranslationProgress( completed, total, targetLang ) {
	process.stdout.write( '\rTranslating (' + targetLang + '): ' + completed + ' of ' + total );
}



// Function to finish translation progress line in the terminal
function finishTranslationProgress() {
	process.stdout.write( '\n' );
}



// Function to check whether the translation error limit has been reached
function hasReachedTranslationErrorLimit() {
	return _translationErrors.length >= _translationErrorLimit;
}



// Function to abort translation when the error limit is reached
function abortTranslationIfErrorLimitReached() {
	if ( hasReachedTranslationErrorLimit() ) {
		_translationAborted = true;
	}
}



// Function to record a translation warning without failing the Gulp task
function addTranslationWarning( message ) {
	_translationWarnings.push( message );
	console.warn( 'Warning: ' + message );
	appendTranslationLog( 'Warning: ' + message );
}

// Function to print a final translation warning summary
function printTranslationWarningSummary() {
	if ( 0 === _translationWarnings.length && 0 === _translationErrors.length ) {
		return;
	}

	console.warn( '' );
	console.warn( '========== Translation warnings ==========' );

	_translationWarnings.forEach( function( message ) {
		console.warn( ' - ' + message );
	} );

	_translationErrors.forEach( function( error ) {
		console.warn( ' - Error translating ' + error.lang + ': ' + error.error );
	} );

	console.warn( 'Auto-translation issues did not fail the package build. See the translation log for details.' );
	printTranslationLogLink();
	console.warn( '==========================================' );
	console.warn( '' );
}

// Function to check whether a translation API key is usable
function isUsableTranslationApiKey( key ) {
	if ( 'string' !== typeof key ) {
		return false;
	}

	var trimmedKey = key.trim();

	if ( ! trimmedKey ) {
		return false;
	}

	// Placeholder values from gulp-settings.local.example.json (e.g. *****)
	if ( /^\*+$/.test( trimmedKey ) ) {
		return false;
	}

	return true;
}

// Function to get a usable translation API config by type, or null when missing
function getTranslationApiConfig( type ) {
	var apis = _gulpSettingsLocal && _gulpSettingsLocal.translationAPIs;

	if ( ! Array.isArray( apis ) ) {
		return null;
	}

	var config = apis.find( function( api ) {
		return api && api.type === type;
	} );

	if ( ! config || ! config.url || ! isUsableTranslationApiKey( config.key ) ) {
		return null;
	}

	return config;
}

// Function to warn about missing translation APIs and whether auto-translation can run
function warnMissingTranslationApis() {
	var deeplConfigured = !! getTranslationApiConfig( 'deepl' );
	var googleConfigured = !! getTranslationApiConfig( 'googleTranslate' );

	if ( ! deeplConfigured && ! googleConfigured ) {
		addTranslationWarning( 'DeepL and Google Translate API keys are missing, empty, or placeholders (*****). Skipping auto-translation. POT and PO files are still updated without calling translation APIs.' );
		return false;
	}

	if ( ! deeplConfigured ) {
		addTranslationWarning( 'DeepL API key is missing, empty, or a placeholder (*****). Skipping DeepL. Languages with a DeepL mapping will fall back to Google Translate where a Google language code exists.' );
	}

	if ( ! googleConfigured ) {
		addTranslationWarning( 'Google Translate API key is missing, empty, or a placeholder (*****). Skipping Google Translate. Languages without a DeepL mapping will not be auto-translated.' );
	}

	return true;
}

// Function to pick an available translation provider for a language
function getAvailableTranslationProvider( langSettings ) {
	if ( ! langSettings ) {
		return null;
	}

	if ( null !== langSettings.deepl && getTranslationApiConfig( 'deepl' ) ) {
		return {
			useDeepl: true,
			translationSource: 'deepl',
			apiTargetLang: langSettings.deepl
		};
	}

	if ( langSettings.googleTranslate && getTranslationApiConfig( 'googleTranslate' ) ) {
		return {
			useDeepl: false,
			translationSource: 'google',
			apiTargetLang: langSettings.googleTranslate
		};
	}

	return null;
}



// Function to run items with a concurrency limit
function runWithConcurrency( items, limit, worker, callback ) {
	if ( 0 === items.length ) {
		return callback();
	}

	var index = 0;
	var inFlight = 0;
	var completed = 0;

	function maybeComplete() {
		if ( completed >= items.length || ( _translationAborted && 0 === inFlight ) ) {
			callback();
		}
	}

	function startNext() {
		while ( inFlight < limit && index < items.length && ! _translationAborted ) {
			var item = items[ index++ ];
			inFlight++;

			worker( item, function() {
				inFlight--;
				completed++;
				startNext();
				maybeComplete();
			} );
		}

		maybeComplete();
	}

	startNext();
}



// Function to translate text using Deepl
function translateWithDeepl( text, targetLang, callback ) {
	var deeplApiConfig = getTranslationApiConfig( 'deepl' );

	if ( ! deeplApiConfig ) {
		callback( new Error( 'DeepL API is not configured' ) );
		return;
	}

	import( 'node-fetch' ).then( function ( fetch ) {
		return fetch.default( deeplApiConfig.url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Authorization': 'DeepL-Auth-Key ' + deeplApiConfig.key
			},
			body: 'text=' + encodeURIComponent( text ) + '&source_lang=EN' + '&target_lang=' + targetLang
		} )
		.then( function( response ) {
			// Handle API error
			if ( 200 !== response.status ) {
				throw new Error( response.status + ' ' + response.statusText );
			}

			return response.json();
		} )
		.then( function( data ) {
			// Handle API error
			if ( ! data.translations ) {
				throw new Error( data.message );
			}

			// Otherwise, pass translated text into callback
			callback( null, data.translations[ 0 ].text );
		} );
	} ).catch( function( error ) { callback( error ); } );
}

// Function to translate text using Google Translate
function translateWithGoogle( text, targetLang, callback ) {
	var googleApiConfig = getTranslationApiConfig( 'googleTranslate' );

	if ( ! googleApiConfig ) {
		callback( new Error( 'Google Translate API is not configured' ) );
		return;
	}

	var requestOptions = {};

	if ( googleApiConfig.referrer ) {
		requestOptions.headers = {
			'Referer': googleApiConfig.referrer
		};
	}

	import( 'node-fetch' ).then( function ( fetch ) {
		return fetch.default( googleApiConfig.url + '?key=' + googleApiConfig.key + '&q=' + encodeURIComponent( text ) + '&source=en' + '&target=' + targetLang, requestOptions )
		.then( function( response ) {
			// Handle API error
			if ( 200 !== response.status ) {
				throw new Error( response.status + ' ' + response.statusText );
			}

			return response.json();
		} )
		.then( function( data ) {
			// Handle API error
			if ( ! data.data || ! data.data.translations ) {
				throw new Error( data.message );
			}

			// Otherwise, pass translated text into callback
			callback( null, data.data.translations[ 0 ].translatedText );
		} );
	} ).catch( function( error ) { callback( error ); } );
}

// Function to translate PO file
function translatePoFile( poFilePath, potFilePath, targetLang, callback ) {
	var poContent;
	var langSettings;

	try {
		// Maybe copy POT file to PO file if it does not exist
		if ( ! fs.existsSync( poFilePath ) ) {
			fs.copyFileSync( potFilePath, poFilePath );
		}

		// Get PO file contents
		poContent = fs.readFileSync( poFilePath );

		// Get language settings
		langSettings = _gulpSettings.languages[ targetLang ];
	}
	catch ( error ) {
		addTranslationWarning( 'Error reading translation files for ' + targetLang + ': ' + error.message );
		return callback();
	}

	// Load gettext-parser
	import( 'gettext-parser' ).then( function( gettextParser ) {
		// Get parsed PO content
		var po = gettextParser.po.parse( poContent );

		// Parse POT file
		var potContent = fs.readFileSync( potFilePath );
		var pot = gettextParser.po.parse( potContent );

		// Update plural format, if available
		if ( langSettings && langSettings.pluralForms ) {
			// Update plural-forms header from language settings
			po.headers[ 'Plural-Forms' ] = langSettings.pluralForms;

			// Update PO file
			fs.writeFileSync( poFilePath, gettextParser.po.compile( po ), { flush: true } );
		}

		// Update PO file with POT content
		Object.keys( pot.translations ).forEach( function( context ) {
			if ( ! po.translations[ context ] ) {
				po.translations[ context ] = {};
			}

			Object.keys( pot.translations[ context ] ).forEach( function( msgid ) {
				if ( ! po.translations[ context ][ msgid ] ) {
					po.translations[ context ][ msgid ] = pot.translations[ context ][ msgid ];
				}
			} );
		} );

		// Delete obsolete translations
		Object.keys( po.translations ).forEach( function( context ) {
			Object.keys( po.translations[ context ] ).forEach( function( msgid ) {
				if ( ! pot.translations[ context ] || ! pot.translations[ context ][ msgid ] ) {
					delete po.translations[ context ][ msgid ];
				}
			} );
		} );

		// Initialize translations
		var translations = po.translations;
		var provider = getAvailableTranslationProvider( langSettings );
		var jobs = [];

		// Build flat job list for strings that need translation
		Object.keys( translations ).forEach( function( context ) {
			var contextTranslations = translations[ context ];

			Object.keys( contextTranslations ).forEach( function( msgid ) {
				var msgidPlural = Object.hasOwnProperty.call( contextTranslations[ msgid ], 'msgid_plural' ) ? contextTranslations[ msgid ].msgid_plural : null;
				var nPluralForms = msgidPlural ? po.headers[ 'Plural-Forms' ].match( /nplurals\s*=\s*(\d+)/ )[ 1 ] : 1;

				for ( var msgstrIndex = 0; msgstrIndex < nPluralForms; msgstrIndex++ ) {
					var msgstr = contextTranslations[ msgid ].msgstr[ msgstrIndex ];

					if ( ! msgstr ) {
						// Skip untranslated strings when no API is available for this language
						if ( ! provider ) {
							continue;
						}

						jobs.push( {
							context: context,
							msgid: msgid,
							msgstrIndex: msgstrIndex,
							strToTranslate: msgstrIndex > 0 ? msgidPlural : msgid,
							useDeepl: provider.useDeepl,
							translationSource: provider.translationSource,
							apiTargetLang: provider.apiTargetLang
						} );
					}
				}
			} );
		} );

		// Bail if no strings need translation (still write merged POT entries)
		if ( 0 === jobs.length ) {
			fs.writeFileSync( poFilePath, gettextParser.po.compile( po ), { flush: true } );
			return callback();
		}

		var completedCount = 0;

		updateTranslationProgress( 0, jobs.length, targetLang );

		runWithConcurrency( jobs, getTranslationConcurrency(), function( job, done ) {
			if ( _translationAborted ) {
				return done();
			}

			var translateFunction = job.useDeepl ? translateWithDeepl : translateWithGoogle;

			translateFunction( job.strToTranslate, job.apiTargetLang, function( err, translatedText ) {
				completedCount++;

				if ( err ) {
					appendTranslationLog( 'Error translating "' + job.strToTranslate + '" to ' + job.apiTargetLang + ' with ' + ( job.useDeepl ? 'Deepl' : 'Google Translate' ) + ': ' + err.message );
					_translationErrors.push( { lang: job.apiTargetLang, error: err.message } );

					if ( ! _loggedFirstTranslationApiError ) {
						_loggedFirstTranslationApiError = true;
						console.warn( 'Warning: Translation API error. Further errors are written to the translation log. The package build will continue.' );
					}

					abortTranslationIfErrorLimitReached();

					if ( _translationAborted && _translationErrorLimit === _translationErrors.length ) {
						addTranslationWarning( 'Translation stopped after ' + _translationErrorLimit + ' API errors. Remaining strings were skipped.' );
					}
				}
				else {
					translations[ job.context ][ job.msgid ].msgstr[ job.msgstrIndex ] = translatedText;

					var indexString = job.msgstrIndex > 0 ? '(' + job.msgstrIndex + ')' : '';
					appendTranslationLog( 'Translated: ' + targetLang + ' ' + job.translationSource + ' ' + indexString + ' ' + job.strToTranslate + ' -> ' + translatedText );
				}

				updateTranslationProgress( completedCount, jobs.length, targetLang );
				done();
			} );
		}, function() {
			finishTranslationProgress();
			fs.writeFileSync( poFilePath, gettextParser.po.compile( po ), { flush: true } );
			callback();
		} );
	} ).catch( function( error ) {
		addTranslationWarning( 'Error processing PO file for ' + targetLang + ': ' + error.message );
		callback();
	} );
}

// Function to convert PO file to PHP
function poToPHP ( jsonFile, enc, cb ) {
	// Initialize variables
	var json = JSON.parse( jsonFile.contents.toString() );
	var phpContents = '';

	// Add PO header contents
	Object.keys( json[''] ).forEach( function ( key ) {
		var outputKey = key.replace( /\'/g, '\\\'' );
		var outputValue = json[ '' ][ key ].replace( /'/g, '\\\'' );
		phpContents += `'${outputKey}' => '${outputValue}',`;
	} );

	// Add PO string contents
	phpContents += `'messages' => [`;
	Object.keys( json ).forEach( function ( key ) {
		// Skip if key is empty, which contains the headers
		if ( '' == key ) { return; }

		var outputKey = key.replace( /\'/g, '\\\'' );

		// Add string values, including plural forms
		phpContents += `'${outputKey}' => '`;
		json[ key ].forEach( function( messageValue, index ) {
			// Skip first item, which is the original plural string
			if ( 0 === index ) { return; }

			// Initialize variable
			var outputValue = messageValue;

			// Use only first value if messageValue is an array
			// 
			// This happens when the language has plural forms = `nplurals=1; plural=0`,
			// which means that there is no distinction between singular and plural forms.
			// 
			// For unknown reasons at this point, the Json object in these cases returns an array
			// as the value of the key, with the first item being translation of the singular form,
			// and with the second item usually empty.
			if ( Array.isArray( messageValue ) ) {
				messageValue = messageValue[ 0 ];
			}

			// Add string value
			var outputValue = messageValue.replace( /'/g, '\\\'' );
			phpContents += outputValue;

			// Add separator
			if ( index < json[ key ].length - 1 ) {
				phpContents += `' . "\\0" . '`;
			}
		} );
		phpContents += `',`;
	} );
	phpContents += `]`;

	var fileContents = `<?php\nreturn [ ${phpContents} ];`;
	jsonFile.contents = Buffer.from( fileContents );
	this.push( jsonFile );
	cb();
}

// Run:
// gulp translate-po
// Translate PO files using Deepl and Google Translate.
// Missing or placeholder API keys skip that provider with a warning.
// API errors are logged and do not fail the package build.
gulp.task( 'translate-po', function ( done ) {
	_translationErrors = [];
	_translationWarnings = [];
	_translationAborted = false;
	_loggedFirstTranslationApiError = false;
	initTranslationLog();
	printTranslationLogLink();
	warnMissingTranslationApis();

	// Get languages
	var languagesList = Object.keys( _gulpSettings.languages );
	var index = 0;

	// Finish the task without failing the package build
	function finishTranslatePo() {
		printTranslationWarningSummary();
		done();
	}

	// Translate next language
	function translateNext() {
		if ( _translationAborted ) {
			appendTranslationLog( 'Translation run stopped after ' + _translationErrorLimit + ' API errors.' );
			return finishTranslatePo();
		}

		if ( index >= languagesList.length ) {
			appendTranslationLog( 'Translation run finished. Languages processed: ' + languagesList.length + '. Errors: ' + _translationErrors.length + '. Warnings: ' + _translationWarnings.length );
			return finishTranslatePo();
		}

		// Get language code and PO file path
		var languageCode = languagesList[ index ];
		var potFilePath = _gulpSettings.translationDest + '/' + _gulpSettings.textDomain + '.pot';
		var poFilePath = _gulpSettings.translationDest + '/' + _gulpSettings.textDomain + '-' + languageCode + '.po';

		// Translate PO file for the language
		translatePoFile( poFilePath, potFilePath, languageCode, function() {
			index++;
			translateNext();
		} );
	}

	translateNext();
} );

// Run:
// gulp update-translations
// Update translations
gulp.task( 'update-translations', gulp.series( 'translate-po', function ( done ) {
	var tasks = Object.keys( _gulpSettings.languages ).map( function( lang ) {
		return function ( done2 ) {
			gulp.src( _gulpSettings.translationDest + '/' + _gulpSettings.textDomain + '-' + lang + '.po' )
				.pipe( po2mo() )
				.pipe( gulp.dest( _gulpSettings.translationDest ) );

			gulp.src( _gulpSettings.translationDest + '/' + _gulpSettings.textDomain + '-' + lang + '.po' )
				.pipe( po2json() )
				.pipe( through2.obj( poToPHP ) )
				.pipe( rename( { extname: '.l10n.php' } ) )
				.pipe( gulp.dest( _gulpSettings.translationDest ) );

			done2();
		};
	} );

	return gulp.series( tasks )( done );
} ) );
	

// Run:
// gulp generate-pot
// Generate the POT translation file
gulp.task( 'generate-pot', function ( done ) {
	try {
		// Load package.json
		_package = loadJsonFile.sync( 'package.json' );

		return gulp.src( _gulpSettings.translationSources )
			.pipe( wpPot( {
				domain: _gulpSettings.textDomain,
				package: _package.name,
				bugReport: _package.bugs.url,
				lastTranslator: _package.author,
				team: _package.author
			} ) )
			.pipe( gulp.dest( _gulpSettings.translationDest + '/' + _gulpSettings.textDomain + '.pot' ) )
			.on( 'end', done )
			.on( 'error', function( err ) {
				console.error( 'Error in generate-pot task', err );
				done( err );
			} );
	}
	catch ( err ) {
		console.error( 'Error in generate-pot task', err );
		done( err );
	}
} );



// Run:
// gulp translations
// Generate POT file and update translations.
// Auto-translation warnings are summarized again at the end of the build run.
gulp.task( 'translate', gulp.series( 'generate-pot', 'update-translations', function( done ) {
	printTranslationWarningSummary();
	done();
} ) );



// Run:
// gulp browser-sync
// Starts browser-sync task for starting the server.
gulp.task( 'browser-sync', gulp.series( function( done ) {
	if ( _gulpSettings.browserSyncWatch && _gulpSettingsLocal && _gulpSettingsLocal.browserSyncOptions ) {
		browserSync.init( _gulpSettings.browserSyncWatch, _gulpSettingsLocal.browserSyncOptions );
	}

	done();
} ) );

// Run:
// gulp browser-sync
// Starts browser-sync task for starting the server.
gulp.task( 'browser-sync-reload', function( done ) {
	browserSync.reload();
	done();
} );



// Run:
// gulp watch
// Starts watcher. Watcher runs appropriate tasks on file changes
gulp.task( 'watch', gulp.series( function( done ) {
	_gulpSettings.watch.forEach( function ( item ) {
		var watchPattern = item[ 0 ];
		var runTasks = item[ 1 ];
		gulp.watch( watchPattern, gulp.series( runTasks ) );
	} );

	done();
} ) );



// Run:
// gulp watch-reload
// Starts watcher with browser-sync. Browser-sync reloads page automatically on your browser
gulp.task( 'watch-reload', gulp.series( gulp.parallel( 'build-js', 'build-css-dev' ), gulp.series( 'watch', 'browser-sync' ) ) );



// Run:
// gulp
// Defines gulp default task
gulp.task( 'default', gulp.series( _defaultAction ) );
