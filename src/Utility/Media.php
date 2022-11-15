<?php

namespace TypeRocket\Utility;

class Media
{
    /**
     * Upload a file to the media library using a URL.
     *
     * Example usage: Upload photo from URL, display the attachment as html <img>
     *   $attachment_id = TypeRocket\Utility\Media::uploadFromUrl( "http://example.com/images/photo.png" );
     *   echo wp_get_attachment_image( $attachment_id, 'large' );
     *
     * @param string $url         URL to be uploaded
     * @param null|string $title  If set, used as the post_title
     * @param int $timeout  How long to wait in seconds for download to complete
     *
     * @return int|null
     */
    public static function uploadFromUrl(string $url, ?string $title = null, int $timeout = 300) : ?int
    {
        require_once( ABSPATH . "/wp-load.php");
        require_once( ABSPATH . "/wp-admin/includes/image.php");
        require_once( ABSPATH . "/wp-admin/includes/file.php");
        require_once( ABSPATH . "/wp-admin/includes/media.php");

        $tmp = download_url( $url, $timeout );
        if ( is_wp_error( $tmp ) ) return null;

        $filename = pathinfo($url, PATHINFO_FILENAME);
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        if ( ! $extension ) {
            $mime = mime_content_type( $tmp );
            $mime = is_string($mime) ? sanitize_mime_type( $mime ) : false;


            $mime_extensions = array(
                'text/plain'         => 'txt',
                'text/csv'           => 'csv',
                'application/msword' => 'doc',
                'image/jpg'          => 'jpg',
                'image/jpeg'         => 'jpeg',
                'image/gif'          => 'gif',
                'image/png'          => 'png',
                'video/mp4'          => 'mp4',
            );

            if ( isset( $mime_extensions[$mime] ) ) {
                $extension = $mime_extensions[$mime];
            } else {
                try {
                    unlink($tmp);
                } catch (\Throwable $t) {}
                return null;
            }
        }

        if(str_contains($extension, '?')) {
            $extension = explode('?', $extension, 2)[0];
        }

        $allowed = get_allowed_mime_types();
        $extension = wp_check_filetype($extension, $allowed)['ext'];

        if(! in_array($extension, $allowed)) {
            return null;
        }

        // Upload by "sideloading": "the same way as an uploaded file is handled by media_handle_upload"
        $args = [
            'name' => "$filename.$extension",
            'tmp_name' => $tmp,
        ];

        // Do the upload
        $attachment_id = media_handle_sideload($args, 0, $title);

        // Cleanup temp file
        try {
            unlink($tmp);
        } catch (\Throwable $t) {}

        // Error uploading
        if ( is_wp_error($attachment_id) ) return null;

        // Success, return attachment ID (int)
        return (int) $attachment_id;
    }
}