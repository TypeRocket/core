<?php


namespace TypeRocket\Core;


use TypeRocket\Utility\UpdateApi;

/**
 * Class Updater
 * @package TypeRocket\Core
 *
 * @link https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
 */
class Updater
{

    /**
     * @var UpdateApi
     */
    private $api;
    private $cache = 43200; // 12 hours

    public function __construct(UpdateApi $api)
    {
        $this->api = $api;

        if($this->api->type == 'plugin') {
            add_action('upgrader_process_complete', [$this, 'pluginCleanup'], 10, 2 );
            add_filter('plugins_api', [$this, 'pluginInfo'], 20, 3);
            add_filter('site_transient_update_plugins', [$this, 'pluginUpdate'] );
        }
    }

    public function pluginGetRemote()
    {
        if( false == $remote = get_transient( $this->api->slug_underscored ) ) {

            // info.json is the file with the actual plugin information on your server
            $remote = wp_remote_get( $this->api->url, [
                    'timeout' => 10,
                    'headers' => [
                        'Accept' => 'application/json'
                    ]]
            );

            if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
                set_transient( $this->api->slug_underscored, $remote, $this->cache );
            }

        }

        return $remote;
    }

    function pluginInfo( $res, $action, $args ){

        // do nothing if this is not about getting plugin information
        if( $action !== 'plugin_information' )
            return false;

        // do nothing if it is not our plugin
        if( $this->api->slug !== $args->slug )
            return $res;

        // trying to get from cache first, to disable cache comment 18,28,29,30,32
        if( $remote = $this->pluginGetRemote()) {
            $remote = json_decode( $remote['body'] );
            $res = new \stdClass();
            $res->name = $remote->name;
            $res->slug = $this->api->slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author;
            $res->download_link = $remote->download_url;
            $res->trunk = $remote->download_url;
            $res->contributors = $remote->contributors;
            $res->requires_php = $remote->requires_php;
            $res->homepage = $remote->homepage;
            $res->last_updated = $remote->last_updated;
            $res->sections = [
                'description' => $remote->sections->description,
                'installation' => $remote->sections->installation,
                'changelog' => $remote->sections->changelog,
            ];

            if($remote->banner['high']) {
                $res->banners = [
                    'high' => $remote->banner['high']
                ];
            }

            return $res;
        }

        return false;

    }


    function pluginUpdate( $transient ){

        if ( empty($transient->checked ) ) {
            return $transient;
        }

        if( $remote = $this->pluginGetRemote() ) {

            $remote = json_decode( $remote['body'] );

            // your installed plugin version should be on the line below! You can obtain it dynamically of course
            if( $remote && version_compare( '1.0', $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
                $res = new \stdClass();
                $res->slug = $this->api->slug;
                $res->plugin = $this->api->locate;
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->download_url;
                $res->url = $remote->homepage;
                $transient->response[$res->plugin] = $res;
            }

        }
        return $transient;
    }

    function pluginCleanup( $upgrader_object, $options ) {
        if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
            // just clean the cache when new plugin version is installed
            delete_transient( $this->api->slug_underscored );
        }
    }
}