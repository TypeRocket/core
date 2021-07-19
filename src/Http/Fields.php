<?php
namespace TypeRocket\Http;

use TypeRocket\Interfaces\Formable;
use TypeRocket\Models\Traits\FieldValue;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\Validator;

class Fields extends \ArrayObject implements Formable
{
    use FieldValue;

    protected $fillable = [];
    protected $rules = [];
    protected $messages = [];
    protected $messagesRegex = true;
    protected $run;
    /** @var Validator|null */
    protected $validator;
    /** @var string Model calss for validator */
    protected $modelClass;
    /** @var string Requests fields group using dot notation to pass data to model */
    protected $modelFieldsGroup;

    /**
     * Load commands
     *
     * @param array|null $fields
     *
     * @throws \TypeRocket\Exceptions\RedirectError
     * @throws \Exception
     */
    public function __construct( $fields = null ) {
        parent::__construct();

        if( empty($fields) ) {
            $fields = (new Request)->getFields();
        }

        $this->exchangeArray( $fields ?? [] );
        $this->fillable = array_merge($this->fillable, $this->fillable());
        $this->rules = array_merge($this->rules, $this->rules());
        $this->messages = array_merge($this->messages, $this->messages());

        if($this->run) {
            $this->runAndRespond();
        }
    }

    /**
     * Run Fields Validation
     *
     * @param null|string $type
     */
    public function runAndRespond($type = null)
    {
        $this->run = $type ?? $this->run;

        if(!$this->validator) {
            $this->validate();
        }

        if($this->run !== 'response') {
            $this->validator->redirectWithErrorsIfFailed([$this, 'afterRespond']);
        }

        $this->validator->respondWithErrors([$this, 'afterRespond']);
    }

    /**
     * @param Response|Redirect $object
     *
     * @throws \Exception
     */
    public function afterRespond($object)
    {
        if($object instanceof Redirect) {
            $this->redirect($object);
        }

        if($object instanceof Response) {
            $this->response($object);
        }
    }

    /**
     * Get Field
     *
     * @param array|string|null $key dot notation key.next.final
     * @param mixed $default
     *
     * @return array|mixed|object|null
     */
    public function get($key = null, $default = null)
    {
        $data = $this->getArrayCopy();
        $value = is_null($key) ? $data : Data::walk($key, $data);

        return $value ?? $default;
    }

    /**
     * @return array
     */
    public function getFormFields()
    {
        $data = $this->get();
        $result = [];

        foreach ($data as $i => $item) {
            if($item instanceof Formable) {
                $result[$i] = $item->getFormFields();
            } else {
                $result[$i] = $item;
            }
        }

        return $result;
    }

    /**
     * Get Fields For Model
     *
     * @return array|mixed|object|null
     */
    public function getModelFields()
    {
        return $this->get($this->modelFieldsGroup);
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
     * @param Response $response
     *
     * @return Response
     */
    public function response(Response $response)
    {
        return $response;
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

        if( $this->validator ) {
            throw new \Exception('Validation already run.');
        }

        $this->validator = new Validator($rules, $this->getArrayCopy(), $modelClass ?? $this->modelClass, false);

        if(!empty($this->messages)) {
            $this->validator->setErrorMessages($this->messages, $this->messagesRegex);
        }

        $this->beforeValidate($this->validator);
        $this->validator->validate(true);
        $this->afterValidate($this->validator);

        return $this->validator;
    }

    /**
     * @param Validator $validator
     */
    public function beforeValidate($validator)
    {
    }

    /**
     * @param Validator $validator
     */
    public function afterValidate($validator)
    {
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