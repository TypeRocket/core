<?php
namespace TypeRocket\Elements;

use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;

class Notice
{
    /**
     * Get Flash notice as a string
     *
     * @param array $data keys include message and type
     * @param bool $dismissible
     *
     * @return false|string
     */
    public static function html($data, $dismissible = false)
    {
        ob_start();

        if($dismissible) {
            static::dismissible($data);
        } else {
            static::permanent($data);
        }

        return ob_get_clean();
    }

    /**
     * Flash dismissible notice
     *
     * Notice can be closed
     *
     * @param array $data keys include message and type
     */
    public static function dismissible( $data )
    {
        $classes = 'notice-' . Sanitize::dash($data['type'] ?? 'success');
        if( !empty($data['message']) ) {
            if( Str::starts('<ul>', $data['message']) ) {
                $message = $data['message'];
            } else {
                $message = "<p>" . $data['message'] . "</p>";
            }
            ?>
            <div class="notice tr-admin-notice <?php echo $classes; ?> is-dismissible">
                <?php echo Sanitize::html($message, null, 'notice'); ?>
            </div>
            <?php
        }
    }

    /**
     * Flash permanent notice
     *
     * Notice can not be closed
     *
     * @param array $data keys include message and type
     */
    public static function permanent( $data )
    {
        $classes = 'notice-' . Sanitize::dash($data['type'] ?? 'success');
        if( !empty($data['message']) ) {
            if( Str::starts('<ul>', $data['message']) ) {
                $message = $data['message'];
            } else {
                $message = "<p>" . $data['message'] . "</p>";
            }
            ?>
            <div class="notice tr-admin-notice <?php echo $classes; ?>">
                <?php echo Sanitize::html($message, null, 'notice'); ?>
            </div>
            <?php
        }
    }

    /**
     * @param array $data keys include message and type
     * @param false $dismissible
     */
    public static function admin($data, $dismissible = false)
    {
        $notice = static::html($data, $dismissible);

        add_action('admin_notices', function() use ($notice) {
            echo $notice;
        });
    }
}