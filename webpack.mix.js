let mix = require('laravel-mix');
const path = require("path");
const exec = require('child_process').exec;
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

mix.webpackConfig({
    module: {
        rules: [
            {
                enforce: 'pre',
                test: /\.(js|vue)$/,
                loader: 'eslint-loader',
                exclude: /node_modules/
            }
        ]
    },
    output: {
        publicPath: Mix.isUsing('hmr') ? '/' : '/wp-content/plugins/fluent-crm/assets/',
        chunkFilename: 'admin/js/[name].js'
    },
    plugins: [
        new BundleAnalyzerPlugin({
            analyzerMode: 'static',
            openAnalyzer: mix.inProduction()
        })
    ],
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: {
            '@Pieces': path.resolve(__dirname, 'resources/admin/Pieces'),
            '@': path.resolve(__dirname, 'resources/admin')
        }
    }
});

mix.options({ processCssUrls: false });

mix
    .js('resources/admin/boot.js', 'admin/js/boot.js').vue({ version: 2 })
    .js('resources/admin/start.js', 'admin/js/start.js').vue({ version: 2 })
    .js('resources/admin/global-search.js', 'admin/js/global-search.js')
    .js('resources/public/public_pref.js', 'public/public_pref.js')
    .js('resources/admin/global_admin.js', 'admin/js/global_admin.js')
    .js('resources/admin/setup-wizard.js', 'admin/js/setup-wizard.js').vue({ version: 2 })
    .js('resources/admin/experiments/contact-navigations.js', 'admin/js/contact-navigations.js')
    .js('resources/admin/visual-editor/visual-editor.js', 'admin/js/visual-editor.js')
    .sass('resources/scss/fluentcrm-admin.scss', 'admin/css/fluentcrm-admin.css')
    .sass('resources/scss/admin_rtl.scss', 'admin/css/admin_rtl.css')
    .sass('resources/scss/public_pref.scss', 'public/public_pref.css')
    .sass('resources/scss/app_global.scss', 'admin/css/app_global.css')
    .sass('resources/scss/setup-wizard.scss', 'admin/css/setup-wizard.css')
    .copy('resources/images', 'assets/images')
    .copy('node_modules/element-ui/lib/theme-chalk/fonts', 'assets/admin/css/fonts')
    .copy('resources/sample.csv', 'assets/sample.csv')
    .copy('resources/index.php', 'assets/index.php')
    .copy('resources/libs', 'assets/libs')
    .copy('resources/scss/fonts', 'assets/admin/css/fonts')
    .setPublicPath('assets')
    .disableNotifications();

if (mix.inProduction()) {
    mix.copy('resources/block_editor', 'assets/block_editor');
    mix.then(() => {
        exec('rtlcss ./assets/admin/css/fluentcrm-admin.css ./assets/admin/css/fluentcrm-admin-rtl.css', (error) => {
            if (error) {
                console.error(`exec error: ${error}`);
                return;
            }
            console.log('fluentcrm-admin-rtl.css has been generated');
        });

        exec('rtlcss ./assets/admin/css/app_global.css ./assets/admin/css/app_global-rtl.css', (error) => {
            if (error) {
                console.error(`exec error: ${error}`);
                return;
            }
            console.log('app_global-rtl.css has been generated');
        });

        exec('rtlcss ./assets/block_editor/index.css ./assets/block_editor/index-rtl.css', (error) => {
            if (error) {
                console.error(`exec error: ${error}`);
                return;
            }
            console.log('index-rtl.css has been generated');
        });
    });
    mix.copy('resources/block_editor_58', 'assets/block_editor_58');
}
