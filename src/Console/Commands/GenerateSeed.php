<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $replace = 'PUT_TYPEROCKET_SEED_HERE';
        $with = 'seed_' . escapeshellcmd( uniqid() );
        $path = realpath(TR_PATH . '/config/app.php');
        $data = file($path);
        $fileContent = '';
        $found = false;
        if( $data ) {
            foreach ($data as $line ) {
                if ( strpos($line, $replace) !== false ) {
                    $found = true;
                    $fileContent .= trim(str_replace($replace, $with, $line)) . PHP_EOL;
                } else {
                    $fileContent .= trim($line) . PHP_EOL;
                }
            }

            if($found) {
                file_put_contents($path, $fileContent);
                $output->writeln('<fg=green>Seeded config/app.php with: ' . $with );
            } else {
                $output->writeln('<fg=red>Manually Seed config/app.php with: ' . $with);
            }
        } else {
            $output->writeln('<fg=red>Manually Seed config/app.php with: ' . $with);
        }
    }

}