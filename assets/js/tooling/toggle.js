;(function( $ ) {
$(document).on('keyup', '.tr-toggle-box-label', function(e) {
    e.preventDefault();
    if( event.keyCode == 13) {
        $(this).trigger('click');
    }
})
}( jQuery ));