<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ypa\Wordpress\Cli\Controllers\DatabaseController;

class BackupDatabaseCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->setName('db:backup')
            ->setDescription('Backup Wordpress database');

        parent::configure();
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeIntro($output, '🐨', "Ok, let's backup you databases...");
        $controller = new DatabaseController();
        $appDirectory = $this->getDirectory($input);
        $controller->createBackupDirectory($output, $appDirectory);
        $controller->startBackup($output, $appDirectory);
        $this->writeClose($output, '🐨', 'Backup process done.');
        return 0;
    }

}
