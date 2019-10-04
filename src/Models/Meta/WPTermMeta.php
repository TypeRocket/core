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

    /**
     * Term
     *
     * @param $modelClass
     * @return WPTermMeta|null
     */
    public function term( $modelClass ) {
        return $this->belongsTo( $modelClass, 'term_id' );
    }

    /**
     * Not Private
     *
     * @return WPTermMeta
     */
    public function notPrivate()
    {
        return $this->where('meta_key', 'NOT LIKE', '\_%');
    }
}