<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('serve')
            ->setDescription('Serve a wordpress application')
            ->setHelp('This command serves your application');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->getDirectory($input);
        $wpDir = $directory . DIRECTORY_SEPARATOR . 'public';
        if (!is_dir($wpDir)) {
            throw new \RuntimeException('First create a Wordpress instance');
        }

        $commands = ['php -S localhost:11001 -t ' . $directory . '/public'];

        $this->writeIntro($output, '👨🏻‍🦲', 'Daniel has served your Wordpress!');

        $this->writeLink($output, 'Home','http://localhost:11001/');
        $this->writeLink($output, 'Admin','http://localhost:11001/wp-admin/');
        $this->runCommands($output, $directory, $commands);

        return 0;
    }
}
