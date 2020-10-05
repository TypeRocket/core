<?php
namespace TypeRocket\Plugins\Controllers;

use TypeRocket\Controllers\Controller;

class SitemapController extends Controller
{
    public function index()
    {
        $post_types = apply_filters('trp_sitemap_post_types', ['page', 'post']);

        $pages = (new \WP_Query(['post_type' => $post_types, 'posts_per_page' => -1, 'post_status' => 'publish']))->get_posts();

        header("Content-type: text/xml");

        return tr_view(__DIR__ . '/../views/sitemap-xml.php', compact('pages'));
    }
}