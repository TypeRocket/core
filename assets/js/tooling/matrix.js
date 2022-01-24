import { tr_apply_repeater_callbacks } from './fn/tr-helpers.js';

;jQuery(document).ready(function($) {
    $(document).on('click', '.tr-matrix-add-button', function(e) {
        var $fields, $select, $that, button_txt, form_group, group, mxid, type, url;
        $that = $(this);
        if (!$that.is(':disabled')) {
            group = $that.attr('data-tr-group');
            $fields = $that.parent().parent().siblings('.tr-matrix-fields');
            $select = $that.parent().prev();
            button_txt = $that.val();
            type = $select.val();
            $that.attr('disabled', 'disabled').val('Adding...');
            url = trHelpers.site_uri+'/tr-api/matrix/' + group + '/' + type;
            form_group = $select.attr('data-group');
            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'html',
                data: {
                    form_group: form_group,
                    _tr_nonce_form: window.trHelpers.nonce
                },
                success: function(data) {
                    data = $(data);
                    data.prependTo($fields).hide().delay(10).slideDown(300).scrollTop('100%');
                    tr_apply_repeater_callbacks(data);
                    $that.val(button_txt).removeAttr('disabled', 'disabled');
                },
                error: function(jqXHR) {
                    $that.val('Try again - Error ' + jqXHR.status).removeAttr('disabled', 'disabled');
                }
            });
        }
    });
});