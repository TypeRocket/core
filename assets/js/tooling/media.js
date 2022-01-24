const { __ } = wp.i18n;

;jQuery(document).ready(function($) {

    let set_image_uploader = function(button, field, default_size) {
        let btnTitle, temp_frame, title, typeInput, editText;
        default_size = default_size || 'thumbnail';
        title = __('Select an Image', 'typerocket-domain');
        btnTitle = __('Use Image', 'typerocket-domain');
        editText = __('Edit', 'typerocket-domain');
        typeInput = 'image';
        temp_frame = wp.media({
            title: title,
            button: {
                text: btnTitle
            },
            library: {
                type: typeInput
            },
            multiple: false
        });
        temp_frame.uploader.options.uploader.params.allowed_mime_types = 'image';
        temp_frame.on('select', function() {
            let attachment, url, btn, height, width, size, img, edit, thumb_url;
            btn = $(button);
            attachment = temp_frame.state().get('selection').first().toJSON();
            size = btn.data('size') ? btn.data('size') : default_size;

            if(attachment.sizes !== undefined) {
                if (attachment.sizes[size] === undefined) {
                    size = default_size;
                }

                if (attachment.sizes[size] === undefined) {
                    size = 'full';
                }

                thumb_url = attachment.sizes[size].url;
                height = attachment.sizes[size].height;
                width = attachment.sizes[size].width;
            } else {
                thumb_url = attachment.url;
                height = '';
                width = '';
            }

            url = window.trUtil.makeUrlHttpsMaybe(thumb_url);
            edit = '<a tabindex="0" class="dashicons dashicons-edit tr-image-edit" title="' + editText +'" target="_blank" href="'+ window.trHelpers.admin_uri + '/post.php?post=' + attachment.id +'&action=edit"></a>';
            img = '<img height="' + height + '" width="' + width + '" src="' + url + '"/>';
            $(field).val(attachment.id).trigger('change');

            $(button).parent().next().html(img + edit);
        });
        wp.media.frames.image_frame = temp_frame;
        wp.media.frames.image_frame.open();
    };

    let set_bg_uploader = function(button, field, default_size) {
        let btnTitle, temp_frame, title, typeInput;
        default_size = default_size || 'full';
        title = __('Select an Image', 'typerocket-domain');
        btnTitle = __('Use Image', 'typerocket-domain');
        typeInput = 'image';
        temp_frame = wp.media({
            title: title,
            button: {
                text: btnTitle
            },
            library: {
                type: typeInput
            },
            multiple: false
        });
        temp_frame.uploader.options.uploader.params.allowed_mime_types = 'image';
        temp_frame.on('select', function() {
            let attachment, url, btn, height, width, size, thumb_url;
            btn = $(button);
            attachment = temp_frame.state().get('selection').first().toJSON();
            size = btn.data('size') ? btn.data('size') : default_size;

            console.log(attachment);

            if(attachment.sizes !== undefined) {
                if (attachment.sizes[size] === undefined) {
                    size = default_size;
                }

                if (attachment.sizes[size] === undefined) {
                    size = 'full';
                }

                thumb_url = attachment.sizes[size].url;
                height = attachment.sizes[size].height;
                width = attachment.sizes[size].width;
            } else {
                thumb_url = attachment.url;
                height = '';
                width = '';
            }

            url = window.trUtil.makeUrlHttpsMaybe(thumb_url);

            $(field).val(attachment.id).trigger('change');
            $(field).parent().attr('style', `--tr-image-field-bg-src: url(${url});`);
            $(field).siblings('.tr-position-image').find('.tr-image-background-placeholder').first().html('<img height="' + height + '" width="' + width + '" src="' + url + '"/>');
        });
        wp.media.frames.image_frame = temp_frame;
        wp.media.frames.image_frame.open();
    };

    let set_file_uploader = function(button, field) {
        var btnTitle, temp_frame, title, typeInput, options;
        title = __('Select a File', 'typerocket-domain');
        btnTitle = __('Use File', 'typerocket-domain');
        typeInput = button.data('type'); // https://codex.wordpress.org/Function_Reference/get_allowed_mime_types
        options = {
            title: title,
            button: {
                text: btnTitle
            },
            library: {
                type: typeInput
            },
            multiple: false
        };
        temp_frame = wp.media(options);
        if(options.library.type) {
          temp_frame.uploader.options.uploader.params.allowed_mime_types = options.library.type;
        }
        temp_frame.on('select', function() {
            var attachment, link;
            attachment = temp_frame.state().get('selection').first().toJSON();
            link = '<a target="_blank" href="' + attachment.url + '">' + attachment.url + '</a>';
            $(field).val(attachment.id).trigger('change');
            $(button).parent().next().html(link);
        });
        wp.media.frames.file_frame = temp_frame;
        wp.media.frames.file_frame.open();
    };

    let set_gallery_uploader = function(button, list) {
        var btnTitle, temp_frame, title, editText;
        title = __('Select Images', 'typerocket-domain');
        btnTitle = __('Use Images', 'typerocket-domain');
        editText = __('Edit', 'typerocket-domain');
        temp_frame = wp.media({
            title: title,
            button: {
                text: btnTitle
            },
            library: {
                type: 'image'
            },
            multiple: 'toggle'
        });
        temp_frame.uploader.options.uploader.params.allowed_mime_types = 'image';
        temp_frame.on('select', function() {
            var attachment, field, i, item, l, use_url, height, width, size, btn, edit, thumb_url;
            attachment = temp_frame.state().get('selection').toJSON();
            l = attachment.length;
            i = 0;
            while (i < l) {
                btn = $(button);
                field = btn.parent().prev().clone();
                use_url = '';
                thumb_url = '';
                size = btn.data('size') ? btn.data('size') : 'thumbnail';

                if(attachment[i].sizes !== undefined) {
                    if (attachment[i].sizes[size] === undefined) {
                        size = 'full';
                    }

                    thumb_url = attachment[i].sizes[size].url;
                    height = attachment[i].sizes[size].height;
                    width = attachment[i].sizes[size].width;
                } else {
                    thumb_url = attachment[i].url;
                    height = '';
                    width = '';
                }

                use_url = thumb_url;
                edit = '<a tabindex="0" class="dashicons dashicons-edit tr-image-edit" target="_blank" title="' + editText +'" href="'+ window.trHelpers.admin_uri + '/post.php?post=' + attachment[i].id +'&action=edit"></a>';
                item = $('<li tabindex="0" class="tr-gallery-item tr-image-picker-placeholder"><a tabindex="0" class="dashicons dashicons-no-alt tr-gallery-remove" title="Remove Image"></a>'+edit+'<img height="' + height + '" width="' + width + '" src="' + use_url + '"/></li>');
                $(item).append(field.val(attachment[i].id).attr('name', field.attr('name') + '[]')).trigger('change');
                $(list).append(item);
                i++;
            }
        });
        wp.media.frames.gallery_frame = temp_frame;
        wp.media.frames.gallery_frame.open();
        return false;
    };

    let clear_gallery = function(button, field) {
        if (confirm(__('Remove all images?', 'typerocket-domain'))) {
            $(field).html('');
        }
    };

    let clear_media = function(button, field) {
        $(field).val('').trigger('change');
        $(button).parent().next().html('');
    };

    let clear_media_bg = function(button, field) {
        $(field).val('').trigger('change');
        $(field).parent().attr('style', '--tr-image-field-bg-src: transparent;');
        $(button).parent().next().children().first().html('');
    };

    // image
    $(document).on('click', '.tr-image-picker-button', function() {
        set_image_uploader($(this), $(this).parent().prev()[0]);
    });

    // file
    $(document).on('click', '.tr-file-picker-button', function() {
        set_file_uploader($(this), $(this).parent().prev()[0]);
    });

    // image & file
    $(document).on('click', '.tr-image-picker-clear, .tr-file-picker-clear', function() {
        clear_media($(this), $(this).parent().prev()[0]);
    });

    // background
    $(document).on('click', '.tr-image-bg-picker-button', function() {
        set_bg_uploader($(this), $(this).parent().prev()[0]);
    });

    $(document).on('click', '.tr-image-bg-picker-clear', function() {
        clear_media_bg($(this), $(this).parent().prev()[0]);
    });

    $(document).on('click', '.tr-image-background-placeholder img', function(e) {
        let posX = $(this).offset().left,
            posY = $(this).offset().top,
            w = $(this).width(),
            l = $(this).height();

        let x = (e.pageX - posX);
        let y = (e.pageY - posY);

        let iX = Math.round((x * 100) / w),
            iY = Math.round((y * 100) / l);

        let $container = $(this).parent(),
            $inputs = $container.parent().siblings('.tr-position-inputs').first();

        $container.parent().attr('style', `--tr-image-field-bg-x: ${iX}%; --tr-image-field-bg-y: ${iY}%;`);
        $inputs.find('.tr-pos-y').first().val(iY);
        $inputs.find('.tr-pos-x').first().val(iX);
    });

    $(document).on('keyup input', '.tr-pos-x', function(e) {
        let that = $(this);

        if (e.target.value === '' || e.target.value < 1) {
            e.target.value = 0
        }

        if (e.target.value > 100) {
            e.target.value = 100
        }

        e.target.value = parseInt(e.target.value, 10);

        window.trUtil.delay((function() {
            let iY = that.parent().parent().find('.tr-pos-y').first().val(),
                iX = that.val();

            that.parent().parent()
                .siblings('.tr-position-image')
                .first()
                .attr('style', `--tr-image-field-bg-x: ${iX}%; --tr-image-field-bg-y: ${iY}%;`);

        }), 350);
    });

    $(document).on('keyup input', '.tr-pos-y', function(e) {
        let that = $(this);

        if (e.target.value === '' || e.target.value < 1) {
            e.target.value = 0;
        }

        if (e.target.value > 100) {
            e.target.value = 100;
        }

        e.target.value = parseInt(e.target.value, 10);

        window.trUtil.delay((function() {
            let iX = that.parent().parent().find('.tr-pos-x').first().val(),
                iY = that.val();

            that.parent().parent()
                .siblings('.tr-position-image')
                .first()
                .attr('style', `--tr-image-field-bg-x: ${iX}%; --tr-image-field-bg-y: ${iY}%;`);

        }), 350);
    });

    // gallery
    $(document).on('click', '.tr-gallery-picker-button', function() {
        set_gallery_uploader($(this), $(this).parent().next()[0]);
    });

    $(document).on('click', '.tr-gallery-picker-clear', function() {
        clear_gallery($(this), $(this).parent().next()[0]);
    });

    $(document).on('click', '.tr-gallery-item', function(e) {
        $(this).focus();
    });

    $(document).on('click', '.tr-gallery-remove', function(e) {
        e.preventDefault();
        $(this).parent().remove();
    });
});