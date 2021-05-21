<?php

namespace Ypa\Wordpress\Cli\Traits;


trait DirectoryTrait
{

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getResourcesDirectory(string $appDirectory): string
    {
        return $appDirectory . DIRECTORY_SEPARATOR . 'resources';
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getWordpressDirectory(string $appDirectory): string
    {
        return $appDirectory . DIRECTORY_SEPARATOR . 'public';
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getPluginsDirectory(string $appDirectory): string
    {
        return $this->getWordpressDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins';
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getWpThemesDirectory(string $appDirectory): string
    {
        return $this->getWordpressDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'themes';
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getThemeDirectory(string $appDirectory): string
    {
        return $this->getResourcesDirectory($appDirectory) . DIRECTORY_SEPARATOR . 'theme';
    }

    /**
     * @param $appDirectory
     *
     * @return string
     */
    protected function getWpCliPath($appDirectory): string
    {
        return $appDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'wp';
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getTempWpDirectory(string $appDirectory): string
    {
        return $appDirectory . DIRECTORY_SEPARATOR . 'wordpress';
    }

    /**
     * @param string $appDirectory
     *
     * @return string
     */
    protected function getWpJsonPath(string $appDirectory): string
    {
        return $appDirectory . DIRECTORY_SEPARATOR . 'wordpress.json';
    }

    protected function getMigrationsDirectory($directory): string
    {
        return $directory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Migrations';
    }
}
