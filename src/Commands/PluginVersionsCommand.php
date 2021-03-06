<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use JosKoomen\Wordpress\Cli\Controllers\PluginsController;
use JosKoomen\Wordpress\Cli\Resources\PluginsResource;

class PluginVersionsCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('list')
            ->setDescription('List versions of a plugin')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('plugin', InputOption::VALUE_REQUIRED, 'The plugin to list'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pluginController = new PluginsController();
        $pluginController->listVersions($output, $input->getArgument('plugin'));

        return 0;
    }
}
