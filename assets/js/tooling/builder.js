import {tr_replace_repeater_hash} from "./fn/tr-helpers";

const $ = window.jQuery;
const { __ } = wp.i18n;

import { tr_apply_repeater_callbacks } from './fn/tr-helpers.js';
import {tr_repeater_item_cloned, tr_repeater_clone_select, tr_repeater_get_hash} from "./repeater";


export default function trBuilder() {
    $(function() {
        let tr_builder_toggle = $('#tr_page_type_toggle');
        let $trContainer = $(document);
        if (tr_builder_toggle.length > 0) {
            if ($('#tr_page_builder_control').hasClass('builder-active')) {
                $('#builderStandardEditor').hide();
            } else {
                $('#tr_page_builder').hide();
            }

            $(tr_builder_toggle).on('click', 'a', function(e) {
                var checkbox, other, that;
                e.preventDefault();
                that = $(this);
                other = $(that.siblings()[0]);
                checkbox = $('#builderSelectRadio input')[1];
                that.addClass('builder-active button-primary');
                other.removeClass('builder-active button-primary');
                $(that.attr('href')).show();
                $(other.attr('href')).hide();
                if (that.attr('id') === 'tr_page_builder_control') {
                    $(checkbox).attr('checked', 'checked');
                } else {
                    $(checkbox).removeAttr('checked');
                    $('#content-html').click();
                    $('#content-tmce').click();
                }
            });
        }

        let get_fields_from_control = function(control) {
            return control.closest('.tr-builder').first().children('.tr-frame-fields').first();
        };

        let get_fields_from_sub_control = function(control) {
            return get_fields_from_control(control);
        };

        $trContainer.on('click', '.tr-builder-add-button', function(e) {
            let overlay, select;
            e.preventDefault();
            select = $(this).next();
            overlay = $('<div>').addClass('tr-builder-select-overlay').on('click', function() {
                $(this).remove();
                $('.tr-builder-select').fadeOut();
            });
            $('body').append(overlay);
            select.fadeIn();
        });

        $trContainer.on('click keyup', '.tr-builder-component-control', function(e) {
            let component, components, frame, index;

            if(e.keyCode && e.keyCode !== 13) {
                return
            }

            e.preventDefault();

            $(this).focus().parent().children().removeClass('active');
            $(this).addClass('active');
            index = $(this).index();
            frame = get_fields_from_control($(this));
            components = frame.children();
            components.removeClass('active');
            component = components[index];
            $(component).addClass('active');
        });

        $trContainer.on('click keydown', '.tr-clone-builder-component', function(e) {
            let component, components, control, frame, id, index;


            if(e.keyCode && e.keyCode !== 13) {
                return;
            }

            e.preventDefault();

            if (confirm(__('Clone component?', 'typerocket-domain'))) {
                try {
                    control = $(this).parent();
                    e.stopPropagation();
                    control.parent().children().removeClass('active');

                    index = control.index();
                    frame = get_fields_from_sub_control($(this));
                    components = frame.children();
                    components.removeClass('active');

                    component = $(components[index]);
                    let $el_clone = component.clone();
                    let $el_clone_c = control.clone();
                    let old_hash = tr_repeater_get_hash($el_clone);

                    $el_clone_c

                    tr_replace_repeater_hash($el_clone, old_hash);
                    tr_repeater_clone_select(component, $el_clone);

                    // place
                    $(component).after($el_clone);
                    $el_clone.addClass('active');
                    control.after($el_clone_c);
                    $el_clone_c.addClass('active').attr('data-tr-component-tile', $el_clone.attr('data-tr-component')).focus();

                    tr_repeater_item_cloned($el_clone);
                } catch (error) {
                    alert(__('Cloning is not available for this component.','typerocket-domain'));
                }
            }
        });

        $trContainer.on('click keydown', '.tr-remove-builder-component', function(e) {
            let component, components, control, frame, id, index;

            if(e.keyCode && e.keyCode !== 13) {
                return;
            }

            e.preventDefault();

            if (confirm(__('Remove component?', 'typerocket-domain'))) {
                control = $(this).parent();
                control.parent().children().removeClass('active');
                index = $(this).parent().index();
                frame = get_fields_from_sub_control($(this));
                components = frame.children();
                component = components[index];
                $(component).remove();
                control.remove();
            }
        });

        $trContainer.on('click keydown', '.tr-builder-select-option', function(e) {
            let $select, $that, form_group, type, url;

            if(e.keyCode && e.keyCode !== 13) {
                e.preventDefault();
                return;
            }

            $that = $(this);
            $select = $that.closest('.tr-builder-select').first();
            $select.fadeOut();
            $('.tr-builder-select-overlay').remove();
            if (!$that.hasClass('disabled')) {

                let $fields = get_fields_from_control($that.parent());
                let group = $that.attr('data-group');
                let img = $that.attr('data-thumbnail');
                let $components = $that.closest('.tr-builder-controls').first().children('.tr-components').first();

                // debugger;
                type = $that.attr('data-value');
                $that.addClass('disabled');
                url = trHelpers.site_uri+'/tr-api/builder/' + group + '/' + type;
                form_group = $select.attr('data-tr-group');
                $.ajax({
                    url: url,
                    method: 'POST',
                    dataType: 'html',
                    data: {
                        form_group: form_group,
                        _tr_nonce_form: window.trHelpers.nonce
                    },
                    success: function(response) {
                        var $active_components, $active_fields, textLabel, options, ri, data, tile;
                        response = $(response);
                        data = response.first();
                        tile = response.last();
                        $active_fields = $fields.children('.active');
                        $active_components = $components.children('.active');
                        $fields.children().removeClass('active');
                        $components.children().removeClass('active');

                        options = {
                            data: data,
                            tile: tile.addClass('active'),
                        };

                        ri = 0;

                        while (TypeRocket.builderCallbacks.length > ri) {
                            if (typeof TypeRocket.builderCallbacks[ri] === 'function') {
                                TypeRocket.builderCallbacks[ri](options);
                            }
                            ri++;
                        }

                        if ($active_components.length > 0 && $active_fields.length > 0) {
                            options.data.insertAfter($active_fields).addClass('active');
                            $active_components.after(options.tile);
                        } else {
                            options.data.prependTo($fields).addClass('active');
                            $components.prepend(options.tile);
                        }
                        tr_apply_repeater_callbacks(options.data);
                        $that.removeClass('disabled');
                    },
                    error: function(jqXHR) {
                        $that.val('Try again - Error ' + jqXHR.status).removeAttr('disabled', 'disabled');
                    }
                });
            }
        });
    });
}