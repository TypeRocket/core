const $ = window.jQuery;

export function wp_editor_init($template) {
    $template.find('.wp-editor-area').each(function() {
        tinyMCE.execCommand('mceAddEditor', false, $(this).attr('id'));
    });
}