<?php

namespace JosKoomen\Wordpress\Cli\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

trait CreatorTrait
{
    /**
     * Get the src directory.
     *
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getDirectory(InputInterface $input): string
    {
        return ($input->hasOption('path')) ? getcwd() . '/' . $input->getOption('path') : getcwd();
    }

    /**
     * Generate a random temporary filename.
     *
     * @param $prefix string;
     *
     * @return string
     */
    protected function makeFilename(string $prefix = 'joskoomen'): string
    {
        return getcwd() . '/' . $prefix . '_' . md5(time() . uniqid('', false)) . '.zip';
    }

    /**
     * run an array of commands.
     *
     * @param OutputInterface $output
     * @param string $directory
     * @param array $commands
     * @param bool $writeOutput
     * @param bool $return
     *
     * @return string
     */
    protected function runCommands(OutputInterface $output, string $directory, array $commands, bool $writeOutput = true, bool $return = false): string
    {
        $process = new Process(implode(' && ', $commands), $directory, null, null, null);
        $process->run(function ($type, $line) use ($writeOutput, $output) {
            if ($writeOutput) {
                $output->write($line);
            }
        });
        if ($return) {
            return $process->getOutput();
        }
        return '';
    }

    /**
     * Clean-up the Zip file.
     *
     * @param string $zipFile
     *
     * @return $this
     */
    protected function cleanUp(string $zipFile): self
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);

        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param string $zipFile
     * @param string $directory
     *
     * @return $this
     */
    protected function extract(string $zipFile, string $directory): self
    {
        $archive = new \ZipArchive();
        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();

        return $this;
    }

    protected function rrmdir(string $dir): self
    {
        if (is_dir($dir)) {
            $files = @scandir($dir);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    $this->rrmdir($dir . DIRECTORY_SEPARATOR . $file);
                }
            }
            @rmdir($dir);
        } else if (@file_exists($dir)) {
            @unlink($dir);
        }

        return $this;
    }

    protected function rcopy(string $src, string $dst): self
    {
        if (file_exists($dst)) {
            $this->rrmdir($dst);
        }
        if (is_dir($src)) {
            if (!mkdir($dst) && !is_dir($dst)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dst));
            }
            $files = @scandir($src);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    $this->rcopy(
                        $src . DIRECTORY_SEPARATOR . $file,
                        $dst . DIRECTORY_SEPARATOR . $file
                    );
                }
            }
        } else if (@file_exists($src)) {
            @copy($src, $dst);
        }

        return $this;
    }

    protected function hasOption(InputInterface $input, string $name): bool
    {
        if ($input->hasOption($name)) {
            if ($input->getOption($name) === null) {
                return true;
            }

            return !($input->getOption($name) === false);
        }
        return false;
    }
}
