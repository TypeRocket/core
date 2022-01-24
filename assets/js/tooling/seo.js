;jQuery(document).ready(function($) {
    var desc, orig_desc, orig_title, val;
    val = '';
    desc = '';
    orig_desc = $('#tr-seo-preview-google-desc-orig').text();
    orig_title = $('#tr-seo-preview-google-title-orig').text();
    $('.tr-js-seo-title-field').on('keyup', function() {
        var title;
        val = $(this).val().substring(0, 60);
        title = $('#tr-seo-preview-google-title');
        title.text(val);
        if (val.length > 0) {
            title.text(val);
        } else {
            title.text(orig_title);
        }
    });
    $('.tr-js-seo-desc-field').on('keyup', function() {
        desc = $(this).val().substring(0, 300);
        if (desc.length > 0) {
            $('#tr-seo-preview-google-desc').text(desc);
        } else {
            $('#tr-seo-preview-google-desc').text(orig_desc);
        }
    });
    $('#tr_seo_redirect_unlock').on('click', function(e) {
        $('.tr-js-seo-redirect-field').removeAttr('readonly').focus();
        $(this).fadeOut();
        e.preventDefault();
    });
});