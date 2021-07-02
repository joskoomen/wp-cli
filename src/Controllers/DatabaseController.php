<?php

namespace Ypa\Wordpress\Cli\Controllers;

use Carbon\Carbon;
use Dotenv\Dotenv;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ypa\Wordpress\Cli\Traits\CmdTrait;
use Ypa\Wordpress\Cli\Traits\CreatorTrait;
use Ypa\Wordpress\Cli\Traits\DirectoryTrait;

class DatabaseController
{
    use CreatorTrait, CmdTrait;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \JsonException
     */
    public function createBackupDirectory(OutputInterface $output, string $appDirectory): void
    {
        if (!$this->backupDirectoryDoesExist($appDirectory)) {
            $this->writeMessage($output, '🐨', "Create the backup folder first inside databases/ ...");
            $fileSystem = new Filesystem();
            $fileSystem->mkdir($this->getDbBackupsDirectory($appDirectory));
        }
    }

    protected function getDbBackupsDirectory($directory): string
    {
        return $directory . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'backups';
    }

    public function startBackup(OutputInterface $output, string $appDirectory): string
    {
        $this->writeMessage($output, '🐨', "Start backup");
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

        return $path . $remoteFilename;
    }

    public function pullRemoteDatabase(OutputInterface $output, string $appDirectory, string $remoteFile): void
    {
        $dotenv = Dotenv::create(dirname(__DIR__, 1));
        $dotenv->load();

        $dbHost = getenv('DB_HOST');
        $dbDB = getenv('DB_DATABASE');
        $dbUser = getenv('DB_USERNAME');
        $dbPass = getenv('DB_PASSWORD');

        $commands = [
            "mysql -h $dbHost -u$dbUser -p$dbPass $dbDB < $remoteFile"
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
