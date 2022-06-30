<?php
namespace TypeRocket\Database;

use TypeRocket\Exceptions\SqlException;
use TypeRocket\Utility\Str;

class SqlRunner
{
    protected $query_prefix_tag = '{!!prefix!!}';
    protected $query_charset_tag = '{!!charset!!}';
    protected $query_collate_tag = '{!!collate!!}';

    /**
     * @param $file_sql
     * @param null|callable $callback
     * @param null|string|array $cb_data
     *
     * @throws SqlException
     */
    public function runQueryFile($file_sql, $callback = null, $cb_data = null) {
        if( ! file_exists( $file_sql ) ) {
            throw new \Exception('Not Found: SQL '. $file_sql .' failed to run.');
            return;
        }

        $sql = file_get_contents($file_sql);
        $this->runQueryString( $sql, $callback, $cb_data );
    }

    /**
     * Compile Query String
     *
     * @param $sql
     *
     * @return string
     */
    public function compileQueryString($sql) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset = $wpdb->charset;
        $collate = $wpdb->collate;

        $compiled = str_replace($this->query_prefix_tag, $prefix, $sql);
        $compiled = str_replace($this->query_charset_tag, $charset, $compiled);
        return str_replace($this->query_collate_tag, $collate, $compiled);
    }

    /**
     * @param $sql
     * @param null|callable $callback
     * @param null|string|array $cb_data
     *
     * @return array
     * @throws SqlException
     */
    public function runQueryString($sql, $callback = null, $cb_data = null, $file = null) {
        $compiled = $this->compileQueryString($sql);
        return $this->runQueryArray( explode(';'.PHP_EOL, $compiled ), $callback, $cb_data, $file );
    }

    /**
     * @param array $queries
     * @param null|callable $callback
     * @param null|array|string $cb_data
     *
     * @return array
     * @throws SqlException
     */
    public function runQueryArray($queries, $callback = null, $cb_data = null, $file = null)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;
        // $wpdb->show_errors();
        $succesess = [];

        foreach ($queries as $query) {

            $RXSQLComments = '(--.*)';
            $query = ( ($query == '') ?  '' : trim(preg_replace( $RXSQLComments, '', $query )));

            if( Str::starts('create table', strtolower($query)) ) {

                if(!function_exists('\dbDelta')) {
                    include ABSPATH . 'wp-admin/includes/upgrade.php';
                }

                $result = \dbDelta($query);
            } elseif( !empty(trim($query)) ) {
                $result = $wpdb->query( $query );
            } else {
                continue;
            }

            $file = ($file ? ' For file ' . $file : '');

            if ( $result === false ) {
                $e = new SqlException('Query Error: SQL failed to run.' . $file);
                $e->setSql($wpdb->last_query);
                $e->setSqlError($wpdb->last_error);
                $wpdb->last_error = '';
                throw $e;
            }

            $result = [
                'message' => 'SQL successfully run.' . $file,
                'result' => $result,
                'wpdb' => $wpdb->last_query
            ];

            if(is_callable($callback)) {
                $callback($result, $cb_data);
            }

            $succesess[] = $result;
        }

        return $succesess;
    }
}