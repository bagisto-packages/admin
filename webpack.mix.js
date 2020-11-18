const mix = require("laravel-mix");

require("laravel-mix-merge-manifest");

const publicPath = mix.inProduction() ? 'publishable/assets' : "../../../public/vendor/packages/admin/assets";

mix.disableNotifications();
mix.setPublicPath(publicPath).mergeManifest();

mix
    .js(__dirname + "/src/Resources/assets/js/app.js", "js/admin.js")
    .sass(__dirname + "/src/Resources/assets/sass/app.scss", "css/admin.css")
    .copy(__dirname + "/src/Resources/assets/js/tinyMCE", publicPath + "/js/tinyMCE")
    .options({
        processCssUrls: false
    });

if (!mix.inProduction()) {
    mix.sourceMaps();
}

if (mix.inProduction()) {
    mix.version();
}
