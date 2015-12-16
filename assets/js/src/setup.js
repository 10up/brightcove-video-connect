requirejs.config({
    baseUrl: wpbc.path
});

if (typeof jQuery === 'function') {
    define('jquery', function () { return jQuery; });
}
if (typeof _ === 'function') {
    define('underscore', function () { return _; });
}
if (typeof Backbone === 'object') {
    define('backbone', function () { return Backbone; });
}
if (typeof wp === 'object') {
    define('wp', function () { return wp; });
}
if (typeof wpbc === 'object') {
    define('wpbc', function () { return wpbc; });
}
if (typeof plupload === 'object') {
    define('plupload', function () { return plupload; });
}
requirejs(['app'], function(App) {
    App.load();
});
