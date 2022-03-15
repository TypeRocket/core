import {tr_esc_html} from "./tr-helpers";

const { __ } = wp.i18n;

export function search_response(data, appendFn, linkList, that, map, obj) {
    let i, id, item, len, results, title, link, items, count,url;
    if (!data) {
        return;
    }

    items = data[map['items'] || 'items'];
    count = tr_esc_html(data[map['count'] || 'count'] || items.length);
    linkList.html('');
    linkList.append('<li class="tr-search-result-title">' + __('Results:', 'typerocket-domain') + ' ' + count + '</li>');
    results = [];
    for (let key in items) {
        item = items[key];

        id = item[map['id'] || 'id'];
        title = item[map['title'] || 'title'] || id;
        url = item[map['url'] || 'url'] || null;

        if (!obj.secure) {
            title = tr_esc_html(title);
        }

        if (!obj.secure) {
            id = tr_esc_html(id);
        }

        link = jQuery('<li tabindex="0" class="tr-search-result" data-url="' + url + '" data-id="' + id + '" ><span class="tr-search-selection-option">' + title + '</span></li>');
        link = link.on('click keyup', function (e) {
            e.preventDefault();
            let keying = false;
            let enterKey = false;
            if (e.keyCode) {
                keying = true;
                enterKey = e.keyCode === 13;
            }

            if (!keying || enterKey) {
                appendFn(that, jQuery(this), obj);
            }
        });
        linkList.append(link);
        results.push(link);
    }
}

export function links_append($that, $this, obj) {
    var id, title, rmvt, linkItem;
    id = $this.attr('data-id');
    title = $this.find('span').html();
    rmvt = __('remove', 'typerocket-domain');
    linkItem = jQuery('<li tabindex="0" class="tr-search-chosen-item"><input name="' + obj.inputName + '[]" value="' + id + '" type="hidden" /><span>' + title + '</span><button aria-label="Close" type="button" tabindex="0" title="' + rmvt + '" class="tr-control-icon tr-control-icon-remove tr-search-chosen-item-remove"><span class="tr-sr-only" aria-hidden="true">×</span></button></li>');

    obj.selectList.append(linkItem);
    $that.focus();

    if(!obj?.config?.keepSearch) {
        $that.val('');
        $this.parent().html('');
    }
}

export function search_append($that, $this, obj) {
    let id, title, rmvt;
    id = $this.data('id');
    title = $this.find('span').html();
    rmvt = __('remove', 'typerocket-domain');

    $this.parent().prev().html('<span>'+title+'</span> <button aria-label="Close" type="button" tabindex="0" title="' + rmvt + '" class="tr-control-icon tr-control-icon-remove tr-search-chosen-item-remove"><span class="tr-sr-only" aria-hidden="true">×</span></button>');
    $that.next().val(id).trigger('change');
    $that.focus();

    if(!obj?.config?.keepSearch) {
        $that.val('');
        $this.parent().html('');
    }
}

export function search_get_map(map) {
    if(map) {
        map = JSON.parse(map);
    }

    if(typeof map === undefined || !map) {
        map = {}
    }

    return map;
}

export function get_the_json(json) {
    if(json) {
        json = JSON.parse(json);
    }

    if(typeof json === undefined || !json) {
        json = {}
    }

    return json;
}