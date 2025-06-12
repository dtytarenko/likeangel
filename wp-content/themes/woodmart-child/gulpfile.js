/*******************************
 *  gulpfile.js (Gulp ^5)      *
 *******************************/

const gulp         = require('gulp');
const gulpIf       = require('gulp-if');
const plumber      = require('gulp-plumber');
const sass         = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer').default;
const cleanCSS     = require('gulp-clean-css');
const sourcemaps   = require('gulp-sourcemaps');
const terser       = require('gulp-terser');
const rename       = require('gulp-rename');
const browserSync  = require('browser-sync').create();

/* ──────────────────────────── */

const paths = {
  styles: {
    src: 'src/scss/**/*.scss',
    dest: './'           // style.css / style.min.css кладемо в корінь теми
  },
  scripts: {
    src: 'src/js/**/*.js',
    dest: 'js/'          // готові .js лежатимуть у /js
  }
};

const isProd = process.env.NODE_ENV === 'production';

/* ---------- STYLES ---------- */
function styles() {
  return gulp.src(paths.styles.src)
    .pipe(plumber())
    .pipe(gulpIf(!isProd, sourcemaps.init()))
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(gulpIf(isProd, cleanCSS({ level: 2 })))
    .pipe(gulpIf(!isProd, sourcemaps.write('.')))
    .pipe(rename({ suffix: isProd ? '.min' : '' }))
    .pipe(gulp.dest(paths.styles.dest))
    .pipe(browserSync.stream());
}

/* ---------- SCRIPTS ---------- */
function scripts() {
  return gulp.src(paths.scripts.src, { allowEmpty: true })
    .pipe(plumber())
    .pipe(gulpIf(!isProd, sourcemaps.init()))
    .pipe(gulpIf(isProd, terser()))
    .pipe(gulpIf(!isProd, sourcemaps.write('.')))
    .pipe(rename({ suffix: isProd ? '.min' : '' }))
    .pipe(gulp.dest(paths.scripts.dest))
    .pipe(browserSync.stream());
}

/* ---------- WATCH + BROWSERSYNC ---------- */
function watchFiles() {
  browserSync.init({
    proxy: 'http://localhost', // змінити, якщо WP крутиться на іншому URL/порту
    notify: false
  });

  gulp.watch(paths.styles.src, styles);
  gulp.watch(paths.scripts.src, scripts);
  gulp.watch('**/*.php').on('change', browserSync.reload);
}

/* ---------- TASK REGISTRATION ---------- */
const build = gulp.parallel(styles, scripts);   // одноразова збірка
const dev   = gulp.series(build, watchFiles);   // build + live-reload

exports.styles  = styles;
exports.scripts = scripts;
exports.build   = build;
exports.dev     = dev;
exports.default = dev;                          // «gulp» без параметрів = dev-режим
