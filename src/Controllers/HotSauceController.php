<?php

namespace JosKoomen\Wordpress\Cli\Controllers;

use JosKoomen\Wordpress\Cli\Constants\Colors;
use JosKoomen\Wordpress\Cli\Services\PackageService;
use JosKoomen\Wordpress\Cli\Traits\CmdTrait;
use JosKoomen\Wordpress\Cli\Traits\CreatorTrait;
use JosKoomen\Wordpress\Cli\Traits\DirectoryTrait;
use Symfony\Component\Console\Output\OutputInterface;

class HotSauceController
{
    use CreatorTrait, DirectoryTrait, CmdTrait;

    private PackageService $packageService;

    public function __construct()
    {
        $this->packageService = new PackageService();
    }

    protected function installHotSauce(OutputInterface $output, string $appDirectory): self
    {
        $this->writeMessage($output, '🌶', 'Adding a bit of Hot Sauce...', Colors::GREEN, true);

        $zipFile = $this->makeFilename('hot-sauce');
        $this->downloadHotSauce($zipFile)
            ->extract($zipFile, $appDirectory)
            ->setupHotSauce($appDirectory)
            ->cleanUpHotSauce($zipFile, $appDirectory);

        return $this;
    }

    /**
     * Download the HotSauce Zip to the given Installation.
     *
     * @param string $zipFile
     *
     * @return $this
     */
    private function downloadHotSauce(string $zipFile): self
    {
        file_put_contents($zipFile, $this->packageService->getWordpressHotSauce());

        return $this;
    }

    /**
     * Setup the HotSauce Zip file.
     *
     * @param string $appDirectory
     *
     * @return $this
     */
    private function setupHotSauce(string $appDirectory): self
    {
        $directory = $appDirectory . DIRECTORY_SEPARATOR;
        $resourcesDirectory = $this->getResourcesDirectory($appDirectory) . DIRECTORY_SEPARATOR;
        @copy($resourcesDirectory . 'wordpress.json', $directory . 'wordpress.json');
        @copy($resourcesDirectory . '.env.example', $directory . '.env.example');
        @copy($resourcesDirectory . '.env.example', $directory . '.env');
        @copy($directory . '_gitignore', $directory . '.gitignore');
        @copy($directory . '_htaccess', $directory . '.htaccess.example');

        @unlink($resourcesDirectory . 'wordpress.json');
        @unlink($resourcesDirectory . '.env.example');
        @unlink($directory . '_gitignore');
        @unlink($directory . '_htaccess');

        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param string $zipFile
     * @param string $directory
     *
     * @return $this
     */
    private function cleanUpHotSauce(string $zipFile, string $directory): HotSauceController
    {
        $directoryName = @glob($directory . '/hot-sauce*')[0];
        $this->rrmdir($directoryName);
        return $this->cleanUp($zipFile);
    }
}
