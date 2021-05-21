<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeederMakeCommand extends AbstractCommand
{

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('make:seeder')
            ->setDescription('Create a new database seeder')
            ->addArgument('name', InputArgument::REQUIRED, 'What is the name of the seeder?');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = getcwd();

        $this->writeIntro($output, '💪', 'Make Seeder ' . $input->getArgument('name'));

        $string = 'php vendor/bin/phinx seed:create ' . $input->getArgument('name') . ' -c migrations.php';

        $commands = [$string];

        $this->runCommands($output, $directory, $commands);

        $this->writeClose($output, '🤙', 'All done');
    }
}
