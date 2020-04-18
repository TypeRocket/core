<?php

namespace TypeRocket\Elements\Fields;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Html\Generator;
use TypeRocket\Html\Tag;

class Location extends Field
{

    protected $useGoogle = false;
    protected $useCountry = false;
    protected $locationLabels = [];

    /**
     * Init is normally used to setup initial configuration like a
     * constructor does.
     *
     * @return mixed
     */
    protected function init()
    {
        $this->setType( 'location' );
        $api = Config::locate('app.keys.google_maps');
    }

    protected function beforeEcho()
    {
        $api = Config::locate('app.api_keys.google_maps');
        $api = apply_filters('tr_field_location_google_api', $api);
        if($this->useGoogle && $api) {
            $this->paths = Config::locate('paths');
            $assetVersion = Config::locate('app.assets');
            $assets = $this->paths['urls']['assets'];

            wp_enqueue_script('tr_field_location_google_script',
                'https://maps.googleapis.com/maps/api/js?libraries=geometry&key=' . $api, [], $assetVersion, true);
            wp_enqueue_script('tr_field_location_script', $assets . '/typerocket/js/location.field.js',
                ['jquery', 'tr_field_location_google_script'], $assetVersion, true);
        }
    }

    /**
     * Configure in all concrete Field classes
     *
     * @return string
     */
    public function getString()
    {
        $class = $this->getAttribute('class');
        $values = $this->getValue();
        $name = $this->getNameAttributeString();
        $html = '<div class="tr_field_location_fields">';

        $labels = $this->locationLabels = array_merge([
            'city' => __('City', 'typerocket-domain'),
            'state' => __('State', 'typerocket-domain'),
            'zip' => __('Zip Code', 'typerocket-domain'),
            'country' => __('Country', 'typerocket-domain'),
            'address1' => __('Address', 'typerocket-domain'),
            'address2' => __('Address Line 2', 'typerocket-domain'),
            'lat' => __('Lat', 'typerocket-domain'),
            'lng' => __('Lng', 'typerocket-domain'),
            'generate' => __('Get Address Lat/Lng', 'typerocket-domain'),
            'clear' => __('Clear Address Lat/Lng', 'typerocket-domain'),
        ], $this->locationLabels);

        $cszc = ['city' => $labels['city'], 'state' => $labels['state'], 'zip' => $labels['zip']];

        if($this->useCountry) {
            $cszc['country'] = $labels['country'];
        }

        $field_groups = [
            ['address1' => $labels['address1']],
            ['address2' => $labels['address2']],
            $cszc
        ];

        if($this->useGoogle) {
            $field_groups[] = ['lat' => $labels['lat'], 'lng' => $labels['lng']];
        }

        foreach ($field_groups as $group) {
            $html .= '<div class="tr-flex-list tr-mt-10">';
            foreach($group as $field => $title ) {
                $attrs = [
                    'type' => 'text',
                    'value' => esc_attr( $values[$field] ?? '' ),
                    'name' => $name . '['. $field .']',
                    'class' => 'tr_field_location_' . $field
                ];
                echo '<div>';
                $html .= Tag::make('label',['class' => 'label-thin'], $title)->prependInnerTag(Tag::make('input', $attrs));
                echo '</div>';
            }
            $html .= '</div>';
        }

        if($this->useGoogle) {
            $html .= '<div class="tr_field_location_load_lat_lng_section button-group">
                <a class="button tr_field_location_load_lat_lng" type="button">'.$labels['generate'].'</a>
                <a class="button tr_field_location_clear_lat_lng" type="button">'.$labels['clear'].'</a>
                </div>
                <div class="tr_field_location_google_map"></div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Set labels
     *
     * @param array $labels
     *
     * @return $this
     */
    public function setLocationLabels(array $labels)
    {
        $this->locationLabels = $labels;
        return $this;
    }

    /**
     * Get labels
     *
     * @return $this
     */
    public function getLocationLabels()
    {
        return $this->locationLabels;
    }

    /**
     * Disable Country
     *
     * @return $this
     */
    public function enableCountry()
    {
        $this->useCountry = true;
        return $this;
    }


    /**
     * Use Google API
     *
     * @return $this
     */
    public function useGoogle()
    {
        $this->useGoogle = true;
        return $this;
    }

}