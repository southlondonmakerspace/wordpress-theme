'use strict';

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')();

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

gulp.task('watch', function() {
	gulp.watch('./static/less/**', ['less']);
});