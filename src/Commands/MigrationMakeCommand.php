<?php

namespace Ypa\Wordpress\Cli\Commands;

use Ypa\Wordpress\Cli\Constants\Colors;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationMakeCommand extends AbstractCommand
{

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('make:migration')
            ->setDescription('Make migrations')
            ->addArgument('migration', InputArgument::REQUIRED);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->getDirectory($input);
        $migrationsDirectory = $this->getMigrationsDirectory($directory);

        $this->writeIntro($output, '🦄', 'Hola! ¿Cómo estás? Soy un unicornio Español!');
        $this->writeMessage($output, '🦄', 'Yo fabricar una Migración: ' . $input->getArgument('migration'), Colors::CYAN);

        $commands = [
            'php vendor/bin/phinx create ' . $input->getArgument('migration') . ' -c  migrations.php --template ' . $migrationsDirectory . '/migration.template'
        ];

        $this->runCommands($output, $directory, $commands);
        $this->writeClose($output, '🦄', 'Listo, hasta luego!');

        return 0;
    }
}
