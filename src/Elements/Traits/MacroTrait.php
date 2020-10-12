<?php
namespace TypeRocket\Elements\Traits;

trait MacroTrait
{
    protected $macros = [];

    /**
     * Set Macro
     *
     * @param string $method
     * @param callable $callback
     *
     * @return $this
     */
    public function setMacro($method, $callback)
    {
        $this->macros[$method] = $callback;

        return $this;
    }

    /**
     * Get Macro
     *
     * @param string $method
     *
     * @return mixed
     */
    public function getMacro($method)
    {
        return $this->macros[$method];
    }

    /**
     * Get Macros
     *
     * @return array
     */
    public function getMacros()
    {
        return $this->macros;
    }

    /**
     * Remove Macro
     *
     * @param string $method
     *
     * @return $this
     */
    public function removeMacro($method)
    {
        if ( array_key_exists( $method, $this->macros ) ) {
            unset( $this->macros[ $method ] );
        }
        return $this;
    }

    /**
     * Call Macro
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (isset($this->macros[$method]) && $this->macros[$method] instanceof \Closure) {
            return call_user_func_array($this->macros[$method]->bindTo($this), $parameters);
        }

        throw new \BadMethodCallException("Method '{$method}' does not exist.");
    }
}