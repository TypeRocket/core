<?php
namespace TypeRocket\Plugins;

class Sitemap
{
    public $match = 'seo\/sitemap\.xml';
    public $noTrailing = true;
    public $do = 'index@Sitemap:\TypeRocket\Plugins\Controllers\SitemapController';

    function __construct()
    {
        do_action('trp_sitemap', $this);

        add_action('tr_load_routes', function() {
            tr_route()
                ->get()
                ->noTrailingSlash($this->noTrailing)
                ->match($this->match)
                ->do($this->do);
        });
    }
}