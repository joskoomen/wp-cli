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
        $controller = new DatabaseController();
        $appDirectory = $this->getDirectory($input);
        $controller->createBackupDirectory($appDirectory);
        $controller->startBackup($input, $output, $appDirectory);
        return 0;
    }

}
