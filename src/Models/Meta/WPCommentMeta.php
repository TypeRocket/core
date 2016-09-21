<?php

namespace TypeRocket\Models\Meta;

use TypeRocket\Models\Model;

class WPCommentMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'commentmeta';

    protected $builtin = [
        'meta_id',
        'comment_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    public function comment( $modelClass ) {
        return $this->belongsTo( $modelClass, 'comment_id' );
    }
}