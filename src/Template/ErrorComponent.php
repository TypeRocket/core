<?php
namespace TypeRocket\Template;

class ErrorComponent extends Component
{
    public function __construct()
    {
        parent::__construct();
        $this->thumbnail = \TypeRocket\Core\Config::get('urls.components') . '/tr-error-component.png';
    }

    /**
     * Admin Fields
     */
    public function fields()
    {
        $title = $this->title();
        echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-warning\"></i> '{$title}' component not found</div>";
    }

    /**
     * Render
     *
     * @var array $data component fields
     * @var array $info name, item_id, model, first_item, last_item, component_id, hash
     */
    public function render(array $data, array $info)
    {
        $title = $this->title();
        echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon dashicons dashicons-warning\"></i> '{$title}' component not found</div>";
        var_dump($data, $info);
    }
}