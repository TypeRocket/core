<?php
namespace TypeRocket\Models;

class WPTermTaxonomy extends Model
{
    protected $idColumn = 'term_taxonomy_id';
    protected $resource = 'term_taxonomy';

    protected $builtin = [
        'term_taxonomy_id',
        'term_id',
        'taxonomy',
        'description',
        'parent',
        'count',
    ];

    protected $guard = [
        'term_taxonomy_id'
    ];

    /**
     * Return table name in constructor
     *
     * @param \wpdb $wpdb
     *
     * @return string
     */
    public function initTable( $wpdb )
    {
        return $wpdb->term_taxonomy;
    }

    /**
     * @return WPTermTaxonomy|null
     */
    public function term()
    {
        return $this->belongsTo( WPTerm::class, 'term_id' );
    }

}