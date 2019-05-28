const { __ } = wp.i18n;

;jQuery(document).ready(function($) {
    var clear_items;
    clear_items = function(button, field) {
        if (confirm(__('Remove all items?', 'typerocket-domain'))) {
            $(field).val('');
            $(button).parent().next().html('');
            var add = button.prev();
            add.removeClass('disabled').attr('value', add.data('add'))
        }
        return false;
    };
    $(document).on('click', '.items-list-button', function() {
        var $ul, name, limit, inputType, removeTitle;
        $ul = $(this).parent().next();
        name = $ul.attr('name');
        inputType = $ul.data('type');
        limit = $ul.data('limit');
        if (name) {
            $ul.data('name', name);
        }
        name = $ul.data('name');
        removeTitle =__('Remove Item', 'typerocket-domain');

        var num_fields = $ul.children().length;

        if(num_fields < limit) {
            $ul.prepend($('<li class="item"><a class="move tr-control-icon tr-control-icon-move"></a><a href="#remove" class="remove tr-control-icon tr-control-icon-remove" title="'+removeTitle+'"></a><input type="'+inputType+'" name="' + name + '[]" /></li>')
                .hide().delay(10).slideDown(150).scrollTop('100%'));
        }

        if(num_fields + 1 >= limit) {
            $(this).addClass('disabled').attr('value',$(this).data('limit'))
        } else {
            $(this).removeClass('disabled').attr('value',$(this).data('add'))
        }
    });
    $(document).on('click', '.items-list-clear', function() {
        var field;
        field = $(this).parent().prev();
        clear_items($(this), field[0]);
    });
    $(document).on('click', '.tr-items-list .remove', function() {
        $(this).parent().slideUp(150, function() {
            $(this).remove();
        });

        var ul = $(this).parent().parent();
        var num_fields = ul.children().length;

        if(num_fields <= ul.data('limit')) {
            var add = ul.prev().find('.items-list-button');
            add.removeClass('disabled').attr('value', add.data('add'))
        }

    });
});
