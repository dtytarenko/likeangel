const gulpIf     = require('gulp-if');
const plumber    = require('gulp-plumber');
const terser     = require('gulp-terser');
const rename     = require('gulp-rename');

const isProd = process.env.NODE_ENV === 'production';

function styles() {
  return gulp.src(paths.styles.src)
    .pipe(plumber())
    .pipe(gulpIf(!isProd, sourcemaps.init()))
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(gulpIf(isProd, cleanCSS({level:2})))
    .pipe(gulpIf(!isProd, sourcemaps.write('.')))
    .pipe(rename({suffix: isProd ? '.min' : ''}))
    .pipe(gulp.dest(paths.styles.dest))
    .pipe(browserSync.stream());
}

function scripts() {
  return gulp.src(paths.scripts.src)
    .pipe(plumber())
    .pipe(gulpIf(!isProd, sourcemaps.init()))
    .pipe(gulpIf(isProd, terser()))
    .pipe(gulpIf(!isProd, sourcemaps.write('.')))
    .pipe(rename({suffix: isProd ? '.min' : ''}))
    .pipe(gulp.dest(paths.scripts.dest))
    .pipe(browserSync.stream());
}
