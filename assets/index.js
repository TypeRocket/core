exports.compileTypeRocketAssets = function( public, use_js, use_scss, move_files ) {
	if(typeof use_js === 'undefined') {
		use_js = [
			'http.js',
			'booyah.js',
			'typerocket.js',
			'items.js',
			'media.js',
			'matrix.js',
			'builder.js',
			'seo.js',
			'link.js',
			'dev.js'
		];
	}

	if(typeof move_files === 'undefined') {
		move_files = true;
	}

	if(typeof use_scss === 'undefined') {
		use_scss = [
			'typerocket.scss',
			'alert.scss',
			'builder.scss',
			'dev.scss',
			'forms.scss',
			'icon-vars.scss',
			'icons.scss',
			'items.scss',
			'media.scss',
			'redactor.scss',
			'search.scss',
			'seo.scss',
			'style-guide.scss',
			'tabs.scss',
			'ui-datepicker.scss',
		];
	}
	var typerocket_elixir = require('laravel-elixir');
	var gulp = require('gulp');

	var originalAssets = typerocket_elixir.config.assetsPath;
	var originalPublic = typerocket_elixir.config.publicPath;
	var originalMaps = typerocket_elixir.config.sourcemaps;

	typerocket_elixir.config.assetsPath = __dirname + '/assets';
	typerocket_elixir.config.publicPath = public;
	typerocket_elixir.config.sourcemaps = false;

	typerocket_elixir(function(mix) {
		// Directories
		var assets = typerocket_elixir.config.publicPath;
		var resource = typerocket_elixir.config.assetsPath;

		// TypeRocket Core Assets
		mix.scripts(use_js, assets + '/typerocket/js/core.js' );
		mix.scripts(['global.js'], assets + '/typerocket/js/global.js' );
		mix.sass(use_scss, assets + '/typerocket/css/core.css' );

		// Move Fonts and JS
		if(move_files) {
			gulp.src( resource + '/fonts/*.{ttf,woff,eof,eot,svg}' )
			.pipe( gulp.dest( assets + '/typerocket/fonts') );

			gulp.src( resource + '/lib/redactor.min.js' )
			.pipe( gulp.dest( assets + '/typerocket/js' ) );
		}

		// Reset
		typerocket_elixir.config.assetsPath = originalAssets;
		typerocket_elixir.config.publicPath = originalPublic;
		typerocket_elixir.config.sourcemaps = originalMaps;
	});
};