const $ = window.jQuery;

$.fn.extend({
    ksortable: function(options, el) {
        this.sortable(options);
        el = el || 'li';
        $(this).on('keydown', '> ' + el, function(event) {
            if(!$(this).is(":focus" )) {
               return;
            }

            if(event.keyCode === 37 || event.keyCode === 38) { // left or up
                $(this).insertBefore($(this).prev());
                event.preventDefault();
            }
            if(event.keyCode === 39 || event.keyCode === 40) { // right or down
                $(this).insertAfter($(this).next());
                event.preventDefault();
            }
            if (event.keyCode === 84 || event.keyCode === 33) { // "t" or page-up
                $(this).parent().prepend($(this));
            }
            if (event.keyCode === 66 || event.keyCode === 34) { // "b" or page-down
                $(this).parent().append($(this));
            }
            if(event.keyCode === 70) { // "f"
                let p = $(this).parent();
                p.children().each(function(){p.prepend($(this))})
            }

            $(this).focus();
        });
    }
});


/**
 * WP Uses jQuery UI 1.11
 *
 * @link https://api.jqueryui.com/1.11/sortable/#option-helper
 *
 * @param obj
 */
export function sorting(obj) {
    let $items_list, $repeater_fields, $gallerySort, $sortableLinks, $builder_fields;
    if ($.isFunction($.fn.sortable)) {
        $gallerySort = $(obj).find('.tr-gallery-list');
        $sortableLinks = $(obj).find('.tr-search-selected-multiple');
        $items_list = $(obj).find('.tr-items-list');
        $repeater_fields = $(obj).find('.tr-repeater-fields');
        $builder_fields = $(obj).find('.tr-components');
        if ($gallerySort.length > 0) {
            $gallerySort.ksortable({
                placeholder: "tr-sortable-placeholder tr-gallery-item",
                forcePlaceholderSize: true,
                update( event, ui ) {
                    ui.item.focus();
                }
            });
        }
        if ($sortableLinks.length > 0) {
            $sortableLinks.ksortable({
                placeholder: "tr-sortable-placeholder",
                forcePlaceholderSize: true,
                update( event, ui ) {
                    ui.item.focus();
                }
            });
        }
        if ($repeater_fields.length > 0) {
            $repeater_fields.ksortable({
                connectWith: '.tr-repeater-group',
                handle: '.tr-repeater-controls',
                placeholder: "tr-sortable-placeholder",
                forcePlaceholderSize: true,
                update( event, ui ) {
                    ui.item.focus();
                }
            });
        }
        if ($items_list.length > 0) {
            $items_list.ksortable({
                connectWith: '.item',
                handle: '.move',
                placeholder: "tr-sortable-placeholder",
                forcePlaceholderSize: true,
                update( event, ui ) {
                    ui.item.focus();
                }
            });
        }
        if($builder_fields.length > 0) {
            $builder_fields.sortable({
                placeholder: "tr-sortable-placeholder",
                forcePlaceholderSize: true,
                start: function(e, ui) {
                    return ui.item.startPos = ui.item.index();
                },
                update: function(e, ui) {
                    let builder, components, frame, index, old;
                    frame = ui.item.parent().parent().siblings('.tr-frame-fields').first();
                    components = frame.children().detach();
                    index = ui.item.index();
                    old = ui.item.startPos;
                    builder = components.splice(old, 1);
                    components.splice(index, 0, builder[0]);
                    frame.append(components);
                }
            });
        }
    }
}