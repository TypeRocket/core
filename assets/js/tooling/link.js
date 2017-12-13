;var tr_delay;

jQuery.fn.TypeRocketLink = function(type, taxonomy) {
    var param, search, that;
    if (type == null) {
        type = 'any';
    }
    if (taxonomy == null) {
        taxonomy = '';
    }
    that = this;
    search = encodeURI(this.val());
    param = 'post_type=' + type + '&s=' + search;
    if (taxonomy) {
        param += '&taxonomy=' + taxonomy;
    }
    jQuery.getJSON('/wp-json/typerocket/v1/search?' + param, function(data) {
        var i, id, item, len, post_status, results, title;
        if (data) {
            that.next().next().next().html('');
            that.next().next().next().append('<li class="tr-link-search-result-title">Results');
            results = [];
            for (i = 0, len = data.length; i < len; i++) {
                item = data[i];
                if (item.post_title) {
                    if (item.post_status === 'draft') {
                        post_status = 'draft ';
                    } else {
                        post_status = '';
                    }
                    title = item.post_title + ' (' + post_status + item.post_type + ')';
                    id = item.ID;
                } else {
                    title = item.name;
                    id = item.term_id;
                }
                results.push(that.next().next().next().append('<li class="tr-link-search-result" data-id="' + id + '" >' + title));
            }
            return results;
        }
    });
    return this;
};

tr_delay = (function() {
    var timer;
    timer = 0;
    return function(callback, ms) {
        clearTimeout(timer);
        timer = setTimeout(callback, ms);
    };
})();

jQuery(document).ready(function($) {
    $('.typerocket-container').on('keyup', '.tr-link-search-input', function() {
        var taxonomy, that, type;
        that = $(this);
        type = $(this).data('posttype');
        taxonomy = $(this).data('taxonomy');
        return tr_delay((function() {
            that.TypeRocketLink(type, taxonomy);
        }), 250);
    });
    return $('.typerocket-container').on('click', '.tr-link-search-result', function() {
        var id, title;
        id = $(this).data('id');
        title = $(this).text();
        $(this).parent().prev().html('Selection: <b>' + title + '</b>');
        $(this).parent().prev().prev().val(id);
        $(this).parent().prev().prev().prev().focus().val('');
        return $(this).parent().html('');
    });
});