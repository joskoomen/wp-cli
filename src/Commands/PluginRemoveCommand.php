<?php

namespace JosKoomen\Wordpress\Cli\Commands;

use JosKoomen\Wordpress\Cli\Constants\OptionNames;
use JosKoomen\Wordpress\Cli\Controllers\PluginsController;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginRemoveCommand extends AbstractCommand
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('remove')
            ->setDescription('Remove a Wordpress plugin')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('plugins', InputArgument::IS_ARRAY),
                ])
            );
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $this->getDirectory($input);
        $creator = new PluginsController();
        $plugins = $input->getArgument('plugins');

        if (empty($plugins)) {
            $jsonFile = $directory . DIRECTORY_SEPARATOR . 'wordpress.json';
            $arr = @json_decode(@file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR)['plugins'];
            $creator->removeUnusedPlugins($output, $directory, $arr);
        } else {
            foreach ($plugins as $plugin) {
                $creator->removePlugin($output, $plugin, $directory);
            }
        }
        return 0;
    }
}
