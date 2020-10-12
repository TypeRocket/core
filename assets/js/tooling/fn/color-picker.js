const $ = window.jQuery;

export function color_picker(obj) {
    if ($.isFunction($.fn.wpColorPicker)) {
        $(obj).find('.tr-color-picker[name]').each(function() {
            var pal, settings, el, pc;
            el = $(this);

            if(el.hasClass('wp-color-picker')) {
                pc = el.parent().parent().parent().parent();
                el = el.clone().off().removeClass('wp-color-picker');
                $(this).parent().parent().parent().off().remove();
                pc.append(el);
            }

            pal = $(this).data('palette');
            settings = {
                palettes: window[pal]
            };
            el.wpColorPicker(settings);
        });
    }
}