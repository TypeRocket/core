const $ = window.jQuery;
const { __ } = wp.i18n;

export function chosen(obj) {
    if ($.isFunction($.fn.chosen)) {
        $(obj).find('.tr-chosen-select-js[name]').each(function() {
            let max = $(this).data('max') ? $(this).data('max') : 999999;
            let dis = $(this).data('threshold') ? $(this).data('threshold') : 5;
            let si = !!$(this).data('empty');

            $(this).chosen("destroy");
            $(this).chosen({
                no_results_text: __("Oops, nothing found!", 'typerocket-domain'),
                max_selected_options: max,
                disable_search_threshold: dis,
                allow_single_deselect: si
            });
        });
    }
}