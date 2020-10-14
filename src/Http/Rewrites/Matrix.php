<?php
namespace TypeRocket\Http\Rewrites;

class Matrix extends FormLocator
{

    public function __construct($group, $type)
    {
        $form = $this->getForm($group, $type);
        $class = \TypeRocket\Elements\Fields\Matrix::getComponentClass($type, $group)->form($form)->data($form->getModel());
        \TypeRocket\Elements\Fields\Matrix::componentTemplate($class, $group);
    }

}