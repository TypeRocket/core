<?php

namespace TypeRocket\Console;

use TypeRocket\Utility\Str;

trait CanQueryDB
{
    protected $query_prefix_tag = '{!!prefix!!}';

    protected function runQueryFile($file_sql) {
        if( ! file_exists( $file_sql ) ) {
            $this->error('Not Found: SQL '. $file_sql .' failed to run.');
            return;
        }

        $sql = file_get_contents($file_sql);
        $this->runQueryString( $sql );
    }

    protected function runQueryString($sql) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $prefix = $wpdb->prefix;
        $prefixed = str_replace($this->query_prefix_tag, $prefix, $sql);
        return $this->runQueryArray( explode(';'.PHP_EOL, $prefixed ) );
    }

    protected function runQueryArray($queries) {
        /** @var \wpdb $wpdb */
        global $wpdb;
        $wpdb->show_errors();
        $errors = [];

        foreach ($queries as $query) {

            $RXSQLComments = '(--.*)';
            $query = ( ($query == '') ?  '' : trim(preg_replace( $RXSQLComments, '', $query )));

            if( Str::starts('create table', strtolower($query)) ) {
                $result = dbDelta($query);
            } elseif( !empty(trim($query)) ) {
                $result = $wpdb->query( $query );
            } else {
                continue;
            }

            if ( $result ) {
                $this->sqlSuccess('SQL successfully run.');
                $this->warning('SQL Run:' );
                $this->line($wpdb->last_query );
            } else {
                $errors[] = $wpdb->last_query;
                $this->sqlError('Query Error: SQL failed to run.');
                $this->warning('SQL Run:' );
                $this->line( $wpdb->last_query );
                if($wpdb->last_error !== '') {
                    $this->error( $wpdb->last_error );
                    $wpdb->last_error = '';
                }
                return $errors;
            }
        }

        return $errors;
    }

    protected function sqlSuccess($message) {
        $this->success($message);
    }

    protected function sqlError($message) {
        $this->error($message);
    }
}