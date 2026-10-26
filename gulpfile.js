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
 *
 * Tasks:
 * - default              Runs the configured default action (usually `watch`)
 * - update-ver           Bump asset version strings from package.json
 * - update-ver-release   update-ver plus changelog version/date for full releases;
 *                        also verifies pluginZipBundledSources with releaseBranch
 * - clean-css            Delete generated CSS files
 * - clean-js             Delete generated JS files
 * - set-assets-version   Set _assetsVersion from package.json in memory only (dev builds)
 * - build-css            Production CSS from Sass
 * - build-css-dev        Development CSS from Sass (sourcemaps; no clean)
 * - build-js             Production JS (uglify)
 * - build-js-dev         Development JS (no uglify; no clean)
 * - npm-run-build        Run `npm run build` for WordPress block assets when present
 * - build                Parallel build-js, build-css, and npm-run-build
 * - copy-plugin-files    Copy committed plugin files into the export folder
 * - pack-plugin-zip      Zip the export folder into an installable package
 * - generate-pot         Generate the POT translation catalog
 * - translate-po         Fill empty PO msgstr via DeepL/Google Translate APIs
 * - update-translations  translate-po, then rebuild .mo and .l10n.php
 * - translate            generate-pot then update-translations
 *                        (pre-release alpha/beta/dev/rc: skipped only during `npm version`)
 * - browser-sync         Start BrowserSync from local settings
 * - browser-sync-reload  Reload BrowserSync clients
 * - watch                Watch sources and rebuild on change (dev asset tasks)
 * - watch-reload         Build once with dev tasks, then watch with BrowserSync reload
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
var execSync = require('child_process').execSync;
var wpPot = require('gulp-wp-pot');
var po2mo = require('gulp-po2mo');
var po2json = require('gulp-po2json');
var through2 = require('through2');
var path = require('path');
var url = require('url');
var readline = require('readline');
var glob = require('glob');
var minimatchModule = require('minimatch');
var minimatch = typeof minimatchModule === 'function' ? minimatchModule : minimatchModule.minimatch;



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
var _translationLogFileForRun = null;
var _translationProgressActiveLang = null;
var _translationProgressTotal = 0;

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
/**
 * Whether the current package.json version is a pre-release (alpha, beta, dev, or rc).
 * Same rules as changelog Stable tag updates: skip when the marker appears after the start.
 */
