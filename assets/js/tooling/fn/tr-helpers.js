const $ = window.jQuery;

export const tr_hash = (function() {
    let id = 0;

    return function() { return (new Date).getTime() + '' + id++; };  // Return and increment
})();

export function tr_editor_height() {
    $('.wp-editor-wrap').each(function() {
        let editor_iframe = $(this).find('iframe');
        if (editor_iframe.height() < 30) {
            editor_iframe.css({
                'height': 'auto'
            });
        }
    });
}

export function tr_esc_html(string) {
    var entityMap = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
    };

    return String(string).replace(/[&<>"'`=\/]/g, function (s) {
        return entityMap[s];
    })
}

export function tr_max_len(el) {
    let $that, length;
    $that = $(el);
    length = [...$that.val()].length;
    return parseInt($that.attr('maxlength')) - length;
}

export function tr_add_button_toggle_limit($add, hide) {
    if(hide) {
        $add.addClass('disabled').attr('value', $add.attr('data-limit'))
    } else {
        $add.removeClass('disabled').attr('value', $add.attr('data-add'))
    }
}

export function tr_apply_repeater_callbacks($group_template) {
    let ri = 0;
    while (TypeRocket.repeaterCallbacks.length > ri) {
        if (typeof TypeRocket.repeaterCallbacks[ri] === 'function') {
            TypeRocket.repeaterCallbacks[ri]($group_template);
        }
        ri++;
    }

    return $group_template;
}

export function tr_replace_repeater_hash($group_template, replacement_id) {
    let nameParse = function(string, hash, id) {
        return string.replace(id, hash);
    };

    let hash = tr_hash();
    // only data in template file
    let data_name_filtered = $group_template.find('.tr-repeater-group-template [data-tr-name]');
    let dev_notes = $group_template.find('.dev .field span');
    let attr_name = $group_template.find('[name]');
    let tr_component = $group_template.find('[data-tr-component]');

    let data_name = $group_template.find('[data-tr-name]');
    let data_group = $group_template.find('[data-tr-group]');
    let data_tr_id = $group_template.find('[id^="tr_field_"],.tr-form-field-help[id]');
    let data_context = $group_template.find('[data-tr-context]');
    let data_tr_field = $group_template.find('[data-tr-field]');
    let data_tr_for = $group_template.find('.tr-label[for], .tr-toggle-box-label[for]');

    $(data_context).each(function() {
        let name = nameParse($(this).attr('data-tr-context'), hash, replacement_id);
        $(this).attr('data-tr-context', name);
    });

    if($group_template.attr('data-tr-component')) {
        $group_template.attr('data-tr-component', 'tr-clone-hash-parent-' + tr_hash())
    }

    $(tr_component).each(function() {
        let lookup = $(this).attr('data-tr-component');
        let hash = 'tr-clone-hash-' + tr_hash();
        let tile = $group_template.find('[data-tr-component-tile='+lookup+']').first();
        $(this).attr('data-tr-component', hash);
        console.log(hash);
        if(tile) {
            tile.attr('data-tr-component-tile', hash);
        }
    });

    $(data_tr_id).each(function() {
        let name = nameParse($(this).attr('id'), hash, replacement_id);
        $(this).attr('id', name);
    });

    $(data_tr_for).each(function() {
        let name = nameParse($(this).attr('for'), hash, replacement_id),
            by = $(this).attr('aria-describedby') || false;

        $(this).attr('for', name);

        if(by) {
            name = nameParse(by, hash, replacement_id);
            $(this).attr('aria-describedby', name);
        }
    });

    $(data_tr_field).each(function() {
        let name = nameParse($(this).attr('data-tr-field'), hash, replacement_id);
        $(this).attr('data-tr-field', name);
    });

    $(dev_notes).each(function() {
        let name = nameParse($(this).html(), hash, replacement_id);
        $(this).html(name);
    });

    $(data_group).each(function() {
        let name = nameParse($(this).attr('data-tr-group'), hash, replacement_id);
        $(this).attr('data-tr-group', name);
    });

    // used when cloning existing element
    $(attr_name).each(function() {
        let name = nameParse($(this).attr('name'), hash, replacement_id);
        $(this).attr('name', name);
        $(this).attr('data-tr-name', null);
    });

    // used when repeater templates are cloned
    $(data_name).each(function() {
        let name = nameParse($(this).attr('data-tr-name'), hash, replacement_id);
        $(this).attr('name', name);
        $(this).attr('data-tr-name', null);
    });

    // remove name attr from template fields
    $(data_name_filtered).each(function() {
        $(this).attr('data-tr-name', $(this).attr('name'));
        $(this).attr('name', null);
    });

    tr_apply_repeater_callbacks($group_template);
}