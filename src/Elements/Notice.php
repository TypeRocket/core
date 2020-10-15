<?php
namespace TypeRocket\Elements;

use TypeRocket\Http\Redirect;
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

    /**
     * @param array|null $data keys include message and type
     * @param bool $dismissible
     *
     * @return false|string|null
     */
    public static function flash($data = null, $dismissible = false)
    {
        if( !empty($_COOKIE[Redirect::KEY_ADMIN]) || !empty($data) ) {
            $flash = (new \TypeRocket\Http\Cookie)->getTransient(Redirect::KEY_ADMIN);

            return static::html([
                'message' => $data['message'] ?? $flash['message'] ?? null,
                'type' => $data['type'] ?? $flash['type'] ?? 'info',
            ], $dismissible);
        }

        return null;
    }
}