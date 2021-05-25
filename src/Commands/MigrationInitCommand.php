<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationInitCommand extends AbstractCommand
{

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('db:init')
            ->setDescription('Init migrations');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeIntro($output, '🤩', 'Alrighty, migrations it is. What a smart choice 🖖.');
        $this->initMigrations($input, $output);

        return 0;
    }

    protected function initMigrations(InputInterface $input, OutputInterface $output): MigrationInitCommand
    {
        $directory = $this->getDirectory($input);

        if (!mkdir($concurrentDirectory = $directory . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations', 0777, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        if (!mkdir($concurrentDirectory = $directory . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds', 0777, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        @copy(
            $directory . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR . 'migrations.example.php',
            $directory . DIRECTORY_SEPARATOR . 'migrations.php'
        );

        $this->writeClose($output, '🥳', 'All done! Migrations are ready to use!');

        return $this;
    }
}
