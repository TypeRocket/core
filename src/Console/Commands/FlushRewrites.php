<?php
namespace TypeRocket\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FlushRewrites extends Command
{

    protected function configure()
    {
        $this->setName('wp:flush')
             ->setDescription('Hard flush the WordPress rewrites')
             ->setHelp("This command hard flushes the WordPress rewrite rules and permalinks.");
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
        $output->writeln('<fg=green>Flushed the WordPress rewrites');
        flush_rewrite_rules( true );
    }

}