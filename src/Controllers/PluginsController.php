<?php

namespace Ypa\Wordpress\Cli\Controllers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ypa\Wordpress\Cli\Constants\Colors;
use Ypa\Wordpress\Cli\Constants\OptionNames;
use Ypa\Wordpress\Cli\Resources\PluginsResource;
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

        $installedPlugins = $this->getInstalledPlugins($output, $appDirectory);

        foreach ($plugins as $name => $source) {
            $install = true;
            foreach ($installedPlugins as $installedPlugin) {
                if ($name === $installedPlugin['name']) {
                    $install = false;
                    break;
                }
            }

            if ($install) {
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
     * @param OutputInterface $output
     * @param string $appDirectory
     *
     * @throws \JsonException
     */
    public function updatePlugins(OutputInterface $output, string $appDirectory): void
    {
        $installedPlugins = $this->getInstalledPlugins($output, $appDirectory);
        $jsonFile = $this->getWpJsonPath($appDirectory);
        $jsonData = @json_decode(@file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);

        $plugins = [];
        foreach ($installedPlugins as $plugin) {
            if (isset($jsonData['plugins'][$plugin['name']])) {
                if ($jsonData['plugins'][$plugin['name']] === '-') {
                    $plugins[$plugin['name']] = '-';
                } elseif (strpos('http', $jsonData['plugins'][$plugin['name']]) === 0) {
                    $plugins[$plugin['name']] = $jsonData['plugins'][$plugin['name']];
                } else {
                    $plugins[$plugin['name']] = $plugin['version'];
                    if ($jsonData['plugins'][$plugin['name']] !== $plugin['version']) {
                        $this->writeln($output, '🐨', 'Update "' . $plugin['name'] . '" : ' . $plugin['version'], Colors::YELLOW);
                    }
                }
            } else {
                $plugins[$plugin['name']] = $plugin['version'];
                $this->writeln($output, '🦁', 'Add "' . $plugin['name'] . '" : ' . $plugin['version'], Colors::YELLOW);
            }
        }
        ksort($plugins);
        $jsonData['plugins'] = $plugins;
        @file_put_contents($jsonFile, @json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->writeClose($output, '✅', 'Plugins in wordpress.json updated');
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
        $cliPath = $this->getWpCliPath($appDirectory);
        $cli = $cliPath . ' plugin search ' . $name . ' --path=' . $this->getWordpressDirectory($appDirectory);
        $commands = [
            $cli . ' --per-page=9 --page=1 --format=json --fields=name,version,slug'
        ];
        $resultsJson = $this->runCommands($output, $appDirectory, $commands, false, true);

        $data = @json_decode($resultsJson, true, 512, JSON_THROW_ON_ERROR);
        $max = count($data);

        if ($max >= 1) {
            $first = $data[0];
            if ($first['slug'] === $name) {
                $this->getPlugin($input, $output, $first['slug'], $first['version'], $appDirectory)
                    ->addToJsonFile($output, $name, $appDirectory);
                $this->writeln($output, '✅️', $first['name'] . ' installed');
            } else {
                $this->writeln($output, '🤷‍️', 'No plugin found with slug ' . $name, Colors::RED);
                for ($index = 1; $index < $max; $index++) {
                    $this->writeln($output, '💡', 'Maybe you mean: ' . $data[$index]['name'], Colors::RED, '');
                    $this->writeln($output, '👉️', 'php ypa-wp require ' . $data[$index]['slug'], Colors::MAGENTA);
                }
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

        $zipSources = [];
        if (strpos($source, 'http') !== false) {
            $zipSources[] = $source;
        } else if(empty($source)){
            $zipSources[] = $this->wordpressService->getPluginZipName($name);
        } else {
            $zipSources[] = $this->wordpressService->getPluginZipName($name . '.' . $source);
            $zipSources[] = $this->wordpressService->getPluginZipName($name);
        }

        if ($this->hasOption($input, OptionNames::PRODUCTION)) {
            $extractDir = $this->getCustomPluginsDirectory($appDirectory);
        } else {
            $extractDir = $this->getPluginsDirectory($appDirectory);
        }

        $this->downloadPlugin($zipFile, $zipSources)
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
     * @param array $downloadUrls
     *
     * @return $this
     */
    private function downloadPlugin(string $zipFile, array $downloadUrls): self
    {
        try {
            @file_put_contents($zipFile, $this->wordpressService->downloadPlugin($downloadUrls[0]));
        } catch (\Exception $e) {
            @file_put_contents($zipFile, $this->wordpressService->downloadPlugin($downloadUrls[1]));
        }
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
        @file_put_contents($jsonFile, @json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
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

    /**
     * @throws \JsonException
     */
    private function getInstalledPlugins(OutputInterface $output, string $appDirectory): array
    {
        $cliPath = $this->getWpCliPath($appDirectory);
        $commands = [
            $cliPath . ' plugin list --path=' . $this->getWordpressDirectory($appDirectory) . ' --format=json',
        ];
        $installed = $this->runCommands($output, $appDirectory, $commands, false, true);
        $installedPlugins = @json_decode($installed, true, 512, JSON_THROW_ON_ERROR);
        return $installedPlugins ?? [];
    }
}
