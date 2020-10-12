;(function( $ ) {
$(document).on('keyup', '.tr-toggle-box-label', function(e) {
    // enter when focused
    if( e.keyCode === 13) {
        $(this).trigger('click');
    }
})
}( jQuery ));