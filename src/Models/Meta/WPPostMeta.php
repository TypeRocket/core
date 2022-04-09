<?php
namespace TypeRocket\Models\Meta;

use TypeRocket\Database\ResultsPostMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPPost;

class WPPostMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'postmeta';
    protected $resultsClass = ResultsPostMeta::class;

    protected $builtin = [
        'meta_id',
        'post_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    /**
     * Post
     *
     * @param null|string $modelClass
     * @return WPPostMeta|null
     */
    public function post( $modelClass = null )
    {
        return $this->belongsTo( $modelClass ?? WPPost::class, 'post_id' );
    }

    /**
     * Not Private
     *
     * @return WPPostMeta
     */
    public function notPrivate()
    {
        return $this->where('meta_key', 'NOT LIKE', '\_%');
    }
}