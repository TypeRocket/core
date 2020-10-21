<?php
namespace TypeRocket\Http;

use TypeRocket\Models\Model;

class WordPressRestAPI
{
    public static function registerModelMetaFields(Model $model)
    {
        $type = $model->getRestMetaType();
        $fields = $model->getRestMetaFieldsCompiled();

        foreach ($fields as $field => $args) {
            register_meta($type, $field, $args);
        }
    }
}