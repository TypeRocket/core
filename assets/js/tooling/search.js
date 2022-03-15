import {links_append, search_append, search_get_map, get_the_json, search_response} from "./fn/search-common";

const { __ } = wp.i18n;

import { tr_esc_html } from './fn/tr-helpers.js';

;(function( $ ) {

$.fn.TypeRocketSearch = function(type, taxonomy, model, url, map) {
    var param, search, that, secure;
    if (type == null) { type = 'any'; }
    if (taxonomy == null) { taxonomy = '';}
    if (model == null) { model = ''; }
    secure = true

    if(this.val() === '') {
        return;
    }

    that = this;
    let linkList = that.next().next().next();
    let config = get_the_json(that.attr('data-search-config'));

    // URL field is not an <input type="search" /> by default
    // but just in case type is set to search keep this.
    this[0].addEventListener("search", function(event) {
        linkList.html('');
    });

    linkList.html('');
    linkList.append('<li class="tr-search-result-title">'+__('Searching...', 'typerocket-domain')+'</li>');
    search = this.val().trim();
    param = 'post_type=' + type + '&s=' +  encodeURI(search);
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
        search_response(data, search_append, linkList, that, map, {
            secure: secure,
            config: config
        })
    }, 'json');
    return this;
};

$(document).on('keydown', '.tr-search-single .tr-search-input', function(e) {
    if(e.keyCode && e.keyCode === 9) {
        let res = $(this).siblings('.tr-search-results').find('.tr-search-result').first();
        if(res.length > 0) {
            e.preventDefault();
            res.focus();
        }
        return;
    }

    let taxonomy, that, type, model, url, map;
    that = $(this);
    type = $(this).data('posttype');
    taxonomy = $(this).data('taxonomy');
    model = $(this).data('model');
    url = $(this).data('endpoint');
    map = search_get_map( $(this).attr('data-map') );

    if(e.keyCode && e.keyCode === 27) {
        that.focus().val('');
        $(this).siblings('.tr-search-results').html('');
        return;
    }

    if(e.keyCode && e.keyCode === 13) {
        e.preventDefault();
        e.stopPropagation();
    }

    window.trUtil.delay((function() {
        that.TypeRocketSearch(type, taxonomy, model, url, map);
    }), 250);
});

$(document).on('click keyup', '.tr-search-single .tr-search-chosen-item-remove', function(e) {
    e.preventDefault();

    if(e.keyCode && e.keyCode !== 13) {
        return;
    }

    let parent = $(this).parent();
    parent.prev().val('').trigger('change');
    parent.prev().prev().focus();
    parent.text(__('No selection... Search and click on a result', 'typerocket-domain'));
});

}( jQuery ));