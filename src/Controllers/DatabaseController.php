<?php

namespace App;

use Carbon\Carbon;
use Dotenv\Dotenv;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ypa\Wordpress\Cli\Traits\CreatorTrait;

class DatabaseController
{
    use CreatorTrait;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \JsonException
     */
    public function createBackupDirectory(string $appDirectory): void
    {
        if (!$this->backupDirectoryDoesExist($appDirectory)) {
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($this->getDbBackupsDirectory($appDirectory));
        }
    }

    protected function getDbBackupsDirectory($directory): string
    {
        return $directory . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'backups';
    }

    public function startBackup(InputInterface $input, OutputInterface $output, string $appDirectory): void
    {

        $dotenv = Dotenv::create(dirname(__DIR__, 1));
        $dotenv->load();

        $fileSystem = new Filesystem();
        $folderName = Carbon::now()->toDateTimeLocalString();
        $path = $this->getDbBackupsDirectory($appDirectory) . DIRECTORY_SEPARATOR . $folderName;
        $fileSystem->mkdir($path);

        $sshServer = getenv('REMOTE_SERVER_SSH_SERVER');
        $sshUser = getenv('REMOTE_SERVER_SSH_USER');
        $dbRemoteHost = getenv('REMOTE_DB_HOST');
        $dbRemoteDB = getenv('REMOTE_DB_DATABASE');
        $dbRemoteUser = getenv('REMOTE_DB_USERNAME');
        $dbRemotePass = getenv('REMOTE_DB_PASSWORD');

        $dbHost = getenv('DB_HOST');
        $dbDB = getenv('DB_DATABASE');
        $dbUser = getenv('DB_USERNAME');
        $dbPass = getenv('DB_PASSWORD');

        $remoteFilename = DIRECTORY_SEPARATOR . "$dbRemoteHost-$dbRemoteDB.sql";
        $localFilename = DIRECTORY_SEPARATOR . "local-$dbDB.sql";
        $commands = [
            "ssh $sshUser@$sshServer 'mysqldump -h $dbRemoteHost -u$dbRemoteUser -p$dbRemotePass $dbRemoteDB' > $path$remoteFilename",
            "mysqldump -h $dbHost -u$dbUser -p$dbPass $dbDB > $path$localFilename"
        ];
        $this->runCommands($output, $appDirectory, $commands);
    }

    /**
     * Verify if there is already a backup directory
     *
     * @param string $appDirectory
     *
     * @return bool
     */
    private function backupDirectoryDoesExist(string $appDirectory): bool
    {
        $backupsDir = $this->getDbBackupsDirectory($appDirectory);
        return (is_dir($backupsDir) || ($backupsDir !== getcwd() && is_file($backupsDir)));
    }
}
