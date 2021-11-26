<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use Symfony\Component\Console\Input\InputOption;
use JosKoomen\Wordpress\Cli\Constants\OptionNames;
use JosKoomen\Wordpress\Cli\Controllers\PluginsController;
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
            ->setDescription('Install the Wordpress plugins')
            ->addOption(OptionNames::PRODUCTION, 'p', InputOption::VALUE_NONE, 'Optionally production mode');
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

        $directory = $this->getDirectory($input);
        $creator = new PluginsController();

        if($this->hasOption($input, OptionNames::PRODUCTION)) {
            $creator->collectPlugins($input, $output, $directory);
        } else {
            $creator->installPlugins($input, $output, $directory);
        }
        return 0;
    }
}
