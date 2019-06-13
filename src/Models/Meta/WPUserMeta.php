<?php

namespace TypeRocket\Models\Meta;

use TypeRocket\Database\ResultsMeta;
use TypeRocket\Models\Model;

class WPUserMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'usermeta';
    protected $resultsClass = ResultsMeta::class;

    protected $builtin = [
        'meta_id',
        'user_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    public function user( $modelClass ) {
        return $this->belongsTo( $modelClass, 'user_id' );
    }
}