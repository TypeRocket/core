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
     *
     * @return int|null|void
     */
    protected function exec()
    {
        $this->success('Flushed the WordPress rewrites');
        flush_rewrite_rules( true );
    }

}