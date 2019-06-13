<?php

namespace TypeRocket\Models\Meta;

use TypeRocket\Database\ResultsMeta;
use TypeRocket\Models\Model;

class WPTermMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'termmeta';
    protected $resultsClass = ResultsMeta::class;

    protected $builtin = [
        'meta_id',
        'term_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    public function term( $modelClass ) {
        return $this->belongsTo( $modelClass, 'term_id' );
    }
}