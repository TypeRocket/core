<?php
namespace TypeRocket\Updates;


class PluginUpdater
{
    public $data = [
        'slug' => null,
        'api_url' => null,
        'locate' => null,
        'transient_name' => null,
        'cache' => 43200, // 12 hours
    ];

    public function __construct(array $data)
    {
        $this->data = array_merge($this->data, $data);

        add_action('upgrader_process_complete', [$this, 'cleanup'], 10, 2 );
        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update'] );
    }

    /**
     * @param null|string $key
     * @return array|mixed
     */
    public function getData($key = null)
    {
        if(empty($this->data['transient_name'])) {
            $this->data['transient_name'] = 'update_' . str_replace('-', '_', $this->data['slug']);
        }

        if(empty($this->data['locate'])) {
            $this->data['locate'] = $this->data['slug'] . '/' . $this->data['slug'] . '.php';
        }

        return $key ? $this->data[$key] : $this->data;
    }

    public function getApiJsonResponseBody()
    {
        $transient = $this->getData('transient_name');
        $remote = get_transient( $transient );

        if( false == $remote ) {

            // info.json is the file with the actual plugin information on your server
            $remote = wp_remote_get( $this->getData('api_url'), [
                    'timeout' => 10,
                    'headers' => [
                        'Accept' => 'application/json'
                    ]]
            );

            $code = $remote['response']['code'] ?? null;
            $body = $remote['body'] ?? null;
            $error = is_wp_error( $remote );

            if ( !$error && $code == 200 && $body ) {
                set_transient( $transient, $remote, $this->getData('cache') );
            } elseif(!$error && $code == 404 && $body) {
                set_transient( $transient, $remote, 500 );
            }
        }

        return empty($remote['body']) ? false : json_decode( $remote['body'] );
    }

    function info( $res, $action, $args ){

        // do nothing if this is not about getting plugin information
        if( $action !== 'plugin_information' )
            return false;

        $slug = $this->getData('slug');

        // do nothing if it is not our plugin
        if( $slug !== $args->slug )
            return $res;

        // trying to get from cache first, to disable cache comment 18,28,29,30,32
        if( $remote = $this->getApiJsonResponseBody()) {
            $cont = json_decode(json_encode($remote->contributors), true);

            $res = new \stdClass();
            $res->name = $remote->name;
            $res->slug = $slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author;
            $res->download_link = $remote->download_url;
            $res->trunk = $remote->download_url;
            $res->contributors = $cont;
            $res->requires_php = $remote->requires_php;
            $res->homepage = $remote->homepage;
            $res->last_updated = $remote->last_updated;
            $res->sections = [
                'description' => $remote->sections->description,
                'installation' => $remote->sections->installation,
                'changelog' => $remote->sections->changelog,
            ];

            if($remote->banners->high) {
                $res->banners = [
                    'high' => $remote->banners->high,
                    'low' => $remote->banners->low ?? false,
                ];
            }

            return $res;
        }

        return false;

    }

    function update( $transient ){

        if ( empty($transient->checked ) ) {
            return $transient;
        }

        if( $remote = $this->getApiJsonResponseBody() ) {

            $locate = $this->getData('locate');

            if( ! $version = $transient->checked[$locate] ) {
                return $transient;
            }

            if( $remote && version_compare( $version, $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
                $res = new \stdClass();
                $res->slug = $this->getData('slug');
                $res->plugin = $locate;
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->download_url;
                $res->url = $remote->homepage;
                $transient->response[$locate] = $res;
                $transient->checked[$locate] = $remote->version;
            }

        }
        return $transient;
    }

    function cleanup( $upgrader_object, $options ) {
        if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
            delete_transient( $this->getData('transient_name') );
        }
    }
}