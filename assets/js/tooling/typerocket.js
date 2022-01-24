const $ = window.jQuery;
const { __ } = wp.i18n;

import { tr_max_len, tr_editor_height } from './fn/tr-helpers.js';
import { tr_repeater } from './repeater.js';
import { color_picker } from './fn/color-picker.js';
import { chosen } from './fn/chosen.js';
import { wp_editor_init } from './fn/editor.js';
import { tabs } from './fn/tabs.js';
import { date_picker } from './fn/date_picker.js';
import { sorting } from './fn/sorting.js';

;jQuery(function($) {

    tr_editor_height();

    [sorting, date_picker, color_picker, chosen].forEach(function(caller) {
        caller($(document));
        TypeRocket.repeaterCallbacks.push(caller);
    });

    // Exclusive to repeater
    TypeRocket.repeaterCallbacks.push(tabs);
    TypeRocket.repeaterCallbacks.push(wp_editor_init);

    $(document).on('input blur change', '.tr-input-maxlength', function() {
        let $that = $(this);
        let $parent = $that.parent();

        if( $parent.hasClass('redactor-box') || $parent.hasClass('tr-text-input') ) {
            $that = $parent;
        }

        $that.siblings('.tr-maxlength').find('span').text( tr_max_len(this) );
    });

    // forms
    $(document).on('submit', '.tr-form-confirm', function(e) {
        if(confirm(__('Confirm Submit.', 'typerocket-domain'))) {
            return true;
        }

        e.preventDefault();
    });

    $(document).on('submit', '.tr-ajax-form', function(e) {
        e.preventDefault();
        window.TypeRocket.lastSubmittedForm = $(this);
        $.typerocketHttp.send('POST', $(this).attr('action'), $(this).serialize());
    });

    $(document).on('click', '.tr-delete-row-rest-button', function(e) {
        let data, target;
        e.preventDefault();
        if (confirm(__("Confirm Delete.", 'typerocket-domain'))) {
            target = $(this).attr('data-target');
            data = {
                _tr_ajax_request: '1',
                _method: 'DELETE',
                _tr_nonce_form: window.trHelpers.nonce
            };
            return $.typerocketHttp.send('POST', $(this).attr('href'), data, false, function() {
                $(target).remove();
            });
        }
    });

    // tr-radio-options

    $(document).on('keyup', '.tr-radio-options-label', function(e) {
        if(e.target !== this) return;
        e.preventDefault();

        if(e.keyCode && e.keyCode === 13) {
            $(this).trigger('click').focus();
            e.preventDefault();
        }
    });

    $(document).on('click', '.tr-focus-on-click', function(e) {
        if(e.target !== this) return;
        e.preventDefault();
        $(this).focus();
    });

    $(document).on('click', '.tr-tabs > li', function(e) {
        $(this).addClass('active').siblings().removeClass('active');
        let section = $(this).find('.tr-tab-link').first().attr('href');
        $(section).addClass('active').siblings().removeClass('active');
        tr_editor_height();
        e.preventDefault();
    });

    tr_repeater();
});