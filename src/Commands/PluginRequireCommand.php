<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use JosKoomen\Wordpress\Cli\Controllers\PluginsController;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginRequireCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('require')
            ->setDescription('Require a Wordpress plugin')
            ->addArgument('plugins', InputArgument::IS_ARRAY);
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
        $plugins = $input->getArgument('plugins');

        foreach ($plugins as $plugin) {
            $this->writeMessage($output, '🔗', 'Installing "' . $plugin . '"');
            $creator->requirePlugin($input, $output, $plugin, $directory);
        }
        return 0;
    }
}
