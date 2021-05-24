<?php

namespace Ypa\Wordpress\Cli\Commands;

use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Ypa\Wordpress\Cli\Controllers\PluginsController;
use Ypa\Wordpress\Cli\Resources\PluginsResource;

class PluginVersionsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('list')
            ->setDescription('List versions of a plugin')
            ->addArgument('plugin', InputOption::VALUE_REQUIRED, 'The plugin to list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pluginController = new PluginsController();
        $pluginController->listVersions($output, $input->getArgument('plugin'));

        return 0;
    }
}
