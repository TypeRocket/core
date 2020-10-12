const $ = window.jQuery;

export function date_picker(obj) {
    if ($.isFunction($.fn.datepicker)) {
        $(obj).find('.tr-date-picker[name]').each(function() {
            let date_format = $(this).data('format');
            let date_format_picker = 'dd/mm/yy';
            if (date_format) { date_format_picker = date_format; }
            $(this)
                .off()
                .removeClass('hasDatepicker')
                .removeData('datepicker')
                .datepicker({
                    beforeShow: function(input, inst) {
                        $('#ui-datepicker-div').addClass('tr-datepicker-container');
                    },
                    dateFormat: date_format_picker
                });
        });
    }
}