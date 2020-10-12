;window.trUtil = {};
window.trUtil.delay = (function() {
    var timer;
    timer = 0;
    return function(callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();

window.trUtil.list_filter = function(input, items) {
    let filter, li, i;

    filter = document.querySelector(input).value.toUpperCase();
    li = document.querySelectorAll(items);

    for (i = 0; i < li.length; i++) {
        if (li[i].dataset.search.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
};

window.trUtil.makeUrlHttpsMaybe = function(url) {
    if (window.location.protocol === "https:") {
        return url.replace("http://", "https://");
    }

    return url;
};

if (typeof Object.assign !== 'function') {
    // Must be writable: true, enumerable: false, configurable: true
    Object.defineProperty(Object, "assign", {
        value: function assign(target, varArgs) { // .length of function is 2
            'use strict';
            if (target === null || target === undefined) {
                throw new TypeError('Cannot convert undefined or null to object');
            }

            var to = Object(target);

            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];

                if (nextSource !== null && nextSource !== undefined) {
                    for (var nextKey in nextSource) {
                        // Avoid bugs when hasOwnProperty is shadowed
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        },
        writable: true,
        configurable: true
    });
}