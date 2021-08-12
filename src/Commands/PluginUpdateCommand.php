<?php

namespace Ypa\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ypa\Wordpress\Cli\Controllers\PluginsController;

class PluginUpdateCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('update')
            ->setDescription('Update the wordpress.json');
        parent::configure();
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->getDirectory($input);
        $creator = new PluginsController();
        $this->writeIntro($output, '💈', 'Update plugins');
        $creator->updatePlugins($output, $directory);
        return 0;
    }
}