function isPreReleaseVersion() {
	var skipVersionPatterns = [ 'beta', 'dev', 'alpha', 'rc' ];
	var i;

	// Reload package.json so npm version bumps are visible mid-lifecycle
	try {
		_package = loadJsonFile.sync( 'package.json' );
	}
	catch ( err ) {
		// Keep the previously loaded package when reload fails
	}

	for ( i = 0; i < skipVersionPatterns.length; i++ ) {
		if ( 0 < _package.version.indexOf( skipVersionPatterns[ i ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether gulp was invoked from the npm `version` lifecycle script.
 * Used to skip expensive auto-translation during alpha/beta packaging
 * while still allowing manual `gulp translate` on pre-release branches.
 */
function isNpmVersionLifecycle() {
	return 'version' === process.env.npm_lifecycle_event;
}

/**
 * Skip auto-translation for pre-release versions only when packing via `npm version`.
 * Manual `npx gulp translate` / `translate-po` / `update-translations` still run.
 */
function shouldSkipPreReleaseTranslations() {
	return isPreReleaseVersion() && isNpmVersionLifecycle();
}

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
// gulp update-ver-release
// Update the project version for full releases, including the version and date in the changelog.
// Official releases also verify pluginZipBundledSources that set releaseBranch.
gulp.task( 'update-ver-release', gulp.series(
	function( done ) {
		verifyBundledSourcesForOfficialRelease( done );
	},
	'update-ver',
	function( done ) {
		// Define assets version
		_assetsVersion = '-' + _package.version.replace( /\./gi, '' );

		// Get current date
		var today = new Date();

		// Only update readme.txt for full release versions
		if ( ! isPreReleaseVersion() ) {
			gulp.src( _gulpSettings.changelogFile )
			// See http://mdn.io/string.replace#Specifying_a_string_as_a_parameter
			.pipe(replace(/Stable tag: (.)*/g, 'Stable tag: ' + _package.version ))
			.pipe(replace(/= Unreleased (.)*/g, '= ' + _package.version + ' - ' + today.toISOString().slice(0, 10) + ' =' ))
			.pipe(gulp.dest('./'));
		}

		done();
	}
) );



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



/**
 * Default dest directories for legacy sassSources / jsSources array entries.
 * New keys should use { "src": [...], "dest": "..." } in gulp-settings.json.
 */
var _defaultSassDest = {
	main: './css/',
	admin: './css/admin/',
	designTemplates: './css/design-templates/',
	pluginCompat: './css/compat/plugins/',
	themeCompat: './css/compat/themes/',
};

var _defaultJsDest = {
	libraries: './js/lib/',
	polyfills: './js/lib/',
	bundles: './js/',
	main: './js/',
	standalone: './js/',
	admin: './js/admin/',
	account: './js/account/',
	pluginCompat: './js/compat/plugins/',
	themeCompat: './js/compat/themes/',
};

/**
 * Normalize a sassSources / jsSources entry to { src, dest }.
 * Accepts a legacy glob array or { src, dest }.
 */
function normalizeSourceEntry( key, value, defaultDestMap ) {
	var dest;

	if ( ! value ) {
		return null;
	}

	if ( Array.isArray( value ) ) {
		dest = defaultDestMap[ key ] || './';
		return { src: value, dest: dest };
	}

	if ( 'object' === typeof value && value.src ) {
		dest = value.dest || defaultDestMap[ key ] || './';
		return {
			src: Array.isArray( value.src ) ? value.src : [ value.src ],
			dest: dest,
		};
	}

	return null;
}

/**
 * List normalized source entries for sassSources or jsSources.
 */
function getNormalizedSources( sourcesConfig, defaultDestMap ) {
	var sources = [];
	var key;
	var entry;

	if ( ! sourcesConfig || 'object' !== typeof sourcesConfig ) {
		return sources;
	}

	for ( key in sourcesConfig ) {
		if ( ! Object.prototype.hasOwnProperty.call( sourcesConfig, key ) ) {
			continue;
		}

		entry = normalizeSourceEntry( key, sourcesConfig[ key ], defaultDestMap );
		if ( entry ) {
			sources.push( { key: key, src: entry.src, dest: entry.dest } );
		}
	}

	return sources;
}

/**
 * Expand src globs to entry file paths (skip Sass partials named _*.scss).
 */
function expandEntryFiles( srcGlobs ) {
	var patterns = Array.isArray( srcGlobs ) ? srcGlobs : [ srcGlobs ];
	var files = [];
	var i;
	var matched;
	var j;
	var file;
	var base;

	for ( i = 0; i < patterns.length; i++ ) {
		matched = glob.sync( patterns[ i ], { nodir: true } );
		for ( j = 0; j < matched.length; j++ ) {
			file = matched[ j ];
			base = path.basename( file );
			if ( 0 === base.indexOf( '_' ) && /\.scss$/i.test( base ) ) {
				continue;
			}
			files.push( file );
		}
	}

	return files;
}

/**
 * Parallel batch size for per-entry Sass compiles.
 */
function getSassParallelBatchSize() {
	var local = _gulpSettingsLocal.sassParallelBatchSize;
	var shared = _gulpSettings.sassParallelBatchSize;

	if ( 'number' === typeof local && local > 0 ) {
		return local;
	}
	if ( 'number' === typeof shared && shared > 0 ) {
		return shared;
	}

	return 32;
}

/**
 * Run gulp task functions in parallel batches to limit peak memory.
 */
function runTasksInBatches( taskFns, batchSize, done ) {
	var index = 0;

	if ( ! taskFns || 0 === taskFns.length ) {
		done();
		return;
	}

	function runNextBatch() {
		var batch;

		if ( index >= taskFns.length ) {
			done();
			return;
		}

		batch = taskFns.slice( index, index + batchSize );
		index += batchSize;

		gulp.parallel( batch )( function( err ) {
			if ( err ) {
				done( err );
				return;
			}
			runNextBatch();
		} );
	}

	runNextBatch();
}

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

/**
 * Wait for a vinyl/gulp stream to finish, then call done once.
 */
function whenStreamDone( stream, done ) {
	var settled = false;

	function finish( err ) {
		if ( settled ) {
			return;
		}
		settled = true;
		done( err );
	}

	stream.on( 'error', finish );
	stream.on( 'finish', function() {
		finish();
	} );
	// Some pipelines (e.g. with browserSync.stream) emit end without finish
	stream.on( 'end', function() {
		finish();
	} );
}

/**
 * Compile Sass for a source group.
 *
 * Full builds use one gulp.src pipeline per source key (same as classic gulpfile;
 * keys still run in parallel via buildCss / buildCssDev). Per-entry parallel
 * streams hang with browserSync.stream() and add overhead — use expandEntryFiles
 * + processCssDev(singleFile) only from the watch handler (Phase 3).
 *
 * Optional parallel per-entry for production-only pipelines (no browserSync) when
 * sassParallelBatchSize is set and the group is large — see processCssEntriesParallel.
 */
function processCssEntries( src, dest, processorFn, done ) {
	var files = expandEntryFiles( src );
	var batchSize = getSassParallelBatchSize();
	var parallelThreshold = 64;
	var useParallel = false;
	var tasks;
	var i;

	if ( 0 === files.length ) {
		done();
		return;
	}

	// Parallelize large production groups only (processCss has no browserSync.stream).
	// Dev CSS stays one stream per key — browserSync.stream hangs under many parallel pipes.
	useParallel = files.length >= parallelThreshold && processorFn === processCss;

	if ( ! useParallel ) {
		whenStreamDone( processorFn( src, dest ), done );
		return;
	}

	tasks = [];
	for ( i = 0; i < files.length; i++ ) {
		( function( filePath ) {
			tasks.push( function() {
				return processorFn( filePath, dest );
			} );
		} )( files[ i ] );
	}

	runTasksInBatches( tasks, batchSize, done );
}

/**
 * Build one Sass source key (production).
 */
function buildCssSourceKey( entry, done ) {
	processCssEntries( entry.src, entry.dest, processCss, done );
}

/**
 * Build one Sass source key (development).
 */
function buildCssDevSourceKey( entry, done ) {
	processCssEntries( entry.src, entry.dest, processCssDev, done );
}

// Run:
// gulp set-assets-version
// Set _assetsVersion from package.json in memory only (does not rewrite plugin header).
gulp.task( 'set-assets-version', function setAssetsVersion( done ) {
	try {
		_package = loadJsonFile.sync( 'package.json' );
	}
	catch ( err ) {
		// Keep the previously loaded package when reload fails
	}

	_assetsVersion = '-' + _package.version.replace( /\./gi, '' );
	done();
} );

/**
 * Register named per-source compile tasks so the terminal shows
 * compile-css-dev:main instead of <anonymous>.
 */
function registerCompileSubtasks() {
	var sassSources = getNormalizedSources( _gulpSettings.sassSources, _defaultSassDest );
	var jsSources = getNormalizedSources( _gulpSettings.jsSources, _defaultJsDest );
	var i;
	var entry;
	var cssDevTasks = [];
	var cssProdTasks = [];
	var jsDevTasks = [];
	var jsProdTasks = [];

	for ( i = 0; i < sassSources.length; i++ ) {
		entry = sassSources[ i ];
		( function( sourceEntry ) {
			var cssDevName = 'compile-css-dev:' + sourceEntry.key;
			var cssProdName = 'compile-css:' + sourceEntry.key;

			gulp.task( cssDevName, function() {
				return processCssDev( sourceEntry.src, sourceEntry.dest );
			} );
			gulp.task( cssProdName, function( done ) {
				var files = expandEntryFiles( sourceEntry.src );
				if ( files.length >= 64 ) {
					processCssEntries( sourceEntry.src, sourceEntry.dest, processCss, done );
					return;
				}
				return processCss( sourceEntry.src, sourceEntry.dest );
			} );

			cssDevTasks.push( cssDevName );
			cssProdTasks.push( cssProdName );
		} )( entry );
	}

	for ( i = 0; i < jsSources.length; i++ ) {
		entry = jsSources[ i ];
		( function( sourceEntry ) {
			var jsDevName = 'compile-js-dev:' + sourceEntry.key;
			var jsProdName = 'compile-js:' + sourceEntry.key;

			gulp.task( jsDevName, function() {
				return processJsDev( sourceEntry.src, sourceEntry.dest );
			} );
			gulp.task( jsProdName, function() {
				return processJs( sourceEntry.src, sourceEntry.dest );
			} );

			jsDevTasks.push( jsDevName );
			jsProdTasks.push( jsProdName );
		} )( entry );
	}

	return {
		cssDevTasks: cssDevTasks,
		cssProdTasks: cssProdTasks,
		jsDevTasks: jsDevTasks,
		jsProdTasks: jsProdTasks,
	};
}

var _compileSubtasks = registerCompileSubtasks();

// Build all css for production
function buildCss( done ) {
	if ( 0 === _compileSubtasks.cssProdTasks.length ) {
		done();
		return;
	}
	return gulp.parallel( _compileSubtasks.cssProdTasks )( done );
}

// Run:
// gulp build-css
// Builds css from scss for production environment and apply other changes.
gulp.task( 'build-css', gulp.series( 'update-ver', 'clean-css', buildCss ) );




// Build all CSS for development
function buildCssDev( done ) {
	if ( 0 === _compileSubtasks.cssDevTasks.length ) {
		done();
		return;
	}
	return gulp.parallel( _compileSubtasks.cssDevTasks )( done );
}

// Run:
// gulp build-css-dev
// Builds css from scss for development environment (no clean; in-memory version suffix).
gulp.task( 'build-css-dev', gulp.series( 'set-assets-version', buildCssDev ) );




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

// Process JS for development environment (no uglify; .min suffix for enqueue parity).
function processJsDev( src, dest ) {
	return gulp.src( src )
		.pipe(plumber())
		.pipe(sourcemaps.init())
		.pipe(rename({ suffix: _assetsVersion }))
		.pipe(gulp.dest( dest )) // save .js
		.pipe(rename({ suffix: '.min' }))
		.pipe(sourcemaps.write( 'maps' ))
		.pipe(gulp.dest( dest )) // save .min.js
		.pipe(browserSync.stream());
}

// Build all JS for production
function buildJS( done ) {
	if ( 0 === _compileSubtasks.jsProdTasks.length ) {
		done();
		return;
	}
	return gulp.parallel( _compileSubtasks.jsProdTasks )( done );
}

// Build all JS for development
function buildJSDev( done ) {
	if ( 0 === _compileSubtasks.jsDevTasks.length ) {
		done();
		return;
	}
	return gulp.parallel( _compileSubtasks.jsDevTasks )( done );
}

// Run:
// gulp build-js.
// Uglifies and concat all JS files into one
gulp.task( 'build-js', gulp.series( 'update-ver', 'clean-js', buildJS ) );

// Run:
// gulp build-js-dev
// Copy and rename JS for development (no uglify; in-memory version suffix).
gulp.task( 'build-js-dev', gulp.series( 'set-assets-version', buildJSDev ) );



// Run:
// gulp npm-run-build
// Run the command `npm run build` to build the project assets for WordPress blocks.
gulp.task( 'npm-run-build', gulp.series( function( done ) {
	// Bail if the `blocks` folder does not exist
	if ( ! fs.existsSync( './blocks' ) ) { return done(); }

	exec( 'npm run build', _execOptions, function ( err, stdout, stderr ) {
		// Successful builds stay quiet. Print the log only when the command fails.
		if ( err ) {
			if ( stdout ) { console.log( stdout ); }
			if ( stderr ) { console.log( stderr ); }
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
 * Merge plugin zip rsync settings from base and optional local overlays.
 *
 * @param {Object} baseSettings  pluginZipRsync from gulp-settings.json
 * @param {Object} localSettings pluginZipRsync from gulp-settings.local.json (optional)
 * @return {Object}
 */
function getMergedPluginZipRsyncSettingsFromFiles( baseSettings, localSettings ) {
	var base = baseSettings || {};
	var local = localSettings || {};

	return {
		include: ( base.include || [] ).concat( local.include || [] ),
		exclude: ( base.exclude || [] ).concat( local.exclude || [] ),
		excludeFrom: local.excludeFrom || base.excludeFrom || null,
		pruneEmptyDirs: local.pruneEmptyDirs !== undefined ? local.pruneEmptyDirs : base.pruneEmptyDirs,
	};
}

/**
 * Merge plugin zip rsync settings from this plugin's shared and local config.
 *
 * @return {Object}
 */
function getMergedPluginZipRsyncSettings() {
	return getMergedPluginZipRsyncSettingsFromFiles(
		_gulpSettings.pluginZipRsync,
		_gulpSettingsLocal.pluginZipRsync
	);
}

/**
 * Load and merge pluginZipRsync from a gulp-settings.json path.
 * Merges a sibling gulp-settings.local.json when present.
 *
 * @param {string} settingsJsonPath Absolute or relative path to gulp-settings.json
 * @return {Object}
 */
function loadPluginZipRsyncSettings( settingsJsonPath ) {
	var absoluteSettingsPath = path.resolve( settingsJsonPath );
	var baseSettings = loadJsonFile.sync( absoluteSettingsPath );
	var localSettings = {};
	var localSettingsPath = path.join( path.dirname( absoluteSettingsPath ), 'gulp-settings.local.json' );

	// Maybe merge local rsync overrides next to the settings file
	if ( fs.existsSync( localSettingsPath ) ) {
		try {
			localSettings = loadJsonFile.sync( localSettingsPath );
		} catch ( e ) {
			localSettings = {};
		}
	}

	return getMergedPluginZipRsyncSettingsFromFiles(
		baseSettings.pluginZipRsync,
		localSettings.pluginZipRsync
	);
}

/**
 * Build rsync filter arguments from a merged pluginZipRsync object.
 *
 * @param {Object} mergedRsync Merged include/exclude settings
 * @return {string}
 */
function getPluginZipRsyncArgsFromSettings( mergedRsync ) {
	var rsync = mergedRsync || {};
	var args = [];

	( rsync.exclude || [] ).forEach( function( pattern ) {
		args.push( '--exclude=' + JSON.stringify( String( pattern ) ) );
	} );

	( rsync.include || [] ).forEach( function( pattern ) {
		args.push( '--include=' + JSON.stringify( String( pattern ) ) );
	} );

	if ( rsync.excludeFrom ) {
		args.push( '--exclude-from=' + JSON.stringify( String( rsync.excludeFrom ) ) );
	}

	if ( rsync.pruneEmptyDirs ) {
		args.push( '--prune-empty-dirs' );
	}

	return args.join( ' ' );
}

/**
 * Build rsync filter arguments for this plugin's zip export.
 *
 * @return {string}
 */
function getPluginZipRsyncArgs() {
	return getPluginZipRsyncArgsFromSettings( getMergedPluginZipRsyncSettings() );
}

/**
 * Bundled sibling sources to materialize into the export zip.
 * Configured in gulp-settings.json → pluginZipBundledSources.
 *
 * @return {Array}
 */
function getBundledSourcesConfig() {
	var sources = _gulpSettings.pluginZipBundledSources;
	return Array.isArray( sources ) ? sources : [];
}

/**
 * Whether a directory is a git working tree (including worktrees / linked .git files).
 *
 * @param {string} absoluteDir Absolute path to check
 * @return {boolean}
 */
function isGitRepository( absoluteDir ) {
	try {
		execSync( 'git rev-parse --git-dir', {
			cwd: absoluteDir,
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'ignore' ],
		} );
		return true;
	}
	catch ( err ) {
		return false;
	}
}

/**
 * Current branch name for a git working tree, or "HEAD" when detached.
 *
 * @param {string} absoluteDir Absolute path to the repository
 * @return {string}
 */
function getGitBranchName( absoluteDir ) {
	return execSync( 'git rev-parse --abbrev-ref HEAD', {
		cwd: absoluteDir,
		encoding: 'utf8',
	} ).trim();
}

/**
 * Whether the git working tree has uncommitted changes (tracked or untracked, non-ignored).
 *
 * @param {string} absoluteDir Absolute path to the repository
 * @return {boolean}
 */
function hasGitWorkingTreeChanges( absoluteDir ) {
	var status = execSync( 'git status --porcelain', {
		cwd: absoluteDir,
		encoding: 'utf8',
	} ).trim();

	return '' !== status;
}

/**
 * For official releases (not alpha/beta/dev/rc):
 * - this plugin must be a git repo on `main`
 * - every bundled source with `releaseBranch` must exist, be a git repo on that
 *   branch, and have a clean working tree
 *
 * @param {Function} done Callback with optional Error
 */
function verifyBundledSourcesForOfficialRelease( done ) {
	var entries;
	var errors;
	var i;
	var entry;
	var absoluteSourceDir;
	var label;
	var branch;
	var cwd;

	// Bail early for pre-release package versions
	if ( isPreReleaseVersion() ) {
		done();
		return;
	}

	errors = [];
	cwd = process.cwd();

	if ( ! isGitRepository( cwd ) ) {
		errors.push( 'This plugin is not a git repository; official releases require git.' );
	}
	else {
		try {
			branch = getGitBranchName( cwd );
			if ( 'main' !== branch ) {
				errors.push(
					'Official releases must be created from branch "main" (current: "' + branch + '").'
				);
			}
		}
		catch ( err ) {
			errors.push( 'Could not read git branch for this plugin: ' + err.message );
		}
	}

	entries = getBundledSourcesConfig();

	for ( i = 0; i < entries.length; i++ ) {
		entry = entries[ i ];

		// Bail if this entry does not require a release branch
		if ( ! entry || ! entry.releaseBranch || ! entry.sourceDir ) {
			continue;
		}

		absoluteSourceDir = path.resolve( cwd, entry.sourceDir );
		label = entry.targetSubdir || entry.sourceDir;

		if ( ! fs.existsSync( absoluteSourceDir ) ) {
			errors.push(
				'Bundled source missing for official release: "' + absoluteSourceDir + '" (' + label + ').'
			);
			continue;
		}

		if ( ! isGitRepository( absoluteSourceDir ) ) {
			errors.push(
				'Bundled source is not a git repository: "' + absoluteSourceDir + '" (' + label + ').'
			);
			continue;
		}

		try {
			branch = getGitBranchName( absoluteSourceDir );
		}
		catch ( err ) {
			errors.push(
				'Could not read git branch for bundled source "' + label + '": ' + err.message
			);
			continue;
		}

		if ( branch !== entry.releaseBranch ) {
			errors.push(
				'Bundled source "' + label + '" must be on branch "' + entry.releaseBranch +
				'" for official releases (current: "' + branch + '").'
			);
		}

		try {
			if ( hasGitWorkingTreeChanges( absoluteSourceDir ) ) {
				errors.push(
					'Bundled source "' + label + '" has uncommitted changes; ' +
					'working tree must be clean for official releases.'
				);
			}
		}
		catch ( err ) {
			errors.push(
				'Could not read git status for bundled source "' + label + '": ' + err.message
			);
		}
	}

	if ( errors.length ) {
		done( new Error(
			'Official release blocked:\n- ' + errors.join( '\n- ' )
		) );
		return;
	}

	done();
}

/**
 * Extra main-rsync excludes so bundled target subdirs are not copied as symlinks.
 *
 * @return {Array}
 */
function getBundledTargetSubdirExcludes() {
	var excludes = [];

	getBundledSourcesConfig().forEach( function( entry ) {
		if ( ! entry || ! entry.targetSubdir ) { return; }
		// Exclude the symlink/file itself and any nested paths
		excludes.push( entry.targetSubdir );
		excludes.push( entry.targetSubdir + '/' );
		excludes.push( entry.targetSubdir + '/**' );
	} );

	return excludes;
}

/**
 * Rsync args for the main plugin export, excluding bundled target folders.
 *
 * @return {string}
 */
function getMainPluginZipRsyncArgs() {
	var merged = getMergedPluginZipRsyncSettings();
	var bundledExcludes = getBundledTargetSubdirExcludes();

	merged.exclude = ( merged.exclude || [] ).concat( bundledExcludes );

	return getPluginZipRsyncArgsFromSettings( merged );
}

/**
 * Run gulp build inside a bundled source directory when configured.
 *
 * @param {Object} entry             Bundled source config entry
 * @param {string} absoluteSourceDir Absolute path to the source plugin root
 * @param {Function} done            Callback with optional error
 */
function runBundledSourceBuild( entry, absoluteSourceDir, done ) {
	// Bail if build before copy is not enabled
	if ( ! entry.buildBeforeCopy ) {
		done();
		return;
	}

	var buildTask = entry.buildTask || 'build';
	console.log( 'Building bundled source: ' + absoluteSourceDir + ' (gulp ' + buildTask + ')' );

	exec(
		'npx gulp ' + buildTask,
		Object.assign( {}, _execOptions, { cwd: absoluteSourceDir } ),
		function( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			if ( err ) {
				done( new Error(
					'Bundled source build failed in "' + absoluteSourceDir + '". ' +
					'Run "npm install" there if dependencies are missing, then retry.'
				) );
				return;
			}
			done();
		}
	);
}

/**
 * Materialize one bundled source into the export plugin folder.
 *
 * @param {string}   exportPluginRoot Absolute path to the exported plugin folder
 * @param {Object}   entry            Bundled source config entry
 * @param {Function} done             Callback with optional error
 */
function copySingleBundledSource( exportPluginRoot, entry, done ) {
	var targetSubdir = entry.targetSubdir;
	var sourceDir = entry.sourceDir;
	var required = entry.required !== false;
	var absoluteSourceDir = path.resolve( process.cwd(), sourceDir );
	var absoluteTargetDir = path.join( exportPluginRoot, targetSubdir );

	// Bail if required fields are missing
	if ( ! targetSubdir || ! sourceDir ) {
		done( new Error( 'pluginZipBundledSources entry requires targetSubdir and sourceDir.' ) );
		return;
	}

	// Bail if source directory is missing
	if ( ! fs.existsSync( absoluteSourceDir ) ) {
		if ( required ) {
			done( new Error( 'Bundled source directory missing: "' + absoluteSourceDir + '".' ) );
			return;
		}
		console.log( 'Skipping optional bundled source (missing): ' + absoluteSourceDir );
		done();
		return;
	}

	runBundledSourceBuild( entry, absoluteSourceDir, function( buildErr ) {
		if ( buildErr ) {
			done( buildErr );
			return;
		}

		// Remove any previous target folder or leftover symlink from the export tree.
		// Broken symlinks fail existsSync, so use lstat.
		try {
			var targetStat = fs.lstatSync( absoluteTargetDir );
			if ( targetStat.isSymbolicLink() ) {
				fs.unlinkSync( absoluteTargetDir );
			} else {
				del.sync( absoluteTargetDir, { force: true } );
			}
		} catch ( e ) {
			// Target does not exist yet
		}

		var rsyncSettings;
		if ( entry.rsyncSettingsFrom ) {
			rsyncSettings = loadPluginZipRsyncSettings( path.resolve( process.cwd(), entry.rsyncSettingsFrom ) );
		} else {
			rsyncSettings = getMergedPluginZipRsyncSettings();
		}

		// Merge optional per-entry include/exclude overrides
		rsyncSettings.include = ( rsyncSettings.include || [] ).concat( entry.include || [] );
		rsyncSettings.exclude = ( rsyncSettings.exclude || [] ).concat( entry.exclude || [] );

		// Resolve exclude-from relative to the source plugin root before building args
		if ( rsyncSettings.excludeFrom ) {
			rsyncSettings.excludeFrom = path.resolve( absoluteSourceDir, rsyncSettings.excludeFrom );
		}

		var rsyncArgs = getPluginZipRsyncArgsFromSettings( rsyncSettings );

		console.log( 'Bundling ' + sourceDir + ' → ' + targetSubdir );

		exec(
			'rsync -av --copy-links ' +
			( rsyncArgs ? rsyncArgs + ' ' : '' ) +
			JSON.stringify( absoluteSourceDir + '/' ) + ' ' +
			JSON.stringify( absoluteTargetDir ),
			_execOptions,
			function( err, stdout, stderr ) {
				console.log( stdout );
				console.log( stderr );
				if ( err ) {
					done( err );
					return;
				}
				done();
			}
		);
	} );
}

/**
 * Materialize all configured bundled sources into the export plugin folder.
 *
 * @param {string}   exportPluginRoot Absolute path to the exported plugin folder
 * @param {Function} done             Callback with optional error
 */
function copyAllBundledSources( exportPluginRoot, done ) {
	var entries = getBundledSourcesConfig();

	// Bail if no bundled sources are configured
	if ( ! entries.length ) {
		done();
		return;
	}

	var index = 0;

	function copyNext( err ) {
		if ( err ) {
			done( err );
			return;
		}

		// Bail when all entries are copied
		if ( index >= entries.length ) {
			done();
			return;
		}

		var entry = entries[ index ];
		index += 1;
		copySingleBundledSource( exportPluginRoot, entry, copyNext );
	}

	// Official releases re-check releaseBranch sources before materializing the zip
	verifyBundledSourcesForOfficialRelease( function( verifyErr ) {
		if ( verifyErr ) {
			done( verifyErr );
			return;
		}

		copyNext();
	} );
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
	// Bundled target subdirs are excluded here and materialized after the main copy.
	var rsyncArgs = getMainPluginZipRsyncArgs();
	exec(
		'rsync -av ' +
		( rsyncArgs ? rsyncArgs + ' ' : '' ) +
		'../' + _package.name + '/ ' +
		destinationFolder,
		_execOptions,
		function ( err, stdout, stderr ) {
			console.log( stdout );
			console.log( stderr );
			if ( err ) {
				// If an error occurred, make the Gulp task fail
				done( err );
				return;
			}

			copyAllBundledSources( destinationFolder, done );
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



// Function to get translation log file path for the current run.
// Uses the configured path with a timestamp so each run keeps its own log.
function getTranslationLogFile() {
	var configured;
	var parsed;
	var stamp;

	// Reuse the same path for the whole translate-po run
	if ( _translationLogFileForRun ) {
		return _translationLogFileForRun;
	}

	configured = _gulpSettings.translationLogFile || './logs/translations.log';
	parsed = path.parse( configured );
	// Filesystem-safe UTC timestamp, e.g. 2026-10-24T17-24-00-123Z
	stamp = new Date().toISOString().replace( /[:.]/g, '-' );

	_translationLogFileForRun = path.join( parsed.dir, parsed.name + '-' + stamp + parsed.ext );

	return _translationLogFileForRun;
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



// Function to start progress for one language (only one language is active at a time)
function beginTranslationProgress( targetLang, total ) {
	_translationProgressActiveLang = targetLang;
	_translationProgressTotal = total;
	updateTranslationProgress( 0, total, targetLang );
}

// Function to update translation progress for the active language only.
// In-place updates need a TTY; non-TTY hosts (some IDE terminals) only get the final line.
function updateTranslationProgress( completed, total, targetLang ) {
	var safeCompleted;
	var message;

	// Bail if this update is for a previous language still returning API callbacks
	if ( targetLang !== _translationProgressActiveLang ) {
		return;
	}

	// Bail if stdout cannot rewrite the current line reliably
	if ( ! process.stdout.isTTY ) {
		return;
	}

	safeCompleted = Math.min( completed, total );
	message = 'Translating (' + targetLang + '): ' + safeCompleted + ' of ' + total;
	readline.clearLine( process.stdout, 0 );
	readline.cursorTo( process.stdout, 0 );
	process.stdout.write( message );
}

// Function to commit the active language progress line, then clear the active language
function finishTranslationProgress( completed, total, targetLang ) {
	var lang = targetLang || _translationProgressActiveLang;
	var tot = ( typeof total === 'number' ) ? total : _translationProgressTotal;
	var doneCount = ( typeof completed === 'number' ) ? Math.min( completed, tot ) : tot;
	var message;

	if ( lang && tot > 0 ) {
		message = 'Translating (' + lang + '): ' + doneCount + ' of ' + tot;

		if ( process.stdout.isTTY ) {
			readline.clearLine( process.stdout, 0 );
			readline.cursorTo( process.stdout, 0 );
			process.stdout.write( message + '\n' );
		}
		else {
			process.stdout.write( message + '\n' );
		}
	}

	_translationProgressActiveLang = null;
	_translationProgressTotal = 0;
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
	var didFinish = false;

	function maybeComplete() {
		// Bail if the batch already finished (avoids starting the next language twice)
		if ( didFinish ) {
			return;
		}

		if ( completed >= items.length || ( _translationAborted && 0 === inFlight ) ) {
			didFinish = true;
			callback();
		}
	}

	function startNext() {
		while ( inFlight < limit && index < items.length && ! _translationAborted ) {
			(function( item ) {
				var settled = false;

				inFlight++;

				worker( item, function() {
					// Bail if this job already settled (API or worker called done twice)
					if ( settled ) {
						return;
					}

					settled = true;
					inFlight--;
					completed++;
					startNext();
					maybeComplete();
				} );
			})( items[ index++ ] );
		}

		maybeComplete();
	}

	startNext();
}



// Function to translate text using Deepl
function translateWithDeepl( text, targetLang, callback ) {
	var deeplApiConfig = getTranslationApiConfig( 'deepl' );
	var settled = false;

	/**
	 * Invoke the job callback once (promise chains can reject after a success path throws in the callback).
	 */
	function finish( err, translatedText ) {
		if ( settled ) {
			return;
		}

		settled = true;
		callback( err, translatedText );
	}

	if ( ! deeplApiConfig ) {
		finish( new Error( 'DeepL API is not configured' ) );
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
			finish( null, data.translations[ 0 ].text );
		} );
	} ).catch( function( error ) { finish( error ); } );
}

// Function to translate text using Google Translate
function translateWithGoogle( text, targetLang, callback ) {
	var googleApiConfig = getTranslationApiConfig( 'googleTranslate' );
	var settled = false;

	/**
	 * Invoke the job callback once (promise chains can reject after a success path throws in the callback).
	 */
	function finish( err, translatedText ) {
		if ( settled ) {
			return;
		}

		settled = true;
		callback( err, translatedText );
	}

	if ( ! googleApiConfig ) {
		finish( new Error( 'Google Translate API is not configured' ) );
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
			finish( null, data.data.translations[ 0 ].translatedText );
		} );
	} ).catch( function( error ) { finish( error ); } );
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
		var jobTotal = jobs.length;

		beginTranslationProgress( targetLang, jobTotal );

		runWithConcurrency( jobs, getTranslationConcurrency(), function( job, done ) {
			var settled = false;

			/**
			 * Settle this job once, even if the translation API calls back more than once.
			 */
			function settleJob( err, translatedText ) {
				if ( settled ) {
					return;
				}

				settled = true;
				completedCount++;

				if ( err ) {
					appendTranslationLog( 'Error translating "' + job.strToTranslate + '" to ' + job.apiTargetLang + ' with ' + ( job.useDeepl ? 'Deepl' : 'Google Translate' ) + ': ' + err.message );
					_translationErrors.push( { lang: job.apiTargetLang, error: err.message } );

					if ( ! _loggedFirstTranslationApiError ) {
						_loggedFirstTranslationApiError = true;
						// Break the in-place progress line so the warning is not mashed into the counts
						if ( process.stdout.isTTY ) {
							process.stdout.write( '\n' );
						}
						console.warn( 'Warning: Translation API error. Further errors are written to the translation log. The package build will continue.' );
					}

					abortTranslationIfErrorLimitReached();

					if ( _translationAborted && _translationErrorLimit === _translationErrors.length ) {
						addTranslationWarning( 'Translation stopped after ' + _translationErrorLimit + ' API errors. Remaining strings were skipped.' );
					}
				}
				else if ( ! err && typeof translatedText === 'string' ) {
					translations[ job.context ][ job.msgid ].msgstr[ job.msgstrIndex ] = translatedText;

					var indexString = job.msgstrIndex > 0 ? '(' + job.msgstrIndex + ')' : '';
					appendTranslationLog( 'Translated: ' + targetLang + ' ' + job.translationSource + ' ' + indexString + ' ' + job.strToTranslate + ' -> ' + translatedText );
				}

				updateTranslationProgress( completedCount, jobTotal, targetLang );
				done();
			}

			if ( _translationAborted ) {
				return settleJob();
			}

			var translateFunction = job.useDeepl ? translateWithDeepl : translateWithGoogle;

			translateFunction( job.strToTranslate, job.apiTargetLang, function( err, translatedText ) {
				settleJob( err, translatedText );
			} );
		}, function() {
			finishTranslationProgress( completedCount, jobTotal, targetLang );
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
// Pre-release versions: skipped only when run from `npm version` (not manual gulp).
gulp.task( 'translate-po', function ( done ) {
	if ( shouldSkipPreReleaseTranslations() ) {
		console.log( 'Skipping translate-po for pre-release version ' + _package.version + ' during npm version' );
		return done();
	}

	_translationErrors = [];
	_translationWarnings = [];
	_translationAborted = false;
	_loggedFirstTranslationApiError = false;
	_translationLogFileForRun = null;
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
// Update translations.
// Pre-release versions: skipped only when run from `npm version` (not manual gulp).
gulp.task( 'update-translations', function( done ) {
	if ( shouldSkipPreReleaseTranslations() ) {
		console.log( 'Skipping update-translations for pre-release version ' + _package.version + ' during npm version' );
		return done();
	}

	return gulp.series( 'translate-po', function ( seriesDone ) {
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

		return gulp.series( tasks )( seriesDone );
	} )( done );
} );
	

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
// gulp translate
// Generate POT file and update translations.
// Auto-translation warnings are summarized again at the end of the build run.
// Pre-release versions: skipped only when run from `npm version` (manual gulp still runs).
gulp.task( 'translate', function( done ) {
	if ( shouldSkipPreReleaseTranslations() ) {
		console.log( 'Skipping translations for pre-release version ' + _package.version + ' during npm version' );
		return done();
	}

	return gulp.series( 'generate-pot', 'update-translations', function( seriesDone ) {
		printTranslationWarningSummary();
		seriesDone();
	} )( done );
} );



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



/**
 * Derive watch globs from a source src list.
 *
 * Compat entry globs become recursive under that compat folder only.
 * Top-level ./sass/*.scss watches entry files plus shared trees
 * (partials, abstract, admin, lib) — not compat/ — so theme edits do not
 * rebuild main and shared partials do not rebuild all themeCompat files.
 * Paths that already use a recursive glob or have no wildcard stay as-is.
 */
function srcGlobsToWatchGlobs( src ) {
	var patterns = Array.isArray( src ) ? src : [ src ];
	var result = [];
	var i;
	var pattern;
	var topLevelSass;
	var ext;

	for ( i = 0; i < patterns.length; i++ ) {
		pattern = patterns[ i ];

		if ( -1 !== pattern.indexOf( '**' ) || -1 === pattern.indexOf( '*' ) ) {
			result.push( pattern );
			continue;
		}

		// Top-level plugin Sass entries: do not watch the whole sass/ tree
		topLevelSass = pattern.match( /^\.\/sass\/\*(\.[^/]+)$/ );
		if ( topLevelSass ) {
			ext = topLevelSass[ 1 ];
			result.push( './sass/*' + ext );
			result.push( './sass/partials/**/*' + ext );
			result.push( './sass/abstract/**/*' + ext );
			result.push( './sass/admin/**/*' + ext );
			result.push( './sass/lib/**/*' + ext );
			continue;
		}

		result.push( pattern.replace( /\/\*(\.[^/]+)$/, '/**/*$1' ) );
	}

	return result;
}

/**
 * Whether a relative path matches any of the given minimatch patterns.
 */
function pathMatchesAnyGlob( relativePath, patterns ) {
	var i;
	var normalized = relativePath.split( path.sep ).join( '/' );

	if ( 0 === normalized.indexOf( './' ) ) {
		normalized = normalized.slice( 2 );
	}

	for ( i = 0; i < patterns.length; i++ ) {
		if ( minimatch( normalized, patterns[ i ].replace( /^\.\//, '' ), { dot: true } ) ) {
			return true;
		}
		if ( minimatch( './' + normalized, patterns[ i ], { dot: true } ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether the changed path is a Sass partial (_*.scss).
 */
function isSassPartialPath( filePath ) {
	return /^_.*\.scss$/i.test( path.basename( filePath ) );
}

/**
 * Whether the changed path is a concrete entry under the source src globs.
 */
function isSourceEntryFile( filePath, srcGlobs ) {
	var relativePath = path.relative( process.cwd(), filePath ).split( path.sep ).join( '/' );
	var entries;
	var i;

	if ( isSassPartialPath( filePath ) ) {
		return false;
	}

	entries = expandEntryFiles( srcGlobs );
	for ( i = 0; i < entries.length; i++ ) {
		if ( path.resolve( entries[ i ] ) === path.resolve( filePath ) ) {
			return true;
		}
		if ( entries[ i ].split( path.sep ).join( '/' ) === relativePath ) {
			return true;
		}
	}

	return false;
}

/**
 * Debounced runners keyed by debounce id.
 */
var _watchDebounceTimers = {};
var _watchDebounceMs = 150;

function debounceWatchAction( debounceId, action ) {
	if ( _watchDebounceTimers[ debounceId ] ) {
		clearTimeout( _watchDebounceTimers[ debounceId ] );
	}

	_watchDebounceTimers[ debounceId ] = setTimeout( function() {
		delete _watchDebounceTimers[ debounceId ];
		action();
	}, _watchDebounceMs );
}

/**
 * Log watch compile start/finish so the terminal matches real frontend readiness.
 */
function logWatchCompile( phase, label, relativePath ) {
	var suffix = relativePath ? ( ' ← ' + relativePath ) : '';
	console.log( '[fc-gulp] ' + phase + ' ' + label + suffix );
}

/**
 * Run set-assets-version then the given named compile tasks (parallel when several).
 */
function runNamedDevCompile( taskNames, relativePath, done ) {
	var runCompile;
	var label = taskNames.join( ', ' );

	if ( 0 === taskNames.length ) {
		done();
		return;
	}

	logWatchCompile( 'start', label, relativePath );

	runCompile = 1 === taskNames.length
		? gulp.series( 'set-assets-version', taskNames[ 0 ] )
		: gulp.series( 'set-assets-version', gulp.parallel( taskNames ) );

	runCompile( function( err ) {
		if ( err ) {
			logWatchCompile( 'error', label, relativePath );
			done( err );
			return;
		}
		logWatchCompile( 'done', label, relativePath );
		done();
	} );
}

/**
 * Register asset watches from sassSources / jsSources.
 */
function registerAssetSourceWatches() {
	var sassSources = getNormalizedSources( _gulpSettings.sassSources, _defaultSassDest );
	var jsSources = getNormalizedSources( _gulpSettings.jsSources, _defaultJsDest );
	var sassWatchMeta = [];
	var i;
	var entry;
	var watchGlobs;
	var allSassWatchGlobs = [];

	for ( i = 0; i < sassSources.length; i++ ) {
		entry = sassSources[ i ];
		watchGlobs = srcGlobsToWatchGlobs( entry.src );
		sassWatchMeta.push( { entry: entry, watchGlobs: watchGlobs } );
		allSassWatchGlobs = allSassWatchGlobs.concat( watchGlobs );
	}

	if ( allSassWatchGlobs.length > 0 ) {
		gulp.watch( allSassWatchGlobs ).on( 'change', function( filePath ) {
			var relativePath = path.relative( process.cwd(), filePath ).split( path.sep ).join( '/' );
			var matchedKeys = [];
			var j;
			var meta;
			var isPartial = isSassPartialPath( filePath );
			var singleEntryMeta = null;
			var taskNames;

			for ( j = 0; j < sassWatchMeta.length; j++ ) {
				meta = sassWatchMeta[ j ];
				if ( pathMatchesAnyGlob( relativePath, meta.watchGlobs ) ) {
					matchedKeys.push( meta );
				}
			}

			// Do not rebuild every Sass group on partials — only keys whose
			// watch globs cover this path (e.g. sass/partials → main only).

			if ( 0 === matchedKeys.length ) {
				return;
			}

			debounceWatchAction( 'sass:' + matchedKeys.map( function( m ) { return m.entry.key; } ).join( ',' ), function() {
				// Single non-partial entry file: compile that file only
				if ( ! isPartial && 1 === matchedKeys.length && isSourceEntryFile( filePath, matchedKeys[ 0 ].entry.src ) ) {
					singleEntryMeta = matchedKeys[ 0 ];
					logWatchCompile( 'start', 'compile-css-dev:' + singleEntryMeta.entry.key + ' (entry)', relativePath );
					gulp.series( 'set-assets-version', function compileCssDevEntry( done ) {
						whenStreamDone( processCssDev( filePath, singleEntryMeta.entry.dest ), function( err ) {
							if ( ! err ) {
								logWatchCompile( 'done', 'compile-css-dev:' + singleEntryMeta.entry.key + ' (entry)', relativePath );
							}
							done( err );
						} );
					} )();
					return;
				}

				taskNames = matchedKeys.map( function( metaItem ) {
					return 'compile-css-dev:' + metaItem.entry.key;
				} );

				runNamedDevCompile( taskNames, relativePath, function() {} );
			} );
		} );
	}

	for ( i = 0; i < jsSources.length; i++ ) {
		( function( jsEntry ) {
			var jsWatchGlobs = srcGlobsToWatchGlobs( jsEntry.src );
			var jsDevTaskName = 'compile-js-dev:' + jsEntry.key;

			gulp.watch( jsWatchGlobs ).on( 'change', function( filePath ) {
				var relativePath = path.relative( process.cwd(), filePath ).split( path.sep ).join( '/' );

				debounceWatchAction( 'js:' + jsEntry.key + ':' + filePath, function() {
					if ( isSourceEntryFile( filePath, jsEntry.src ) ) {
						logWatchCompile( 'start', jsDevTaskName + ' (entry)', relativePath );
						gulp.series( 'set-assets-version', function compileJsDevEntry( done ) {
							whenStreamDone( processJsDev( filePath, jsEntry.dest ), function( err ) {
								if ( ! err ) {
									logWatchCompile( 'done', jsDevTaskName + ' (entry)', relativePath );
								}
								done( err );
							} );
						} )();
						return;
					}

					runNamedDevCompile( [ jsDevTaskName ], relativePath, function() {} );
				} );
			} );
		} )( jsSources[ i ] );
	}
}

// Run:
// gulp watch
// Starts watcher. Asset watches come from sassSources / jsSources (dev builds).
// gulp-settings.watch remains for non-asset tasks (e.g. blocks → npm-run-build).
gulp.task( 'watch', gulp.series( function( done ) {
	registerAssetSourceWatches();

	if ( _gulpSettings.watch && Array.isArray( _gulpSettings.watch ) ) {
		_gulpSettings.watch.forEach( function ( item ) {
			var watchPattern = item[ 0 ];
			var runTasks = item[ 1 ];
			gulp.watch( watchPattern, gulp.series( runTasks ) );
		} );
	}

	done();
} ) );



// Run:
// gulp watch-reload
// Starts watcher with browser-sync. Browser-sync reloads page automatically on your browser
gulp.task( 'watch-reload', gulp.series( gulp.parallel( 'build-js-dev', 'build-css-dev' ), gulp.series( 'watch', 'browser-sync' ) ) );



// Run:
// gulp
// Defines gulp default task
gulp.task( 'default', gulp.series( _defaultAction ) );
