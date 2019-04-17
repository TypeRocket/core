const { __ } = wp.i18n;

;jQuery.typerocketHttp = {
    get: function(url, data) {
        this.send('GET', url, data);
    },
    post: function(url, data) {
        this.send('POST', url, data);
    },
    put: function(url, data) {
        this.send('PUT', url, data);
    },
    "delete": function(url, data) {
        this.send('DELETE', url, data);
    },
    send: function(method, url, data, trailing) {
        if (trailing == null) {
            trailing = true;
        }
        if (trailing) {
            url = this.tools.addTrailingSlash(url);
        }
        this.tools.ajax({
            method: method,
            data: data,
            url: url
        });
    },
    tools: {
        stripTrailingSlash: function(str) {
            if (str.substr(-1) === '/') {
                return str.substr(0, str.length - 1);
            }
            return str;
        },
        addTrailingSlash: function(str) {
            if (!str.indexOf('.php')) {
                return str.replace(/\/?(\?|#|$)/, '/$1');
            }
            return str;
        },
        ajax: function(obj) {
            var settings, tools;
            tools = this;
            settings = {
                method: 'GET',
                data: {},
                dataType: 'json',
                success: function(data) {
                    if (data.redirect) {
                        window.location = data.redirect;
                        return;
                    }
                    tools.checkData(data);
                },
                error: function(hx, error, message) {
                    alert(__('Your request had an error. ', 'typerocket-domain') + hx.status + ' - ' + message);
                }
            };
            jQuery.extend(settings, obj);
            jQuery.ajax(settings);
        },
        checkData: function(data) {
            var ri, type;
            ri = 0;
            while (TypeRocket.httpCallbacks.length > ri) {
                if (typeof TypeRocket.httpCallbacks[ri] === 'function') {
                    TypeRocket.httpCallbacks[ri](data);
                }
                ri++;
            }
            type = data.message_type;
            if (data.flash === true) {
                jQuery('body').prepend(jQuery('<div class="typerocket-ajax-alert tr-alert-' + type + ' ">' + data.message + '</div>').fadeIn(200).delay(2000).fadeOut(200, function() {
                    jQuery(this).remove();
                }));
            }
        }
    }
};

jQuery(document).ready(function($) {
    $('form.typerocket-ajax-form').on('submit', function(e) {
        e.preventDefault();
        TypeRocket.lastSubmittedForm = $(this);
        $.typerocketHttp.send('POST', $(this).attr('action'), $(this).serialize());
    });
    return $('.tr-delete-row-rest-button').on('click', function(e) {
        var data, target;
        e.preventDefault();
        if (confirm(__("Confirm Delete.", 'typerocket-domain'))) {
            target = $(this).data('target');
            $(target).remove();
            data = {
                _tr_ajax_request: '1',
                _method: 'DELETE'
            };
            return $.typerocketHttp.send('POST', $(this).attr('href'), data, false);
        }
    });
});
