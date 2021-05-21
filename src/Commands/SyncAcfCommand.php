<?php

namespace Ypa\Wordpress\Cli\Commands;

use Ypa\Wordpress\Cli\Controllers\AcfController;
use Ypa\Wordpress\Cli\Resources\AcfSyncResource;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncAcfCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('sync-acf')
            ->setDescription('Synchronize the ACF json files');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeIntro($output, '🪄', "Let's import your ACF JSON files");

        $appDirectory = $this->getDirectory($input);
        $controller = new AcfController();
        $controller->start($output, $appDirectory);
    }
}
