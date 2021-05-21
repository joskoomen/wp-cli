<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SeedCommand extends AbstractCommand
{

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('db:seed')
            ->setDescription('Run database seeders')
            ->addOption('--seed', '-s', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'What is the name of the seeder?');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = getcwd();
        $this->writeIntro($output, '🌱', "Start Seeding. ALl plant based!");

        $string = 'php vendor/bin/phinx seed:run -c migrations.php';

        if ($input->hasOption('--seed')) {
            $string .= $input->getOptions();
        }

        $commands = [$string];

        $this->runCommands($output, $directory, $commands);

        $this->writeClose($output, '🌴', 'All done. Ok bye!');
    }
}
