;jQuery(document).ready(function($) {
    $('.typerocket-container').on('click', '.matrix-button', function(e) {
        var $fields, $select, $that, button_txt, callbacks, folder, form_group, group, mxid, type, url;
        $that = $(this);
        if (!$that.is(':disabled')) {
            mxid = $that.data('id');
            folder = $that.data('folder');
            group = $that.data('group');
            $fields = $('#' + mxid);
            $select = $('select[data-mxid="' + mxid + '"]');
            button_txt = $that.val();
            type = $select.val();
            callbacks = TypeRocket.repeaterCallbacks;
            $that.attr('disabled', 'disabled').val('Adding...');
            url = trHelpers.site_uri+'/tr_matrix_api/v1/' + group + '/' + type + '/' + folder;
            form_group = $select.data('group');
            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'html',
                data: {
                    form_group: form_group
                },
                success: function(data) {
                    var $items_list, $repeater_fields, $sortables, ri;
                    data = $(data);
                    ri = 0;
                    while (callbacks.length > ri) {
                        if (typeof callbacks[ri] === 'function') {
                            callbacks[ri](data);
                        }
                        ri++;
                    }
                    data.prependTo($fields).hide().delay(10).slideDown(300).scrollTop('100%');
                    if ($.isFunction($.fn.sortable)) {
                        $sortables = $fields.find('.tr-gallery-list');
                        $items_list = $fields.find('.tr-items-list');
                        $repeater_fields = $fields.find('.tr-repeater-fields');
                        if ($sortables.length > 0) {
                            $sortables.sortable();
                        }
                        if ($repeater_fields.length > 0) {
                            $repeater_fields.sortable({
                                connectWith: '.tr-repeater-group',
                                handle: '.repeater-controls'
                            });
                        }
                        if ($items_list.length > 0) {
                            $items_list.sortable({
                                connectWith: '.item',
                                handle: '.move'
                            });
                        }
                    }
                    $that.val(button_txt).removeAttr('disabled', 'disabled');
                },
                error: function(jqXHR) {
                    $that.val('Try again - Error ' + jqXHR.status).removeAttr('disabled', 'disabled');
                }
            });
        }
    });
});