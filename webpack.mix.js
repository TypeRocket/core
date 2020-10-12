const mix = require('laravel-mix');

/*
|--------------------------------------------------------------------------
| TypeRocket Core Assets
|--------------------------------------------------------------------------
|
| When there are updates to the TypeRocket core assets you must also
| compile those assets.
|
*/

let pub = 'assets/dist';

// Compile
mix.setPublicPath(pub)
    .options({ processCssUrls: false })
    .js('assets/js/core.js', 'js/core.js')
    .react('assets/js/builder.ext.jsx', 'js')
    .sass('assets/sass/core.scss', 'css/core.css');

// Babel
mix.babel('assets/js/global.js', pub + '/js/global.js');

// Version
mix.version();