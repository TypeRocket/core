<?php
namespace TypeRocket\Models\Meta;

use TypeRocket\Database\ResultsCommentMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPComment;

class WPCommentMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'commentmeta';
    protected $resultsClass = ResultsCommentMeta::class;

    protected $builtin = [
        'meta_id',
        'comment_id',
        'meta_key',
        'meta_value',
    ];

    protected $guard = [
        'meta_id'
    ];

    /**
     * Comment
     *
     * @param $modelClass
     * @return WPCommentMeta|null
     */
    public function comment( $modelClass = null )
    {
        return $this->belongsTo( $modelClass ?? WPComment::class, 'comment_id' );
    }

    /**
     * Not Private
     *
     * @return WPCommentMeta
     */
    public function notPrivate()
    {
        return $this->where('meta_key', 'NOT LIKE', '\_%');
    }
}