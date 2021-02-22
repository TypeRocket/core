<?php
namespace TypeRocket\Controllers;

use TypeRocket\Elements\Fields\Search;
use TypeRocket\Http\Request;
use TypeRocket\Http\Rewrites\Builder;
use TypeRocket\Http\Rewrites\Matrix;

class FieldsController extends Controller
{
    public function component($caller, $group, $type)
    {
        if($caller == 'matrix') {
            new Matrix($group, $type);
        }

        if($caller == 'builder') {
            new Builder($group, $type);
        }

        die();
    }

    public function search(Request $request)
    {
        $limit = 10;
        $params = $request->getInput();
        $results = [];
        $search = $params['s'] ?? '';

        if ($model = $request->getInput('model')) {
            /** @var \TypeRocket\Models\Model $model */
            $model = new $model;

            if(method_exists($model, 'limitFieldOptions')) {
                $model->limitFieldOptions();
            }

            $results = $model->where($model->getSearchColumn(), 'like', '%' . $search . '%' )->getSearchResults($limit);
            $results['search_type'] = 'model';
            $results['count'] = $results['count'] . ' ' . __('only showing up to', 'typerocket-domain') . ' ' . $limit;

            $results['items'] = array_map(function($value) use ($model) {
                return [
                    'title' => Search::getSearchTitle($value, ['id' => 'model', 'registered' => $model]),
                    'id' => $value['id'],
                    'url' => Search::getSearchUrl($value, ['id' => 'model', 'registered' => $model]),
                ];
            }, $results['items'] ?? []);
        }
        elseif( array_key_exists('taxonomy', $params) && !empty($params['taxonomy']) ) {
            $results = get_terms( [
                'taxonomy' => $params['taxonomy'],
                'hide_empty' => false,
                'search' =>  $search,
                'number' => $limit,
            ] );

            $results = array_map(function($value) use ($params) {
                return [
                    'title' => Search::getSearchTitle($value, ['id' => 'taxonomy', 'registered' => $params['taxonomy']]),
                    'id' => $value->term_id,
                    'url' => Search::getSearchUrl($value->term_id, ['id' => 'taxonomy', 'registered' => $params['taxonomy']]),
                ];
            }, $results);

            $results = [
                'search_type' => 'taxonomy',
                'items' => $results,
                'count' => count($results) . ' ' . __('in limit of', 'typerocket-domain') . ' ' . $limit,
            ];
        }
        else {
            add_filter( 'posts_search', [$this, 'postsSearch'], 500, 2 );
            $query = new \WP_Query([
                'post_type' => $params['post_type'] ?? 'post',
                's' => $search,
                'orderby' => 'title',
                'order'     => 'ASC',
                'post_status' => ['publish', 'pending', 'draft', 'future'],
                'posts_per_page' => $limit,
                'tr_fields_controller' => true,
            ]);

            if ( ! empty( $query->posts ) ) {
                $results =  $query->posts;
            }

            $results = array_map(function($value) use ($params) {
                return [
                    'title' => Search::getSearchTitle($value, ['id' => 'post_type', 'registered' => $params['post_type'] ?? 'post']),
                    'id' => $value->ID,
                    'url' => Search::getSearchUrl($value->ID, ['id' => 'post_type', 'registered' => $params['post_type'] ?? 'post']),
                ];
            }, $results);

            $results = [
                'search_type' => 'post_type',
                'items' => $results,
                'count' => count($results) . ' ' . __('in limit of', 'typerocket-domain') . ' ' . $limit,
            ];
        }

        \TypeRocket\Http\Response::getFromContainer()->setData('results', $results);

        return $results;
    }

    /**
     * Posts search hook
     *
     * @param string $search
     * @param \WP_Query $wp_query
     *
     * @return string
     */
    public function postsSearch( $search, $wp_query )
    {
        /** @var \wpdb */
        global $wpdb;

        if ( ! empty( $search ) ) {
            $q = $wp_query->query_vars;
            $search = $searchand = '';
            foreach ( (array) $q['search_terms'] as $term ) {
                $term = esc_sql( $wpdb->esc_like( $term ) );
                $search .= "{$searchand}({$wpdb->posts}.post_title LIKE '%{$term}%')";
                $searchand = ' AND ';
            }
            if ( ! empty( $search ) ) {
                $search = " AND ({$search}) ";
                if ( ! is_user_logged_in() ) {
                    $search .= " AND ({$wpdb->posts}.post_password = '') ";
                }
            }
        }

        return $search;
    }
}