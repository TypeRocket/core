<?php

namespace TypeRocket\Utility;

class ValidationRule {

  private $config = [];
  private $count = 0;
  private $limit = 0;
  private $options = [];
  private $rule = '';
  private $type = '';

  function __construct( $type, $value, $field, $options ) {
    $this->field = $field;
    $this->type = $type;
    $this->value = $value;
    $this->setOptions( $options );
    $this->setAttributes();
    $this->setConfig();
    $this->setMessage();
  }

  private function getSetFunc( $string ) {
    $func = implode( '_', ['set', $this->type, $string] );
    return Str::camelize( $func, '_', false );
  }


  /*
  ==========================================================================
  set attributes
  ==========================================================================
  */
  private function setAttributes() {
    $func = $this->getSetFunc( 'attributes' );
    $this->$func( $this->field );
  }

  private function setCompcountAttributes( $field ) {
    if( empty( $this->options[2] ) ) return;
    $subfield = $this->options[2];

    $this->count = count( array_column( $this->value, $subfield ) );

    $builder = tr_form()->builder( Str::title( $field ) );
    $builder_opts = $builder->setOptionsFromFolder()->getOptions();
    $field = '<strong>"' . array_search( $subfield, $builder_opts ) . '"</strong>';

    $this->field = $field;
  }

  private function setRepcountAttributes( $field ) {
    $field = explode( ' ', $field );
    $field = Str::title( end( $field ) );
    $field = '<strong>"' . $field . '"</strong>';

    // $this->field = $field;
  }


  /*
  ==========================================================================
  set options
  ==========================================================================
  */
  private function setOptions( $options ) {
    $options = explode( ',', $options );
    $this->options = $options;
    $this->rule = $options[0];
    $this->limit = $options[1];

    if(is_array($this->value)) $this->count = count( $this->value );
  }


  /*
  ==========================================================================
  set config
  ==========================================================================
  */
  private function setConfig() {
    $config = [
      'max' => [
        'start' => 'a maximum of',
        'end' => 'is allowed',
        'cond' => $this->count > $this->limit
      ],
      'min' => [
        'start' => 'a minimum of',
        'end' => 'is required',
        'cond' => $this->count < $this->limit
      ],
      'size' => [
        'start' => 'there cannot be more or less than',
        'end' => 'on this page',
        'cond' => $this->count != $this->limit
      ]
    ];
    if( $this->type == 'repcount' ) $config['size']['end'] = '';
    $config = $config[$this->rule];

    $this->config = $config;
  }


  /*
  ==========================================================================
  set message
  ==========================================================================
  */
  private function setMessage() {
    $func = $this->getSetFunc( 'message' );
    $message = $this->$func();
    if( !empty( $this->config['end'] ) ) {
      $message[] = $this->config['end'];
    }
    $message = implode( ' ', $message ) . '.';

    $this->message = $message;
  }

  private function setCompcountMessage() {
    $message = [
      ucfirst( $this->config['start'] ),
      Inflect::pluralizeIf( $this->limit, $this->field . ' component' )
    ];

    return $message;
  }

  private function setRepcountMessage() {
    $message = [
      ucfirst( $this->config['start'] ),
      Inflect::pluralizeIf( $this->limit, 'row' ),
      'in the ' . $this->field  . ' repeater'
    ];

    return $message;
  }


  /*
  ==========================================================================
  get message
  ==========================================================================
  */
  public function getMessage() {
    return $this->message;
  }


  /*
  ==========================================================================
  get condition met attribute
  ==========================================================================
  */
  public function conditionMet() {
    return $this->config['cond'];
  }

}

?>
