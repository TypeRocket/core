<?php
namespace TypeRocket\Elements\Traits;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\BaseForm;

trait CloneFields
{
    /**
     * Clone Fieldset
     *
     * @param null|BaseForm $form
     *
     * @return $this
     */
    public function cloneToForm($form = null) {
        $clone = clone $this;
        return $clone->cloneElementsToForm($form);
    }

    /**
     * Clone Fieldset
     *
     * @param null|BaseForm $form
     *
     * @return $this
     */
    public function cloneElementsToForm($form = null) {
        if($this->fields && $form instanceof BaseForm) {
            /** @var Field|CloneFields $field */
            foreach ($this->fields as $i => $field) {
                if(!is_null($field) && method_exists($field, 'cloneToForm')) {
                    $this->fields[$i] = $field->cloneToForm(clone $form);
                }
            }
        }

        if(method_exists($this, 'afterCloneElementsToForm')) {
            $this->afterCloneElementsToForm($form);
        }

        $this->configureToForm($form);

        return $this;
    }

    /**
     * Configure To Form
     *
     * @param BaseForm|mixed $form
     *
     * @return $this
     */
    public function configureToForm(BaseForm $form)
    {
        if(property_exists($this, 'form')) {
            $this->form = clone $form;
        }

        if(property_exists($this, 'dots')) {
            $this->dots = $form->getGroup();
        }

        if(property_exists($this, 'contextRoot')) {
            $this->contextRoot = $form->getPrefix();
        }

        if($this->fields) {
            /** @var Field|CloneFields $field */
            foreach ($this->fields as $field) {
                if (!is_null($field) && method_exists($field, 'configureToForm')) {
                    $field->configureToForm($form);
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function cloneFields() {
        if($this->fields) {
            foreach ($this->fields as $i => $field) {
                $this->fields[$i] = clone $field;
            }
        }

        return $this;
    }

}