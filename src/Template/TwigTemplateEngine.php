<?php


namespace TypeRocket\Template;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use TypeRocket\Core\Config;

class TwigTemplateEngine extends TemplateEngine
{
    /**
     * Load Template
     */
    public function load()
    {
        $name = basename($this->file, '.php');

        $debug = Config::locate('app.debug');

        $cache = Config::locate('paths.base') . '/storage/cache';
        $cache = $debug ? false : Config::locate('paths.cache', $cache) . '/twig';

        $env = Config::locate('twig.env', [
            'debug' => $debug,
            'cache' => $cache,
        ]);

        $loader = new FilesystemLoader( dirname($this->file) );
        $twig = new Environment($loader, $env);

        $template = $twig->load( $name . '.twig' );
        echo $template->render($this->data);
    }
}