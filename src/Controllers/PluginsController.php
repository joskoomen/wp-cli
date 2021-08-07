<?php

namespace Ypa\Wordpress\Cli\Controllers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ypa\Wordpress\Cli\Constants\Colors;
use Ypa\Wordpress\Cli\Constants\OptionNames;
use Ypa\Wordpress\Cli\Resources\PluginsResource;
use Ypa\Wordpress\Cli\Services\SearchPluginService;
use Ypa\Wordpress\Cli\Services\WordpressService;
use Ypa\Wordpress\Cli\Traits\CmdTrait;
use Ypa\Wordpress\Cli\Traits\CreatorTrait;
use Ypa\Wordpress\Cli\Traits\DirectoryTrait;

class PluginsController
{
    use CreatorTrait, DirectoryTrait, CmdTrait;

    private WordpressService $wordpressService;

    public function __construct()
    {
        $this->wordpressService = new WordpressService();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $appDirectory
     *
     * @return $this
     * @throws \JsonException
     */
    public function installPlugins(InputInterface $input, OutputInterface $output, string $appDirectory): self
    {
        $this->writeMessage($output, '🔌', 'Syncing your plugins...', Colors::GREEN, true);

        $jsonFile = $this->getWpJsonPath($appDirectory);
        $plugins = @json_decode(@file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR)['plugins'];

        $this->removeUnusedPlugins($output, $appDirectory, $plugins);

        foreach ($plugins as $name => $source) {
            $this->writeMessage($output, '🔗', 'Installing "' . $name . '"');

            if ($source === '-') {
                $filesystem = new Filesystem();
                $filesystem->symlink(
                    $this->getCustomPluginsDirectory($appDirectory) . DIRECTORY_SEPARATOR . $name,
                    $this->getPluginsDirectory($appDirectory) . DIRECTORY_SEPARATOR . $name
                );
                $this->activatePlugin($input, $output, $name, $appDirectory);
            } else {
                $this->getPlugin($input, $output, $name, $source, $appDirectory);
            }
        }
        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $appDirectory
     *
     * @return $this
     * @throws \JsonException
     */
    public function collectPlugins(InputInterface $input, OutputInterface $output, string $appDirectory): self
    {
        $this->writeMessage($output, '🔌', 'Syncing your plugins...', Colors::GREEN, true);

        $jsonFile = $this->getWpJsonPath($appDirectory);
        $plugins = @json_decode(@file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR)['plugins'];

        foreach ($plugins as $name => $source) {
            $this->writeMessage($output, '🔗', 'Retrieve "' . $name . '"');

            if ($source !== '-') {
                $this->getPlugin($input, $output, $name, $source, $appDirectory);
            }
        }
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @param string $appDirectory
     * @param array $plugins
     *
     * @return $this
     * @throws \JsonException
     */
    public function removeUnusedPlugins(OutputInterface $output, string $appDirectory, array $plugins): self
    {
        $pluginsList = [];
        foreach ($plugins as $name => $source) {
            $pluginsList[] = $name;
        }

        $iterator = new \DirectoryIterator($this->getPluginsDirectory($appDirectory));
        while ($iterator->valid()) {
            $file = $iterator->current();
            $name = $file->getFilename();
            $skip = ['.', '..', 'index.php'];
            if (!in_array($name, $skip) && !in_array($name, $pluginsList, true)) {
                $this->removePlugin($output, $name, $appDirectory, false);
            }
            $iterator->next();
        }

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $name
     * @param string $appDirectory
     *
     * @throws \JsonException
     */
    public function requirePlugin(InputInterface $input, OutputInterface $output, string $name, string $appDirectory): void
    {
        $service = new SearchPluginService();
        $data = $service->searchPlugin($name);

        $first = $data['plugins'][0];
        if ($first['slug'] === $name) {
            $this->getPlugin($input, $output, $name, $first['download_link'], $appDirectory)
                ->addToJsonFile($output, $name, $appDirectory);
        } else {
            $this->writeln($output, '🤷🏼‍♂️', 'No plugin found with slug ' . $name, Colors::RED);
            $max = count($data['plugins']);
            for ($index = 1; $index < $max; $index++) {
                $this->writeln($output, '👉️', $data['plugins'][$index]['slug']);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param $name
     * @param $appDirectory
     * @param bool $removeFromJson
     *
     * @return $this
     * @throws \JsonException
     */
    public function removePlugin(OutputInterface $output, $name, $appDirectory, bool $removeFromJson = true): self
    {
        $filesystem = new Filesystem();

        $this->writeMessage($output, '🗑', 'Removing "' . $name . '"', Colors::MAGENTA);
        $cliPath = $this->getWpCliPath($appDirectory);

        $commands = [
            $cliPath . ' plugin deactivate ' . $name . ' --path=' . $this->getWordpressDirectory($appDirectory) . ' --quiet',
        ];
        $this->runCommands($output, $appDirectory, $commands);
        $filesystem->remove($this->getPluginsDirectory($appDirectory) . DIRECTORY_SEPARATOR . $name);
        $filesystem->remove($this->getCustomPluginsDirectory($appDirectory) . DIRECTORY_SEPARATOR . $name);

        if ($removeFromJson) {
            $this->removeFromJsonFile($name, $appDirectory);
        }

        return $this;
    }

    public function listVersions(OutputInterface $output, string $pluginName): void
    {
        $this->writeIntro($output, '🔎', 'Ok, we try to collect the available versions for ' . $pluginName . '.');
        $resource = new PluginsResource($output);
        $resource->listVersions($this->wordpressService, $pluginName);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $name
     * @param string $source
     * @param string $appDirectory
     *
     * @return PluginsController
     */
    private function getPlugin(InputInterface $input, OutputInterface $output, string $name, string $source, string $appDirectory): self
    {
        $zipFile = $this->makeFilename('plugin-' . $name);
        if (empty($source)) {
            $zipSource = $this->wordpressService->getPluginZipName($name);
        } elseif ((float)$source > 0) {
            $zipSource = $this->wordpressService->getPluginZipName($name . '.' . $source);
        } else {
            $zipSource = $source;
        }

        if ($this->hasOption($input, OptionNames::PRODUCTION)) {
            $extractDir = $this->getCustomPluginsDirectory($appDirectory);
        } else {
            $extractDir = $this->getPluginsDirectory($appDirectory);
        }

        $this->downloadPlugin($zipFile, $zipSource)
            ->extract($zipFile, $extractDir)
            ->cleanUp($zipFile);

        if (!$this->hasOption($input, OptionNames::PRODUCTION)) {
            $this->activatePlugin($input, $output, $name, $appDirectory);
        }
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @param string $appDirectory
     * @param string $name
     *
     * @return string
     */
    private function getPluginVersion(OutputInterface $output, string $appDirectory, string $name): string
    {
        $cliPath = $this->getWpCliPath($appDirectory);
        $wpPath = $this->getWordpressDirectory($appDirectory);
        $commands = [
            $cliPath . ' plugin install ' . $name . ' --path=' . $wpPath . ' --quiet',
            $cliPath . ' plugin get ' . $name . ' --field=version --format=json --path=' . $wpPath . ' --quiet'
        ];
        return trim(str_replace(['\n', '"'], ['', ''], $this->runCommands($output, $appDirectory, $commands, false, true)));
    }

    /**
     * Download the temporary Zip to the given plugin.
     *
     * @param string $zipFile
     * @param string $downloadUrl
     *
     * @return $this
     */
    private function downloadPlugin(string $zipFile, string $downloadUrl): self
    {
        @file_put_contents($zipFile, $this->wordpressService->downloadPlugin($downloadUrl));
        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $name
     * @param string $appDirectory
     */
    private function activatePlugin(InputInterface $input, OutputInterface $output, string $name, string $appDirectory): void
    {
        if (!$this->hasOption($input, OptionNames::PRODUCTION)) {
            $cliPath = $this->getWpCliPath($appDirectory);
            $commands = [
                $cliPath . ' plugin activate ' . $name . ' --path=' . $this->getWordpressDirectory($appDirectory) . ' --quiet',
            ];
            $this->runCommands($output, $appDirectory, $commands);
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $name
     * @param string $appDirectory
     *
     * @throws \JsonException
     */
    private function addToJsonFile(OutputInterface $output, string $name, string $appDirectory): void
    {
        $jsonFile = $appDirectory . DIRECTORY_SEPARATOR . 'wordpress.json';
        $jsonData = @json_decode(@file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);

        $plugins = $jsonData['plugins'];
        $plugins[$name] = $this->getPluginVersion($output, $appDirectory, $name);
        ksort($plugins);
        $jsonData['plugins'] = $plugins;
        file_put_contents($jsonFile, json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /**
     * @param string $name
     * @param string $appDirectory
     *
     * @throws \JsonException
     */
    private function removeFromJsonFile(string $name, string $appDirectory): void
    {
        $jsonFile = $appDirectory . DIRECTORY_SEPARATOR . 'wordpress.json';
        $jsonData = @json_decode(@file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);

        $plugins = $jsonData['plugins'];
        unset($plugins[$name]);
        ksort($plugins);
        $jsonData['plugins'] = $plugins;
        file_put_contents($jsonFile, json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    private function getCustomPluginsDirectory(string $appDirectory): string
    {
        return $this->getResourcesDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'plugins';
    }
}
