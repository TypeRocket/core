<?php
namespace TypeRocket\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 *
 * @link http://symfony.com/doc/current/components/console.html
 *
 * @package TypeRocket\Console
 */
class Command extends \Symfony\Component\Console\Command\Command
{
    const REQUIRED = 1;
    const OPTIONAL = 2;
    const IS_ARRAY = 4;

    /** @var InputInterface $input */
    protected $input;

    /** @var OutputInterface $output */
    protected $output;

    protected $command = [
        'name',
        'description',
        'help',
    ];

    /**
     * Configure
     */
    protected function configure()
    {
        $this->setName($this->command[0])
             ->setDescription($this->command[1])
             ->setHelp($this->command[2]);
        $this->config();
    }

    /**
     * Execute
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->input = $input;
        $this->output = $output;
        $this->exec();
    }

    /**
     * Add your configuration
     */
    protected function config()
    {
    }

    /**
     * Add your execution
     */
    protected function exec()
    {
    }

    /**
     * Output error line
     *
     * @param string $content
     */
    protected function error( $content )
    {
        $this->output->writeln('<fg=red>'.$content.'</>');
    }

    /**
     * Output success line
     *
     * @param string $content
     */
    protected function success($content)
    {
        $this->output->writeln('<fg=green>'.$content.'</>');
    }

    /**
     * Output warning line
     *
     * @param string $content
     */
    protected function warning($content)
    {
        $this->output->writeln('<fg=yellow>'.$content.'</>');
    }

    /**
     * Output line
     *
     * @param string $content
     */
    protected function line($content)
    {
        $this->output->writeln($content);
    }

    /**
     * Get Argument
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getArgument( $name ) {
        return $this->input->getArgument($name);
    }

    /**
     * Get Option
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getOption( $name ) {
        return $this->input->getOption($name);
    }

}