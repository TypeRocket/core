<?php
namespace TypeRocket\Console;

class Config
{
    static private $config = null;

    /**
     * Set initial values
     *
     * @param array $config
     */
    public function __construct( $config = [] )
    {
        $key = array_search('configurations', array_keys( $config ), true);
        if ($key !== false) {
            $slice = array_slice($config, $key, null, true);
            $config = array_merge($slice['configurations'], ['galaxy']);
        } else {
            $config = ['galaxy'];
        }

        if (self::$config === null) {
            foreach ($config as $type) {
                self::$config[$type] = include TR_PATH . "/config/{$type}.php";
            }
        }
    }

    /**
     * Get Config
     *
     * @return null
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Get the WordPress root directory
     *
     * @return null
     */
    public static function getWordPressPath()
    {
        return self::$config['galaxy']['wordpress'];
    }

    /**
     * Get Custom Commands
     *
     * @return null
     */
    public static function getCommands()
    {
        $commands = self::$config['galaxy']['commands'];
        return !empty($commands) ? $commands : [];
    }
}