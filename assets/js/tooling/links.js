;(function( $ ) {
    $.fn.TypeRocketLinks = function(type, taxonomy, model) {
        var param, search, that;
        if (type == null) { type = 'any'; }
        if (taxonomy == null) { taxonomy = ''; }
        if (model == null) { model = ''; }

        that = this;
        search = encodeURI(this.val().trim());
        param = 'post_type=' + type + '&s=' + search;
        if (taxonomy) { param += '&taxonomy=' + taxonomy; }
        if (model) { param += '&model=' + model; }

        jQuery.getJSON(trHelpers.site_uri+'/wp-json/typerocket/v1/search?' + param, function(data) {
            var i, id, item, len, post_status, results, title, link;
            if (data) {
                var linkList = that.next();
                var selectedList = that.parent().next();
                var inputName = selectedList.data('input');
                linkList.html('');
                linkList.append('<div class="tr-link-search-result-title">Results</div>');
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
                    } else if(item.term_id) {
                        title = item.name;
                        id = item.term_id;
                    } else {
                        title = item.title;
                        id = item.id;
                    }

                    link = jQuery('<a tabindex="0" class="tr-link-search-result" data-id="' + id + '" >' + title + '</a>');
                    link = link.on('click keyup', function(e) {
                        e.preventDefault();
                        var keying = false;
                        var enterKey = false;
                        if(event.keyCode) {
                            keying = true;
                            enterKey = event.keyCode == 13;
                        }

                        if( !keying || enterKey) {
                            var id, title;
                            id = $(this).data('id');
                            title = $(this).text();
                            linkItem = jQuery('<li class="tr-link-chosen-item"><input name="'+inputName+'[]" value="'+id+'" type="hidden" />' + title + '<a title="remove" class="tr-control-icon tr-control-icon-remove tr-link-chosen-item-remove"></a></li>');
                            selectedList.append(linkItem)
                            that.focus().val('');
                            return $(this).parent().html('');
                        }
                    })
                    linkList.append(link);
                    results.push(link);
                }
                return results;
            }
        });
        return this;
    };

    $('.typerocket-container').on('click', '.tr-link-chosen-item-remove', function(e) {
        e.preventDefault()
        $(this).parent().remove();
    });

    $('.typerocket-container').on('keyup', '.tr-link-links-input', function() {
        var taxonomy, that, type, model;
        that = $(this);
        type = $(this).data('posttype');
        taxonomy = $(this).data('taxonomy');
        model = $(this).data('model');
        window.trUtil.delay((function() {
            that.TypeRocketLinks(type, taxonomy, model);
        }), 250);
    });

}( jQuery ));