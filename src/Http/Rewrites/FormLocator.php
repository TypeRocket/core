<?php
namespace TypeRocket\Http\Rewrites;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Utility\Helper;

class FormLocator
{
    /**
     * @param $group
     * @param $type
     *
     * @return BaseForm
     */
    public function getForm($group, $type)
    {
        $formGroup = $_POST['form_group'] ?? null;
        $prefix = $_POST['_tr_form_prefix'] ?? null;
        $index = Helper::hash();

        $form_class = \TypeRocket\Core\Config::get('app.class.form', BaseForm::class);
        /** @var BaseForm $form */
        $form = new $form_class;

        $form->setPopulate(false)->setDebugStatus(false);

        if( $formGroup ) {
            $formGroup .= '.';
        }

        if($prefix) {
            $form->setPrefix($prefix);
        }

        $form->setGroup($formGroup . "{$group}.{$index}.{$type}");

        return $form;
    }
}