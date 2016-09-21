<?php

namespace TypeRocket\Models\Meta;

use TypeRocket\Models\Model;

class WPPostMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'postmeta';

    protected $builtin = [
        'meta_id',
        'post_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    public function post( $modelClass ) {
        return $this->belongsTo( $modelClass, 'post_id' );
    }
}