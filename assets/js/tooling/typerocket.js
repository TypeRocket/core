jQuery(document).ready(function($) {
    var $trContainer, add_color_picker, add_date_picker, add_editor, add_sorting, editorHeight, repeaterClone, tr_max;
    editorHeight = function() {
        $('.wp-editor-wrap').each(function() {
            var editor_iframe;
            editor_iframe = $(this).find('iframe');
            if (editor_iframe.height() < 30) {
                editor_iframe.css({
                    'height': 'auto'
                });
            }
        });
    };
    editorHeight();
    add_sorting = function(obj) {
        var $items_list, $repeater_fields, $sortables;
        if ($.isFunction($.fn.sortable)) {
            $sortables = $(obj).find('.tr-gallery-list');
            $items_list = $(obj).find('.tr-items-list');
            $repeater_fields = $(obj).find('.tr-repeater-fields');
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
    };
    add_date_picker = function(obj) {
        if ($.isFunction($.fn.datepicker)) {
            $(obj).find('.date-picker[name]').each(function() {
                $(this).datepicker({
                    beforeShow: function(input, inst) {
                        $('#ui-datepicker-div').addClass('typerocket-datepicker');
                    }
                });
            });
        }
    };
    add_tabs = function(obj) {
        obj.find('.tr-tabbed-top:not(.tr-repeater-group-template .tr-tabbed-top)').each(function() {
            $(this).find('> .tabbed-sections > .tr-tabs > li').each(function(tab_index) {
                var old_uid, new_uid, $a_tag, $tab_panel;
                old_uid = $(this).data('uid');
                new_uid = new Date().getTime() + 'rtabuid';
                $a_tag = $(this).find('a');
                $tab_panel = $($(this).parent().parent().next().children()[tab_index]);
                $(this).attr('id', $(this).attr('id').replace(old_uid, new_uid) );
                $a_tag.attr('href', $a_tag.attr('href').replace(old_uid, new_uid) );
                $tab_panel.attr('id', $tab_panel.attr('id').replace(old_uid, new_uid) );

                $(this).click(function(e) {
                    var $section;
                    $(this).addClass('active').siblings().removeClass('active');
                    $section = $($(this).find('a').attr('href'));
                    $($section).addClass('active').siblings().removeClass('active');
                    editorHeight();
                    e.preventDefault();
                });
            });
        });
    };
    add_color_picker = function(obj) {
        if ($.isFunction($.fn.wpColorPicker)) {
            $(obj).find('.color-picker[name]').each(function() {
                var pal, settings;
                pal = $(this).attr('id') + '_color_palette';
                settings = {
                    palettes: window[pal]
                };
                $(this).wpColorPicker(settings);
            });
        }
    };
    add_editor = function(obj) {
        var redactorSettings;
        if ($.isFunction($.fn.redactor)) {
            redactorSettings = {
                formatting: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'blockquote'],
                buttons: ['formatting', 'bold', 'italic', 'deleted', 'unorderedlist', 'orderedlist', 'outdent', 'indent', 'link', 'alignment', 'horizontalrule', 'html']
            };
            if (!$.isEmptyObject(window.TypeRocket.redactorSettings)) {
                redactorSettings = window.TypeRocket.redactorSettings;
            }
            $(obj).find('.typerocket-editor[name]').each(function() {
                $(this).redactor(redactorSettings);
            });
        }
    };
    $trContainer = $('.typerocket-container');
    add_sorting($trContainer);
    add_date_picker($trContainer);
    add_color_picker($trContainer);
    add_editor($trContainer);
    TypeRocket.repeaterCallbacks.push(add_date_picker);
    TypeRocket.repeaterCallbacks.push(add_color_picker);
    TypeRocket.repeaterCallbacks.push(add_editor);
    TypeRocket.repeaterCallbacks.push(add_tabs);
    $trContainer.on('input keyup', '.redactor-editor', function () {
        var $textarea = $(this).siblings('textarea');
        $textarea.trigger('change');
    });
    $trContainer.on('blur keyup change', 'input[maxlength], textarea[maxlength]', function() {
        var $that;
        $that = $(this);

        if( $that.parent().hasClass('redactor-box') ) {
            $that = $that.parent();
        }

        $that.next().find('span').text( tr_max.len(this) );
    });
    $('.tr-tabs li').each(function() {
        $(this).click(function(e) {
            var section;
            $(this).addClass('active').siblings().removeClass('active');
            section = $(this).find('a').attr('href');
            $(section).addClass('active').siblings().removeClass('active');
            editorHeight();
            e.preventDefault();
        });
    });
    $('.contextual-help-tabs a').click(function() {
        editorHeight();
    });
    repeaterClone = {
        init: function() {
            var obj;
            obj = this;
            $(document).on('click', '.tr-repeater .controls .add', function() {
                var $fields_div, $group_template, data_name, data_name_filtered, dev_notes, hash, replacement_id, ri;
                $group_template = $($(this).parent().parent().next().clone()).removeClass('tr-repeater-group-template').addClass('tr-repeater-group');
                hash = (new Date).getTime();
                replacement_id = $group_template.data('id');
                dev_notes = $group_template.find('.dev .field span');
                data_name = $group_template.find('[data-name]');
                data_name_filtered = $group_template.find('.tr-repeater-group-template [data-name]');
                $(data_name).each(function() {
                    var name;
                    name = obj.nameParse($(this).data('name'), hash, replacement_id);
                    $(this).attr('name', name);
                    $(this).attr('data-name', null);
                });
                $(dev_notes).each(function() {
                    var name;
                    name = obj.nameParse($(this).html(), hash, replacement_id);
                    $(this).html(name);
                });
                $(data_name_filtered).each(function() {
                    $(this).attr('data-name', $(this).attr('name'));
                    $(this).attr('name', null);
                });
                add_sorting($group_template);
                ri = 0;
                while (TypeRocket.repeaterCallbacks.length > ri) {
                    if (typeof TypeRocket.repeaterCallbacks[ri] === 'function') {
                        TypeRocket.repeaterCallbacks[ri]($group_template);
                    }
                    ri++;
                }
                $fields_div = $(this).parent().parent().next().next();
                $group_template.prependTo($fields_div).hide().delay(10).slideDown(300).scrollTop('100%');
            });
            $(document).on('click', '.tr-repeater .repeater-controls .remove', function(e) {
                $(this).parent().parent().slideUp(300, function() {
                    $(this).remove();
                });
                e.preventDefault();
            });
            $(document).on('click', '.tr-repeater .repeater-controls .collapse', function(e) {
                var $group;
                $group = $(this).parent().parent();
                if ($group.hasClass('tr-repeater-group-collapsed') || $group.height() === 90) {
                    $group.removeClass('tr-repeater-group-collapsed');
                    $group.addClass('tr-repeater-group-expanded');
                    $group.attr('style', '');
                } else {
                    $group.removeClass('tr-repeater-group-expanded');
                    $group.addClass('tr-repeater-group-collapsed');
                }
                e.preventDefault();
            });
            $(document).on('click', '.tr-repeater .controls .tr_action_collapse', function(e) {
                var $collapse, $groups_group;
                $groups_group = $(this).parent().parent().next().next();
                if ($(this).val() === 'Contract') {
                    $(this).val('Expand');
                    $groups_group.find('> .tr-repeater-group').animate({
                        height: '90px'
                    }, 200);
                } else {
                    $(this).val('Contract');
                    $groups_group.find('> .tr-repeater-group').attr('style', '');
                }
                $collapse = $(this).parent().parent().next().next();
                if ($collapse.hasClass('tr-repeater-collapse')) {
                    $collapse.toggleClass('tr-repeater-collapse');
                    $collapse.find('> .tr-repeater-group').removeClass('tr-repeater-group-collapsed').attr('style', '');
                } else {
                    $collapse.toggleClass('tr-repeater-collapse');
                    $collapse.find('> .tr-repeater-group').removeClass('tr-repeater-group-expanded');
                }
                e.preventDefault();
            });
            $(document).on('click', '.tr-repeater .controls .clear', function(e) {
                if (confirm('Remove all items?')) {
                    $(this).parent().parent().next().next().html('');
                }
                e.preventDefault();
            });
            $(document).on('click', '.tr-repeater .controls .flip', function(e) {
                var items;
                if (confirm('Flip order of all items?')) {
                    items = $(this).parent().parent().next().next();
                    items.children().each(function(i, item) {
                        items.prepend(item);
                    });
                }
                e.preventDefault();
            });
        },
        nameParse: function(string, hash, id) {
            var liveTemplate, temp;
            liveTemplate = string;
            temp = new Booyah;
            liveTemplate = temp.addTemplate(liveTemplate).addTag('{{ ' + id + ' }}', hash).ready();
            return liveTemplate;
        }
    };
    repeaterClone.init();
    tr_max = {
        len: function(that) {
            var $that;
            var length;
            $that = $(that);
            length = ( $that.val().length );
            return parseInt($that.attr('maxlength')) - length;
        }
    };
});