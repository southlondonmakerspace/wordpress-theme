'use strict';

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')();

var del = require('del');

gulp.task('less', function() {
	gulp.src('./static/less/main.less')
		.pipe(plugins.less())
		.pipe(plugins.autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(plugins.cssnano())
		.pipe(gulp.dest('./static/css/'))
});

gulp.task('build-css', function() {
	gulp.src('./static/css/main.css')
		.pipe(plugins.rev())
		.pipe(gulp.dest('./static/dist'));
});

gulp.task('clean', function() {
	del('./static/dist/**');
});

gulp.task('build',['clean','build-css']);

gulp.task('watch', function() {
	gulp.watch('./static/less/**', ['less']);
});