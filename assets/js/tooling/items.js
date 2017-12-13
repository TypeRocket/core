;jQuery(document).ready(function($) {
    var clear_items;
    clear_items = function(button, field) {
        if (confirm('Remove all items?')) {
            $(field).val('');
            $(button).parent().next().html('');
        }
        return false;
    };
    $(document).on('click', '.items-list-button', function() {
        var $ul, name;
        $ul = $(this).parent().next();
        name = $ul.attr('name');
        if (name) {
            $ul.data('name', name);
        }
        name = $ul.data('name');
        $ul.prepend($('<li class="item"><div class="move tr-icon-menu"></div><a href="#remove" class="tr-icon-remove2 remove" title="Remove Item"></a><input type="text" name="' + name + '[]" /></li>').hide().delay(10).slideDown(150).scrollTop('100%'));
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
    });
});
