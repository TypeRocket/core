import {tr_add_button_toggle_limit} from "./fn/tr-helpers";

const { __ } = wp.i18n;

;jQuery(function($) {
    let clear_items;
    clear_items = function(button, field) {
        if (confirm(__('Remove all items?', 'typerocket-domain'))) {
            $(field).val('');
            $(button).parent().next().html('');
            let add = button.prev();
            add.removeClass('disabled').attr('value', add.attr('data-add'))
        }
        return false;
    };

    function tr_list_item_add($ul, append) {
        let inputType = $ul.attr('data-type');
        let name = $ul.attr('data-tr-name');
        let removeTitle = __('Remove Item', 'typerocket-domain');

        let $el = $('<li tabindex="0" class="tr-items-list-item"><a class="move tr-control-icon tr-control-icon-move"></a><input type="'+inputType+'" name="' + name + '[]" /><a href="#remove" class="remove tr-items-list-item-remove tr-control-icon tr-control-icon-remove" title="'+removeTitle+'"></a></li>');

        if(append) {
            $ul.append($el);
        } else {
            $ul.prepend($el);
        }

        $el.focus().scrollTop('100%');
    }

    $(document).on('click', '.tr-items-list-item', function(e) {
        if(e.target !== this) return;
        e.preventDefault();
        $(this).focus();
    });

    $(document).on('click', '.tr-items-list-button', function() {
        let $ul, $p, name, limit, $other;

        $p = $(this).parent();

        if($p.hasClass('button-group')) {
            $p = $p.parent();
        }

        $ul = $p.children('.tr-items-list');
        name = $ul.attr('name');
        limit = $ul.attr('data-limit');

        if (name) {
            $ul.attr('data-tr-name', name);
        }

        let num_fields = $ul.children().length;

        if(num_fields < limit) {
            if($(this).hasClass('tr-items-prepend')) {
                tr_list_item_add($ul, false);
                $other = $(this).parent().siblings('.tr-items-append');
            } else {
                tr_list_item_add($ul, true);
                $other = $p.find('.tr-items-prepend').first();
            }
        }

        let hide = num_fields + 1 >= limit;

        tr_add_button_toggle_limit($(this), hide);
        tr_add_button_toggle_limit($other, hide);
    });

    $(document).on('click', '.tr-items-list-clear', function() {
        let field;
        field = $(this).parent().prev();
        clear_items($(this), field[0]);
    });

    $(document).on('click', '.tr-items-list-item-remove', function() {
        let ul = $(this).parent().parent();

        $(this).parent().remove();

        let num_fields = ul.children().length,
            limit = ul.attr('data-limit'),
            hide = num_fields >= limit;

        let prepend = ul.prev().find('.tr-items-list-button');
        let append = ul.next();

        tr_add_button_toggle_limit(prepend, hide);
        tr_add_button_toggle_limit(append, hide);
    });
});
