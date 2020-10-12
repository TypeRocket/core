<?php
namespace TypeRocket\Exceptions;

class RedirectError extends \Exception
{
    protected $redirect;

    public function redirect($redirect = null)
    {
        if($redirect) {
            $this->redirect = $redirect;

            return $this;
        }

        return $this->redirect;
    }
}