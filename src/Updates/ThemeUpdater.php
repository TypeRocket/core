<?php
namespace TypeRocket\Updates;


class ThemeUpdater
{
    public $data = [
        'slug' => null,
        'api_url' => null,
        'transient_name' => null,
        'cache' => 43200, // 12 hours
    ];

    public function __construct(array $data)
    {
        $this->data = array_merge($this->data, $data);

        add_action('upgrader_process_complete', [$this, 'cleanup'], 10, 2 );
        add_filter('themes_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_themes', [$this, 'update'] );
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
            $this->data['locate'] = $this->data['slug'];
        }

        return $key ? $this->data[$key] : $this->data;
    }

    public function getApiJsonResponseBody()
    {
        $transient = $this->getData('transient_name');

        if( false == $remote = get_transient( $transient ) ) {

            // info.json is the file with the actual plugin information on your server
            $remote = wp_remote_get( $this->getData('api_url'), [
                    'timeout' => 10,
                    'headers' => [
                        'Accept' => 'application/json'
                    ]]
            );

            if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
                set_transient( $transient, $remote, $this->getData('cache') );
            }
        }

        return json_decode(wp_remote_retrieve_body($remote));
    }

    function info( $res, $action, $args ){

        // do nothing if this is not about getting plugin information
        if( $action !== 'theme_information' )
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

            return $res;
        }

        return false;

    }

    function update( $transient ){

        if ( empty($transient->checked ) ) {
            return $transient;
        }

        $locate = $this->getData('locate');

        if( ! $version = $transient->checked[$locate] ) {
            return $transient;
        }

        if( $remote = $this->getApiJsonResponseBody() ) {
            if( $remote && version_compare( $version, $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
                $res['slug'] = $this->getData('slug');
                $res['theme'] = $locate;
                $res['new_version'] = $remote->version;
                $res['tested'] = $remote->tested;
                $res['package'] = $remote->download_url;
                $res['url'] = $remote->homepage;
                $transient->response[$locate] = $res;
                $transient->checked[$locate] = $locate;
            }

        }
        return $transient;
    }

    function cleanup( $upgrader_object, $options ) {
        if ( $options['action'] == 'update' && $options['type'] === 'theme' )  {
            delete_transient( $this->getData('transient_name') );
        }
    }
}