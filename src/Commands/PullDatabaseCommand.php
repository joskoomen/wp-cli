<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Ypa\Wordpress\Cli\Controllers\DatabaseController;

class PullDatabaseCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this
            ->setName('db:pull')
            ->setDescription('Pull a remote Wordpress database');

        parent::configure();
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<comment>Do you have an admin account to use in the remote database? 👉 </comment> ',
            ['Yes', 'No'],
            0
        );
        $hasAdmin = $helper->ask($input, $output, $question);
        if ($hasAdmin === 'yes') {
            $this->writeIntro($output, '🐨', "Ok, let's pull the remote database and install it locally...");
            $controller = new DatabaseController();
            $appDirectory = $this->getDirectory($input);
            $controller->createBackupDirectory($output, $appDirectory);
            $remoteFile = $controller->startBackup($output, $appDirectory);

            $controller->pullRemoteDatabase($output, $appDirectory, $remoteFile);

            $this->writeClose($output, '🐨', "Enjoy your new data set!");
            return 0;
        }

        throw new \RuntimeException('Make sure you have an admin user on the remote Database first!');

    }

}
