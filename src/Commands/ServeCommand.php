<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure():void
    {
        $this
            ->setName('serve')
            ->setDescription('Serve a wordpress application')
            ->setHelp('This command serves your application');

        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->getDirectory($input);
        $wpDir = $directory . DIRECTORY_SEPARATOR . 'public';
        if (!is_dir($wpDir)) {
            throw new \RuntimeException('First create a Wordpress instance');
        }

        $commands = ['php -S localhost:11001 -t ' . $directory . '/public'];
        $output->writeln('<fg=cyan;options=bold>Tommy has served your Wordpress!</>');
        $output->writeln('<comment>Home </comment> http://localhost:11001/');
        $output->writeln('<comment>Admin </comment> http://localhost:11001/wp-admin/');
        $this->runCommands($output, $directory, $commands);

        return 0;
    }
}
