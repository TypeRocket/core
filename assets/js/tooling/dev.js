;jQuery.fn.selectText = function() {
    var doc, element, range, selection;
    doc = document;
    element = this[0];
    range = void 0;
    selection = void 0;
    if (doc.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();
        range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
};

jQuery(document).ready(function($) {
    $(document).on('click', '.tr-dev-field-function', function() {
        $(this).selectText();
    });
});