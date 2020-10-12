const { __ } = wp.i18n;
import {tr_media_model} from './fn/media-wp';

if(typeof Redactor !== 'undefined') {



    (function($R) {
        $R.add('plugin', 'wpmedia', {
            init: function(app)
            {
                this.app = app;
                this.insertion = app.insertion;
                this.toolbar = app.toolbar;
                this.component = app.component;
                this.inspector = app.inspector;
            },
            start: function()
            {
                // add the button to the toolbar
                let $button = this.toolbar.addButton('wpmedia', {
                    title: 'WordPress Media',
                    api: 'plugin.wpmedia.toggle'
                });
                $button.setIcon('<i class="dashicons dashicons-admin-media"></i>');
            },
            toggle: function()
            {
                this._media();
            },
            _media: function() {
                // @link https://stackoverflow.com/questions/20101909/wordpress-media-uploader-with-size-select
                let title = __('Select an Image', 'typerocket-domain'),
                    btnTitle = __('Use Image', 'typerocket-domain'),
                    self = this,
                    typeInput = 'image';

                let cb = function(selection) {
                    let state = temp_frame.state(),
                        sel = state.get('selection').first(),
                        attachment = sel.toJSON(),
                        display = state.display(sel).toJSON(),
                        classes = [],
                        size = display.size || 'full',
                        open_link = '',
                        close_link = '';

                    if (attachment.sizes[size] === 'undefined') {
                        size = 'full';
                    }

                    let url = window.trUtil.makeUrlHttpsMaybe(attachment.sizes[size].url);
                    let full_url = window.trUtil.makeUrlHttpsMaybe(attachment.sizes['full'].url);
                    let height = attachment.sizes[size].height;
                    let width = attachment.sizes[size].width;
                    let alt = attachment.alt;

                    let align = {
                        "left": "alignleft",
                        "right": "alignright",
                        "center": "aligncenter",
                    };

                    if(align[display.align] !== 'undefined') {
                        classes.push(align[display.align]);
                    }

                    if(display.link === 'custom') {
                        open_link = `<a href="${display.linkUrl}">`;
                        close_link = '</a>';
                    }
                    else if(display.link === 'file') {
                        open_link = `<a href="${full_url}">`;
                        close_link = '</a>';
                    }
                    else if(display.link === 'post') {
                        open_link = `<a href="${attachment.link}">`;
                        close_link = '</a>';
                    }

                    self._insert(`<figure class="${classes.join(' ')}">${open_link}<img height="${height}" width="${width}" src="${url}" alt="${alt}"/>${close_link}</figure>`)
                };

                let temp_frame = tr_media_model({
                    title: title,
                    button: {
                        text: btnTitle
                    },
                    editable:   true,
                    library: {
                        type: typeInput,
                    }
                }, cb, {});

                temp_frame.uploader.options.uploader.params.allowed_mime_types = 'image';
                // temp_frame.on('select', );
                temp_frame.open();
            },
            _insert: function(imgHtml) {
                this.insertion.insertHtml(imgHtml);
            }
        });
    })(Redactor);
}