const $ = window.jQuery;

export function tr_media_lib(attributes) {
    let defaults = _.defaults({
        id:        'tr-insert-image',
        title:      'Insert Image',
        allowLocalEdits: true,
        displaySettings: true,
        displayUserSettings: true,
        multiple : false,
        type : 'image' //audio, video, application/pdf, ... etc
    }, wp.media.controller.Library.prototype.defaults );

    return wp.media.controller.Library.extend({
        defaults :  _.defaults(attributes || {}, defaults)
    });
}

export function tr_media_model(attributes, cb, lib) {
    // @link https://stackoverflow.com/questions/21540951/custom-wp-media-with-arguments-support
    let libClass = tr_media_lib(lib);

    let frame = wp.media(_.defaults(attributes, {
        button : { text : 'Select Image' },
        state : 'tr-insert-image',
        states : [
            new libClass()
        ]
    }));

    // //on close, if there is no select files, remove all the files already selected in your main frame
    // frame.on('close',function() {
    //     let selection = frame.state('insert-image').get('selection');
    //
    //     if(!selection.length){
    //         // #remove file nodes
    //         // #such as: jq("#my_file_group_field").children('div.image_group_row').remove();
    //         // #...
    //     }
    // });

    frame.on('select', cb);

    //reset selection in popup, when open the popup
    frame.on('open',function() {
        let selection = frame.state('tr-insert-image').get('selection');

        //remove all the selection first
        selection.each(function(image) {
            let attachment = wp.media.attachment( image.attributes.id );
            attachment.fetch();
            selection.remove( attachment ? [ attachment ] : [] );
        });

        // add back current selection, in here let us assume you attach all the [id]
        // to <div id="my_file_group_field">...<input type="hidden" id="file_1" .../>...<input type="hidden" id="file_2" .../>
        $("#my_file_group_field").find('input[type="hidden"]').each(function(){
            let input_id = $(this);
            if( input_id.val() ){
                let attachment = wp.media.attachment( input_id.val() );
                attachment.fetch();
                selection.add( attachment ? [ attachment ] : [] );
            }
        });
    });

    return frame;
}