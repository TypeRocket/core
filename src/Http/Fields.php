<?php
namespace TypeRocket\Http;

use TypeRocket\Utility\Validator;

class Fields extends \ArrayObject
{
    protected $fillable = [];
    protected $rules = [];
    protected $messages = [];
    protected $run = false;
    protected $validator;
    protected $modelClass;

    /**
     * Load commands
     *
     * @param array $fields
     *
     * @throws \TypeRocket\Exceptions\RedirectError
     * @throws \Exception
     */
    public function __construct( $fields = [] ) {
        parent::__construct();

        if( empty($fields) ) {
            $fields = (new Request)->getFields();
        }

        $this->exchangeArray( $fields );
        $this->fillable = array_merge($this->fillable, $this->fillable());
        $this->rules = array_merge($this->rules, $this->rules());
        $this->messages = array_merge($this->messages, $this->messages());

        if($this->run) {
            $this->validate()->redirectWithErrorsIfFailed([$this, 'redirect']);
        }
    }

    /**
     * @return array
     */
    protected function fillable() {
        return [];
    }

    /**
     * @return array
     */
    protected function rules() {
        return [];
    }

    /**
     * @return array
     */
    protected function messages() {
        return [];
    }

    /**
     * @param Redirect $redirect
     *
     * @return Redirect
     */
    public function redirect(Redirect $redirect)
    {
        return $redirect;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     *
     * @return $this
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     *
     * @return $this
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Get fillable
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set fillable
     *
     * @param array $fillable
     *
     * @return $this
     */
    public function setFillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Validate fields
     *
     * @param array|null $rules
     * @param string|null $modelClass
     *
     * @return Validator
     * @throws \Exception
     */
    public function validate($rules = null, $modelClass = null)
    {
        if( ! $rules ) {
            $rules = $this->rules;
        }

        if( empty($rules) ) {
            throw new \Exception('No options for validator set.');
        }

        $this->validator = new Validator($rules, $this->getArrayCopy(), $this->modelClass ?? $modelClass, false);

        if(!empty($this->messages)) {
            $this->validator->setErrorMessages($this->messages, true);
        }

        return $this->validator->validate(true);
    }

    /**
     * @return mixed|Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param mixed ...$args
     *
     * @return static
     */
    public static function new(...$args)
    {
        return new static(...$args);
    }
}