<?php
namespace TypeRocket\Extensions;

class PostMessages
{
    public function __construct()
    {
        add_action( 'post_updated_messages', [$this, 'setMessages']);
        add_action( 'bulk_post_updated_messages', [$this, 'setBulkMessages'], 10, 2);
    }

    /**
     * Set custom post type messages
     *
     * @param string $messages
     *
     * @return mixed
     */
    public function setMessages( $messages )
    {
        global $post;

        $pt = get_post_type( $post->ID );

        if ($pt != 'attachment' ) :

            $obj      = get_post_type_object( $pt );
            $singular = $obj->labels->singular_name;

            if ($obj->public == true) :
                /** @noinspection HtmlUnknownTarget */
                $view    = sprintf( __( '<a href="%s">View %s</a>' ), esc_url( get_permalink( $post->ID ) ), $singular );
                $preview = sprintf( __( '<a target="_blank" href="%s">Preview %s</a>' ),
                    esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ), $singular );
            else :
                $view = $preview = '';
            endif;

            $messages[$pt] = [
                1  => sprintf( __( '%s updated. %s' ), $singular, $view ),
                2  => __( 'Custom field updated.' ),
                3  => __( 'Custom field deleted.' ),
                4  => sprintf( __( '%s updated.' ), $singular ),
                5  => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s' ), $singular,
                    wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => sprintf( __( '%s published. %s' ), $singular, $view ),
                7  => sprintf( __( '%s saved.' ), $singular ),
                8  => sprintf( __( '%s submitted. %s' ), $singular, $preview ),
                9  => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. %s' ), $singular,
                    date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ), $preview ),
                10 => sprintf( __( '%s draft updated. ' ), $singular ),
            ];

        endif;

        return $messages;
    }

    /**
     * Set custom post type bulk messages to make more since.
     *
     * @param array $bulk_messages
     * @param array $bulk_counts
     *
     * @return mixed
     */
    public function setBulkMessages($bulk_messages, $bulk_counts)
    {
        global $post;
        if(empty($post)) { return $bulk_messages; }

        $pt = get_post_type( $post->ID );

        if ($pt != 'attachment') :
            $obj      = get_post_type_object( $pt );
            $singular = strtolower($obj->labels->singular_name);
            $plural   = strtolower($obj->labels->name);

            $bulk_messages[$pt] = array(
                'updated'   => _n( "%s {$singular} updated.", "%s {$plural} updated.", $bulk_counts["updated"] ),
                'locked'    => _n( "%s {$singular} not updated, somebody is editing it.", "%s {$plural} not updated, somebody is editing them.", $bulk_counts["locked"] ),
                'deleted'   => _n( "%s {$singular} permanently deleted.", "%s {$plural} permanently deleted.", $bulk_counts["deleted"] ),
                'trashed'   => _n( "%s {$singular} moved to the Trash.", "%s {$plural} moved to the Trash.", $bulk_counts["trashed"] ),
                'untrashed' => _n( "%s {$singular} restored from the Trash.", "%s {$plural} restored from the Trash.", $bulk_counts["untrashed"] ),
            );
        endif;

        return $bulk_messages;
    }

}