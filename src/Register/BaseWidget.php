<?php

namespace TypeRocket\Register;

abstract class BaseWidget extends \WP_Widget
{

    /** @var \TypeRocket\Elements\Form */
    protected $form;
    protected $newFields;
    protected $oldFields;

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        $this->frontend($args, $instance);
    }

    /**
     * Outputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form($instance) {
        echo '<div class="typerocket-container">';
        $this->form = tr_form()->useWidget($this, $instance);
        $this->backend($instance);
        echo '</div>';
    }

    public function update( $new_instance, $old_instance ) {
        $this->newFields = $new_instance;
        $this->oldFields = $old_instance;
        return $this->save($new_instance, $old_instance);
    }

    public function getNewFieldValue($name)
    {
        return !empty( $this->newFields[$name] ) ? $this->newFields[$name] : '';
    }

    public function getOldFieldValue($name)
    {
        return !empty( $this->oldFields[$name] ) ? $this->oldFields[$name] : '';
    }

    abstract public function backend($fields);
    abstract public function frontend($args, $fields);
    abstract public function save($new_fields, $old_fields);

}