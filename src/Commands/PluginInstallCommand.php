<?php

namespace Ypa\Wordpress\Cli\Commands;

use Ypa\Wordpress\Cli\Controllers\PluginsController;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginInstallCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('install')
            ->setDescription('Install the Wordpress plugins');
        parent::configure();
    }

    /**
     * @inheritDoc
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->writeIntro($output, '🦁', "Ok, let's go! Installing your plugins.");

        $directory = $this->getDirectory($input);
        $creator = new PluginsController();
        $creator->installPlugins($output, $directory);

        return 0;
    }
}
