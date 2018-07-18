;window.trUtil = {};
window.trUtil.delay = (function() {
    var timer;
    timer = 0;
    return function(callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();