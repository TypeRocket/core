const { __ } = wp.i18n;
const $ = window.jQuery;

import {tr_add_button_toggle_limit, tr_replace_repeater_hash} from "./fn/tr-helpers";

export function tr_repeater() {

    // use this with clone, remove and collapse
    let get_group = function(control) {
        return $(control).closest('.tr-repeater-group').first();
    };

    // use this with clone, remove and collapse
    let get_group_container = function(control) {
        return $(control).closest('.tr-repeater-fields').first();
    };

    // use this with clone, remove and collapse
    let get_group_container_controls = function(control) {
        return $(control).closest('.tr-repeater-fields').first().siblings('.controls').first();
    };

    // get add button - use this with clone, remove and collapse
    let get_group_container_add_control = function(control) {
        return $(control).closest('.tr-repeater-fields').first().siblings('.controls').find('.tr-repeater-action-add').first();
    };

    /**
     * Group Controls
     */
    $(document).on('click keydown', '.tr-repeater-clone', function(e) {
        if(e.keyCode && e.keyCode !== 13) {
            return;
        }

        e.preventDefault();

        try {
            let $group = get_group(this);
            let limit = $group.data('limit');
            let $fields = get_group_container(this).children();

            if( !tr_repeater_limit(limit, $fields, 1) ) {
                let $el_clone = $group.clone();
                let clone_hash = tr_repeater_get_hash($el_clone);
                tr_replace_repeater_hash($el_clone, clone_hash);
                tr_repeater_clone_select($group, $el_clone);
                get_group(this).after($el_clone);

                tr_repeater_item_cloned($el_clone);
                $el_clone.focus();
            } else {
                $(this).addClass('tr-shake');

                setTimeout(() => {
                    $(this).removeClass('tr-shake');
                }, 400);
            }
        } catch(error) {
            console.error(error);
            alert(__('Cloning is not available for this group.','typerocket-domain'));
        }

    });

    $(document).on('click keydown', '.tr-repeater-fields.tr-repeater-confirm-remove .tr-repeater-remove', function(e) {
        if(e.keyCode && e.keyCode !== 13) {
            return;
        }

        if (!confirm(__('Permanently Remove?', 'typerocket-domain'))) {
            e.stopImmediatePropagation();
        }

    });

    $(document).on('click keydown', '.tr-repeater-remove', function(e) {
        if(e.keyCode && e.keyCode !== 13) {
            return;
        }

        e.preventDefault();

        let $el = get_group(this);
        let limit = $el.data('limit');
        let $items = get_group_container(this);

        $el.slideUp(300, function() {
            $el.remove();
            let $children = $items.children();
            tr_repeater_limit(limit, $children, 0)
        });

    });

    $(document).on('click keydown', '.tr-repeater-collapse', function(e) {
        let $group, $group_parent, is_expanded, is_collapsed;

        if(e.keyCode && e.keyCode !== 13) {
            return;
        }

        e.preventDefault();

        $group = get_group(this);
        $group_parent = get_group_container(this);
        is_collapsed = $group.hasClass('tr-repeater-group-collapsed');
        is_expanded = $group.hasClass('tr-repeater-group-expanded');

        if (is_collapsed || (!is_expanded && $group_parent.hasClass('tr-repeater-collapse'))) {
            $group.removeClass('tr-repeater-group-collapsed');
            $group.addClass('tr-repeater-group-expanded');
        } else {
            $group.removeClass('tr-repeater-group-expanded');
            $group.addClass('tr-repeater-group-collapsed');
        }
    });

    $(document).on('click', '.tr-repeater-action-add', function(e) {
        e.preventDefault();
        let $repeater = $(this).parents('.tr-repeater').first();

        tr_repeater_item_template($repeater, function($new, $fields) {
            $new.prependTo($fields).scrollTop('100%').focus();
        });
    });

    $(document).on('click', '.tr-repeater-action-add-append', function(e) {
        e.preventDefault();
        let $repeater = $(this).parents('.tr-repeater').first();

        tr_repeater_item_template($repeater, function($new, $fields) {
            $new.appendTo($fields).scrollTop('100%').focus();
        });
    });

    /**
     * Master Controls
     */
    $(document).on('click', '.tr-repeater-action-collapse', function(e) {
        let $groups_group;
        $groups_group = $(this).parent().parent().next().next();
        if ($(this).hasClass('tr-repeater-expanded')) {
            $(this).val($(this).data('expand'));
            // $(this).addClass('tr-repeater-contacted');
            $(this).removeClass('tr-repeater-expanded').removeClass('tr-repeater-group-expanded');
        } else {
            $(this).val($(this).data('contract'));
            $(this).addClass('tr-repeater-expanded');
            // $(this).removeClass('tr-repeater-contacted');
            $groups_group.find('> .tr-repeater-group').removeClass('tr-repeater-group-collapsed');
        }

        if ($groups_group.hasClass('tr-repeater-collapse')) {
            $groups_group.toggleClass('tr-repeater-collapse');
            $groups_group.find('> .tr-repeater-group').removeClass('tr-repeater-group-collapsed');
        } else {
            $groups_group.toggleClass('tr-repeater-collapse');
            $groups_group.find('> .tr-repeater-group').removeClass('tr-repeater-group-expanded');
        }
        e.preventDefault();
    });

    $(document).on('click', '.tr-repeater-action-clear', function(e) {
        if (confirm(__('Remove all items?', 'typerocket-domain'))) {
            $(this).parent().parent().next().next().html('');
            var add = $(this).parent().prev().children();
            add.removeClass('disabled').attr('value', add.data('add'))
        }
        e.preventDefault();
    });

    $(document).on('click', '.tr-repeater-action-flip', function(e) {
        if (confirm(__('Flip order of all items?', 'typerocket-domain'))) {
            let items = $(this).parent().parent().next().next();
            items.children().each(function(i, item) {
                items.prepend(item);
            });
        }
        e.preventDefault();
    });
}

