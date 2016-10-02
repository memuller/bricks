'use strict'

const $           = require('gulp-load-plugins')(),
      argv        = require('yargs').argv,
      gulp        = require('gulp'),
      gutil       = require('gulp-util'),
      exit        = require('gulp-exit'),
      fs          = require('fs'),
      source      = require('vinyl-source-stream'),
      buffer      = require('vinyl-buffer'),
      sourcemaps  = require('gulp-sourcemaps'),
      browserify  = require('browserify'),
      babelify    = require('babelify'),
      watchify    = require('watchify'),
      sass        = require('gulp-sass'),
      concat      = require('gulp-concat'),
      uglify      = require('gulp-uglify'),
      cssnano     = require('gulp-cssnano')

const Bricks = {
  abspath: './',
  path: (file) => {
    return Bricks.abspath + file
  },
  task: (name) => {
    return require('./js/tasks/'+name)(gulp, $, Bricks)
  },
}

gulp.task('version', () => Bricks.task('version') )

gulp.task('sass', function(){
	return gulp.src('./assets/scss/main.scss')
		.pipe(sass().on('error', gutil.log))
		.pipe(concat('bundle.css'))
    .pipe(cssnano().on('error', gutil.log))
		.pipe(gulp.dest('./assets/dist/'))
})

const bundler = watchify(
  browserify({
    entries: ['./assets/js/index.js']
  })
  .transform(babelify, {
    presets: ['es2015']
  })
)

const rebundle = () => {
  return bundler.bundle()
    .on('error', gutil.log.bind(gutil, 'Browserify Error'))
    .pipe(source('bundle.js'))
    .pipe(buffer())
    .pipe(uglify())
    .pipe(sourcemaps.init({loadMaps: true}))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./assets/dist'))
}

bundler.on('update', rebundle)
bundler.on('log', gutil.log)

gulp.task('js', () => rebundle().pipe(exit()) )

gulp.task('watch', () => {
  gulp.watch('./assets/scss/*.scss', ['sass'])
  rebundle()
})
