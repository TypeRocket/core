<?php
namespace TypeRocket\Models\Meta;

use TypeRocket\Database\ResultsTermMeta;
use TypeRocket\Models\Model;
use TypeRocket\Models\WPTerm;

class WPTermMeta extends Model
{
    protected $idColumn = 'meta_id';
    protected $resource = 'termmeta';
    protected $resultsClass = ResultsTermMeta::class;

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
    public function term( $modelClass = null )
    {
        return $this->belongsTo( $modelClass ?? WPTerm::class, 'term_id' );
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