<?php

namespace TypeRocket\Elements;

use TypeRocket\Utility\Str;

class Notice
{
    /**
     *  Flash dismissible notice
     *
     * Notice can be closed
     *
     * @param array $data
     */
    public static function dismissible( $data )
    {

        $classes = 'notice-' . $data['type'];
        if( !empty($data) ) {
            if( Str::starts('<ul>', $data['message']) ) {
                $message = $data['message'];
            } else {
                $message = "<p>" . $data['message'] . "</p>";
            }
            ?>
            <div class="notice tr-admin-notice <?php echo $classes; ?> is-dismissible">
                <?php echo $message; ?>
            </div>
            <?php
        }
    }

    /**
     *  Flash permanent notice
     *
     *  Notice can not be closed
     *
     * @param array $data
     */
    public static function permanent( $data )
    {
        $classes = 'notice-' . $data['type'];
        if( !empty($data) ) {
            ?>
            <div class="notice tr-admin-notice <?php echo $classes; ?>">
                <p><?php echo $data['message']; ?></p>
            </div>
            <?php
        }
    }
}