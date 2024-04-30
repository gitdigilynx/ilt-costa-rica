// Config
var project_name = 'ilt-costa-rica';
var theme_name = 'ilt-costa-rica';
var wordpress = false;
var localhost = false;
var custom_path_theme = 'src/Wicrew/CoreBundle/Resources/public/stylesheet/'; // If project not wordpress, Please add path
var custom_host_path = 'costa.test';
var pathInput = 'src/Wicrew/CoreBundle/Resources/public/stylesheet/';
var pathOutput = 'public/bundles/wicrewcore/stylesheet/';

// ----------------------------------------------------------
// ----------------------------------------------------------
// ----------------------------------------------------------
// ----------------------------------------------------------
// ----------------------------------------------------------

// if ( wordpress ) {
//     var path = 'wp-content/themes/' + theme_name + '/';
// } else {
//     var path = custom_path_theme;
// }

// ----------------------------------------------------------

// if ( localhost ) {
//     console.log('slkdvsdv');
//     var localhost_path = 'http://localhost/' + project_name;
// } else {
//     var localhost_path = custom_host_path;
// }

// ----------------------------------------------------------

var gulp = require('gulp');
var sass = require('gulp-sass');
var browserSync = require('browser-sync');
var plumber = require('gulp-plumber');
var tinypng = require('gulp-tinypng-compress');
var gettext = require('gulp-gettext');
var autoprefixer = require('gulp-autoprefixer');
var gcmq = require('gulp-group-css-media-queries');
var cleanCSS = require('gulp-clean-css');
var rename = require('gulp-rename');
// var webp = require('gulp-webp');

var browser_support = [
    'ie >= 9',
    'ie_mob >= 10',
    'ff >= 31',
    'chrome >= 36',
    'safari >= 6',
    'ios >= 6',
    'android >= 4'
];

var autoprefixerOptions = {
    browsers: browser_support,
    cascade: false
};

gulp.task('default', ['sass', 'browser-sync'], function() {
    // gulp.watch([ path + '*.php'], browserSync.reload);
    // gulp.watch([ path + '*.html'], browserSync.reload);
    gulp.watch(path +'scss/*.scss', ['sass']);
    // gulp.watch(path +'languages/*.po', ['gettext']);
});

gulp.task('sass', function () {
    return gulp.src( pathInput + 'scss/style.scss')
        .pipe(plumber())
        .pipe(sass({outputStyle: 'compressed'}))
        .on('error', swallowError)
        .pipe(autoprefixer(autoprefixerOptions))
        // .pipe(gcmq())
        .pipe(cleanCSS({compatibility: '*'}))
        .pipe(rename('style.min.css'))
        .pipe(gulp.dest( pathOutput ))
    // .pipe(browserSync.stream());
});

gulp.task('browser-sync', function() {
    browserSync({
        proxy: localhost_path
        // server: {
        //     baseDir: "./"
        // }
    });
});

gulp.task('tinypng', function () {
    gulp.src( path + 'assets/**/*.{png,jpg,jpeg}')
        .pipe(tinypng({
            key: 'Da6BVlVq37wH0hb7Y0werd9S7O6yIKfH',
            sigFile: path + 'assets/.tinypng-sigs',
            log: true,
            sameDest: true
        }))
        .pipe(gulp.dest( path + 'assets'));
});

gulp.task('gettext', function() {
    gulp.src( path + 'languages/*.po' )
        .pipe(gettext())
        .pipe(gulp.dest( path + 'languages' ))
    ;
});

// gulp.task('gcmq', function () {
//     gulp.src( path + 'assets/css/style.css' )
//         .pipe(gcmq())
//         .pipe(gulp.dest( path + 'assets/css/' ));
// });

// gulp.task('webp', () =>
// gulp.task('default', () =>
//     gulp.src( path + 'assets/**/*.{png,jpg,jpeg}')
//         .pipe(webp())
//         .pipe(gulp.dest( path + 'assets'))
// );

function swallowError (error) {

    // If you want details of the error in the console
    console.log(error.toString());

    this.emit('end');
}
