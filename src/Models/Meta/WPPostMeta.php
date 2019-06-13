<?php

namespace TypeRocket\Models\Meta;

use TypeRocket\Database\ResultsMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;

class WPPostMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'postmeta';
    protected $resultsClass = ResultsMeta::class;

    protected $builtin = [
        'meta_id',
        'post_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    public function post( $modelClass = null ) {
        return $this->belongsTo( $modelClass ?? WPPost::class, 'post_id' );
    }
}