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
    send: function(method, url, data, trailing, fnSuccess, fnFail) {
        if (trailing == null) {
            trailing = true;
        }
        if (trailing) {
            url = this.tools.addTrailingSlash(url);
        }

        if(data instanceof URLSearchParams) {
            data.append('_tr_ajax_request', '1');
            data = data.toString();
        }

        this.tools.ajax({
            method: method,
            data: data,
            url: url
        }, {success: fnSuccess, error: fnFail });
    },
    tools: {
        entityMap: {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        },
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
        escapeHtml: function(string) {
            let that = this;
            return String(string).replace(/[&<>"'`=\/]/g, function (s) {
                return that.entityMap[s];
            });
        },
        ajax: function(obj, fn) {
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
                    tools.checkData(data, 3500, fn.success, __('Success', 'typerocket-domain'));
                },
                error: function(hx, error, message) {
                    if(hx.responseText) {
                        let json = JSON.parse(hx.responseText);
                        tools.checkData(json, 5500, fn.error, __('Error', 'typerocket-domain'));
                    } else {
                        alert(__('Your request had an error.', 'typerocket-domain') + hx.status + ' - ' + message);
                    }
                }
            };
            jQuery.extend(settings, obj);
            jQuery.ajax(settings);
        },
        checkData: function(data, delay, fn, defaultMessage) {
            let ri, type, message, that, flashSettings;
            ri = 0;
            that = this;
            while (TypeRocket.httpCallbacks.length > ri) {
                if (typeof TypeRocket.httpCallbacks[ri] === 'function') {
                    TypeRocket.httpCallbacks[ri](data);
                }
                ri++;
            }
            message = data.message ? data.message : defaultMessage;
            flashSettings = data?.data?._tr?.flashSettings ?? {};
            if(flashSettings?.escapeHtml !== false) {
                message = that.escapeHtml(message);
            }

            type = that.escapeHtml(data.messageType);
            delay = flashSettings?.delay ?? delay;

            // TODO: Add flashing errors option
            // if(!jQuery.isEmptyObject(data.errors)) {
            //     message += '<ul>';
            //     jQuery.each( data.errors, function( key, value ) {
            //         message += '<li><b>' + that.escapeHtml(key) + "</b>: " + that.escapeHtml(value) + '</li>';
            //     });
            //     message += '</ul>';
            // }

            if (data.flash === true) {
                jQuery('body').prepend(jQuery('<div class="tr-ajax-alert tr-alert-' + type + ' ">' + message + '</div>').fadeIn(200).delay(delay).fadeOut(200, function() {
                    jQuery(this).remove();
                }));
            }

            if(typeof fn !== "undefined") {
                fn(data);
            }
        }
    }
};
