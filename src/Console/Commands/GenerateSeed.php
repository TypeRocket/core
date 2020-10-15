<?php
namespace TypeRocket\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;

class GenerateSeed extends Command
{

    protected $command = [
        'config:seed',
        'Generate seed for app.php',
        'This command generates a seed for config.php by replacing PUT_TYPEROCKET_SEED_HERE with a seed.',
    ];

    /**
     * Execute Command
     *
     * Example command: php galaxy seed
     *
     * @return int|null|void
     */
    protected function exec()
    {
        try {
            $file = new File(TYPEROCKET_CORE_CONFIG_PATH . '/app.php');
            $seed = 'seed_' . escapeshellcmd( uniqid() );
            $found = $file->replaceOnLine('PUT_TYPEROCKET_SEED_HERE', $seed );

            if($found) {
                $this->success('Seeded config/app.php with: ' . $seed );
            } else {
                $this->error('Manually Seed config/app.php with: ' . $seed);
            }

        } catch ( \Exception $e ) {
            $this->error('File empty or missing');
        }
    }

}