<?php
namespace TypeRocket\Http\Rewrites;

class Builder extends FormLocator
{

    public function __construct($group, $type)
    {
        $form = $this->getForm($group, $type);
        $class = \TypeRocket\Elements\Fields\Matrix::getComponentClass($type, $group)->form($form)->data($form->getModel());
        \TypeRocket\Elements\Fields\Builder::componentTemplate($class, $group);
        \TypeRocket\Elements\Fields\Builder::componentTile($class, $group);
    }

}