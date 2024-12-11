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



// Defining settings
var _gulpSettings = loadJsonFile.sync( 'gulp-settings.json' );
var _gulpSettingsLocal = {};
var _package = {},
	_assetsVersion = '';
var _defaultAction = _gulpSettingsLocal.defaultAction || _gulpSettings.defaultAction || 'watch';



// Try loading local gulpfile settings
try{ _gulpSettingsLocal = loadJsonFile.sync( 'gulp-settings.local.json' ); }
catch ( err ) {}



// Run:
// gulp update-ver
// Update the project version
gulp.task( 'update-ver', gulp.series( function( done ) {
	// Load package.json
	_package = loadJsonFile.sync( 'package.json' );
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
	// Load package.json
	_package = loadJsonFile.sync( 'package.json' );

	// Define assets version
	_assetsVersion = '-' + _package.version.replace( /\./gi, '' );

	// Get current date
	var today = new Date();

	// Only update readme.txt for full release versions
	if ( _package.version.indexOf( 'beta' ) < 0 && _package.version.indexOf( 'dev' ) < 0 ) {
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
		.pipe(sass())
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
		.pipe(sass())
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

	exec('npm run build', function (err, stdout, stderr) {
		console.log( stdout );
		console.log( stderr );
		if ( err ) {
			// If an error occurred, make the Gulp task fail
			done( err );
		}
	});

	done();
} ) );



// Run:
// gulp build
// Build css and js assets
gulp.task( 'build', gulp.series( gulp.parallel( 'build-js', 'build-css', 'npm-run-build' ) ) );



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

	exec( 'rsync -av --exclude-from=.gitignore ../' + _package.name + ' ' + _gulpSettingsLocal.pluginZipPath, function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		if ( err ) {
			// If an error occurred, make the Gulp task fail
			done( err );
		}
	} );

	done();
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
	exec( 'cd ' + _gulpSettingsLocal.pluginZipPath + ' && zip -r ' + destinationFile + ' ' + _package.name, function ( err, stdout, stderr ) {
		console.log( stdout );
		console.log( stderr );
		if ( err ) {
			// If an error occurred, make the Gulp task fail
			done( err );
		}
	});

	done();
} ) );



// Run:
// gulp copy-updater
// Copy the theme/plugin updater class into the project
gulp.task( 'copy-updater', gulp.series( function( done ) {
	if ( _gulpSettings.copyUpdater ) {
		del.sync( _gulpSettings.copyUpdater.destination );
		
		gulp.src( _gulpSettings.copyUpdater.source )
		.pipe( gulp.dest( _gulpSettings.copyUpdater.destination ) );
	}

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
