<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeRocket\Utility\File;

class GenerateSeed extends Command
{

    protected function configure()
    {
        $this->setName('seed')
             ->setDescription('Generate seed for config.php')
             ->setHelp("This command generates a seed for config.php by replacing PUT_TYPEROCKET_SEED_HERE with a seed.");
    }

    /**
     * Execute Command
     *
     * Example command: php galaxy seed
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $file = new File(TR_PATH . '/config/app.php');
            $seed = 'seed_' . escapeshellcmd( uniqid() );
            $found = $file->replaceOnLine('PUT_TYPEROCKET_SEED_HERE', $seed );

            if($found) {
                $output->writeln('<fg=green>Seeded config/app.php with: ' . $seed );
            } else {
                $output->writeln('<fg=red>Manually Seed config/app.php with: ' . $seed);
            }

        } catch ( \Exception $e ) {
            $output->writeln('<fg=red>File empty or missing');
        }
    }

}