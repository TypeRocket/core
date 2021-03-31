<?php
namespace TypeRocket\Utility;

use TypeRocket\Database\Results;
use TypeRocket\Models\WPComment;
use TypeRocket\Models\WPPost;
use TypeRocket\Models\WPTerm;
use TypeRocket\Models\WPUser;

class QueryCaster
{
    /**
     * @param string $model must be a class of WPPost
     * @param array $wp_query_args args for WP_Query
     *
     * @return Results
     */
    public static function posts(string $model, array $wp_query_args)
    {
        $model = new $model;

        if(!$model instanceof WPPost) {
            throw new \Exception(static::class . ' requires a WPPost model. You provided: ' . get_class($model));
        }

        $wp_query_args = array_merge($wp_query_args, ['post_type' => $model::POST_TYPE]);
        $wp_query = new \WP_Query($wp_query_args);
        $results_class = $model->getResultsClass();
        /** @var Results $results */
        $results = new $results_class;

        return $results->exchangeAndCast($wp_query->posts, get_class($model));
    }

    /**
     * @param string $model must be a class of WPTerm
     * @param array $wp_query_args args for WP_Term_Query
     *
     * @return Results
     * @throws \Exception
     */
    public static function terms(string $model, array $wp_query_args)
    {
        $model = new $model;

        if(!$model instanceof WPTerm) {
            throw new \Exception(static::class . ' requires a WPTerm model. You provided: ' . get_class($model));
        }

        $wp_query_args = array_merge($wp_query_args, ['taxonomy' => $model::TAXONOMY]);
        $wp_query = new \WP_Term_Query($wp_query_args);
        $results_class = $model->getResultsClass();
        /** @var Results $results */
        $results = new $results_class;

        return $results->exchangeAndCast($wp_query->terms, get_class($model));
    }

    /**
     * @param string $model must be a class of WPUser
     * @param array $wp_query_args args for WP_User_Query
     *
     * @return Results
     * @throws \Exception
     */
    public static function users(string $model, array $wp_query_args)
    {
        $model = new $model;

        if(!$model instanceof WPUser) {
            throw new \Exception(static::class . ' requires a WPUser model. You provided: ' . get_class($model));
        }

        $wp_query = new \WP_User_Query($wp_query_args);
        $results_class = $model->getResultsClass();
        /** @var Results $results */
        $results = new $results_class;

        return $results->exchangeAndCast($wp_query->get_results(), get_class($model));
    }

    /**
     * @param string $model must be a class of WPComment
     * @param array $wp_query_args args for WP_Comment_Query
     *
     * @return Results
     * @throws \Exception
     */
    public static function comments(string $model, array $wp_query_args)
    {
        $model = new $model;

        if(!$model instanceof WPComment) {
            throw new \Exception(static::class . ' requires a WPComment model. You provided: ' . get_class($model));
        }

        $wp_query = new \WP_Comment_Query($wp_query_args);
        $results_class = $model->getResultsClass();
        /** @var Results $results */
        $results = new $results_class;

        return $results->exchangeAndCast($wp_query->comments, get_class($model));
    }
}