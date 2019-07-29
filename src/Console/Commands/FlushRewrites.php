<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;

class FlushRewrites extends Command
{

    protected $command = [
        'wp:flush',
        'Hard flush the WordPress rewrites',
        'This command hard flushes the WordPress rewrite rules and permalinks.',
    ];

    /**
     * Execute Command
     *
     * Example command: php galaxy wp:flush
     */
    protected function exec()
    {
        flush_rewrite_rules( true );
        $this->success('Flushed the WordPress rewrites');
    }

}