export function tr_repeater_limit(limit, $fields, increment) {
    let num_fields = $fields.length;
    let $repeater = $fields.first().parents('.tr-repeater').first();
    let $append = $repeater.children('.tr-repeater-action-add-button');
    let $prepend = $repeater.children('.controls').find('.tr-repeater-action-add-button');

    if($prepend.length > 0) {
        let hide = num_fields + increment >= limit;
        tr_add_button_toggle_limit($prepend, hide);
        tr_add_button_toggle_limit($append, hide);
    }

    return num_fields >= limit;
}

export function tr_repeater_get_hash(clone) {
    let $control = clone.find('[data-tr-context]').first(),
        control_id = $control.attr('data-tr-context'),
        hash_map = [],
        m,
        reg = /\.(\d{9,})\./g;

    while ((m = reg.exec(control_id)) !== null) {
        hash_map.push(m.pop());
        // This is necessary to avoid infinite loops with zero-width matches
        if (m.index === reg.lastIndex) {
            reg.lastIndex++;
        }
    }

    return hash_map.pop();
}

export function tr_repeater_item_template($repeater, cb) {
    let $new = $repeater.children('.tr-repeater-group-template').children().first().clone();
    let $fields = $repeater.children('.tr-repeater-fields');
    let limit = $new.data('limit');

    let replacement_id = '{{ ' + $new.data('id') + ' }}';
    tr_replace_repeater_hash($new, replacement_id);

    if( !tr_repeater_limit(limit, $fields.children(), 1) ) {
        cb($new, $fields)
    }
}

export function tr_repeater_item_cloned(clone) {
    clone.addClass('tr-cloned-item');
    setTimeout(() => clone.removeClass("tr-cloned-item"), 2400);
}

export function tr_repeater_clone_select($og, $clone) {
    let $originalSelects = $og.find('select');
    $clone.find('select').each(function(index, item) {
        $(item).val( $originalSelects.eq(index).val() );
    });
}