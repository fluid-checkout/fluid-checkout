// Defining settings
var settings = {
    pkg: {},
    assetsVersion: '',
    nodePath: './node_modules/',
    jsPath: './js-src/'
  };
  
  // Defining requirements
  var gulp = require('gulp');
  var gulpSequence = require('gulp-sequence')
  var plumber = require('gulp-plumber');
  var sass = require('gulp-sass');
  var watch = require('gulp-watch');
  var cssnano = require('gulp-cssnano');
  var rename = require('gulp-rename');
  var concat = require('gulp-concat');
  var uglify = require('gulp-uglify');
  var ignore = require('gulp-ignore');
  var replace = require('gulp-replace');
  var rimraf = require('gulp-rimraf');
  var loadJsonFile = require('load-json-file');
  var autoprefixer = require('gulp-autoprefixer');
  var sourcemaps = require('gulp-sourcemaps');
  
  // Run: 
  // gulp update-ver
  // Starts watcher. Watcher runs appropriate tasks on file changes
  gulp.task('update-ver', function(){
        loadJsonFile('package.json').then(function (json) {
          settings.pkg = json;
          settings.assetsVersion = '-' + settings.pkg.version.replace( /\./gi, '' );
  
          gulp.src(['./woocommerce-fluid-checkout.php'])
              // See http://mdn.io/string.replace#Specifying_a_string_as_a_parameter
              .pipe(replace(/Version: (.)*/g, 'Version: ' + settings.pkg.version ))
              .pipe(replace(/	const VERSION = (.)*/g, '	const VERSION = \'' + settings.pkg.version + '\';' ))
              .pipe(replace(/	const ASSET_VERSION = (.)*/g, '	const ASSET_VERSION = \'-' + settings.pkg.version.replace(/(\.)/g, '') + '\';' ))
              .pipe(gulp.dest('./'));
        });
  });
  
  // Run: 
  // gulp build-css
  // Builds css from scss and apply other changes.
  gulp.task( 'build-css', ['update-ver', 'cleancss'], function( callback ) {
      return gulp.src('./sass/*.scss')
          .pipe(plumber())
          .pipe(sass())
          .pipe(autoprefixer({ cascade: false }))
          .pipe(gulp.dest('./css/')) // save .css
          .pipe(cssnano( { zindex:false, reduceIdents: false, discardComments: {removeAll: true}, discardUnused: {fontFace: false} } ))
          .pipe(rename({suffix: settings.assetsVersion + '.min'}))
          .pipe(gulp.dest('./css/')) // save .min.css
  });
  
  gulp.task('cleancss', function() {
      return gulp.src(['./css/*.css','./css/maps/*.map'], { read: false }) // much faster 
          .pipe(rimraf());
  });
  
  gulp.task('cleanjs', function() {
      return gulp.src(['./js/*.js','./js/maps/*.map'], { read: false }) // much faster 
          .pipe(rimraf());
  });
  
  // Run: 
  // gulp watch
  // Starts watcher. Watcher runs appropriate tasks on file changes
  gulp.task('watch', function () {
      gulp.watch('./sass/**/*.scss', ['build-css']);
      gulp.watch('./js-src/**/*.js', ['build-js']);
      gulp.watch('./package.json', ['build-js', 'build-css']);
  });
  
  // Run: 
  // gulp
  // Defines gulp default task
  gulp.task('default', ['watch'], function () { });
  
  // Run: 
  // gulp build-scripts. 
  // Uglifies and concat all JS files into one
  gulp.task('build-js', ['build-scripts'], function () { });
  gulp.task('build-scripts', ['update-ver','cleanjs'], function() {
  
      // LIBRARIES from node_modules
      // copy without modifications
      gulp.src([
          settings.nodePath + 'intl-tel-input/build/js/intlTelInput.min.js',
        //   settings.nodePath + 'atomicjs/dist/atomic.min.js',
        //   settings.nodePath + 'Modals/dist/js/modals.min.js',
        //   settings.nodePath + 'tocca/Tocca.min.js',
      ])
      .pipe(gulp.dest('./js/lib/'));
      
      // LIBRARIES INTL-TEL-UTILS.JS (copied, but needs renaming)
      gulp.src([
          settings.nodePath + 'intl-tel-input/build/js/utils.js',
      ])
      .pipe(concat('intl-tel-utils.js'))
      .pipe(rename({suffix: '.min'}))
      .pipe(gulp.dest('./js/lib/')); // save .min.js
  
  
      
      // LIBRARIES from theme folder
      gulp.src([
          settings.jsPath + 'lib/*.js',
      ])
      .pipe(sourcemaps.init())  
      .pipe(uglify())
      .pipe(rename({suffix: '.min'}))
      .pipe(sourcemaps.write('maps'))
      .pipe(gulp.dest('./js/lib/')); // save .min.js
  
  
      
  
      // REQUIRE-BUNDLE.JS
      gulp.src([
          settings.nodePath + 'loadjs/dist/loadjs.js',
          settings.jsPath + 'shared/require-bundle.js',
      ])
      .pipe(sourcemaps.init())  
      .pipe(concat('require-bundle.js'))
      .pipe(uglify())
      .pipe(rename({suffix: '.min'}))
      .pipe(sourcemaps.write('maps'))
      .pipe(gulp.dest('./js/')); // save .min.js
  
  
  
      // BUNDLES.JS
      gulp.src([
          settings.jsPath + 'shared/bundles.js',
      ])
      .pipe(sourcemaps.init())
      .pipe(concat('bundles.js'))
      .pipe(uglify())
      .pipe(rename({suffix: settings.assetsVersion + '.min'}))
      .pipe(sourcemaps.write('maps'))
      .pipe(gulp.dest('./js/')); // save .min.js
  
    
    
      // THEME.JS
      gulp.src([
          // Polyfills
          settings.jsPath + 'skip-link-focus-fix.js',
          settings.jsPath + 'back-to-top.js',
      ])
      .pipe(sourcemaps.init())
      .pipe(concat('theme.js'))
      .pipe(uglify())
      .pipe(rename({suffix: settings.assetsVersion + '.min'}))
      .pipe(sourcemaps.write('maps'))
      .pipe(gulp.dest('./js/')); // save .min.js
    
  
  
      // THEME FILES
      gulp.src([
          settings.jsPath + '*.js',
      ])
      .pipe(sourcemaps.init())
      .pipe(uglify())
      .pipe(rename({suffix: settings.assetsVersion + '.min'}))
      .pipe(sourcemaps.write('maps'))
      .pipe(gulp.dest('./js/')); // save .min.js
  
    
  });
  
  

// Run: 
// gulp copy-updater
// Copy fluidweb-updater to inc/vendor
gulp.task( 'copy-updater', function() {
    gulp.src(['./inc/vendor/fluidweb-updater/**/*'], { read: false }) // much faster
    .pipe(rimraf());

    gulp.src( settings.nodePath + 'fluidweb-updater/**/*' )
        .pipe( gulp.dest( './inc/vendor/fluidweb-updater' ) );
});
