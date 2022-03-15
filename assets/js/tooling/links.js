import {search_response, links_append, search_get_map, get_the_json} from "./fn/search-common";

const { __ } = wp.i18n;

;(function( $ ) {
    $.fn.TypeRocketLinks = function(type, taxonomy, model, url, map, fnSelect) {
        let param, search, that, secure, linkList;
        if (type == null) { type = 'any'; }
        if (taxonomy == null) { taxonomy = ''; }
        if (model == null) { model = ''; }
        secure = true

        if(this.val() === '') {
            return;
        }

        that = this;
        let config = get_the_json($(this).attr('data-search-config'));
        linkList = that.next();

        this[0].addEventListener("search", function(event) {
            linkList.html('');
        });

        linkList.html('');
        linkList.append('<li class="tr-search-result-title">'+__('Searching...', 'typerocket-domain')+'</li>');
        search = this.val().trim();
        param = 'post_type=' + type + '&s=' + encodeURI(search);
        if (taxonomy) { param += '&taxonomy=' + taxonomy; }

        if(!url) {
            url = trHelpers.site_uri+'/tr-api/search?' + param;
        }

        if(!url.startsWith(trHelpers.site_uri)) {
            secure = false;
        }

        jQuery.post(url, {
            _method: 'POST',
            _tr_nonce_form: window.trHelpers.nonce,
            model: model,
            post_type: type,
            taxonomy: taxonomy,
            s: search
        }, (data) => {

            let selectList = that.parent().next();
            let inputName = selectList.siblings('.tr-search-controls').find('.tr-field-hidden-input').first().attr('name');

            search_response(data, fnSelect, linkList, that, map, {
                secure: secure,
                inputName: inputName,
                selectList: selectList,
                config: config
            })
        } , "json");
        return this;
    };

    $(document).on('click keyup', '.tr-search-multiple .tr-search-chosen-item-remove', function(e) {
        e.preventDefault();

        if(e.keyCode && e.keyCode !== 13) {
            return;
        }

        let sib = $(this).parent().siblings().first();
        if(sib.length > 0) {
            sib.focus()
        } else {
            $(this)
                .closest('.tr-search-selected-multiple')
                .siblings('.tr-search-controls')
                .find('.tr-search-input')
                .first()
                .focus();
        }

        $(this).parent().remove();
    });

    $(document).on('click', '.tr-search-multiple .tr-search-chosen-item', function(e) {
        e.preventDefault();
        $(this).focus();
    });

    $(document).on('keydown', '.tr-search-multiple .tr-search-input', function(e) {

        if(e.keyCode && e.keyCode === 9) {
            return;
        }

        let taxonomy, that, type, model, url, map;
        that = $(this);
        type = $(this).attr('data-posttype');
        taxonomy = $(this).attr('data-taxonomy');
        model = $(this).attr('data-model');
        url = $(this).attr('data-endpoint');
        map = search_get_map( $(this).attr('data-map') );

        if(e.keyCode && e.keyCode === 27) {
            that.focus().val('');
            $(this).siblings('.tr-search-results').html('');
            return;
        }

        window.trUtil.delay((function() {
            that.TypeRocketLinks(type, taxonomy, model, url, map, links_append);
        }), 250);

        if(e.keyCode && e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    $(document).on('input', '.tr-url-input', function(e) {

        if(e.keyCode && e.keyCode === 9) {
            return;
        }

        let taxonomy, that, type, model, url, map, val;
        that = $(this);
        val = that.val();

        if(!val || val.startsWith('#') || val.startsWith('/')) {
            that.next().html('');
            return;
        }

        type = that.attr('data-posttype');
        taxonomy = that.attr('data-taxonomy');
        model = that.attr('data-model');
        url = that.attr('data-endpoint');
        map = search_get_map( that.attr('data-map') );

        if(e.keyCode && e.keyCode === 27) {
            that.focus().val('');
            that.siblings('.tr-search-results').html('');
            return;
        }

        window.trUtil.delay((function() {
            that.TypeRocketLinks(type, taxonomy, model, url, map, function($that, $this, obj) {
                let url = $this.attr('data-url');
                $that.focus();
                $that.val(url);

                console.log(obj);

                if(!obj?.config?.keepSearch) {
                    $this.parent().html('');
                }
            });
        }), 250);

        if(e.keyCode && e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

}( jQuery ));