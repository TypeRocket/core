import {tr_hash} from "./tr-helpers";
const $ = window.jQuery;

/**
 * This Adds Tabs to Repeaters Only
 */
export function tabs(obj) {
    obj.find('.tr-tabbed-top:not(.tr-repeater-group-template .tr-tabbed-top)').each(function() {
        $(this).find('> .tr-tabbed-sections > .tr-tabs > li').each(function(tab_index) {
            var old_uid, new_uid, $a_tag, $tab_panel;

            old_uid = $(this).attr('data-uid');
            new_uid = tr_hash();

            // replace
            $(this).attr('data-uid', new_uid);
            $a_tag = $(this).find('.tr-tab-link');
            $tab_panel = $($(this).parent().parent().next().children()[tab_index]);
            $(this).attr('id', $(this).attr('id').replace(old_uid, new_uid) );
            $a_tag.attr('href', $a_tag.attr('href').replace(old_uid, new_uid) );
            $tab_panel.attr('id', $tab_panel.attr('id').replace(old_uid, new_uid) );
        });
    });
}