<?php

namespace Ypa\Wordpress\Cli\Controllers;

use Ypa\Wordpress\Cli\Services\WordpressService;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Dotenv\Dotenv;

class WordpressController extends HotSauceController
{
    private string $projectName;
    private string $adminEmail;
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPass;
    private string $dbPrefix;
    private WordpressService $wordpressService;

    public function __construct($projectName = null, $adminEmail = null, $dbHost = 'localhost', $dbName = null, $dbUser = null, $dbPass = null, $dbPrefix = 'wp_')
    {
        parent::__construct();

        $this->wordpressService = new WordpressService();

        $this->projectName = $projectName;
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbPrefix = $dbPrefix;
        $this->adminEmail = $adminEmail;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \JsonException
     */
    public function start(InputInterface $input, OutputInterface $output): void
    {
        $appDirectory = $this->getDirectory($input);
        $version = $this->getVersion($input);
        $wpDirectory = $this->getTempWpDirectory($appDirectory) . DIRECTORY_SEPARATOR;
        $directory = $appDirectory . DIRECTORY_SEPARATOR;
        $filesystem = new Filesystem();
        $this->installWordpress($output, $directory, $version);
        if ($filesystem->exists($this->getResourcesDirectory($directory))) {
            @copy($directory . '.env.example', $directory . '.env');
        } else {
            $this->installHotSauce($output, $appDirectory);
        }
        $this->updateConfig($output, $appDirectory)
            ->updateSaltKeys($wpDirectory . 'wp-config.php', $output);
        $this->setThemeName($appDirectory)
            ->updateWordpressVersion($appDirectory);
        $this->finishingUp($input, $output, $appDirectory);
    }

    /**
     * @param InputInterface $input
     *
     * @return bool|mixed|string|string[]|null
     * @throws \JsonException
     */
    private function getVersion(InputInterface $input): string
    {
        if ($input->getOption('wpv')) {
            return $input->getOption('wpv');
        }

        if ($input->getOption('dev')) {
            return 'develop';
        }

        $wpJsonFile = $this->getWpJsonPath($this->getDirectory($input));
        try {
            $json = json_decode(file_get_contents($wpJsonFile), true, 512, JSON_THROW_ON_ERROR);
            $version = $json['version'];
        } catch (\Exception $e) {
            $version = $this->wordpressService->checkLatestVersion();
        }

        return $version ?? 'latest';
    }

    /**
     * @param $appDirectory
     * @param OutputInterface $output
     *
     * @return WordpressController
     */
    private function prepareWritableDirectories($appDirectory, OutputInterface $output): self
    {
        $filesystem = new Filesystem();
        $wpContentDirectory = $this->getTempWpDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-content';
        try {
            $filesystem->mkdir($wpContentDirectory . DIRECTORY_SEPARATOR . 'uploads');

            $filesystem->chmod($wpContentDirectory . DIRECTORY_SEPARATOR . 'uploads', 0777, 0000, true);
            $filesystem->chmod($wpContentDirectory . DIRECTORY_SEPARATOR . 'plugins', 0777, 0000, true);
        } catch (IOExceptionInterface $e) {
            $output->writeComment('You should verify that the "wp-content/uploads" directory is writable.');
        }

        return $this;
    }

    /**
     * @param $directory
     *
     * @return string
     */
    private function updateWordpressVersion(string $appDirectory): string
    {
        $versionFile = $this->getTempWpDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php';
        $wpjsonFile = $this->getWpJsonPath($appDirectory);

        $string = @file_get_contents($versionFile);
        $wp_json = @file_get_contents($wpjsonFile);

        preg_match('/(\$wp_version)/', $string, $matches, PREG_OFFSET_CAPTURE);
        $version = @explode(' = ', @substr($string, $matches[0][1], 37))[1];

        $string = str_replace('"plugins"', '"version": ' . str_replace("'", '"', $version) . ",\n\t" . '"plugins"', $wp_json);

        @file_put_contents($wpjsonFile, $string);

        return $version;
    }

    /**
     * @param $directory
     *
     * @return WordpressController
     */
    private function setThemeName(string $appDirectory): self
    {
        $projectName = is_null($this->projectName) ? '' : $this->projectName;
        $wpJsonFile = $this->getWpJsonPath($appDirectory);
        $cssFile = $this->getThemeDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'style.css';

        $string = @file_get_contents($wpJsonFile);
        $string = @str_replace('PRODUCT_NAME', $projectName, $string);
        @file_put_contents($wpJsonFile, $string);

        $string = @file_get_contents($cssFile);
        $string = @str_replace('PRODUCT_NAME', $projectName, $string);
        @file_put_contents($cssFile, $string);

        return $this;
    }

    /**
     * Install a fresh copy of Wordpress for local usage.
     *
     * @param OutputInterface $output
     * @param string $appDirectory
     * @param string $version
     *
     * @return $this
     */
    private function installWordpress(OutputInterface $output, string $appDirectory, string $version = 'latest'): self
    {
        $this->writeMessage($output, '🎉', 'Downloading Wordpress...');

        $this->downloadWordpress($zipFile = $this->makeFilename('wordpress'), $version)
            ->extract($zipFile, $appDirectory)
            ->prepareWritableDirectories($appDirectory, $output)
            ->cleanUp($zipFile);

        return $this;
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param string $zipFile
     * @param string $version
     *
     * @return $this
     */
    private function downloadWordpress(string $zipFile, string $version = 'latest'): self
    {
        @file_put_contents($zipFile, $this->wordpressService->getWordpressByVersion($version));
        return $this;
    }

    /**
     * @param $wpConfig
     * @param OutputInterface $output
     *
     * @return WordpressController
     */
    private function updateSaltKeys($wpConfig, OutputInterface $output): self
    {
        $salt = $this->wordpressService->getWpSaltSections();
        $config = @file_get_contents($wpConfig);

        $originals = [
            "define('AUTH_KEY', 'put your unique phrase here')",
            "define('SECURE_AUTH_KEY', 'put your unique phrase here')",
            "define('LOGGED_IN_KEY', 'put your unique phrase here')",
            "define('NONCE_KEY', 'put your unique phrase here')",
            "define('AUTH_SALT', 'put your unique phrase here')",
            "define('SECURE_AUTH_SALT', 'put your unique phrase here')",
            "define('LOGGED_IN_SALT', 'put your unique phrase here')",
            "define('NONCE_SALT', 'put your unique phrase here')"
        ];
        $new = @explode(";\n", $salt);

        for ($i = 0, $iMax = count($originals); $i < $iMax; $i++) {
            try {
                $config = @str_replace($originals[$i], $new[$i], $config);
            } catch (\Exception $e) {
                throw new \RuntimeException($originals[$i] . ' could not be updated in wp-config.php');
            }
        }

        @file_put_contents($wpConfig, $config);

        return $this;
    }

    /**
     * @param OutputInterface $output
     * @param string $appDirectory
     *
     * @return WordpressController
     */
    private function updateConfig(OutputInterface $output, string $appDirectory): self
    {
        $this->writeMessage($output, '🔐', 'Copy our own dotenv wp-config.php...');

        $filesystem = new Filesystem();

        $filesystem->copy(
            $this->getResourcesDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-config.example.php',
            $this->getTempWpDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-config.php'
        );
        return $this->updateDatabaseConfig($appDirectory);
    }

    /**
     * @param string $appDirectory
     *
     * @return WordpressController
     */
    private function updateDatabaseConfig(string $appDirectory): self
    {
        $envFile = $appDirectory . DIRECTORY_SEPARATOR . '.env';

        $string = @file_get_contents($envFile);
        $string = @str_replace("DB_HOST=localhost", "DB_HOST=" . $this->dbHost, $string);
        $string = @str_replace("DB_DATABASE=database", "DB_DATABASE=" . $this->dbName, $string);
        $string = @str_replace("DB_USERNAME=root", "DB_USERNAME=" . $this->dbUser, $string);
        $string = @str_replace("DB_PASSWORD=password", "DB_PASSWORD=" . $this->dbPass, $string);
        $string = @str_replace("DB_TABLE_PREFIX=wp_", "DB_TABLE_PREFIX=" . $this->dbPrefix, $string);
        @file_put_contents($envFile, $string);
        return $this;
    }

    /**
     * @param string $appDirectory
     * @param string $themeFolderName
     *
     * @return void
     */
    private function symlinkTheme(string $appDirectory, string $themeFolderName): void
    {
        $filesystem = new Filesystem();
        $filesystem->symlink(
            $this->getThemeDirectory($appDirectory),
            $this->getWpThemesDirectory($appDirectory) . DIRECTORY_SEPARATOR . $themeFolderName
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $appDirectory
     *
     * @return WordpressController
     */
    private function installAndActivate(InputInterface $input, OutputInterface $output, string $appDirectory): self
    {
        $themeFolderName = strtolower(str_replace(' ', '-', $this->projectName));

        $this->symlinkTheme($appDirectory, $themeFolderName);

        $this->writeMessage($output, '👀', 'Install Wordpress');

        $dotenv = Dotenv::create($appDirectory);
        $dotenv->load();

        $cliPath = $this->getWpCliPath($appDirectory);
        $wpPath = $this->getWordpressDirectory($appDirectory);
        $commands = [
            $cliPath . ' core install --path=' . $wpPath . ' --url=http://localhost:11001 --title="' . $this->projectName .
            '" --admin_name=admin --admin_password=Yarno123! --admin_email=' . $this->adminEmail . ' --quiet',
            $cliPath . ' theme activate ' . $themeFolderName . ' --path=' . $wpPath . '  --quiet',
            $cliPath . ' option set blog_public 0 --path=' . $wpPath . '  --quiet'
        ];
        $this->runCommands($output, $appDirectory, $commands);

        if ($this->hasOption($input, 'install')) {
            $plugins = new PluginsController();
            $plugins->installPlugins($output, $appDirectory);
        }

        $output->writeln('<fg=green;options=bold>🥳 You\'re done</>.');
        $output->writeln('<fg=green;>💪 Wordpress Installed</>');
        $output->writeln('<fg=green;>💪 Your theme is activated</>');
        $output->writeln('<fg=green;>💪 Search engines are blocked</>');
        $output->writeln('<comment>🤠 Your wp-admin username 👉 </comment> admin');
        $output->writeln('<comment>🤠 Your wp-admin password 👉 </comment> Yarno123!');
        $output->writeln('<fg=cyan;options=bold>🚀 To start try to run one of the following commands:</>');
        if (!$this->hasOption($input, 'install')) {
            $output->writeln('<comment>👉 php ypa-wp install</comment> to install your plugins');
        }
        $output->writeln('<comment>👉 php ypa-wp serve</comment> to run your localhost');
        $output->writeln('<comment>👉 php ypa-wp db:init</comment> to start with migrations');
        $output->writeln('<comment>👉 php ypa-wp require your-pluginname</comment> to add a new plugin');
        $output->writeln('<comment>👉 php ypa-wp require your-pluginname another-plugin-name</comment> to add multiple plugins');
        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $appDirectory
     *
     * @return $thiss
     */
    private function finishingUp(InputInterface $input, OutputInterface $output, string $appDirectory): self
    {
        $this->rcopy($this->getTempWpDirectory($appDirectory), $this->getWordpressDirectory($appDirectory));
        $this->rrmdir($this->getTempWpDirectory($appDirectory));

        return $this->installAndActivate($input, $output, $appDirectory);
    }
}
